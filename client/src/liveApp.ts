/**
 * Точка входа живой сессии — логин → вход в мир → первичная загрузка →
 * подписка на клики по карте → цикл «отправили команду → разобрали
 * ответ» на каждое дальнейшее действие. См. план «Сшивание HTML5-порта в
 * единое рабочее приложение» (CLIENT_PORTING_PLAN.md, 2026-09-08).
 *
 * Выделено из main.tsx отдельным модулем, чтобы не раздувать и то и
 * дальше огромный список `?screen=`-веток для изолированного тестирования
 * каждого окна (см. main.tsx) — тот путь тестирования не трогается.
 */
import { GameSession } from './protocol/gameProtocol';
import { handleResponse, type DispatchContext } from './protocol/dispatch';
import { tag } from './protocol/xmlBuilder';
import type { MapRenderer } from './map/renderer';
import { raceFromNumber } from './map/buildingTypes';
import { liveToolbarActions } from './ui/liveToolbarStore';

export async function runLiveApp(renderer: MapRenderer, login: string, pass: string, uiid: string): Promise<void> {
  // baseUrl='' — запросы идут на текущий origin (localhost), прокси Vite
  // перенаправляет /ds/* на https://destinysphere.ru server-side (иначе
  // браузерный fetch() упрётся в CORS, см. vite.config.ts).
  const session = new GameSession({ baseUrl: '' });

  const ctx: DispatchContext = {
    renderer,
    activeCell: undefined,
    gateOpen: false,
    // Race подставляется ниже, сразу после enterWorld() — placeholder на
    // случай раннего доступа (не должен реально сработать, т.к. никакие
    // XML-команды до входа в мир ещё не отправлены).
    race: 'bio',
    // Общий колбэк для команд, отправляемых из УЖЕ открытого окна
    // (кнопки действий) — без привязки к конкретной ячейке, аналог
    // произвольного reqvestdata-вызова в оригинале.
    send: async (xml: string) => {
      const marks = await session.sendCommand(xml);
      handleResponse(marks, ctx);
    },
  };

  // Клик по ячейке соты — аналог onRelease на building/ground-спрайте в
  // sota.as: занятая ячейка открывает информацию о здании (формат
  // подтверждён живым тестом 2026-09-08, см. buildInfoData.ts docstring).
  // `ctx.activeCell` — аналог debug.link.active_cell оригинала: ячейка
  // "прилипает" к открытому через getbuildmenu-семейство окну, клики по
  // вкладкам внутри него переиспользуют это же значение (см. dispatch.tsx).
  //
  // ВАЖНО (найдено 2026-09-10 живым кликом — сервер вернул SYSTEM ERROR):
  // `num` из hexGrid.ts — 0-based (совпадает с индексом в 217-символьной
  // строке грунта), а `c` в протоколе — 1-based. Подтверждено буквально
  // `reqvestdata.as` (`cell_click`, вызывается из `sota.as:276`,
  // `onRelease` на реальной ячейке): `debug.link.active_cell = " c=\"" +
  // (Number(id_cell) + 1) + "\" "` — явный `+1`. Раньше здесь его не
  // было (`c=String(num)` без сдвига).
  //
  // ВАЖНО #2 (найдено 2026-09-10 живым кликом по-настоящему пустой
  // ячейки — SYSTEM ERROR UA_GetBuildMenu::performAction1): для пустой
  // ячейки НЕЛЬЗЯ слать явный `tab` вообще — подтверждено чтением
  // `UA_GetBuildMenu.class.php:200-208` (`performAction()`): если
  // `attachedbid` пуст (постройки нет) И `tab` НЕ пуст — сервер
  // возвращает именно эту ошибку. Если `tab` пуст — сервер сам
  // подставляет `PN_AllowBuildings` без вопросов (:209-247). Раньше тут
  // стоял явный `tab="AllowBuildings"` — неподтверждённая (несмотря на
  // формулировку в старом докстринге "подтверждено чтением класса")
  // догадка по аналогии с занятой ячейкой, которая как раз ломала запрос.
  renderer.onCellClick = (num, hasBuilding) => {
    ctx.activeCell = String(num + 1);
    void session
      .sendCommand(tag('getbuildmenu', { c: ctx.activeCell, tab: hasBuilding ? 'BuildingInfo' : undefined }))
      .then((marks) => {
        handleResponse(marks, ctx);
      });
  };

  await session.login(login, pass);
  await session.enterWorld(uiid);
  ctx.race = raceFromNumber(session.getRace());
  const marks = await session.sendCommand('<load locations="3"/>');
  handleResponse(marks, ctx);

  // Точки входа, явно включённые в первый проход "Сшивания" (план, §5) в
  // дополнение к клику по карте. Настоящего статус-бара с графикой
  // оригинала ещё нет — временные кнопки, см. ui/LiveToolbar.tsx.
  //
  // Почта — подтверждённый формат `reqvestdata.as:380-388` (get_mail):
  // `<getbuildmenu tab="MessageOverview" type="in|out"/>`, БЕЗ `c` (почта
  // не привязана к ячейке).
  //
  // ИСПРАВЛЕНО (2026-09-10, уточнение владельца после живого теста):
  // «Личный кабинет» — это НЕ команда игрового XML-протокола, а отдельная
  // страница портала (`https://destinysphere.ru/ds/user_cabinet.php`,
  // подтверждено файлом `ds/user_cabinet.php` — свой сессионный вход
  // `SU_GameSessionWork::SessionContinue()`/`UC_ControlUserCabinet`,
  // отдельный от `UA_`/`PN_`-классов игровых действий). Раньше здесь стоял
  // `<cabinetRequest command="open"/>` — живой клик вернул РЕАЛЬНУЮ
  // серверную ошибку (`ASSERT UserAction.class.php:297`, см.
  // CLIENT_PORTING_PLAN.md), и это не баг конкретно порта: прямым
  // SQL-запросом к `ds_useractiontype` подтверждено, что этот tagname на
  // мире вообще не зарегистрирован. Причина нашлась в `game.as:1060-1134`
  // (конфигурация кнопок статус-бара, `array_button` от сервера) —
  // `cabinetRequest()` шлётся только в режиме `"flashcabinet"`
  // (VK/POP-встраивание, вне объёма порта, см. память проекта), а в
  // обычном режиме сайта та же кнопка настроена как `"service"` и просто
  // делает `getURL(url, "_blank")` — открывает внешнюю ссылку. Реестр
  // `cabinet`/`CabinetWindow.tsx` (Фаза 3) технически рабочий (сверен по
  // коду), но на обычном сайте, судя по всему, мёртвый путь — не удалён
  // (не в объёме этого исправления), просто не вызывается отсюда.
  liveToolbarActions.value = {
    openMail: () => void ctx.send(tag('getbuildmenu', { tab: 'MessageOverview', type: 'in' })),
    openCabinet: () => window.open('https://destinysphere.ru/ds/user_cabinet.php', '_blank'),
    openComplex: () => void ctx.send(tag('complexview', { direction: 'HOME', ring: '', complexnum: '' })),
    openColonies: () => void ctx.send(tag('getbuildmenu', { tab: 'ColonyNavigatePanel' })),
  };
}
