import './style.css';
import './ui/window.css';
import { render } from 'preact';
import { MapRenderer } from './map/renderer';
import type { BuildRecord } from './map/buildData';
import { runLiveApp } from './liveApp';
import { BattleRenderer } from './battle/battleRenderer';
import type { BattleRound } from './battle/battleData';
import { WindowHost } from './ui/WindowHost';
import { openWindow } from './ui/windowStore';
import { ArmyOverviewWindow } from './army/ArmyOverviewWindow';
import { ArmyCreationWindow } from './army/ArmyCreationWindow';
import { TrainUnitWindow } from './army/TrainUnitWindow';
import { WarStatisticWindow } from './army/WarStatisticWindow';
import { SpyingWindow } from './spying/SpyingWindow';
import { ZondCreateWindow } from './spying/ZondCreateWindow';
import { PowerShieldWindow } from './spying/PowerShieldWindow';
import { ProtectShieldWindow } from './spying/ProtectShieldWindow';
import { ScannerWindow } from './spying/ScannerWindow';
import { BuildMenuWindow } from './buildings/BuildMenuWindow';
import { BuildInfoWindow } from './buildings/BuildInfoWindow';
import { ArtDepotWindow } from './buildings/ArtDepotWindow';
import { ResearchWindow } from './science/ResearchWindow';
import { AuctionWindow } from './trade/AuctionWindow';
import { SellWindow } from './trade/SellWindow';
import { TransferWindow } from './trade/TransferWindow';
import { AllianceManagementWindow } from './alliance/AllianceManagementWindow';
import { AllianceMemberWindow } from './alliance/AllianceMemberWindow';
import { PrivatePeaceWindow } from './alliance/PrivatePeaceWindow';
import { TerraformWindow } from './world/TerraformWindow';
import { ReligionWindow } from './world/ReligionWindow';
import { ArcheologyWindow } from './world/ArcheologyWindow';
import { ColonizationWindow } from './world/ColonizationWindow';
import { ReincarnateWindow } from './world/ReincarnateWindow';
import { ArtefactStatWindow } from './artefacts/ArtefactStatWindow';
import { BuildArtefactWindow } from './artefacts/BuildArtefactWindow';
import { MailWindow } from './mail/MailWindow';
import { CreateNewMailWindow } from './mail/CreateNewMailWindow';
import { LogWindow } from './mail/LogWindow';
import { SysMessageWindow } from './mail/SysMessageWindow';
import { QuestWindow } from './quests/QuestWindow';
import { SettingsWindow } from './settings/SettingsWindow';
import { AdviceWindow } from './settings/AdviceWindow';
import { CabinetWindow } from './cabinet/CabinetWindow';
import { ArtefactShopWindow } from './cabinet/ArtefactShopWindow';
import { VacationWindow } from './vacation/VacationWindow';
import type { ArmyOverview } from './army/armyData';
import type { ArmyCreationData } from './army/armyCreationData';
import type { TrainUnitData } from './army/trainUnitData';
import type { WarComplexData } from './army/warFactoryData';
import type { SpyingData } from './spying/spyingData';
import type { ZondCreateData } from './spying/zondCreateData';
import type { PowerShieldData } from './spying/powerShieldData';
import type { ProtectShieldData } from './spying/protectShieldData';
import type { SpyLogData } from './spying/scannerData';
import type { BuildMenuEntry, PlayerResourcePoolFull } from './buildings/buildMenuData';
import type { BuildInfoData } from './buildings/buildInfoData';
import type { ArtDepotEntry } from './buildings/artDepotData';
import type { ResearchData } from './science/researchData';
import type { AuctionLot } from './trade/auctionData';
import type { TradeMenuData } from './trade/tradeData';
import type { TransferMenuData } from './trade/transferData';
import type { AllianceManagementData } from './alliance/allianceData';
import type { AllianceOverviewData } from './alliance/allianceMemberData';
import type { PrivatePeaceData } from './alliance/privatePeaceData';
import type { TerraformOption } from './world/terraformData';
import type { ReligionOption } from './world/religionData';
import type { ArcheologyData, PlayerKadFoodPool } from './world/archeologyData';
import type { ColonizationData } from './world/colonizationData';
import type { ReincarnateData } from './world/reincarnateData';
import type { ArtefactStatData } from './artefacts/artefactStatData';
import type { BuildArtefactData } from './artefacts/buildArtefactData';
import type { MailListData } from './mail/mailData';
import type { CreateNewMailData } from './mail/createNewMailData';
import type { LogWindowData } from './mail/logData';
import type { QuestMenuData } from './quests/questData';
import type { AdviceHint } from './settings/adviceData';
import type { CabinetMainData } from './cabinet/cabinetMainData';
import type { SubmenuFormData } from './cabinet/submenuData';
import type { ArtefactShopData } from './cabinet/artefactShopData';
import type { VacationData } from './vacation/vacationData';
import type { LocalUnit, LocalBuilding } from './data/localData';
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

// Синтетические данные для проверки ZondCreateWindow (?screen=zond-create).
const TEST_ZOND_CREATE: ZondCreateData = {
  battleXp: '10',
  alert: null,
  allowZond: [
    { type: '1', id: 'Z1', count: 5, inAction: 2, already: 15, awail: '1', price: { K: 100, N: 20 }, delta: { K: 1.05, N: 1.05 } },
    { type: '2', id: 'Z2', count: 1, inAction: 0, already: 3, awail: '1', price: { K: 400, N: 80, S: 50 }, delta: { K: 1.1, N: 1.1, S: 1.1 } },
  ],
};

