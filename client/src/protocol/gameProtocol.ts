/**
 * Сетевой слой игрового протокола Destiny Sphere.
 *
 * Порт протокола описан в docs/protocol-client-server.md (основной
 * репозиторий документации, не в этом пакете) и подтверждён живым тестом
 * 2026-09-04 (см. CLIENT_PORTING_PLAN.md, Фаза 1) — обычный HTTP,
 * POST-формы, никаких сокетов/AMF, сессия — стандартная PHPSESSID-cookie.
 */

import { parseMarks, type ParsedMarks } from './response';

export interface GameProtocolOptions {
  /** Базовый адрес сервера, например https://destinysphere.ru */
  baseUrl: string;
  /**
   * Ручное управление cookie — нужно только вне браузера (Node), где
   * fetch() не хранит cookie между вызовами сам. В браузере оставить
   * пустым — используется `credentials: 'include'`.
   */
  cookieJar?: CookieJar;
  fetchImpl?: typeof fetch;
}

export interface CookieJar {
  header(): string | undefined;
  update(setCookieHeaders: string[]): void;
  set(name: string, value: string): void;
}

/** Простейший cookie jar для Node-тестов (не для браузера — там cookie сами). */
export class SimpleCookieJar implements CookieJar {
  private jar = new Map<string, string>();

  header(): string | undefined {
    if (this.jar.size === 0) return undefined;
    return [...this.jar.entries()].map(([k, v]) => `${k}=${v}`).join('; ');
  }

  update(setCookieHeaders: string[]): void {
    for (const raw of setCookieHeaders) {
      const first = raw.split(';')[0];
      const eq = first.indexOf('=');
      if (eq === -1) continue;
      const name = first.slice(0, eq).trim();
      const value = first.slice(eq + 1).trim();
      if (value === 'deleted') {
        this.jar.delete(name);
      } else {
        this.jar.set(name, value);
      }
    }
  }

  set(name: string, value: string): void {
    this.jar.set(name, value);
  }
}

export class GameProtocolError extends Error {}

export class GameSession {
  private ck: string | null = null;
  private race: number | null = null;
  private readonly baseUrl: string;
  private readonly cookieJar?: CookieJar;
  private readonly fetchImpl: typeof fetch;

  constructor(opts: GameProtocolOptions) {
    this.baseUrl = opts.baseUrl.replace(/\/$/, '');
    this.cookieJar = opts.cookieJar;
    // fetch как голая ссылка теряет привязку к window/globalThis — при
    // вызове через this.fetchImpl(...) браузер бросает "Illegal invocation".
    this.fetchImpl = opts.fetchImpl ?? fetch.bind(globalThis);
  }

  getClientKey(): string | null {
    return this.ck;
  }

  /** `debug.link.race` оригинала (1/2/3 = tech/bio/psi, см. map/buildingTypes.ts) — известна только после enterWorld(). */
  getRace(): number | null {
    return this.race;
  }

  /**
   * ds/index_login.php — обычный веб-логин, POST login/pass/enter=Login.
   *
   * ВАЖНО: `redirect:'manual'` тут не работает — по спецификации Fetch
   * ответ с ручным редиректом в браузере ВСЕГДА "opaqueredirect"
   * (status=0, заголовки недоступны), причём независимо от того,
   * same-origin редирект или нет (в Node/undici ведёт себя иначе, поэтому
   * баг не проявился в тестовом скрипте Фазы 1 — там `redirect:'manual'`
   * действительно давал доступ к Location). Поэтому здесь редирект просто
   * ПРОХОДИТСЯ (`redirect:'follow'`, по умолчанию), а `ck` читается из
   * итогового `response.url` — сервер kладёт его в query string цели
   * редиректа, так что после автоматического перехода он остаётся на
   * месте. Если логин/пароль неверны, сервер не редиректит вовсе
   * (возвращает форму логина 200 OK) — `ck` в `res.url` не найдётся,
   * это и есть сигнал ошибки.
   */
  async login(login: string, password: string): Promise<void> {
    const body = new URLSearchParams({ login, pass: password, enter: 'Login' });
    const res = await this.rawPost('/ds/index_login.php', body);
    const target = new URL(res.url);
    const ck = target.searchParams.get('ck');
    if (!ck) {
      throw new GameProtocolError('Логин не удался — сервер не перенаправил на index_start.php (неверный логин/пароль?).');
    }
    this.ck = ck;
    this.seedSessionCookieFromUrl(target);
  }

