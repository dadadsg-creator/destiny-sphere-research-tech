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
}

export class GameProtocolError extends Error {}

export class GameSession {
  private ck: string | null = null;
  private readonly baseUrl: string;
  private readonly cookieJar?: CookieJar;
  private readonly fetchImpl: typeof fetch;

  constructor(opts: GameProtocolOptions) {
    this.baseUrl = opts.baseUrl.replace(/\/$/, '');
    this.cookieJar = opts.cookieJar;
    this.fetchImpl = opts.fetchImpl ?? fetch;
  }

  getClientKey(): string | null {
    return this.ck;
  }

  /** ds/index_login.php — обычный веб-логин, POST login/pass/enter=Login. */
  async login(login: string, password: string): Promise<void> {
    const body = new URLSearchParams({ login, pass: password, enter: 'Login' });
    const res = await this.rawPost('/ds/index_login.php', body);

    if (res.status !== 302) {
      throw new GameProtocolError(`Логин не удался: ожидался 302, получен ${res.status}`);
    }
    const location = res.headers.get('location');
    if (!location) {
      throw new GameProtocolError('Логин: сервер не вернул Location-редирект — вероятно, неверный логин/пароль.');
    }
    const ck = new URL(location, this.baseUrl).searchParams.get('ck');
    if (!ck) {
      throw new GameProtocolError(`Логин: не нашли ck в редиректе (${location}).`);
    }
    this.ck = ck;
  }

  /** ds/index_start.php — выбор мира/инстанса по uiid. Возвращает адрес входа в игру (ds/index.php?...). */
  async enterWorld(uiid: string): Promise<string> {
    this.assertLoggedIn();
    const body = new URLSearchParams({ ck: this.ck!, start: '1', uiid });
    const res = await this.rawPost('/ds/index_start.php', body);
    if (res.status !== 302) {
      throw new GameProtocolError(`Вход в мир не удался: ожидался 302, получен ${res.status}`);
    }
    const location = res.headers.get('location');
    if (!location) {
      throw new GameProtocolError('Вход в мир: сервер не вернул Location-редирект.');
    }
    const abs = new URL(location, this.baseUrl + '/ds/');
    const ck = abs.searchParams.get('ck');
    if (ck) this.ck = ck;
    return abs.toString();
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

  private async rawPost(path: string, body: URLSearchParams): Promise<Response> {
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
      redirect: 'manual',
      credentials: this.cookieJar ? undefined : 'include',
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