// Синтетические данные для проверки PowerShieldWindow (?screen=power-shield).
const TEST_POWER_SHIELD: PowerShieldData = {
  turn: '24',
  minTurn: '1',
  maxTurn: '10',
  status: '3',
  price: 50,
  desc1: 'Силовой щит заряжен и готов к активации.',
  desc2: '',
};

// Синтетические данные для проверки ProtectShieldWindow (?screen=protect-shield).
const TEST_PROTECT_SHIELD: ProtectShieldData = {
  currentPercent: 40,
  priceRes: [10, 5, 8, 3, 2],
  priceSilver: 4,
};

// Синтетические данные для проверки ScannerWindow (?screen=scanner).
const TEST_SPY_LOG: SpyLogData = {
  showFiltersMenu: true,
  entries: [
    {
      id: '56',
      action: 'Razvedko',
      addrFrom: '1.2.3',
      addrTo: '2.6.6',
      date: '15.01 13:15',
      typelog: 'FullLog',
      zname: 'Зонд Исследователь',
      zcolor: '#66ccff',
    },
    {
      id: '57',
      action: 'Razvedko',
      addrFrom: '1.2.3',
      addrTo: '2.6.7',
      date: '15.01 14:02',
      typelog: 'ShortLog',
      zname: 'Зонд Разведчик',
      zcolor: '#ffcc66',
    },
  ],
  filter: {
    maxAllowZond: '10',
    typesMove: [],
    colonys: [],
    defName: null,
    time: { cur: null, min: null, max: null },
  },
};

// Синтетические данные для проверки BuildMenuWindow (?screen=build-menu).
const TEST_BUILD_MENU: BuildMenuEntry[] = [
  { id: 9, serverAllowed: true, goldCredit: 0, silverCredit: 0, alwaysAllow: false },
  { id: 15, serverAllowed: true, goldCredit: 0, silverCredit: 50, alwaysAllow: false },
  { id: 6, serverAllowed: false, goldCredit: 0, silverCredit: 0, alwaysAllow: true },
];
const TEST_LOCAL_BUILDINGS = new Map<number, LocalBuilding>([
  [
    9,
    {
      id: 9,
      name: 'RESIDENTIAL_BUILDING',
      type: 'RES',
      ln: 'Жилой дом',
      unique: false,
      description: '',
      attrs: { K: '100', N: '20', S: '0', F: '50', P: '0', W: '1', C: '0.5' },
    },
  ],
  [
    15,
    {
      id: 15,
      name: 'CITY',
      type: 'RES',
      ln: 'Город',
      unique: true,
      description: '',
      attrs: { K: '5000', N: '2000', S: '500', F: '1000', P: '200', W: '10', C: '2' },
    },
  ],
  [
    6,
    {
      id: 6,
      name: 'CENTRAL_GATE',
      type: 'MIL',
      ln: 'Центральные врата',
      unique: true,
      description: '',
      attrs: { K: '0', N: '0', S: '0', F: '0', P: '0', W: '0', C: '0' },
    },
  ],
]);
const TEST_PLAYER_RESOURCES: PlayerResourcePoolFull = {
  kaderium: 300,
  nanoCrystal: 100,
  stasisEnergy: 50,
  food: 200,
  prodium: 5,
  worker: 5,
  silverCredit: 10,
  goldCredit: 0,
};

// Синтетические данные для проверки BuildInfoWindow (?screen=build-info).
const TEST_BUILD_INFO: BuildInfoData = {
  info: { destr: '1', deact: '1', upgrade: '1' },
  upgrade: {
    idNextBuild: '40',
    allow: true,
    kad: '2000',
    nano: '500',
    stat: '100',
    food: '300',
    prod: '50',
    time: '12',
    work: '2',
    sctarg: null,
    sccur: null,
  },
  added: { K: '0', N: '0', S: '0', F: '0', P: '0' },
  removed: { K: '0', N: '0', S: '0', F: '0', P: '0' },
  allowGround: [],
  messages: {},
};

// Синтетические данные для проверки ArtDepotWindow (?screen=art-depot).
const TEST_ART_DEPOT: ArtDepotEntry[] = [
  {
    id: '1',
    libName: 'ARTEFACT_TECH_CATALYZATION',
    prefix: 'ARTEFACT',
    scope: 'TECH',
    postfix: 'CATALYZATION',
    expired: '',
    removeAllow: true,
    bonuses: [{ name: 'PRODUCTION_SPEED', value: '15' }],
    link: null,
  },
  {
    id: '2',
    libName: 'ARTEFACT_BONUS_GIFT',
    prefix: 'ARTEFACT',
    scope: 'BONUS',
    postfix: 'GIFT',
    expired: 'истекает через 3 дня',
    removeAllow: false,
    bonuses: [],
    link: { id: '2', text: 'Активировать', hintText: 'Разовый подарок' },
  },
];

