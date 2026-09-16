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
import { effect } from '@preact/signals';
import { GameSession } from './protocol/gameProtocol';
import { handleResponse, type DispatchContext } from './protocol/dispatch';
import { tag } from './protocol/xmlBuilder';
import type { MapRenderer } from './map/renderer';
import { raceFromNumber } from './map/buildingTypes';
import { buildNavigateCommand } from './world/colonyNavigateData';
import { hudActions } from './ui/hud/hudActionsStore';
import { selectedBuilding } from './ui/hud/selectedBuildingStore';

export async function runLiveApp(renderer: MapRenderer, login: string, pass: string, uiid: string): Promise<void> {
  // baseUrl='' — запросы идут на текущий origin (localhost), прокси Vite
  // перенаправляет /ds/* на https://destinysphere.ru server-side (иначе
  // браузерный fetch() упрётся в CORS, см. vite.config.ts).
  const session = new GameSession({ baseUrl: '' });

  // Безопасная зона карты (docs/main_screen.md §8) — держать синхронно с
  // фактическими размерами панелей в ui/hud/hud.css (resbar 48 + nav 46
  // = верхний отступ; bottombar 60 + явный зазор 20px, запрошенный
  // владельцем, = нижний). Правая панель здания больше НЕ резервируется
  // (владелец, 2026-09-16: "справа много пустого места… правый край
  // может частично заходить под область правой панели, пока она
  // закрыта") — она уже непрозрачная накладка поверх готового канваса,
  // а не часть безопасной зоны; открытие/закрытие панели не вызывает
  // `fitToViewport()`, поэтому масштаб карты и правда не меняется.
  renderer.setSafeAreaInsets({ top: 92, bottom: 80 });

  // Подсветка выбранной ячейки (docs/main_screen.md §9) — реактивно на
  // изменение selectedBuilding, а не отдельными ручными вызовами на
  // каждом месте, где сигнал меняется (клик/закрытие панели/ответ
  // сервера) — так не потерять синхронизацию, если появится ещё одно
  // место записи сигнала.
  effect(() => {
    renderer.setSelectedCell(selectedBuilding.value?.cellNum ?? null);
  });

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
  // sota.as. `ctx.activeCell` — аналог debug.link.active_cell оригинала:
  // ячейка "прилипает" к открытому через getbuildmenu-семейство окну,
  // клики по вкладкам внутри него переиспользуют это же значение (см.
  // dispatch.tsx).
  //
  // ВАЖНО (найдено 2026-09-10 живым кликом — сервер вернул SYSTEM ERROR):
  // `num` из hexGrid.ts — 0-based (совпадает с индексом в 217-символьной
  // строке грунта), а `c` в протоколе — 1-based. Подтверждено буквально
  // `reqvestdata.as` (`cell_click`, вызывается из `sota.as:276`,
  // `onRelease` на реальной ячейке): `debug.link.active_cell = " c=\"" +
  // (Number(id_cell) + 1) + "\" "` — явный `+1`. Раньше здесь его не
  // было (`c=String(num)` без сдвига).
  //
  // ВАЖНО #2 (найдено 2026-09-16, разбор реального клика оригинала —
  // владелец справедливо спросил, как разрешается противоречие "панель
  // здания vs окно домена, привязанное к зданию"): `cell_click`/
  // `cell_click_m` (`reqvestdata.as:160-185`) шлют РОВНО ОДНУ и ту же
  // команду для ЛЮБОЙ ячейки, занятой ИЛИ пустой — БЕЗ `tab` вообще,
  // `<getbuildmenu c=""/>`. Клиент НИКОГДА не решает сам, какой экран
  // открыть по типу постройки. Решает сервер:
  // `UA_GetBuildMenu::performAction():272-275` — если `tab` пуст, берёт
  // `GL_DefaultBuildingTab::Get($bid)` (таблица `ds_buildinghasspecialfunction`
  // /`ds_specialfunction`, `isdefault='t'` по конкретному `id_building`)
  // и подставляет ЕЁ как реальный класс-обработчик (`:337`,
  // `$panel = new $this->tab`) — КОРЕНЬ ОТВЕТА РАЗНЫЙ ПО ТИПУ ЗДАНИЯ:
  // военная база вернёт `<armyoverview>` НАПРЯМУЮ, а не `<getbuildinfo>`;
  // институт археологии — `<archeology>`; коннектор альянса —
  // `<alliancemanagement>` и т.д. (подтверждено реальными зафиксированными
  // ответами `SPHERE_client/army_overview_3.txt`/`archeology.txt`/
  // `allianceCreate.txt` — не декомпиляция, реальный трафик). Только у
  // зданий БЕЗ спецфункции (простые производственные/жилые) дефолт
  // — сама `PN_BuildingInfo` (`<getbuildinfo>`). Для недостроенных/
  // неактивных построек сервер тоже сам форсирует `PN_BuildingInfo`
  // (`:290-295`) — не нужно повторять эту проверку на клиенте.
  //
  // Отсюда — ответ на "чей экран открывать": ничей заранее, сервер уже
  // решил. Здесь только один универсальный клик; какой корневой тег
  // придёт — решает уже подключённый общий диспетчер (`RENDERERS` в
  // dispatch.tsx, был построен ещё в фазе "Сшивание" именно для этого):
  // `getbuildinfo` → докнутая панель (`renderBuildInfo`), любой другой
  // домен (`armyoverview`/`archeology`/`alliancemanagement`/`religion`/
  // `table` и т.д.) → его уже существующее модальное окно, как и раньше.
  // Прежний ЖЁСТКИЙ `tab="BuildingInfo"` для занятых ячеек был неверным
  // упрощением — он подавлял этот серверный выбор и всегда показывал
  // только общую информацию, даже для военной базы/института и т.п.
  // Раньше для пустой ячейки `tab` уже не слался по той же причине,
  // что и здесь (иначе SYSTEM ERROR, `UA_GetBuildMenu.class.php:200-208`)
  // — теперь то же правило просто распространено на все ячейки.
  renderer.onCellClick = (num) => {
    ctx.activeCell = String(num + 1);
    // Снимаем текущий выбор ДО ответа сервера — какой бы экран ни
    // пришёл (докнутая панель или чужой модальный домен), не показывать
    // одновременно панель здания от предыдущего клика и что-то новое.
    // Панель здания снова заполнится сама, если ответ действительно
    // окажется `<getbuildinfo>` (см. renderBuildInfo() в dispatch.tsx).
    selectedBuilding.value = null;
    void session.sendCommand(tag('getbuildmenu', { c: ctx.activeCell })).then((marks) => {
      handleResponse(marks, ctx);
    });
  };

  await session.login(login, pass);
  await session.enterWorld(uiid);
  ctx.race = raceFromNumber(session.getRace());
  const marks = await session.sendCommand('<load locations="3"/>');
  handleResponse(marks, ctx);

  // Точки входа главного экрана, не привязанные к клику по карте
  // (docs/main_screen.md — верхняя навигация + круговое меню).
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
  hudActions.value = {
    openMail: () => void ctx.send(tag('getbuildmenu', { tab: 'MessageOverview', type: 'in' })),
    openCabinet: () => window.open('https://destinysphere.ru/ds/user_cabinet.php', '_blank'),
    openComplex: () => void ctx.send(tag('complexview', { direction: 'HOME', ring: '', complexnum: '' })),
    openColonies: () => void ctx.send(tag('getbuildmenu', { tab: 'ColonyNavigatePanel' })),
    // "Домой"/"Центр"/"Нейтрал" кругового меню — тот же `<navigate
    // cell="1" dest="..."/>`, что уже подтверждён для перехода на
    // другую свою соту (colonyNavigateData.ts, `dest` документирован
    // как "HOME"/"CENTRAL"/"NEUTRAL"/"RING.COMPLEX.COMB"), просто с
    // константным dest вместо координат другой соты.
    navigateHome: () => void ctx.send(tag('navigate', buildNavigateCommand('HOME'))),
    navigateCentral: () => void ctx.send(tag('navigate', buildNavigateCommand('CENTRAL'))),
    navigateNeutral: () => void ctx.send(tag('navigate', buildNavigateCommand('NEUTRAL'))),
    // "Обновить" в центре кругового меню — та же команда первичной
    // загрузки, что и вход в мир. Возвращает Promise (не void) — центр
    // кругового меню показывает индикатор загрузки, пока запрос летит
    // (docs/main_screen.md: "во время обновления вместо текста —
    // круговой индикатор"), ему нужно дождаться реального ответа.
    refresh: () => ctx.send('<load locations="3"/>'),
  };
}
