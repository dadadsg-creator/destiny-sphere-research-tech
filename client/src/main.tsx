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
import { ArmyCreationWindow } from './army/ArmyCreationWindow';
import { TrainUnitWindow } from './army/TrainUnitWindow';
import { WarStatisticWindow } from './army/WarStatisticWindow';
import { SpyingWindow } from './spying/SpyingWindow';
import type { ArmyOverview } from './army/armyData';
import type { ArmyCreationData } from './army/armyCreationData';
import type { TrainUnitData } from './army/trainUnitData';
import type { WarComplexData } from './army/warFactoryData';
import type { SpyingData } from './spying/spyingData';
import type { LocalUnit } from './data/localData';
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

const TEST_TABS_CREATE: TabDef[] = [
  { id: 'ARMY_OVERVIEW', ln: 'Обзор армий', cur: 0 },
  { id: 'ARMY_CREATION', ln: 'Создать армию', cur: 1 },
];

// Синтетические данные для проверки ArmyCreationWindow (?screen=army-create).
const TEST_ARMY_CREATION: ArmyCreationData = {
  cur: 3,
  max: 10,
  units: [
    { id: '3', libname: 'SOLDIER', amount: 500, prodoweight: 0 },
    { id: '9', libname: 'TANK', amount: 40, prodoweight: 2 },
    { id: '7', libname: 'GUARD', amount: 120, prodoweight: 0 },
  ],
};

// Синтетические данные для проверки TrainUnitWindow (?screen=train-unit) —
// живые поля (trainUnitData) + статические характеристики (localData),
// как они реально объединяются в оригинале (см. trainUnitData.ts docstring).
const TEST_TRAIN_UNIT: TrainUnitData = {
  turn: '24',
  speedup: '1',
  units: [
    {
      idName: 'SOLDIER',
      idUnit: '3',
      type: null,
      sctarg: null,
      sccur: null,
      avail: '1',
      inProgress: 0,
      endDate: null,
      upkeep: { kad: 1, nano: 0, energy: 2, food: 3, prod: 0, work: 0 },
    },
    {
      idName: 'TANK',
      idUnit: '9',
      type: null,
      sctarg: null,
      sccur: null,
      avail: '1',
      inProgress: 5,
      endDate: '2026-09-06 10:00:00',
      upkeep: { kad: 4, nano: 2, energy: 5, food: 1, prod: 1, work: 0 },
    },
    {
      idName: 'GUARD',
      idUnit: '7',
      type: null,
      sctarg: null,
      sccur: null,
      avail: '0',
      inProgress: 0,
      endDate: null,
      upkeep: { kad: 2, nano: 0, energy: 1, food: 2, prod: 0, work: 0 },
    },
  ],
};
const TEST_LOCAL_UNITS = new Map<string, LocalUnit>([
  [
    'SOLDIER',
    {
      id: 'SOLDIER',
      ln: 'Солдат',
      level: '1',
      race: '1',
      attack: '50%',
      defence: '25%',
      damageMin: '10',
      damageMax: '10',
      hitpoints: '10',
      description: '',
      attrs: {},
    },
  ],
  [
    'TANK',
    {
      id: 'TANK',
      ln: 'Танк',
      level: '3',
      race: '1',
      attack: '80%',
      defence: '60%',
      damageMin: '40',
      damageMax: '60',
      hitpoints: '200',
      description: '',
      attrs: {},
    },
  ],
  [
    'GUARD',
    {
      id: 'GUARD',
      ln: 'Страж',
      level: '2',
      race: '1',
      attack: '30%',
      defence: '70%',
      damageMin: '15',
      damageMax: '20',
      hitpoints: '150',
      description: '',
      attrs: {},
    },
  ],
]);

// Синтетические данные для проверки WarStatisticWindow (?screen=war-statistic).
const TEST_WAR_COMPLEX: WarComplexData = {
  turn: '24',
  speedup: '0',
  units: [
    { id: '3', unitname: 'SOLDIER', types: ['LIGHT'], upkeep: { kad: 1, nano: 0, food: 3, energy: 2, prod: 0 }, maxallow: null },
    { id: '9', unitname: 'TANK', types: ['HEAVY'], upkeep: { kad: 4, nano: 2, food: 1, energy: 5, prod: 1 }, maxallow: null },
  ],
  factories: new Map([
    ['1', { idCell: '1', type: 'LIGHT', busy: false, unit: null, count: 0, timeEnd: null }],
    ['2', { idCell: '2', type: 'LIGHT', busy: true, unit: 'SOLDIER', count: 12, timeEnd: '2026-09-06 10:00:00' }],
    ['3', { idCell: '3', type: 'HEAVY', busy: false, unit: null, count: 0, timeEnd: null }],
    ['4', { idCell: '4', type: 'HEAVY', busy: true, unit: 'TANK', count: 3, timeEnd: '2026-09-06 12:00:00' }],
  ]),
};

// Синтетические данные для проверки SpyingWindow (?screen=spying).
const TEST_SPYING: SpyingData = {
  turn: '24',
  battleXp: '10',
  numRing: '1',
  actions: [
    { id: 'RESEARCH', name: 'Исследовать', addrAllow: '1', timeAllow: '1' },
    { id: 'SCOUT', name: 'Разведать', addrAllow: '1', timeAllow: '0' },
  ],
  time: { min: '1', max: '24', def: '4' },
  speed: { min: '10', max: '150', def: '80' },
  allowZonds: [
    { typeNum: '1', id: 'Z1', allowCount: 15 },
    { typeNum: '2', id: 'Z2', allowCount: 3 },
  ],
  ringAllow: [
    { ringKey: 'RING_TECH', allowed: true },
    { ringKey: 'RING_BIO', allowed: true },
    { ringKey: 'RING_PSI', allowed: false },
    { ringKey: 'RING_COLONIZE', allowed: false },
  ],
  activeGroups: [],
};
const TEST_ZOND_NAMES = new Map([
  ['Z1', 'Зонд-Разведчик'],
  ['Z2', 'Зонд-Исследователь'],
]);

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

  if (screen === 'train-unit') {
    openWindow(
      <TrainUnitWindow
        data={TEST_TRAIN_UNIT}
        localUnits={TEST_LOCAL_UNITS}
        onClose={() => console.log('[train-unit] close')}
        onTrain={(idUnit, count) => console.log('[train-unit] trainWarUnit(', count, idUnit, ')')}
        onCancel={(idUnit) => console.log('[train-unit] cancel(', idUnit, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'spying') {
    openWindow(
      <SpyingWindow
        data={TEST_SPYING}
        zondNames={TEST_ZOND_NAMES}
        onClose={() => console.log('[spying] close')}
        onSend={(params) => console.log('[spying] send_zond(', params, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'war-statistic') {
    openWindow(
      <WarStatisticWindow
        data={TEST_WAR_COMPLEX}
        localUnits={TEST_LOCAL_UNITS}
        onClose={() => console.log('[war-statistic] close')}
        onLoad={(cellIds, unitId, amount) =>
          console.log('[war-statistic] load_pack_war_factory(', cellIds, unitId, amount, ')')
        }
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'army-create') {
    openWindow(
      <ArmyCreationWindow
        data={TEST_ARMY_CREATION}
        tabs={TEST_TABS_CREATE}
        onTabClick={(tabId) => console.log('[army-create] tab click ->', tabId)}
        onClose={() => console.log('[army-create] close')}
        onCreate={(name, units) => console.log('[army-create] create_army(', name, units, ')')}
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