// Синтетические данные для проверки ResearchWindow (?screen=research).
const TEST_RESEARCH: ResearchData = {
  branches: [
    { id: 'KADERIUM', name: 'Кадериум', ir: null, nextLevel: '4', level: '3', allow: '1', bonusAll: '15%', bonus: '5%', time: '12', inProgressUntil: null },
    { id: 'NANO_CRYSTAL', name: 'Нанокристалл', ir: null, nextLevel: '2', level: '1', allow: '1', bonusAll: '5%', bonus: '3%', time: '-', inProgressUntil: '2026-09-07 10:00' },
    { id: 'SPYING', name: 'Шпионаж', ir: null, nextLevel: '0', level: null, allow: '0', bonusAll: '-', bonus: '-', time: '-', inProgressUntil: null },
  ],
  train: {
    turn: '24',
    work: 3,
    scientistMax: 10,
    scientistAllowRaw: 8,
    inProgress: 1,
    priceWork: 1,
    priceNano: 50,
    priceFood: 30,
    priceTime: 2,
  },
  messages: {},
};

// Синтетические данные для проверки AuctionWindow (?screen=auction).
const TEST_AUCTION_LOTS: AuctionLot[] = [
  {
    id: '1',
    endDate: '2026-09-06 12:00',
    amount: 500,
    getPrice: { K: '100' },
    currentPrice: 100,
    currentPriceOnce: null,
    seller: 'Игрок1',
    tradeBy: 'KADERIUM',
    tradeScope: null,
    immediate: '1',
    libname: 'NANO_CRYSTAL',
    buyer: null,
    serverDisabled: false,
    nextPrice: 100,
    maxPrice: null,
  },
  {
    id: '2',
    endDate: '2026-09-06 15:00',
    amount: 10,
    getPrice: { K: '5000' },
    currentPrice: 5000,
    currentPriceOnce: null,
    seller: 'Игрок2',
    tradeBy: 'KADERIUM',
    tradeScope: null,
    immediate: '0',
    libname: 'TANK',
    buyer: null,
    serverDisabled: false,
    nextPrice: 5200,
    maxPrice: null,
  },
];
const TEST_PLAYER_RESOURCE_BY_NAME = new Map([['KADERIUM', 300]]);

// Синтетические данные для проверки SellWindow (?screen=sell).
const TEST_TRADE_MENU: TradeMenuData = {
  turn: '24',
  minQuantity: 50,
  time2Trade: 24,
  flags: { nalog: true, trend: true, person: true, anonymous: false },
  matrix: {
    trades: ['1', '2', '3'],
    reses: [
      { name: 'KADERIUM', ln: 'Кадериум', price: 1, category: '1', id: '1' },
      { name: 'SILVER_CREDIT', ln: 'Серебро', price: 1, category: '1', id: '9' },
    ],
    data: [
      [{ allow: true, minOrder: 10, maxOrder: 200, tax: 5 }, { allow: true, minOrder: 5, maxOrder: 300, tax: 2 }],
      [{ allow: true, minOrder: 10, maxOrder: 200, tax: 5 }, { allow: false }],
      [{ allow: false }, { allow: true, minOrder: 5, maxOrder: 300, tax: 2 }],
    ],
  },
  articlesByCategory: {
    1: [
      { category: 1, idCat: '1_2', id: '2', amount: 1000, upkeep: { K: 0, N: 0, P: 0, F: 0, S: 0 }, power: {}, libname: 'NANO_CRYSTAL', ln: null, isArtefact: false },
    ],
    2: [
      { category: 2, idCat: '2_9', id: '9', amount: 20, upkeep: { K: 0, N: 0, P: 0, F: 0, S: 0 }, power: {}, libname: 'TANK', ln: null, isArtefact: false },
    ],
    3: [
      { category: 3, idCat: '3_1', id: '1', amount: 3, upkeep: { K: 0, N: 0, P: 0, F: 0, S: 0 }, power: {}, libname: null, ln: null, isArtefact: true, artId: '1', datum: '' },
    ],
  },
  scopes: [
    { id: '1', ln: 'Комплекс' },
    { id: '2', ln: 'Кольцо' },
  ],
};

// Синтетические данные для проверки TransferWindow (?screen=transfer).
const TEST_TRANSFER: TransferMenuData = {
  articlesByCategory: {
    1: [{ category: 1, id: '2', libname: 'NANO_CRYSTAL', amount: '500' }],
    2: [{ category: 2, id: '9', libname: 'TANK', amount: '10' }],
    3: [],
  },
  targets: [
    { idComb: '5', combName: 'Вторая колония', coords: '2.1.7' },
    { idComb: '6', combName: 'Третья колония', coords: '2.1.9' },
  ],
};

// Синтетические данные для проверки AllianceManagementWindow (?screen=alliance).
const TEST_ALLIANCE_OWN: AllianceManagementData = {
  inAlliance: true,
  own: { id: '10', name: 'Наш альянс', tag: 'OUR', religion: null, status: 'LEADER', leave: '1' },
};
const TEST_ALLIANCE_JOIN: AllianceManagementData = {
  inAlliance: false,
  alliancesToJoin: [
    { id: '20', name: 'Чужой альянс', tag: 'FRN', leader: 'SomeLeader' },
  ],
};

