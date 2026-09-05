import './style.css';
import './ui/window.css';
import { render } from 'preact';
import { MapRenderer } from './map/renderer';
import { GameSession } from './protocol/gameProtocol';
import { parseRefresh } from './map/refreshData';
import { parseBuild, type BuildRecord } from './map/buildData';
import { BattleRenderer } from './battle/battleRenderer';
import type { BattleRound } from './battle/battleData';
import { WindowHost } from './ui/WindowHost';
import { openWindow } from './ui/windowStore';
import { ArmyOverviewWindow } from './army/ArmyOverviewWindow';
import type { ArmyOverview } from './army/armyData';
import type { TabDef } from './ui/tabs';

// Синтетический набор построек для проверки renderBuildings() без живого
// сервера — по одной штуке в углах и в центре соты, id — из buildingTypes.ts
// (снято с живой БД, см. CLIENT_PORTING_PLAN.md, Фаза 2).
const TEST_BUILDINGS: BuildRecord[] = [
  { num: 0, id: 9, art: '', status: 'OK' }, // RESIDENTIAL_BUILDING
  { num: 8, id: 15, art: '', status: 'OK' }, // CITY
  { num: 108, id: 6, art: '', status: 'OK' }, // CENTRAL_GATE (центр соты)
  { num: 208, id: 17, art: '', status: 'OK' }, // FARM
  { num: 216, id: 26, art: '', status: 'OK' }, // MILITARY_BASE
];

// Синтетический раунд боя для проверки BattleRenderer (?screen=battle) —
// две армии защиты против одной атакующей, чтобы проверить группировку
// по army (см. docs/client-combat-ui.md §4).
const TEST_BATTLE_ROUND: BattleRound = {
  sideA: [
    { libname: 'TANK', amount: 12, army: 'Атакующий флот' },
    { libname: 'SOLDIER', amount: 40, army: 'Атакующий флот' },
  ],
  sideD: [
    { libname: 'GUARD', amount: 8, army: 'Гарнизон' },
    { libname: 'KNIGHT', amount: 5, army: 'Союзник (альянс)' },
  ],
};

// Синтетический обзор армий для проверки Window/TabBar/Popup-каркаса
// (?screen=army) — пример из комментария парсера (xmlparsers.as:1482).
const TEST_ARMY_OVERVIEW: ArmyOverview = {
  turn: '123',
  maxSpeed: 150,
  minSpeed: 10,
  armies: [
    {
      idArmy: '4',
      armyName: 'Sota Inner army',
      basecomb: '2.1.2',
      armyPower: 9,
      speed: 100,
      res: 'ALL_RES',
      inaction: 3,
      lastcoord: null,
      lastspeed: null,
      mst: 0,
      prodoweight: null,
      units: [
        { id: '9', libname: 'TANK', amount: 12 },
        { id: '3', libname: 'SOLDIER', amount: 40 },
      ],
      allowTransfer: [{ id: '5', armyName: 'Вторая армия' }],
      allowAction: [
        { id: 'ATTACK', actionName: 'Атака' },
        { id: 'DEFEND', actionName: 'Защита' },
      ],
    },
    {
      idArmy: '5',
      armyName: 'Вторая армия',
      basecomb: '2.1.3',
      armyPower: 3,
      speed: 80,
      res: 'ALL_RES',
      inaction: 1,
      lastcoord: '2.1.7',
      lastspeed: 80,
      mst: 0,
      prodoweight: null,
      units: [{ id: '7', libname: 'GUARD', amount: 8 }],
      allowTransfer: [],
      allowAction: [{ id: 'CANCEL_ATTACK', actionName: 'Отменить атаку' }],
    },
  ],
};

const TEST_TABS: TabDef[] = [
  { id: 'ARMY_OVERVIEW', ln: 'Обзор армий', cur: 1 },
  { id: 'ARMY_CREATION', ln: 'Создать армию', cur: 0 },
];

// Точка входа HTML5-клиента Destiny Sphere.
// Живая интеграция сети (Фаза 1) + карты (Фаза 2), см. CLIENT_PORTING_PLAN.md.
//
// ВАЖНО: логин на живой сервер НЕ запускается автоматически при каждой
// загрузке страницы — только по явному ?live=1 в URL. Иначе каждая
// перезагрузка/скриншот бьёт по живому продакшену тестовым аккаунтом, а
// повторные логин+вход-в-мир в течение сессии, похоже, уже задели
// антибот/rate-limiting (см. CLIENT_PORTING_PLAN.md, Фаза 2 — открытая
// находка, не разобрана до конца, дальше живые тесты не гонять без
// необходимости).

const app = document.querySelector<HTMLDivElement>('#app')!;
app.innerHTML = '';
app.style.width = '900px';
app.style.height = '400px';

const params = new URLSearchParams(location.search);
const wantsLive = params.has('live');
const screen = params.get('screen'); // "battle"/"army" — тестовые экраны вместо карты
const login = import.meta.env.VITE_TEST_LOGIN as string | undefined;
const pass = import.meta.env.VITE_TEST_PASS as string | undefined;
const uiid = import.meta.env.VITE_TEST_UIID as string | undefined;

async function main() {
  const uiRoot = document.querySelector<HTMLDivElement>('#ui-root')!;
  render(<WindowHost />, uiRoot);

  if (screen === 'army') {
    openWindow(
      <ArmyOverviewWindow
        overview={TEST_ARMY_OVERVIEW}
        tabs={TEST_TABS}
        onTabClick={(tabId) => console.log('[army] tab click ->', tabId)}
        onClose={() => console.log('[army] close')}
        onDismissAll={() => console.log('[army] dismissAll()')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'battle') {
    const battleRenderer = await BattleRenderer.create(app);
    await battleRenderer.renderRound(TEST_BATTLE_ROUND);
    (window as any).__renderDone = true;
    return;
  }

  const renderer = await MapRenderer.create(app);

  if (!wantsLive || !login || !pass || !uiid) {
    await renderer.renderGround('G'.repeat(217));
    await renderer.renderBuildings(TEST_BUILDINGS);
    (window as any).__renderDone = true;
    return;
  }

  // baseUrl='' — запросы идут на текущий origin (localhost), прокси
  // Vite перенаправляет /ds/* на https://destinysphere.ru server-side
  // (иначе браузерный fetch() упрётся в CORS, см. vite.config.ts).
  const session = new GameSession({ baseUrl: '' });

  await session.login(login, pass);
  await session.enterWorld(uiid);
  const marks = await session.sendCommand('<load locations="3"/>');

  if (!marks.refresh) {
    throw new Error('В ответе нет mark "refresh" — нечего рисовать.');
  }
  const refresh = parseRefresh(marks.refresh);
  console.log('refresh:', refresh.location, 'id_comb=', refresh.idComb, 'sota.length=', refresh.sota.length);

  await renderer.renderGround(refresh.sota);
  const buildings = parseBuild(refresh.build);
  await renderer.renderBuildings(buildings);
  console.log('MapRenderer готов, реальный грунт+постройки отрисованы (location=' + refresh.location + ', построек=' + buildings.length + ')');
  (window as any).__renderDone = true;
}

main().catch((err) => {
  console.error('ОШИБКА:', err);
  (window as any).__renderError = String(err);
});
