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
  // подтверждён живым тестом 2026-09-08, см. buildInfoData.ts docstring);
  // пустая — список построек для возведения (имя таба "AllowBuildings",
  // подтверждено чтением PN_AllowBuildings.class.php, см. план §3 —
  // исправлено после первоначальной неподтверждённой догадки "BuildMenu").
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
  renderer.onCellClick = (num, hasBuilding) => {
    ctx.activeCell = String(num + 1);
    const tab = hasBuilding ? 'BuildingInfo' : 'AllowBuildings';
    void session.sendCommand(tag('getbuildmenu', { c: ctx.activeCell, tab })).then((marks) => {
      handleResponse(marks, ctx);
    });
  };

  await session.login(login, pass);
  await session.enterWorld(uiid);
  ctx.race = raceFromNumber(session.getRace());
  const marks = await session.sendCommand('<load locations="3"/>');
  handleResponse(marks, ctx);
}