// Синтетические данные для проверки AllianceMemberWindow (?screen=alliance-members).
const TEST_ALLIANCE_MEMBERS: AllianceOverviewData = {
  curPage: '1',
  maxPage: '1',
  leader: '1',
  idAlliance: '10',
  members: [
    { name: 'Игрок1', userId: '1', ranking: '100', coord: '1.2.3', artefact: null, statusLn: 'Лидер', status: 'LEADER', deleteButtonDisabled: false },
    { name: 'Игрок2', userId: '2', ranking: '80', coord: '1.2.4', artefact: null, statusLn: 'Участник', status: 'MEMBER', deleteButtonDisabled: false },
    { name: 'Игрок3', userId: '3', ranking: '50', coord: '1.2.5', artefact: null, statusLn: 'Защищён', status: 'MEMBER', deleteButtonDisabled: true },
  ],
};

// Синтетические данные для проверки PrivatePeaceWindow (?screen=private-peace).
const TEST_PRIVATE_PEACE: PrivatePeaceData = {
  requests: [{ idAlliance: '10', tag: '[OUR]', member: 'Enemy1', uid: '5' }],
  members: [
    { idAlliance: '20', tag: '[FRN]', member: 'Friend1', joined: '1', uid: '6' },
    { idAlliance: '30', tag: '[NEW]', member: 'Newcomer', joined: '0', uid: '7' },
  ],
};

// Синтетические данные для проверки TerraformWindow (?screen=terraform).
const TEST_TERRAFORM: TerraformOption[] = [
  { typeGround: 'GRASS', time: '12', kad: 100, nano: 20, prod: 0, stas: 10, work: 1, food: 5, allow: '1', action: '' },
  { typeGround: 'DESERT', time: '24', kad: 200, nano: 40, prod: 0, stas: 20, work: 2, food: 10, allow: '2', action: '' },
  { typeGround: 'ROCK', time: '36', kad: 300, nano: 60, prod: 0, stas: 30, work: 3, food: 15, allow: undefined, action: '' },
];

// Синтетические данные для проверки ReligionWindow (?screen=religion).
const TEST_RELIGION: ReligionOption[] = [
  {
    id: '1',
    status: 'RELIGI_CONNECT',
    name: 'RELIGI_WOLKENENDER',
    percent: null,
    date: null,
    bonuses: [{ name: 'PRODUCTION_BONUS', value: 10 }],
    desc: 'Религия облачных странников.',
  },
];

// Синтетические данные для проверки ArcheologyWindow (?screen=archeology).
const TEST_ARCHEOLOGY: ArcheologyData = {
  extendedTime: true,
  expeditions: [{ count: '20', dest: 'CENTRAL', endDate: '2026-09-06 19:22', minSize: '3' }],
  train: { turn: '600', archeologistMax: 20, archeologistAllowRaw: 15, inProgress: 2, work: 5, priceWork: 1, priceKad: 50, priceFood: 20, priceTime: 1 },
  messages: {},
};
const TEST_ARCH_RESOURCES: PlayerKadFoodPool = { kaderium: 10000, food: 10000 };

// Синтетические данные для проверки ColonizationWindow (?screen=colonization).
const TEST_COLONIZATION: ColonizationData = {
  action: '1',
  idModule: '1',
  rings: [
    { id: '1', ln: 'Кольцо технологий' },
    { id: '2', ln: 'Кольцо биологии' },
  ],
  textStatus: '',
};

// Синтетические данные для проверки ReincarnateWindow (?screen=reincarnate).
const TEST_REINCARNATE: ReincarnateData = {
  fork: {
    maxProdium: 1000,
    lostProdium: 200,
    buildProdium: 500,
    reincProdium: 300,
    targetProdium: 700,
    resEnabled: true,
    silEnabled: true,
  },
  statsByWeek: [
    { w: '10,20,30,40,50,60,70', m: '100,200,300' },
    { w: '5,10,15,20,25,30,35', m: '50,100,150' },
    { w: '1,2,3,4,5,6,7', m: '10,20,30' },
  ],
  units: [
    { id: '3', libname: 'SOLDIER', prodiumWeight: 2, price: { KA: 10, NA: 5, ST: 0, FO: 5, PR: 0, SC: 0 } },
    { id: '9', libname: 'TANK', prodiumWeight: 10, price: { KA: 50, NA: 20, ST: 10, FO: 5, PR: 0, SC: 0 } },
  ],
  queue: [{ id: '3', libname: 'SOLDIER', time: ['2026-09-05 10:00', '2026-09-05 14:00'], amount: '5' }],
};
const TEST_REINCARNATE_RESOURCES = [100000, 100000, 100000, 100000, 100000, 100000];

// Синтетические данные для проверки VacationWindow (?screen=vacation).
const TEST_VACATION_NOT_STARTED: VacationData = { status: '1', dataText: 'Вы можете уйти в отпуск.', descText: 'Постройки не разрушаются, атаки невозможны.' };
const TEST_VACATION_ACTIVE: VacationData = { status: '3', dataText: 'Вы в отпуске.', descText: 'Введите пароль отпуска, чтобы выйти.' };

