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

export async function runLiveApp(renderer: MapRenderer, login: string, pass: string, uiid: string): Promise<void> {
  // baseUrl='' — запросы идут на текущий origin (localhost), прокси Vite
  // перенаправляет /ds/* на https://destinysphere.ru server-side (иначе
  // браузерный fetch() упрётся в CORS, см. vite.config.ts).
  const session = new GameSession({ baseUrl: '' });

  const ctx: DispatchContext = {
    renderer,
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
  // пустая — список построек для возведения (имя таба "BuildMenu" — по
  // аналогии, живым тестом НЕ подтверждено, см. план §3).
  renderer.onCellClick = (num, hasBuilding) => {
    const cellNum = String(num);
    const tab = hasBuilding ? 'BuildingInfo' : 'BuildMenu';
    void session.sendCommand(tag('getbuildmenu', { c: cellNum, tab })).then((marks) => {
      handleResponse(marks, ctx, cellNum);
    });
  };

  await session.login(login, pass);
  await session.enterWorld(uiid);
  const marks = await session.sendCommand('<load locations="3"/>');
  handleResponse(marks, ctx);
}