  /**
   * ds/index_start.php — выбор мира/инстанса по uiid. Возвращает адрес входа в игру (ds/index.php?...).
   *
   * ВАЖНО: `ds/index.php` (куда сервер после успеха отдаёт 302, и куда
   * браузерный `fetch()` с `redirect:'follow'` автоматически идёт дальше)
   * проверяет `HTTP_REFERER` по белому списку подстрок — `index_start.php`,
   * `index_queue.php`, `flash/ds3` (реальный Flash-клиент проходил эту
   * проверку через referrer страницы с embed .swf) и т.д.,
   * `ds/index.php:84-98`. Без совпадения — редирект на `index_login.php`
   * (полностью успешный `enterWorld()`, но браузер сам добивает себя на
   * следующем шаге редиректа — Referer по умолчанию = адрес нашей SPA,
   * ни с чем из списка не совпадает). Явно указываем правдоподобный
   * `referrer` с `index_start.php` в пути — распространяется на весь
   * redirect-chain этого fetch(), включая финальный прыжок на index.php.
   *
   * `race` (1/2/3 = tech/bio/psi, см. map/buildingTypes.ts) НЕ приходит ни
   * в одной XML-марке игрового протокола — подтверждено
   * `docs/protocol-client-server.md`: это часть `FlashVars`, которые
   * `flash_code.php:9,25` буквально вклеивает в HTML embed/object-тег
   * страницы `ds/index.php` (`"&race=".$_SESSION['race'].$_SESSION[
   * 'windowKey']` — БЕЗ разделителя перед `windowKey`). Раз `fetch()` в
   * `enterWorld()` и так доходит до этой самой страницы
   * (`redirect:'follow'`), читаем `race` прямо из её тела, а не
   * игнорируем его как раньше.
   *
   * НАЙДЕННЫЙ И ИСПРАВЛЕННЫЙ БАГ (2026-09-16, живой тест на premium,
   * Enzo/race=1/TECH): регэксп раньше был `(\d+)` — "жадно" захватывал
   * не только сам `race` (всегда ровно одна цифра, 1-3), но и ведущие
   * цифры СЛЕДОМ ИДУЩЕГО `windowKey` (раз разделителя между ними нет).
   * Живой ответ содержал буквально `race=191...` (race=1, windowKey
   * начинается на "91...") — `(\d+)` читал это как `race=191`, отсюда
   * `raceFromNumber(191)` проваливался в default-ветку 'bio' вместо
   * 'tech', и постройки рисовались чужой расой (найдено владельцем по
   * скриншоту, сравнение с оригиналом). Исправлено на `(\d)` — ровно
   * одна цифра, т.к. race больше одной цифры не бывает по определению
   * (`game.as:2319-2359`, switch только на 1/2/3).
   */
  async enterWorld(uiid: string): Promise<string> {
    this.assertLoggedIn();
    const body = new URLSearchParams({ ck: this.ck!, start: '1', uiid });
    const referrer =
      typeof location !== 'undefined' ? new URL('/ds/index_start.php', location.origin).toString() : undefined;
    const res = await this.rawPost('/ds/index_start.php', body, referrer);
    const target = new URL(res.url);
    const ck = target.searchParams.get('ck');
    const bodyText = await res.clone().text().catch(() => '');
    if (!ck) {
      console.error('[enterWorld debug] res.url=', res.url, 'res.status=', res.status, 'document.cookie=', typeof document !== 'undefined' ? document.cookie : '(n/a)', 'body=', bodyText.slice(0, 300));
      throw new GameProtocolError('Вход в мир не удался — сервер не перенаправил на ds/index.php (неверный uiid?).');
    }
    this.ck = ck;
    this.seedSessionCookieFromUrl(target);
    const raceMatch = bodyText.match(/[?&]race=(\d)/);
    this.race = raceMatch ? Number(raceMatch[1]) : null;
    return res.url;
  }

  /**
   * Сервер добавляет `session_name()=session_id()` (в проде — `PHPSESSID`,
   * подтверждено живым тестом Фазы 1) в query редиректа, если у входящего
   * запроса ещё не было cookie сессии (легаси-фолбэк для Flash Player,
   * см. docs/client-engine-and-protocol.md §2.1). Используем этот же
   * параметр напрямую для ручного cookie jar (Node), вместо того чтобы
   * полагаться на Set-Cookie с промежуточного прыжка редиректа — доступа
   * к заголовкам промежуточных прыжков при `redirect:'follow'` в браузере
   * нет в принципе.
   */
  private seedSessionCookieFromUrl(url: URL): void {
    if (!this.cookieJar) return;
    const sessId = url.searchParams.get('PHPSESSID');
    if (sessId) this.cookieJar.set('PHPSESSID', sessId);
  }

  /**
   * ds/useraction.php — основная команда игрового протокола.
   * `xml` — одна корневая команда без обёртки, например `<load locations="3"/>`.
   */
  async sendCommand(xml: string): Promise<ParsedMarks> {
    this.assertLoggedIn();
    const body = new URLSearchParams({ xmldata: xml, ck: this.ck! });
    const res = await this.rawPost('/ds/useraction.php', body);
    if (res.status !== 200) {
      throw new GameProtocolError(`useraction.php вернул ${res.status}`);
    }
    const text = await res.text();
    const marks = parseMarks(text);

    // ck ротируется на каждый ответ — обновляем для следующего запроса.
    if (marks.ck) {
      this.ck = marks.ck;
    }
    // Невалидная сессия/ключ — сервер разлогинивает и шлёт loadkey valid="0".
    if (marks.loadkey && /valid="0"/.test(marks.loadkey)) {
      throw new GameProtocolError('Сессия недействительна (loadkey valid="0") — требуется повторный логин.');
    }
    return marks;
  }

  private assertLoggedIn(): void {
    if (!this.ck) {
      throw new GameProtocolError('Нет активной сессии — сначала вызовите login().');
    }
  }

  private async rawPost(path: string, body: URLSearchParams, referrer?: string): Promise<Response> {
    const headers: Record<string, string> = {
      'Content-Type': 'application/x-www-form-urlencoded',
    };
    if (this.cookieJar) {
      const cookie = this.cookieJar.header();
      if (cookie) headers['Cookie'] = cookie;
    }

    const res = await this.fetchImpl(this.baseUrl + path, {
      method: 'POST',
      headers,
      body: body.toString(),
      credentials: this.cookieJar ? undefined : 'include',
      ...(referrer ? { referrer } : {}),
    });

    if (this.cookieJar) {
      // Node fetch: getSetCookie() доступен в Node >=18.14 (undici).
      const anyHeaders = res.headers as unknown as { getSetCookie?: () => string[] };
      const setCookies = anyHeaders.getSetCookie ? anyHeaders.getSetCookie() : [];
      this.cookieJar.update(setCookies);
    }

    return res;
  }
}