// Синтетические данные для проверки CabinetWindow (?screen=cabinet).
const TEST_CABINET: CabinetMainData = {
  billingUrl: 'https://billing.example/pay',
  user: {
    name: 'Dartana',
    email: 'dartana@example.com',
    allowWorld: [
      { name: 'Sphere Wolf', id: '123', gold: 12, silver: 14 },
      { name: 'Sphere Orion', id: '1123', gold: 16, silver: 18 },
    ],
  },
  currentStatus: { gold: 1500, silver: 15000 },
  serviceList: [
    { type: 'HEAD', name: 'Услуги' },
    { type: 'SUB', id: 'buy_res', name: 'Купить ресурсы' },
    { type: 'SUB', id: 'finish_act', name: 'Ускорить действие' },
    { type: 'SPLITER' },
    { type: 'HEAD', name: 'Прочее' },
    { type: 'SUB', id: 'set_user_name', name: 'Сменить имя' },
  ],
  popupMessage: 'Добро пожаловать в личный кабинет!',
  lastOperation: { date: '2026-09-05', text: 'Обмен 100 золота на серебро' },
};

// Синтетические данные для проверки SubmenuForm внутри CabinetWindow.
const TEST_SUBMENU: SubmenuFormData = {
  idmenu: 'buy_res',
  titleWindow: 'Купить ресурсы',
  closeButtonEnabled: true,
  elements: [
    { type: 'textfield', id: 't1', text: 'Выберите ресурс и количество:' },
    { type: 'br' },
    {
      type: 'combobox',
      id: 'resource',
      defIndex: 0,
      options: [
        { id: 'kad', ln: 'Кадерий' },
        { id: 'nano', ln: 'Нанокристалл' },
      ],
    },
    { type: 'amountf', id: 'count', min: 0, max: 1000, def: 10 },
    { type: 'hidden', id: 'src', data: 'cabinet' },
    { type: 'button', id: 'confirm', label: 'Купить', fieldIdsForSend: ['resource', 'count', 'src'], confirmText: 'Точно купить?' },
  ],
};

// Синтетические данные для проверки ArtefactShopWindow (?screen=artefact-shop).
const TEST_ARTEFACT_SHOP: ArtefactShopData = {
  filters: [
    { id: '1', listTypes: [{ id: '10', name: 'Военные' }, { id: '11', name: 'Экономические' }] },
    { id: '2', listTypes: [{ id: '20', name: 'Редкие' }] },
  ],
  artefacts: [
    {
      id: '1', name: 'Артефакт атаки', exp: '30', icon: 'ART1', priceSilver: 500, priceGold: 50,
      bonusText: 'Атака +15%', life: '2592000', filterData: [{ id: 'filter1', value: '10' }, { id: 'filter2', value: '20' }],
    },
    {
      id: '2', name: 'Артефакт защиты', exp: '30', icon: 'ART2', priceSilver: 300, priceGold: 30,
      bonusText: 'Защита +10%', life: '2592000', filterData: [{ id: 'filter1', value: '11' }, { id: 'filter2', value: '20' }],
    },
  ],
};

// Синтетические данные для проверки AdviceWindow (?screen=advice).
const TEST_ADVICE: AdviceHint = {
  title: 'Добро пожаловать',
  total: 5,
  number: 3,
  build: 'WAREHOUSE',
  tobuild: '',
  surface: 0,
  icon: '',
  txt: 'Постройте <b>Склад</b>, чтобы увеличить лимит хранения ресурсов.',
  relatedHints: [
    { id: '1', name: 'Основы экономики' },
    { id: '4', name: 'Постройки' },
  ],
};

// Синтетические данные для проверки QuestWindow (?screen=quest).
// Специально: newQuest непусто → окно должно по умолчанию открыться на
// вкладке id=2 (подпись "Новые"), но показать data.finishedQuest —
// намеренно воспроизведённый сдвиг подписи/данных, см. questData.ts.
const TEST_QUEST: QuestMenuData = {
  currentQuest: [{ name: 'Задание А (current_quest)', desc: '', bonus: [{ name: 'Кадерий', icon: 'KADERIUM', value: '500' }] }],
  newQuest: [{ name: 'Задание Б (new_quest)', desc: '', bonus: [{ name: 'Рабочий', icon: 'WORKER', value: '10' }] }],
  finishedQuest: [{ name: 'Задание В (finished_quest)', desc: '', bonus: [{ name: 'Продиум', icon: 'PRODIUM', value: '100' }] }],
};

// Синтетические данные для проверки MailWindow (?screen=mail).
const TEST_MAIL: MailListData = {
  type: 'in',
  mails: [
    { id: '1', name: 'Alice', datecreate: '2026-09-01 10:00', dateread: '', subj: 'Привет!', lscopename: 'Личное', scopeid: '1', deleteFlag: false },
    { id: '2', name: 'Совет альянса', datecreate: '2026-09-02 12:00', dateread: '2026-09-02 13:00', subj: 'Собрание', lscopename: 'Альянс', scopeid: '2', deleteFlag: false },
    { id: '3', name: 'Bob', datecreate: '2026-09-03 09:30', dateread: '', subj: 'Торговое предложение', lscopename: 'Личное', scopeid: '1', deleteFlag: false },
  ],
};

// Синтетические данные для проверки CreateNewMailWindow (?screen=create-mail).
const TEST_CREATE_MAIL: CreateNewMailData = {
  scopes: [
    { id: '1', name: 'personal', ln: 'Личное' },
    { id: '2', name: 'alliance', ln: 'Альянс' },
  ],
  recipientName: 'Alice',
  quoteSubject: 'Re: Привет!',
  quoteBody: 'исходный текст письма',
};

// Синтетические данные для проверки LogWindow (?screen=log-window).
const TEST_LOG_WINDOW: LogWindowData = {
  title: 'Отчёт о разведке',
  titleData: '<b>Цель:</b> 1.2.3',
  textData: 'Обнаружено: 5 юнитов SOLDIER, 2 юнита TANK.<br/>Оборона: слабая.',
};

