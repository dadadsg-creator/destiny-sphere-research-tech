/**
 * Точки входа главного экрана, не привязанные к клику по карте — почта,
 * личный кабинет, комплекс, колонии (перенесено сюда из бывшего
 * `liveToolbarStore.ts`/`LiveToolbar.tsx`, временной заглушки статус-бара
 * времён "Сшивания", см. CLIENT_PORTING_PLAN.md — теперь это реальный
 * статус-бар/навигация главного экрана, docs/main_screen.md), плюс три
 * новых действия кругового меню (Домой/Центр/Обновить — те же команды,
 * что уже подтверждены `reqvestdata.as:267-292`/`world/colonyNavigateData.ts`
 * для "Мои колонии", просто другой `dest`).
 *
 * Заполняется один раз в `liveApp.ts` после входа в мир, `null` вне живой
 * сессии (`?screen=`-тесты, обычная загрузка без `?live=1`).
 */
import { signal } from '@preact/signals';

export interface HudActions {
  openMail: () => void;
  openCabinet: () => void;
  openComplex: () => void;
  openColonies: () => void;
  /** `<navigate cell="1" dest="HOME"/>` — вернуться на собственную соту. */
  navigateHome: () => void;
  /** `<navigate cell="1" dest="CENTRAL"/>` — центральный слой соты. */
  navigateCentral: () => void;
  /** `<navigate cell="1" dest="NEUTRAL"/>` — нейтральный слой соты (пятый сектор кругового меню оригинала). */
  navigateNeutral: () => void;
  /** Перезапрос текущего состояния — тот же `<load locations="3"/>`, что и при первом входе. Promise — центр кругового меню показывает индикатор загрузки, пока запрос летит. */
  refresh: () => Promise<void>;
  /**
   * Внешние ссылки верхней навигации — НЕ игровой XML-протокол, а статичный
   * `ds/address.php` (`<links ranking="…" profile="…" forum="…" portal="…"/>`,
   * ветка "Классика" по IP сервера), который в оригинале грузится один раз
   * через `xmlparsers.init_interface_button`/`game.init_links_button` и
   * просто открывает `getURL(url, "_blank")`. Четыре из этих ссылок не
   * требуют серверной команды вообще, только реальный URL — он уже известен
   * из `ds/address.php`, выдумывать нечего.
   */
  openRanking: () => void;
  openProfile: () => void;
  openForum: () => void;
  openPortal: () => void;
  /**
   * "Магазин" — тоже `ds/address.php`, атрибут `CABINET` (не `openCabinet`
   * выше — у того свой, отдельно найденный URL без `?action=login`, два
   * разных предположения из двух разных заходов расследования). Владелец
   * подтвердил напрямую (2026-09-16) + перепроверено вживую: порядок
   * атрибутов в реальном ответе `address.php` (`CABINET, analizator,
   * ranking, profile, forum, portal, GAMES`) совпадает 1:1 с порядком
   * кнопок на экране (МАГАЗИН, АНАЛИЗ, РЕЙТИНГ, ПРОФИЛЬ, ФОРУМ, ПОРТАЛ,
   * ИГРЫ) — "Магазин" это и есть `CABINET`.
   */
  openShop: () => void;
  /**
   * Лог сообщений (`sys_mess_menu.as`) — кнопки нижней панели (владелец
   * прислал скриншот реального клиента, 2026-09-17): меч/бинокль/шеврон
   * = WAR/SPY/SYS. Данные уже копятся локально (`mail/sysMessageStore.ts`
   * на каждую марку `sysmsg`) — команда серверу не нужна, только открыть
   * уже готовое окно с нужным фильтром.
   */
  openSysLog: (type: 'SYS' | 'WAR' | 'SPY') => void;
  /**
   * Круговое меню статус-бара, левый нижний угол (владелец прислал
   * скриншот реального клиента, 2026-09-17) — `ds/address.php`, узел
   * `<interface>`, атрибуты `service`/`premium` (`game.as:1046-1230`,
   * `init_interface_button`, `case "service"`/`case "premium"` — оба
   * просто `getURL(url, "_blank")`, без параметров сверх URL из XML).
   * `openBilling` — `service="/ds/billing.php?SID=SIDID"`, тот же приём
   * подстановки живого PHPSESSID, что и `openRanking` выше (SIDID —
   * серверный плейсхолдер, подставляется в реальном XML на самом
   * сервере, здесь читаем то же значение напрямую из куки).
   */
  openBilling: () => void;
  /** `premium="http://destinysphere.ru/index.php?p=premium_info_page"` — статичный URL, без параметров сессии. */
  openPremiumInfo: () => void;
  /**
   * FAQ (`FUCKEN_HELP`, `game.as:2011-2020` → `help_class.as:18-35`,
   * `getHelp("INFO_GAME_MAIN")` → `redirect("/ds/help/?name=INFO_GAME_MAIN")`)
   * — статичный URL, без параметров сессии.
   */
  openFaq: () => void;
  /**
   * Кнопка выхода (`ctrl_player.log_out`, `game.as:2240-2249`) — НЕ обычная
   * команда протокола: `reqvestdata.send_logout()` (`reqvestdata.as:
   * 430-434`) шлёт `xmldata=logout` (голая строка, не XML-тег), сервер
   * (`ds/useraction.php:120-128`) отвечает `<key valid="0" addr="index_
   * start.php?ck=..."/>` — это ОЖИДАЕМЫЙ, не ошибочный ответ на logout
   * (в отличие от любой другой команды, где `valid="0"` — реальный сбой
   * сессии), после чего оригинал (`parser.as:21-36`) делает `_top`-редирект
   * браузера на `addr`. Здесь — тот же смысл: шлём `logout`, игнорируем
   * ожидаемую ошибку невалидной сессии, затем жёстко уводим браузер на
   * страницу логина.
   */
  logout: () => void;
}

export const hudActions = signal<HudActions | null>(null);