// Синтетические данные для проверки SysMessageWindow (?screen=sys-message).
const TEST_SYS_MESSAGES = [
  '<font color="#33cc66">Постройка завершена: Склад</font>',
  '<font color="#ffcc66">Атака отражена в соте 1.2.4</font>',
];

// Синтетические данные для проверки ArtefactStatWindow (?screen=artefact-stat).
const TEST_ARTEFACT_STAT: ArtefactStatData = new Map([
  ['1', { type: 'STASIS_ENERGY', maxSlots: 3, fullSlots: 1, artInBuild: [
    { id: '1', bonus: '15%', idArt: '101', remain: '2600000' }, // >= 30 суток — зелёный
  ] }],
  ['2', { type: 'KADERIUM', maxSlots: 2, fullSlots: 1, artInBuild: [
    { id: '2', bonus: '10%', idArt: '102', remain: '700000' }, // >= 7 суток — жёлтый
  ] }],
  ['3', { type: 'COMBO', maxSlots: 4, fullSlots: 1, artInBuild: [
    { id: '3', bonus: '20%', idArt: '103', remain: '100000' }, // < 7 суток — красный
  ] }],
]);

// Синтетические данные для проверки BuildArtefactWindow (?screen=build-artefact).
const TEST_BUILD_ARTEFACT: BuildArtefactData = {
  countSlots: 3,
  attached: [
    { id: '1', libname: 'ART_ATTACK_MEDIUM', bonuses: [{ bonusType: 'attack', bonusValue: '15' }], expired: '' },
  ],
  available: [
    { id: '2', libname: 'ART_DEFENSE_SMALL', bonuses: [{ bonusType: 'defense', bonusValue: '5' }], expired: '' },
    { id: '3', libname: 'ART_SPEED_LARGE', bonuses: [{ bonusType: 'speed', bonusValue: '30' }], expired: 'скоро истекает' },
  ],
};

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

  if (screen === 'reincarnate') {
    openWindow(
      <ReincarnateWindow
        data={TEST_REINCARNATE}
        playerResources={TEST_REINCARNATE_RESOURCES}
        onClose={() => console.log('[reincarnate] close')}
        onSend={(cmd) => console.log('[reincarnate] getReincarnate(', cmd, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'terraform') {
    openWindow(
      <TerraformWindow
        options={TEST_TERRAFORM}
        isPremium={false}
        onClose={() => console.log('[terraform] close')}
        onStart={(type) => console.log('[terraform] start_terraform(', type, ')')}
        onCancel={(type) => console.log('[terraform] stop_terraform(', type, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'religion') {
    openWindow(
      <ReligionWindow
        religions={TEST_RELIGION}
        onClose={() => console.log('[religion] close')}
        onConnect={(id) => console.log('[religion] religyConnect(', id, ')')}
        onLeave={(id) => console.log('[religion] religyOut(', id, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'archeology') {
    openWindow(
      <ArcheologyWindow
        data={TEST_ARCHEOLOGY}
        playerResources={TEST_ARCH_RESOURCES}
        isPremium={false}
        gateOpen={true}
        onClose={() => console.log('[archeology] close')}
        onTrain={(count) => console.log('[archeology] trainArch(', count, ')')}
        onSendExpedition={(dest, count, turns, size, group) =>
          console.log('[archeology] sendArchExpedition(', count, dest, turns, size, group, ')')
        }
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'colonization') {
    openWindow(
      <ColonizationWindow
        data={TEST_COLONIZATION}
        onClose={() => console.log('[colonization] close')}
        onSend={(dest, name) => console.log('[colonization] sendColonyModule(', dest, name, ')')}
        onCancel={() => console.log('[colonization] cancelColonyModule()')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'alliance' || screen === 'alliance-join') {
    openWindow(
      <AllianceManagementWindow
        data={screen === 'alliance' ? TEST_ALLIANCE_OWN : TEST_ALLIANCE_JOIN}
        onClose={() => console.log('[alliance] close')}
        onExit={(id) => console.log('[alliance] exitAlliance(', id, ')')}
        onCreate={(cmd) => console.log('[alliance] createAlliance(', cmd, ')')}
        onJoin={(cmd) => console.log('[alliance] joinAlliance(', cmd, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'alliance-members') {
    openWindow(
      <AllianceMemberWindow
        data={TEST_ALLIANCE_MEMBERS}
        onClose={() => console.log('[alliance-members] close')}
        onKick={(userId) => console.log('[alliance-members] kick(', userId, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'private-peace') {
    openWindow(
      <PrivatePeaceWindow
        data={TEST_PRIVATE_PEACE}
        onClose={() => console.log('[private-peace] close')}
        onSend={(cmd) => console.log('[private-peace] getPPeace(', cmd, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'auction') {
    openWindow(
      <AuctionWindow
        lots={TEST_AUCTION_LOTS}
        playerResourceByName={TEST_PLAYER_RESOURCE_BY_NAME}
        onClose={() => console.log('[auction] close')}
        onBuy={(lotId, price) => console.log('[auction] buy_lot(', lotId, price, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'sell') {
    openWindow(
      <SellWindow
        data={TEST_TRADE_MENU}
        onClose={() => console.log('[sell] close')}
        onSell={(cmd) => console.log('[sell] sell(', cmd, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'transfer') {
    openWindow(
      <TransferWindow
        data={TEST_TRANSFER}
        onClose={() => console.log('[transfer] close')}
        onTransfer={(cmd) => console.log('[transfer] transfer_to_colony(', cmd, ')')}
        onCalculate={(id, category, amount, target) =>
          console.log('[transfer] calc_transfer_to_colony(', id, category, amount, target, ')')
        }
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'research') {
    openWindow(
      <ResearchWindow
        data={TEST_RESEARCH}
        playerResources={{ nanoCrystal: 1000, food: 1000 }}
        onClose={() => console.log('[research] close')}
        onStartResearch={(id) => console.log('[research] start_research(', id, ')')}
        onCancelResearch={(id) => console.log('[research] cancel_research(', id, ')')}
        onTrainScientists={(count) => console.log('[research] trainscientists(', count, ')')}
        onCancelTrainScientists={() => console.log('[research] canceltrainscientists()')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'build-menu') {
    openWindow(
      <BuildMenuWindow
        entries={TEST_BUILD_MENU}
        localBuildings={TEST_LOCAL_BUILDINGS}
        resources={TEST_PLAYER_RESOURCES}
        onClose={() => console.log('[build-menu] close')}
        onBuild={(id) => console.log('[build-menu] putbuild(', id, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'build-info') {
    openWindow(
      <BuildInfoWindow
        data={TEST_BUILD_INFO}
        onClose={() => console.log('[build-info] close')}
        onCancelBuild={() => console.log('[build-info] cancelbuild()')}
        onDemolish={() => console.log('[build-info] delbuild()')}
        onCancelUpgrade={() => console.log('[build-info] cancelupgrade()')}
        onActivate={() => console.log('[build-info] activatebuild()')}
        onCancelActivate={() => console.log('[build-info] cancelactivate()')}
        onDeactivate={() => console.log('[build-info] deactivatebuild()')}
        onUpgrade={() => console.log('[build-info] upgradebuild()')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'art-depot') {
    openWindow(
      <ArtDepotWindow
        entries={TEST_ART_DEPOT}
        onClose={() => console.log('[art-depot] close')}
        onDeleteSelected={(ids) => console.log('[art-depot] delArtFromDepot(', ids, ')')}
        onActivate={(id) => console.log('[art-depot] artActivate(', id, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'zond-create') {
    openWindow(
      <ZondCreateWindow
        data={TEST_ZOND_CREATE}
        zondNames={TEST_ZOND_NAMES}
        onClose={() => console.log('[zond-create] close')}
        onTrain={(id, count) => console.log('[zond-create] train(', id, count, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'power-shield') {
    openWindow(
      <PowerShieldWindow
        data={TEST_POWER_SHIELD}
        onClose={() => console.log('[power-shield] close')}
        onCharge={() => console.log('[power-shield] poweractivation()')}
        onActivate={(turns) => console.log('[power-shield] powershieldenabled(', turns, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'protect-shield') {
    openWindow(
      <ProtectShieldWindow
        data={TEST_PROTECT_SHIELD}
        playerResources={{ kaderium: 300, nano: 100, stasis: 50, food: 200, prodium: 5, silver: 100 }}
        onClose={() => console.log('[protect-shield] close')}
        onRestoreWithResources={(percent) => console.log('[protect-shield] restore(res,', percent, ')')}
        onRestoreWithSilver={(percent) => console.log('[protect-shield] restore(silver,', percent, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'scanner') {
    openWindow(
      <ScannerWindow
        data={TEST_SPY_LOG}
        onClose={() => console.log('[scanner] close')}
        onScanSelected={(ids) => console.log('[scanner] requestScan(', ids, ')')}
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

  if (screen === 'artefact-stat') {
    openWindow(
      <ArtefactStatWindow
        data={TEST_ARTEFACT_STAT}
        isPremium={true}
        onClose={() => console.log('[artefact-stat] close')}
        onCellClick={(cellNum) => console.log('[artefact-stat] getbuildmenu(', cellNum, 'tab=BuildingArtefact)')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'build-artefact') {
    openWindow(
      <BuildArtefactWindow
        data={TEST_BUILD_ARTEFACT}
        onClose={() => console.log('[build-artefact] close')}
        onAttach={(id) => console.log('[build-artefact] attachartefact(', id, ')')}
        onDetach={(id) => console.log('[build-artefact] detachartefact(', id, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'mail') {
    openWindow(
      <MailWindow
        data={TEST_MAIL}
        openBody={null}
        onClose={() => console.log('[mail] close')}
        onSwitchType={() => console.log('[mail] get_mail(', TEST_MAIL.type === 'in' ? 'out' : 'in', ')')}
        onOpenMail={(id) => console.log('[mail] getMailMessage(', id, ')')}
        onDeleteSelected={(ids) => console.log('[mail] deletemessages(', ids, ')')}
        onReadSelected={(ids) => console.log('[mail] readmessages(', ids, ')')}
        onNewMail={() => console.log('[mail] createnewmail("new")')}
        onReply={(id) => console.log('[mail] createnewmail("reply",', id, ')')}
        onReplyQuote={(id) => console.log('[mail] createnewmail("quote",', id, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'create-mail') {
    openWindow(
      <CreateNewMailWindow
        data={TEST_CREATE_MAIL}
        isVk={false}
        onClose={() => console.log('[create-mail] close')}
        onSend={(to, subject, body, typeScope) => console.log('[create-mail] send_mail(', to, subject, body, typeScope, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'log-window') {
    openWindow(<LogWindow data={TEST_LOG_WINDOW} onClose={() => console.log('[log-window] close')} onCopy={() => console.log('[log-window] copyToBuffer')} />);
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'sys-message') {
    openWindow(
      <SysMessageWindow
        messages={TEST_SYS_MESSAGES}
        windowName="Системные сообщения"
        typeWin="SPY"
        gateOpen={true}
        onClose={() => console.log('[sys-message] close')}
        onViewSpyLog={() => console.log('[sys-message] getSpyMenu()')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'quest') {
    openWindow(<QuestWindow data={TEST_QUEST} onClose={() => console.log('[quest] close')} />);
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'settings') {
    openWindow(<SettingsWindow onClose={() => console.log('[settings] close')} />);
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'advice') {
    openWindow(
      <AdviceWindow
        hint={TEST_ADVICE}
        onClose={() => console.log('[advice] close')}
        onNext={() => console.log('[advice] getNubHelp(4, HINT)')}
        onPrev={() => console.log('[advice] getNubHelp(2, HINT)')}
        onJumpTo={(id) => console.log('[advice] substnick(', id, ') -> getNubHelp(', id, ', HINT)')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'cabinet') {
    openWindow(
      <CabinetWindow
        data={TEST_CABINET}
        openSubmenu={null}
        onClose={() => console.log('[cabinet] close')}
        onSelectService={(id) => console.log('[cabinet] getSubMenuCabinet(', id, ')')}
        onCloseSubmenu={() => console.log('[cabinet] closeSubMenu')}
        onSubmitSubmenu={(buttonId, values) => console.log('[cabinet] subMenuAction(', buttonId, values, ')')}
        onDismissPopup={() => console.log('[cabinet] dismiss popup')}
        rightBlock={{
          buyGoldMode: 'vk',
          onBuyGold: () => console.log('[cabinet] buyGold (VK)'),
          onOpenExternal: (url) => console.log('[cabinet] open external', url),
          onChangeGoldToSilver: (count) => console.log('[cabinet] changeGoldTOSelver(', count, ')'),
          onMoneyToWorld: (id, gold, silver) => console.log('[cabinet] moneyToWorld(', id, gold, silver, ')'),
          onMoneyFromWorld: (id, gold, silver) => console.log('[cabinet] moneyFormWorld(', id, gold, silver, ')'),
          onGiftMoney: (gold, silver) => console.log('[cabinet] giftMoney(', gold, silver, ')'),
          onShowAllStatistic: () => console.log('[cabinet] getAllStatistic()'),
        }}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'cabinet-submenu') {
    openWindow(
      <CabinetWindow
        data={TEST_CABINET}
        openSubmenu={TEST_SUBMENU}
        onClose={() => console.log('[cabinet] close')}
        onSelectService={(id) => console.log('[cabinet] getSubMenuCabinet(', id, ')')}
        onCloseSubmenu={() => console.log('[cabinet] closeSubMenu')}
        onSubmitSubmenu={(buttonId, values) => console.log('[cabinet] subMenuAction(', buttonId, values, ')')}
        onDismissPopup={() => console.log('[cabinet] dismiss popup')}
        rightBlock={{
          buyGoldMode: 'vk',
          onBuyGold: () => console.log('[cabinet] buyGold (VK)'),
          onOpenExternal: (url) => console.log('[cabinet] open external', url),
          onChangeGoldToSilver: (count) => console.log('[cabinet] changeGoldTOSelver(', count, ')'),
          onMoneyToWorld: (id, gold, silver) => console.log('[cabinet] moneyToWorld(', id, gold, silver, ')'),
          onMoneyFromWorld: (id, gold, silver) => console.log('[cabinet] moneyFormWorld(', id, gold, silver, ')'),
          onGiftMoney: (gold, silver) => console.log('[cabinet] giftMoney(', gold, silver, ')'),
          onShowAllStatistic: () => console.log('[cabinet] getAllStatistic()'),
        }}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'artefact-shop') {
    openWindow(
      <ArtefactShopWindow
        data={TEST_ARTEFACT_SHOP}
        onClose={() => console.log('[artefact-shop] close')}
        onBuy={(cart, concurrency) => console.log('[artefact-shop] buyArtInShop(', cart, concurrency, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'vacation' || screen === 'vacation-active') {
    openWindow(
      <VacationWindow
        data={screen === 'vacation' ? TEST_VACATION_NOT_STARTED : TEST_VACATION_ACTIVE}
        onClose={() => console.log('[vacation] close')}
        onStart={(password) => console.log('[vacation] vacationstart(', password, ')')}
        onStop={(password) => console.log('[vacation] vacationstop(', password, ')')}
        onForgotPassword={() => console.log('[vacation] vacationsedpass()')}
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

  await runLiveApp(renderer, login, pass, uiid);
  console.log('liveApp готов — карта отрисована, клики по ячейкам подключены.');
  (window as any).__renderDone = true;
}

main().catch((err) => {
  console.error('ОШИБКА:', err);
  (window as any).__renderError = String(err);
});
