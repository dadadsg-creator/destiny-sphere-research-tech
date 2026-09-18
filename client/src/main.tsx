import './style.css';
import './ui/window.css';
import './ui/hud/hud.css';
import { render } from 'preact';
import { MapRenderer } from './map/renderer';
import type { BuildRecord } from './map/buildData';
import type { Race } from './map/buildingTypes';
import { runLiveApp } from './liveApp';
import { BattleRenderer } from './battle/battleRenderer';
import type { BattleRound } from './battle/battleData';
import { WindowHost } from './ui/WindowHost';
import { MainScreenHud } from './ui/hud/MainScreenHud';
import { openWindow, pushPopup, popPopup } from './ui/windowStore';
import { InfoPopup } from './ui/Popup';
import { parseOutsideAlert } from './data/outsideAlertData';
import { ArmyOverviewWindow } from './army/ArmyOverviewWindow';
import { ArmyCreationWindow } from './army/ArmyCreationWindow';
import { TrainUnitWindow } from './army/TrainUnitWindow';
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
import { ComplexViewWindow } from './world/ComplexViewWindow';
import type { ComplexViewData } from './world/complexViewData';
import { EconomicalOverviewWindow } from './world/EconomicalOverviewWindow';
import { parseEconomicalOverview } from './world/economicalOverviewData';
import { parseLocalData } from './data/localData';
import { setLocalData, localData } from './data/localDataStore';
import { parseTabsArray } from './protocol/tabsParser';
import { ReincarnateWindow } from './world/ReincarnateWindow';
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
import { AllianceSettingsWindow } from './alliance/AllianceSettingsWindow';
import { AlliancePeaceWindow } from './alliance/AlliancePeaceWindow';
import { AlliancePeaceDetailWindow } from './alliance/AlliancePeaceDetailWindow';
import { ArmyCalculatorWindow } from './army/ArmyCalculatorWindow';
import { BattleOverviewWindow } from './battle/BattleOverviewWindow';
import { BattleWindow } from './battle/BattleWindow';
import type { ArmyOverview } from './army/armyData';
import type { ArmyCreationData } from './army/armyCreationData';
import type { TrainUnitData, PlayerResourcePool } from './army/trainUnitData';
import type { SpyingData } from './spying/spyingData';
import type { ZondCreateData } from './spying/zondCreateData';
import type { PowerShieldData } from './spying/powerShieldData';
import type { ProtectShieldData } from './spying/protectShieldData';
import type { SpyLogData } from './spying/scannerData';
import type { BuildMenuEntry, PlayerResourcePoolFull } from './buildings/buildMenuData';
import type { BuildInfoData } from './buildings/buildInfoData';
import type { ArtDepotEntry, ArtDepotFilter } from './buildings/artDepotData';
import type { ResearchData } from './science/researchData';
import type { AuctionLot, AuctionData } from './trade/auctionData';
import type { TradeMenuData } from './trade/tradeData';
import type { TransferMenuData } from './trade/transferData';
import type { AllianceManagementData } from './alliance/allianceData';
import type { AllianceOverviewData } from './alliance/allianceMemberData';
import type { PrivatePeaceData } from './alliance/privatePeaceData';
import type { AllianceSettingData } from './alliance/allianceSettingData';
import type { AlliancePeaceListData, AlliancePeaceDetailData } from './alliance/alliancePeaceData';
import type { ArmyCalculatorData } from './army/armyCreationData';
import type { BattleOverview } from './battle/battleOverviewData';
import type { BattleInfo } from './battle/battleData';
import type { TerraformOption } from './world/terraformData';
import type { ReligionOption } from './world/religionData';
import type { ArcheologyData, PlayerKadFoodPool } from './world/archeologyData';
import type { ColonizationData } from './world/colonizationData';
import type { ReincarnateData } from './world/reincarnateData';
import type { BuildArtefactData } from './artefacts/buildArtefactData';
import type { MailListData } from './mail/mailData';
import type { CreateNewMailData } from './mail/createNewMailData';
import type { LogWindowData } from './mail/logData';
import type { QuestMenuData } from './quests/questData';
import type { AdviceHint } from './settings/adviceData';
import type { CabinetMainData, OperationStatisticData } from './cabinet/cabinetMainData';
import type { SubmenuFormData } from './cabinet/submenuData';
import type { ArtefactShopData } from './cabinet/artefactShopData';
import type { VacationData } from './vacation/vacationData';
import type { LocalUnit, LocalBuilding, LocalArtefact } from './data/localData';
import type { TabDef } from './ui/tabs';

// Синтетический набор построек для проверки renderBuildings() без живого
// сервера — по одной штуке в углах и в центре соты, id — из buildingTypes.ts
// (снято с живой БД, см. CLIENT_PORTING_PLAN.md, Фаза 2).
const TEST_BUILDINGS: BuildRecord[] = [
  { num: 0, id: 9, art: '', status: 'OK' }, // RESIDENTIAL_BUILDING
  { num: 8, id: 15, art: '', status: 'OK' }, // CITY
  { num: 108, id: 6, art: '', status: 'OK' }, // CENTRAL_GATE (центр соты)
  { num: 208, id: 17, art: '', status: 'OK' }, // FARM
  { num: 216, id: 26, art: '', status: '4' }, // MILITARY_BASE, деактивирована — проверка бейджа UNACTIVE (sota.as:864-868)
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
      mst: 24,
      prodoweight: null,
      units: [
        { id: '9', libname: 'TANK', amount: 12 },
        { id: '3', libname: 'SOLDIER', amount: 40 },
      ],
      allowTransfer: [{ id: '5', armyName: 'Вторая армия' }],
      allowAction: [
        { id: 'ATTACK', actionName: 'Атака' },
        { id: 'DEFEND', actionName: 'Защита' },
        { id: 'HIDE_ARMY', actionName: 'Укрыть' },
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
// Полный ростер боевых юнитов (`war_units.lib.swf`) — под сотню машинных
// имён, здесь используется 10 с подтверждёнными живой БД (2026-09-09,
// ds_unit/ds_localizedtextlanguage) русскими названиями — раньше в
// тестовых данных было только 3 юнита, что заметно меньше, чем в реальной
// игре (замечание владельца).
const TEST_ARMY_CREATION: ArmyCreationData = {
  cur: 3,
  max: 10,
  units: [
    { id: '3', libname: 'SOLDIER', amount: 500, prodoweight: 0 },
    { id: '9', libname: 'TANK', amount: 40, prodoweight: 2 },
    { id: '7', libname: 'GUARD', amount: 120, prodoweight: 0 },
    { id: '11', libname: 'SNIPER', amount: 60, prodoweight: 1 },
    { id: '12', libname: 'KNIGHT', amount: 25, prodoweight: 3 },
    { id: '13', libname: 'GLADIATOR', amount: 15, prodoweight: 4 },
    { id: '14', libname: 'GRENADIER', amount: 80, prodoweight: 1 },
    { id: '15', libname: 'VETERAN', amount: 10, prodoweight: 5 },
    { id: '16', libname: 'CRUISER', amount: 8, prodoweight: 6 },
    { id: '17', libname: 'DESTROYER', amount: 4, prodoweight: 8 },
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
      maxallow: null,
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
      maxallow: null,
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
      maxallow: null,
      upkeep: { kad: 2, nano: 0, energy: 1, food: 2, prod: 0, work: 0 },
    },
    {
      idName: 'SNIPER',
      idUnit: '11',
      type: null,
      sctarg: null,
      sccur: null,
      avail: '1',
      inProgress: 0,
      endDate: null,
      maxallow: null,
      upkeep: { kad: 2, nano: 1, energy: 1, food: 1, prod: 0, work: 0 },
    },
    {
      idName: 'KNIGHT',
      idUnit: '12',
      type: null,
      sctarg: null,
      sccur: null,
      avail: '1',
      inProgress: 0,
      endDate: null,
      maxallow: null,
      upkeep: { kad: 6, nano: 3, energy: 4, food: 2, prod: 2, work: 0 },
    },
    {
      idName: 'GLADIATOR',
      idUnit: '13',
      type: null,
      sctarg: null,
      sccur: null,
      avail: '1',
      inProgress: 0,
      endDate: null,
      maxallow: null,
      upkeep: { kad: 8, nano: 4, energy: 6, food: 3, prod: 3, work: 0 },
    },
    {
      idName: 'GRENADIER',
      idUnit: '14',
      type: null,
      sctarg: null,
      sccur: null,
      avail: '1',
      inProgress: 0,
      endDate: null,
      maxallow: null,
      upkeep: { kad: 3, nano: 1, energy: 2, food: 2, prod: 0, work: 0 },
    },
    {
      idName: 'VETERAN',
      idUnit: '15',
      type: null,
      sctarg: null,
      sccur: null,
      avail: '1',
      inProgress: 0,
      endDate: null,
      maxallow: null,
      upkeep: { kad: 10, nano: 5, energy: 5, food: 4, prod: 3, work: 0 },
    },
    {
      idName: 'CRUISER',
      idUnit: '16',
      type: null,
      sctarg: null,
      sccur: null,
      avail: '1',
      inProgress: 0,
      endDate: null,
      maxallow: null,
      upkeep: { kad: 15, nano: 8, energy: 10, food: 2, prod: 6, work: 0 },
    },
    {
      idName: 'DESTROYER',
      idUnit: '17',
      type: null,
      sctarg: null,
      sccur: null,
      avail: '0',
      inProgress: 0,
      endDate: null,
      maxallow: null,
      upkeep: { kad: 20, nano: 12, energy: 14, food: 3, prod: 9, work: 0 },
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
      // Цена постройки (не путать с upkeep живой марки) — game.getLocalNames(unitname).K/N/S/F/C, см. trainUnitData.ts:unitBuildPriceFromLocal().
      attrs: { K: '10', N: '5', S: '2', F: '8', C: '2' },
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
      attrs: { K: '50', N: '30', S: '20', F: '15', P: '10' },
    },
  ],
  [
    'GUARD',
    {
      id: 'GUARD',
      ln: 'Стражник',
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
  [
    'SNIPER',
    { id: 'SNIPER', ln: 'Снайпер', level: '2', race: '1', attack: '65%', defence: '15%', damageMin: '25', damageMax: '35', hitpoints: '20', description: '', attrs: {} },
  ],
  [
    'KNIGHT',
    { id: 'KNIGHT', ln: 'Рыцарь', level: '4', race: '1', attack: '70%', defence: '65%', damageMin: '50', damageMax: '70', hitpoints: '300', description: '', attrs: {} },
  ],
  [
    'GLADIATOR',
    { id: 'GLADIATOR', ln: 'Гладиатор', level: '5', race: '1', attack: '85%', defence: '55%', damageMin: '70', damageMax: '100', hitpoints: '350', description: '', attrs: {} },
  ],
  [
    'GRENADIER',
    { id: 'GRENADIER', ln: 'Гренадёр', level: '3', race: '1', attack: '60%', defence: '35%', damageMin: '35', damageMax: '55', hitpoints: '90', description: '', attrs: {} },
  ],
  [
    'VETERAN',
    { id: 'VETERAN', ln: 'Ветеран', level: '6', race: '1', attack: '90%', defence: '75%', damageMin: '90', damageMax: '130', hitpoints: '400', description: '', attrs: {} },
  ],
  [
    'CRUISER',
    { id: 'CRUISER', ln: 'Крейсер', level: '7', race: '1', attack: '95%', defence: '80%', damageMin: '150', damageMax: '220', hitpoints: '900', description: '', attrs: {} },
  ],
  [
    'DESTROYER',
    { id: 'DESTROYER', ln: 'Эсминец', level: '8', race: '1', attack: '99%', defence: '85%', damageMin: '220', damageMax: '320', hitpoints: '1400', description: '', attrs: {} },
  ],
]);

// Скромный запас ресурсов игрока — намеренно небольшой (не 2×10⁹), чтобы
// computeAllowMax() реально ограничивал степпер количества у TrainUnitWindow
// (?screen=train-unit), а не всегда упирался в глобальный дефолт (см.
// trainUnitData.ts GLOBAL_MAX_NUMBER).
const TEST_PLAYER_RESOURCE_POOL: PlayerResourcePool = {
  kaderium: 500,
  nanoCrystal: 300,
  stasisEnergy: 1000,
  food: 1000,
  prodium: 1000,
  worker: 1000,
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
    { type: '1', id: 'Z1', count: 5, inAction: 2, already: 15, awail: '1', price: { K: 100, N: 20, C: 60, WR: 2.2 }, delta: { K: 1.05, N: 1.05, C: 1.05, WR: 1.2 } },
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
      // Текст — дословно с реального Flash-клиента (скриншот всплывашки
      // "ГОРОД", 2026-09-10), проверка ds-building-icon[title].
      description:
        'Здесь живут рабочие. Каждый город вмещает 4000 человек. Города могут строить только Лиенсу. Данное здание предназначено для размещения рабочих на соте. Рабочие необходимы для строительства зданий, тренировки: ученых, археологов и военных юнитов. Чем больше число рабочих тем проще и быстрее вы сможете развиваться и тем быстрее они восстанавливаются. Восстановлением рабочих происходит по формуле X/24 , где X - максимальное кол-во рабочих на соте. Рекомендованное количество рабочих для развитой соты Лиенсу составляет приблизительно 30000. Для увеличения числа рабочих стройте больше домов.',
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
// TURN_TIME=130мин условного мира ("turn" живого <getbuildmenu> — уже в
// секундах, см. buildMenuData.ts::buildTimeSeconds) — при C=2 (CITY) даёт
// "4ч 20м", буквально совпадая со скриншотом реального Flash-клиента,
// использованным для сверки этого окна (2026-09-10).
const TEST_BUILD_MENU_TURN = '7800';
const TEST_BUILD_MENU_RACE: Race = 'bio';

// Синтетические данные для проверки BuildInfoWindow (?screen=build-info).
const TEST_BUILD_INFO: BuildInfoData = {
  info: { destr: '1', deact: '1', upgrade: '1' },
  buildingId: 2,
  endDate: null,
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
  production: { K: '0', N: '0', S: '0', F: '0', P: '0' },
  upkeep: { K: '0', N: '0', S: '0', F: '0', P: '0' },
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
const TEST_ART_DEPOT_FILTER: ArtDepotFilter = {
  ordersType: [
    { id: 'ALL', ln: 'Все типы', cur: true },
    { id: 'ARTEFACT', ln: 'Артефакты', cur: false },
  ],
  ordersScope: [
    { id: 'ALL', ln: 'Любая область', cur: true },
    { id: 'TECH', ln: 'Технические', cur: false },
    { id: 'BONUS', ln: 'Бонусные', cur: false },
  ],
  ordersSize: [
    { id: 'ALL', ln: 'Любой размер', cur: true },
    { id: 'SMALL', ln: 'Малый', cur: false },
    { id: 'GIANT', ln: 'Гигантский', cur: false },
  ],
};

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
// Общий тестовый каталог одного артефакта — проверка живой локализации
// категории 3 (артефакты) в аукционе/продаже/передаче (2026-09-18,
// artefactDisplayName()/transferArtefactDisplayName(), resourceIcons.ts).
const TEST_ARTEFACT_ID = 'MIGHTY_SPHERE_STASIS_ENERGY_EGG';
const TEST_ARTEFACT_LOADDATA_XML = `<load v="1"><artefact id="${TEST_ARTEFACT_ID}" ln="Могучая сфера энергии" bn="STASIS_ENERGY" bv="#800"/></load>`;

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
    idCatOn: '1',
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
    idCatOn: '2',
    buyer: null,
    serverDisabled: false,
    nextPrice: 5200,
    maxPrice: null,
  },
  {
    id: '3',
    endDate: '2026-09-06 18:00',
    amount: 1,
    getPrice: { K: '2000' },
    currentPrice: 2000,
    currentPriceOnce: null,
    seller: 'Игрок3',
    tradeBy: 'KADERIUM',
    tradeScope: null,
    immediate: '1',
    libname: TEST_ARTEFACT_ID,
    idCatOn: '3',
    buyer: null,
    serverDisabled: false,
    nextPrice: 2000,
    maxPrice: null,
  },
];
const TEST_PLAYER_RESOURCE_BY_NAME = new Map([['KADERIUM', 300]]);

// Дерево фильтров аукциона (2026-09-18, см. докстринг auctionData.ts) —
// категория RESOURCES приходит готовой с сервера (все 5 основных
// ресурсов, не 2 — владелец справедливо указал, что урезанный тестовый
// набор не показывает реальную картину), UNITS строится из
// localData.units (race/level, набор из нескольких рас/уровней —
// проверка, что дерево держит форму на количестве, близком к реальному
// каталогу юнитов), ARTEFACTS — из тестовых `<artscope>/<artsize>/
// <artbonus>` в loaddata-сиде ниже (формат найден и портирован 2026-09-18).
const TEST_AUCTION_DATA: AuctionData = {
  curPage: '1',
  maxPages: '3',
  onPage: '20',
  order: null,
  trader: '',
  lots: TEST_AUCTION_LOTS,
  sortPlace: [
    { id: 'SCOPE_COMB', ln: 'Эта сота', cur: true },
    { id: 'SCOPE_COMBCOMPLEX', ln: 'Весь комплекс', cur: false },
  ],
  sortBuy: [
    { id: 'KADERIUM', ln: 'Кадериум', cur: true },
    { id: 'NANO_CRYSTAL', ln: 'Нано-кристаллы', cur: true },
    { id: 'STASIS_ENERGY', ln: 'Энергия', cur: true },
    { id: 'FOOD', ln: 'Еда', cur: true },
    { id: 'PRODIUM', ln: 'Продиум', cur: true },
    { id: 'SILVER_CREDIT', ln: 'Серебро', cur: false },
  ],
  categories: [
    {
      id: 'RESOURCES',
      ln: 'Ресурсы',
      cur: true,
      items: [
        { id: 'KADERIUM', ln: 'Кадериум', cur: true },
        { id: 'NANO_CRYSTAL', ln: 'Нано-кристаллы', cur: true },
        { id: 'STASIS_ENERGY', ln: 'Энергия', cur: true },
        { id: 'FOOD', ln: 'Еда', cur: true },
        { id: 'PRODIUM', ln: 'Продиум', cur: true },
      ],
    },
    { id: 'UNITS', ln: 'Юниты', cur: false, items: [] },
    { id: 'ARTEFACTS', ln: 'Артефакты', cur: false, items: [] },
  ],
  generalFilters: [
    { id: 'IMMEDIATE_ONLY', name: 'Только мгновенная покупка', stat: true },
    { id: 'MY_LOTS', name: 'Мои лоты', stat: true },
  ],
  sortFieldOptions: [
    { id: 'X', ln: null },
    { id: 'PRICE', ln: 'Цена' },
    { id: 'END_DATE', ln: 'Дата конца' },
  ],
  activeSort: [{ id: 'PRICE', ln: 'Цена' }],
};

// Реалистичный набор юнитов для проверки дерева "что купить" (категория
// UNITS) на количестве, близком к реальному каталогу — 3 расы × 2-3
// уровня, не 2 юнита, как раньше.
const TEST_AUCTION_UNITS_XML = [
  { id: 'SOLDIER', ln: 'Солдат', race: '1', level: '1' },
  { id: 'GRENADIER', ln: 'Гренадёр', race: '1', level: '2' },
  { id: 'TANK', ln: 'Танк', race: '1', level: '2' },
  { id: 'HEAVY_TANK', ln: 'Тяжёлый танк', race: '1', level: '3' },
  { id: 'SCOUT_2', ln: 'Разведчик', race: '2', level: '1' },
  { id: 'STRIKER_2', ln: 'Штурмовик', race: '2', level: '2' },
  { id: 'DESTROYER_2', ln: 'Разрушитель', race: '2', level: '3' },
  { id: 'WARRIOR_3', ln: 'Воин', race: '3', level: '1' },
  { id: 'BERSERK_3', ln: 'Берсерк', race: '3', level: '2' },
]
  .map((u) => `<unit id="${u.id}" ln="${u.ln}" level="${u.level}" race="${u.race}"/>`)
  .join('');

// Тестовый artscope/artsize/artbonus (формат — см. localData.ts,
// найден и портирован 2026-09-18: "name|id|sizeIds(,)|bonusIds(,);...").
const TEST_AUCTION_ARTEFACT_FILTERS_XML =
  '<artscope>Сфера|SPHERE|SIZE_S,SIZE_M|STASIS_ENERGY,PRODUCTION_SPEED;Здание|BUILDING|SIZE_S|FOOD</artscope>' +
  '<artsize>Малый|SIZE_S;Средний|SIZE_M</artsize>' +
  '<artbonus>Энергия|STASIS_ENERGY;Скорость производства|PRODUCTION_SPEED;Еда|FOOD</artbonus>';

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
      { category: 1, idCat: '1_2', id: '2', amount: 1000, upkeep: { K: 0.1, N: 0, P: 0, F: 0.05, S: 0 }, power: {}, libname: 'NANO_CRYSTAL', ln: null, isArtefact: false },
    ],
    2: [
      { category: 2, idCat: '2_9', id: '9', amount: 20, upkeep: { K: 0, N: 0, P: 0, F: 0, S: 0 }, power: {}, libname: 'TANK', ln: null, isArtefact: false },
    ],
    3: [
      {
        category: 3,
        idCat: '3_1',
        id: '1',
        amount: 3,
        upkeep: { K: 0, N: 0, P: 0, F: 0, S: 0 },
        power: {},
        libname: null,
        ln: null,
        isArtefact: true,
        artId: TEST_ARTEFACT_ID,
        datum: '',
      },
    ],
    // Калькулятор (4-я вкладка, только премиум) — расовые списки юнитов,
    // см. докстринг tradeData.ts (5=Воранеры, 6=Лиенсу, 7=Псолао).
    // upkeep/power у этих категорий в оригинале пустые — расчёт читает
    // содержание юнита из loaddata (см. TEST_TRADE_CALC_UNITS_XML ниже).
    5: [{ category: 5, idCat: '5_100', id: '100', amount: 0, upkeep: { K: 0, N: 0, P: 0, F: 0, S: 0 }, power: {}, libname: 'CALC_SOLDIER', ln: null, isArtefact: false }],
    6: [{ category: 6, idCat: '6_101', id: '101', amount: 0, upkeep: { K: 0, N: 0, P: 0, F: 0, S: 0 }, power: {}, libname: 'CALC_WORKER', ln: null, isArtefact: false }],
    7: [{ category: 7, idCat: '7_102', id: '102', amount: 0, upkeep: { K: 0, N: 0, P: 0, F: 0, S: 0 }, power: {}, libname: 'CALC_SCOUT', ln: null, isArtefact: false }],
  },
  scopes: [
    { id: '1', ln: 'Комплекс' },
    { id: '2', ln: 'Кольцо' },
  ],
};

const TEST_TRADE_CALC_UNITS_XML =
  '<unit id="CALC_SOLDIER" ln="Солдат" level="1" race="1" K="2" N="1" S="0.5" F="3" P="1.5"/>' +
  '<unit id="CALC_WORKER" ln="Рабочий" level="1" race="2" K="1" N="0.5" S="0.2" F="2" P="0.5"/>' +
  '<unit id="CALC_SCOUT" ln="Разведчик" level="1" race="3" K="3" N="2" S="1" F="1" P="2"/>';

// Синтетические данные для проверки TransferWindow (?screen=transfer).
const TEST_TRANSFER: TransferMenuData = {
  articlesByCategory: {
    1: [{ category: 1, id: '2', libname: 'NANO_CRYSTAL', amount: '500' }],
    2: [{ category: 2, id: '9', libname: 'TANK', amount: '10' }],
    3: [
      {
        category: 3,
        id: '1',
        libname: TEST_ARTEFACT_ID,
        amount: '1',
        bonusValue: '+800',
        bonusName: 'STASIS_ENERGY',
      },
    ],
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
// Название/тег — реальный альянс с мира orion (ds_alliance, сверено с
// живой БД 2026-09-09), не выдуманное "Чужой альянс [FRN]" (замечание
// владельца — placeholder звучал как бессмыслица).
const TEST_ALLIANCE_JOIN: AllianceManagementData = {
  inAlliance: false,
  alliancesToJoin: [
    { id: '2589', name: 'ORION', tag: 'ORION', leader: 'SomeLeader' },
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

// Синтетические данные для проверки AllianceSettingsWindow (?screen=alliance-settings).
const TEST_ALLIANCE_SETTINGS: AllianceSettingData = {
  leader: '1',
  members: [
    { id: '1', ln: 'Игрок1' },
    { id: '2', ln: 'Игрок2' },
    { id: '3', ln: 'Игрок3' },
  ],
};

// Синтетические данные для проверки ArmyCalculatorWindow (?screen=army-calculator).
const TEST_ARMY_CALCULATOR: ArmyCalculatorData = {
  koeff: [1, 1.5, 2, 1, 1.2, 1],
  units: [
    { id: '3', libname: 'SOLDIER', amount: 2_000_000_000, prodiumWeight: 1, power: { kaderium: 10, nano: 5, food: 2 } },
    { id: '9', libname: 'TANK', amount: 500, prodiumWeight: 4, power: { kaderium: 40, nano: 20, stasis: 10, prodium: 5 } },
  ],
};

// Синтетические данные для проверки AlliancePeaceWindow (?screen=alliance-peace).
const TEST_ALLIANCE_PEACE_LIST: AlliancePeaceListData = {
  alliName: 'ORION',
  viewData: '1',
  allowAction: '1',
  blinkDismiss: false,
  maxPeaceLength: '30',
  allow: [
    {
      name: 'Соседний альянс', short: 'NBR', leader: 'Leader1', id: '101', peaceDuration: null, peaceEndDate: null, you: false,
      myPrice: { k: null, n: null, f: null, p: null, s: null, e: null }, hisPrice: { k: null, n: null, f: null, p: null, s: null, e: null },
    },
  ],
  ready: [
    {
      name: 'Союзный альянс', short: 'ALY', leader: 'Leader2', id: '102', peaceDuration: '30', peaceEndDate: '2026-10-09', you: true,
      myPrice: { k: null, n: null, f: null, p: null, s: null, e: null }, hisPrice: { k: null, n: null, f: null, p: null, s: null, e: null },
    },
  ],
  offer: [],
  wait: [
    {
      name: 'Просящий мира', short: 'REQ', leader: 'Leader3', id: '103', peaceDuration: null, peaceEndDate: null, you: false,
      myPrice: { k: null, n: null, f: null, p: null, s: null, e: null }, hisPrice: { k: null, n: null, f: null, p: null, s: null, e: null },
    },
  ],
  dismiss: [],
};

// Синтетические данные для проверки AlliancePeaceDetailWindow (?screen=alliance-peace-detail).
const TEST_ALLIANCE_PEACE_DETAIL: AlliancePeaceDetailData = {
  id: '101',
  edit: '1',
  buttons: { allow: '1', dismiss: null, offer: null, confirm: null, confirmDismiss: null },
  youSide: { name: 'Наш альянс', k: '500', n: '200', f: '0', e: null, p: '100', s: '50' },
  opponentSide: { name: 'Соседний альянс', k: '300', n: '150', f: '0', e: null, p: '80', s: '20' },
  desc: 'Взаимные набеги последнюю неделю привели к предложению перемирия.',
};

// Синтетические данные для проверки BattleOverviewWindow (?screen=battle-overview).
const TEST_BATTLE_OVERVIEW: BattleOverview = {
  battles: [
    {
      idBattle: 501, startBattle: '2026-09-05 12:00', endBattle: '2026-09-05 12:10', typeBattle: 'A', winnerBattle: 'Мы',
      ra: '0', combBattleName: 'Наша сота', combBattleComplex: '2.1.2', opponentName: 'Враг1', opponentComplexName: 'Их сота',
      opponentComplexNum: '3.1.1', opponentRace: '1', youRanking: '12', battleRoundNum: 3,
    },
    {
      idBattle: 502, startBattle: '2026-09-09 08:00', endBattle: '', typeBattle: 'D', winnerBattle: '', ra: '1',
      combBattleName: 'Наша сота', combBattleComplex: '2.1.2', opponentName: 'Враг2', opponentComplexName: 'Их сота 2',
      opponentComplexNum: '3.1.5', opponentRace: '1', youRanking: null, battleRoundNum: 1,
    },
  ],
  filters: {
    status: 'ALL', winner: 'ALL', opponents: ['Враг1', 'Враг2'], sotes: ['2.1.2'],
    allowdays: [{ ln: '1 SEPTEMBER' }, { ln: '9 SEPTEMBER' }], startday: '1', endday: '9', reward: '1', ra: '1',
  },
};

// Синтетические данные для проверки BattleWindow (?screen=battle-window) — переиспользует TEST_BATTLE_ROUND (уже проверенный ?screen=battle).
const TEST_BATTLE_INFO: BattleInfo = {
  idBattle: '501',
  nicks: ['Враг1'],
  rounds: [
    TEST_BATTLE_ROUND,
    { sideA: [{ libname: 'TANK', amount: 9, army: 'Атакующий флот' }], sideD: [{ libname: 'GUARD', amount: 2, army: 'Гарнизон' }] },
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
// Названия/номера колец — реальные (RING_TECH/RING_BIO/RING_PSI/RING_COLONIZE
// из ds_ring, сверено с живой БД 2026-09-09) — это собственные имена
// локаций, не описательные фразы вроде "Кольцо технологий" (первая версия
// была выдуманным плейсхолдером, ошибка).
const TEST_COLONIZATION: ColonizationData = {
  action: '1',
  idModule: '1',
  rings: [
    { id: '1', ln: 'Ворания' },
    { id: '2', ln: 'Лиенсорд' },
    { id: '3', ln: 'Псолеон' },
    { id: '4', ln: 'Мелсион' },
  ],
  textStatus: '',
};

// Данные для проверки ComplexViewWindow (?screen=complex) — не выдуманные:
// реальный ответ живого <complexview direction="HOME"/> на premium
// 2026-09-16 (см. CLIENT_PORTING_PLAN.md), совпадает со скриншотом
// оригинала, которым владелец сверял этот экран.
const TEST_COMPLEX_VIEW: ComplexViewData = {
  complexNumber: 90,
  ringNumber: 1,
  ringName: 'Ворания',
  gateOpen: true,
  combs: [
    {
      num: 1,
      cscore: 13210,
      home: true,
      league: 'I',
      combName: 'Enzo_test',
      captured: false,
      userName: 'Enzo',
      avatar: '',
      race: '1',
      newbee: false,
      gateOpen: true,
      banned: false,
      godModeMs: null,
      premium: true,
      frozen: false,
      npc: false,
      allianceTag: '[ENZOTEST]',
      allianceIconUrl: null,
      sameAlliance: true,
    },
    { num: 2, cscore: 0, home: false, league: '', combName: null, captured: false, userName: null, avatar: null, race: null, newbee: false, gateOpen: false, banned: false, godModeMs: null, premium: false, frozen: false, npc: false, allianceTag: null, allianceIconUrl: null, sameAlliance: false },
    { num: 3, cscore: 0, home: false, league: '', combName: null, captured: false, userName: null, avatar: null, race: null, newbee: false, gateOpen: false, banned: false, godModeMs: null, premium: false, frozen: false, npc: false, allianceTag: null, allianceIconUrl: null, sameAlliance: false },
    { num: 4, cscore: 0, home: false, league: '', combName: null, captured: false, userName: null, avatar: null, race: null, newbee: false, gateOpen: false, banned: false, godModeMs: null, premium: false, frozen: false, npc: false, allianceTag: null, allianceIconUrl: null, sameAlliance: false },
    { num: 5, cscore: 0, home: false, league: '', combName: null, captured: false, userName: null, avatar: null, race: null, newbee: false, gateOpen: false, banned: false, godModeMs: null, premium: false, frozen: false, npc: false, allianceTag: null, allianceIconUrl: null, sameAlliance: false },
    {
      num: 6,
      cscore: 54220,
      home: false,
      league: 'I',
      combName: 'Franklinss',
      captured: false,
      userName: 'Franklinss',
      avatar: '',
      race: '1',
      newbee: false,
      gateOpen: true,
      banned: false,
      godModeMs: null,
      premium: false,
      frozen: false,
      npc: false,
      allianceTag: null,
      allianceIconUrl: null,
      sameAlliance: false,
    },
  ],
  rings: [
    { id: 'RING_TECH', ln: 'Ворания' },
    { id: 'RING_BIO', ln: 'Лиенсорд' },
    { id: 'RING_PSI', ln: 'Псолеон' },
    { id: 'RING_COLONIZE', ln: 'Мелсион' },
  ],
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

// Синтетические данные для проверки CabinetStatisticPanel (?screen=cabinet-statistic).
const TEST_CABINET_STATISTIC: OperationStatisticData = {
  filters: [{ arrItems: [{ id: 'gold', ln: 'Золото' }, { id: 'silver', ln: 'Серебро' }] }],
  operations: [
    { date: '10.09.26', text: 'Покупка золота', concurency: '+500 GC' },
    { date: '08.09.26', text: 'Перевод в мир Orion', concurency: '-200 GC' },
    { date: '01.09.26', text: 'Обмен золота на серебро', concurency: '-50 GC / +500 SC' },
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
      id: '1', name: 'Артефакт атаки', exp: '30', icon: 'SUPREME_WAR_PRECISION', priceSilver: 500, priceGold: 50,
      bonusText: 'Атака +15%', life: '2592000', filterData: [{ id: 'filter1', value: '10' }, { id: 'filter2', value: '20' }],
    },
    {
      id: '2', name: 'Артефакт защиты', exp: '30', icon: 'SMALL_SPHERE_PROTECTION', priceSilver: 300, priceGold: 30,
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

// Синтетические данные для проверки BuildArtefactWindow (?screen=build-artefact).
const TEST_BUILD_ARTEFACT: BuildArtefactData = {
  countSlots: 3,
  attached: [
    {
      id: '1',
      libname: 'MIGHTY_SPHERE_STASIS_ENERGY_EGG',
      prefix: 'MIGHTY',
      scope: 'SPHERE',
      postfix: 'STASIS_ENERGY_EGG',
      bonuses: [{ bonusType: 'STASIS_ENERGY', bonusValue: '+800' }],
      expired: '',
    },
  ],
  available: [
    {
      id: '2',
      libname: 'SMALL_SPHERE_PROTECTION',
      prefix: 'SMALL',
      scope: 'SPHERE',
      postfix: 'PROTECTION',
      bonuses: [{ bonusType: 'KADERIUM', bonusValue: '+5' }],
      expired: '',
    },
    {
      id: '3',
      libname: 'SUPREME_WAR_PRECISION',
      prefix: 'SUPREME',
      scope: 'WAR',
      postfix: 'PRECISION',
      bonuses: [{ bonusType: 'PRODIUM', bonusValue: '+30%' }],
      expired: 'скоро истекает',
    },
  ],
};

// Порядок слов в `ln` — "{размер} {родовое слово по scope} {эффект в
// родительном падеже}" (сверено с реальным именем с живого аккаунта,
// "Могучая Сфера Энервитриума", и со скриншотом владельца реального
// Flash-клиента — на нём для одного из НЕ-SPHERE scope родовое слово
// "локальный артефакт", например "Гигантский локальный артефакт
// поливки"). Родовое слово зависит от КОНКРЕТНОГО scope (SPHERE ->
// "Сфера", WAR -> "военный артефакт" и т.д.) — не единое "локальный
// артефакт" на все НЕ-SPHERE случаи разом, это была следующая ошибка в
// этих тестовых строках, тоже поправленная по замечанию владельца.
// Первая версия была написана рукой ещё и в обратном порядке
// ("{размер} {эффект} {сферы}") — не особенность игры, просто ошибка.
const TEST_LOCAL_ARTEFACTS = new Map<string, LocalArtefact>([
  [
    'MIGHTY_SPHERE_STASIS_ENERGY_EGG',
    { id: 'MIGHTY_SPHERE_STASIS_ENERGY_EGG', idn: '1', ln: 'Могучая Сфера Энервитриума', description: '', bonusName: 'STASIS_ENERGY', bonusValue: '+800' },
  ],
  [
    'SMALL_SPHERE_PROTECTION',
    { id: 'SMALL_SPHERE_PROTECTION', idn: '2', ln: 'Малая Сфера Защиты', description: '', bonusName: 'KADERIUM', bonusValue: '+5' },
  ],
  [
    'SUPREME_WAR_PRECISION',
    { id: 'SUPREME_WAR_PRECISION', idn: '3', ln: 'Колоссальный военный артефакт точности', description: '', bonusName: 'PRODIUM', bonusValue: '+30%' },
  ],
  // Запасной вариант (каталога локализации нет/артефакта в нём нет — сырой
  // libname без иконки) больше не держим отдельным элементом основного
  // демо-вида — он визуально выглядел как баг. Логика fallback'а
  // (local?.ln ?? art.libname) достаточно простая, чтобы не требовать
  // постоянного примера в общих тестовых данных.
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

const params = new URLSearchParams(location.search);
const wantsLive = params.has('live');
const screen = params.get('screen'); // "battle"/"army" — тестовые экраны вместо карты
const login = import.meta.env.VITE_TEST_LOGIN as string | undefined;
const pass = import.meta.env.VITE_TEST_PASS as string | undefined;
const uiid = import.meta.env.VITE_TEST_UIID as string | undefined;

// Главный экран (docs/main_screen.md §4) — карта на весь вьюпорт, без
// прокрутки страницы. Для изолированных `?screen=`-тестов (окна поверх
// произвольного фона) размер #app не важен — оставлен прежним
// компактным, чтобы не менять поведение уже устоявшихся скриншот-тестов.
if (wantsLive && !screen) {
  app.style.width = '100vw';
  app.style.height = '100vh';
} else {
  app.style.width = '900px';
  app.style.height = '400px';
}

async function main() {
  const uiRoot = document.querySelector<HTMLDivElement>('#ui-root')!;
  render(
    <>
      <WindowHost />
      {wantsLive && !screen && <MainScreenHud />}
    </>,
    uiRoot,
  );

  if (screen === 'army' || screen === 'army-nopremium') {
    openWindow(
      <ArmyOverviewWindow
        overview={TEST_ARMY_OVERVIEW}
        isPremium={screen === 'army'}
        tabs={TEST_TABS}
        onTabClick={(tabId) => console.log('[army] tab click ->', tabId)}
        onClose={() => console.log('[army] close')}
        onDismissAll={() => console.log('[army] dismissAll()')}
        onArmyAction={(command) => console.log('[army] action ->', command.tag, command.attrs)}
        onTransferUnit={(idUnit, count, idArmyFrom, idArmyTo) =>
          console.log('[army] transferUnit ->', idUnit, count, idArmyFrom, idArmyTo)
        }
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
        playerResources={TEST_PLAYER_RESOURCE_POOL}
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

  if (screen === 'complex') {
    openWindow(
      <ComplexViewWindow
        data={TEST_COMPLEX_VIEW}
        onClose={() => console.log('[complex] close')}
        onNavigate={(cmd) => console.log('[complex] navigate(', cmd, ')')}
        onWriteMail={(nick) => console.log('[complex] writeMail(', nick, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'economical') {
    // Реальный ответ живого <getbuildmenu c="124" tab="EconomicalOverview"/>
    // на premium (здание SHELTER), 2026-09-16 — не выдуманные данные,
    // см. CLIENT_PORTING_PLAN.md.
    const REAL_ECONOMICAL_XML = `<?xml version="1.0" encoding="utf-8"?>
<table><tr><td width="38" rcol="0xBBBBBB" fcol="0x222222" alpha="80"> - </td><img width="12" rcol="0xBBBBBB" fcol="0x222222" alpha="80">KADERIUM</img><img width="12" rcol="0xBBBBBB" fcol="0x222222" alpha="80">NANO_CRYSTAL</img><img width="12" rcol="0xBBBBBB" fcol="0x222222" alpha="80">STASIS_ENERGY</img><img width="12" rcol="0xBBBBBB" fcol="0x222222" alpha="80">FOOD</img><img width="12" rcol="0xBBBBBB" fcol="0x222222" alpha="80">PRODIUM</img></tr><tr><td width="38" rcol="0xBBBBBB" fcol="0x222222" alpha="80"><FONT COLOR="#FFFFFF" FACE="Arial"><b>{:VENDORS_COUNT:}</b></FONT></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#FFC77B" FACE="Arial">7</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#6396E7" FACE="Arial">4</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#B5B28C" FACE="Arial">24</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#63B263" FACE="Arial">22</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#9C9E4A" FACE="Arial">1</FONT></b></td></tr><tr><td width="38" rcol="0xBBBBBB" fcol="0x222222" alpha="80"><FONT COLOR="#FFFFFF" FACE="Arial"><b>{:BASE_PRODUCTION:}</b></FONT></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#FFC77B" FACE="Arial">525</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#6396E7" FACE="Arial">175</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#B5B28C" FACE="Arial">12 800</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#63B263" FACE="Arial">1 600</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#9C9E4A" FACE="Arial">250</FONT></b></td></tr><tr><td width="38" rcol="0xBBBBBB" fcol="0x222222" alpha="80"><FONT COLOR="#FFFFFF" FACE="Arial"><b>{:EO_SP:}</b></FONT></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#FFC77B" FACE="Arial">1 034</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#6396E7" FACE="Arial">335</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#B5B28C" FACE="Arial">38 400</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#63B263" FACE="Arial">3 920</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#9C9E4A" FACE="Arial">405</FONT></b></td></tr><tr><td width="38" rcol="0xBBBBBB" fcol="0x222222" alpha="80"><FONT COLOR="#FFFFFF" FACE="Arial"><b>{:EO_SU:}</b></FONT></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#FFC77B" FACE="Arial">0</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#6396E7" FACE="Arial">0</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#B5B28C" FACE="Arial">19 140</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#63B263" FACE="Arial">3 183</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#9C9E4A" FACE="Arial">0</FONT></b></td></tr><tr><td width="38" rcol="0xBBBBBB" fcol="0x222222" alpha="80"><FONT COLOR="#FFFFFF" FACE="Arial"><b>{:EO_B:}</b></FONT></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#FFC77B" FACE="Arial">1 034</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#6396E7" FACE="Arial">335</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#B5B28C" FACE="Arial">19 260</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#63B263" FACE="Arial">737</FONT></b></td><td width="12" rcol="0xBBBBBB" fcol="0x222222" style="center" alpha="80"><b><FONT COLOR="#9C9E4A" FACE="Arial">405</FONT></b></td></tr><tr><td width="38" rcol="0xBBBBBB" fcol="0x222222" alpha="80"><FONT COLOR="#FFFFFF" FACE="Arial"><b>{:BUY_RESOURCES:}</b></FONT></td></tr><tr><td width="38" rcol="0xBBBBBB" fcol="0x222222" alpha="80"><FONT COLOR="#FFFFFF" FACE="Arial"><b>{:BUY_BONUSES:}</b></FONT></td></tr><alltabs><tab id="PN_BuildingInfo" ln="Информация" icon="BUILD_INFO"/><tab id="PN_EconomicalOverview" ln="Обзор экономики" icon="ECONOMIC" cur="1"/><tab id="PN_EconomicalOverviewIncoming" ln="Доходы" icon="INCOME"/><tab id="PN_EconomicalOverviewOutcoming" ln="Расходы" icon="OUTCOME"/></alltabs></table>`;
    const doc = new DOMParser().parseFromString(REAL_ECONOMICAL_XML, 'application/xml');
    // Реальные переводы строк-меток (`{:KEY:}`) этой панели, живьём
    // подтверждённые в этой же сессии (см. CLIENT_PORTING_PLAN.md) — без
    // `loaddata` резолвер не может раскрыть `{:EO_SP:}` и т.п. в читаемый
    // текст, что и было замечено на скриншоте синтетического теста.
    const REAL_LOADDATA_XML = `<load v="118"><interface id="VENDORS_COUNT" ln="Производящих зданий"/><interface id="BASE_PRODUCTION" ln="Базовая продукция"/><interface id="EO_SP" ln="Продукция с бонусами"/><interface id="EO_SU" ln="Суммарное потребление"/><interface id="EO_B" ln="Баланс"/></load>`;
    setLocalData(parseLocalData(REAL_LOADDATA_XML));
    openWindow(
      <EconomicalOverviewWindow
        data={parseEconomicalOverview(REAL_ECONOMICAL_XML)}
        tabs={parseTabsArray(doc.documentElement)}
        onTabClick={(tabId) => console.log('[economical] tab click', tabId)}
        onClose={() => console.log('[economical] close')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'outside') {
    // Точный шаблон из UA_Attack.class.php:198-200 (реальный, не
    // выдуманный), с правдоподобными значениями — проверка разбора
    // марки outside (см. CLIENT_PORTING_PLAN.md, пункт 4 аудита).
    const REAL_OUTSIDE_XML = `<?xml version="1.0" encoding="utf-8"?>
<swfloader override="1" url="flash/dialog_alert.swf" title="Внимание!" res="ALL_RES" speed="100" destination="4.844.3" id_army="1234395"><txt><![CDATA[Вы уверены, что хотите атаковать игрока старшей лиги? Ваша лига будет повышена до лиги III.]]></txt></swloader>`;
    const alert = parseOutsideAlert(REAL_OUTSIDE_XML);
    console.log('[outside] parsed:', alert);
    pushPopup(<InfoPopup message={`${alert.title}\n\n${alert.text}`} onClose={popPopup} />);
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

  if (screen === 'alliance-settings') {
    openWindow(
      <AllianceSettingsWindow
        data={TEST_ALLIANCE_SETTINGS}
        onClose={() => console.log('[alliance-settings] close')}
        onChangePassword={(pwd) => console.log('[alliance-settings] changeAlliancePass(', pwd, ')')}
        onChangeLeader={(id) => console.log('[alliance-settings] changeAllianceLeader(', id, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'army-calculator') {
    openWindow(
      <ArmyCalculatorWindow
        data={TEST_ARMY_CALCULATOR}
        localUnits={TEST_LOCAL_UNITS}
        onClose={() => console.log('[army-calculator] close')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'alliance-peace') {
    openWindow(
      <AlliancePeaceWindow
        data={TEST_ALLIANCE_PEACE_LIST}
        onClose={() => console.log('[alliance-peace] close')}
        onOpenDetail={(id) => console.log('[alliance-peace] getInfoAboutPeace(', id, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'alliance-peace-detail') {
    openWindow(
      <AlliancePeaceDetailWindow
        data={TEST_ALLIANCE_PEACE_DETAIL}
        onClose={() => console.log('[alliance-peace-detail] close')}
        onBack={() => console.log('[alliance-peace-detail] back')}
        onOfferPeace={(my, he, time) => console.log('[alliance-peace-detail] getAlliancePeace(setPeace,', my, he, time, ')')}
        onCancelOffer={() => console.log('[alliance-peace-detail] getAlliancePeace(cancelPeace)')}
        onConfirmPeace={() => console.log('[alliance-peace-detail] getAlliancePeace(confirmPeace)')}
        onDeclinePeace={() => console.log('[alliance-peace-detail] getAlliancePeace(declinePeace)')}
        onOfferTerminate={() => console.log('[alliance-peace-detail] getAlliancePeace(terminatePeace)')}
        onConfirmTerminate={() => console.log('[alliance-peace-detail] getAlliancePeace(confirmTerminatePeace)')}
        onDeclineTerminate={() => console.log('[alliance-peace-detail] getAlliancePeace(declineTerminatePeace)')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'battle-overview') {
    openWindow(
      <BattleOverviewWindow
        data={TEST_BATTLE_OVERVIEW}
        onClose={() => console.log('[battle-overview] close')}
        onApplyFilter={(v) => console.log('[battle-overview] ShowBattle(', v, ')')}
        onViewBattle={(id) => console.log('[battle-overview] getShowBattle(', id, ')')}
        onSurrender={(id) => console.log('[battle-overview] SurrenderSampleBattle(', id, ')')}
        onWriteToOpponent={(nick) => console.log('[battle-overview] createnewmail(new, undefined,', nick, ')')}
        onClaimReward={() => console.log('[battle-overview] getRewards()')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'battle-window') {
    openWindow(<BattleWindow data={TEST_BATTLE_INFO} onClose={() => console.log('[battle-window] close')} />);
    // BattleWindow монтирует PixiJS-канвас асинхронно внутри useEffect (уже
    // ПОСЛЕ возврата openWindow()) — ждём появления <canvas>, а затем ещё
    // немного, пока не отрисуется сам раунд (Assets.load + отрисовка спрайтов
    // — отдельный второй useEffect, гонка с появлением самого <canvas>).
    for (let i = 0; i < 100 && !document.querySelector('.ds-battle-window__canvas canvas'); i++) {
      await new Promise((r) => setTimeout(r, 50));
    }
    await new Promise((r) => setTimeout(r, 800));
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'auction') {
    setLocalData(
      parseLocalData(
        `<load v="1"><artefact id="${TEST_ARTEFACT_ID}" ln="Могучая сфера энергии" bn="STASIS_ENERGY" bv="#800"/>` +
          TEST_AUCTION_UNITS_XML +
          // Реальные названия рас (не выдуманные) — docs/mechanics-races-world-ranking.md
          // §1.1: ringnumber 1/2/3 = VORANIA/LIENSU/PSOLAO = Воранеры/Лиенсу/Псолао.
          `<interface id="race_1" ln="Воранеры"/><interface id="race_2" ln="Лиенсу"/><interface id="race_3" ln="Псолао"/>` +
          `<interface id="level_1" ln="1 уровень"/><interface id="level_2" ln="2 уровень"/><interface id="level_3" ln="3 уровень"/>` +
          `<att id="SORT_CHOOSE" ln="выбрать сортировку"/>` +
          TEST_AUCTION_ARTEFACT_FILTERS_XML +
          `</load>`,
      ),
    );
    openWindow(
      <AuctionWindow
        data={TEST_AUCTION_DATA}
        localUnits={localData.value?.units ?? new Map()}
        localArtefactScopes={localData.value?.artefactScopes ?? new Map()}
        localArtefactSizes={localData.value?.artefactSizes ?? new Map()}
        localArtefactBonuses={localData.value?.artefactBonuses ?? new Map()}
        playerResourceByName={TEST_PLAYER_RESOURCE_BY_NAME}
        onClose={() => console.log('[auction] close')}
        onBuy={(lotId, price) => console.log('[auction] buy_lot(', lotId, price, ')')}
        onApplyFilter={(cmd) => console.log('[auction] setfilterauction(', cmd, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'sell' || screen === 'sell-nopremium') {
    setLocalData(
      parseLocalData(
        `<load v="1"><artefact id="${TEST_ARTEFACT_ID}" ln="Могучая сфера энергии" bn="STASIS_ENERGY" bv="#800"/>` +
          TEST_TRADE_CALC_UNITS_XML +
          `</load>`,
      ),
    );
    openWindow(
      <SellWindow
        data={TEST_TRADE_MENU}
        localUnits={localData.value?.units ?? new Map()}
        isPremium={screen === 'sell'}
        playerRace="tech"
        onClose={() => console.log('[sell] close')}
        onSell={(cmd) => console.log('[sell] sell(', cmd, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'transfer') {
    setLocalData(parseLocalData(TEST_ARTEFACT_LOADDATA_XML));
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
        race={TEST_BUILD_MENU_RACE}
        turn={TEST_BUILD_MENU_TURN}
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
    setLocalData(
      parseLocalData(
        '<load v="1"><artefact id="ARTEFACT_TECH_CATALYZATION" ln="Катализатор производства"/><artefact id="ARTEFACT_BONUS_GIFT" ln="Дар изобилия"/><att id="PRODUCTION_SPEED" ln="скорость производства"/></load>',
      ),
    );
    openWindow(
      <ArtDepotWindow
        entries={TEST_ART_DEPOT}
        filter={TEST_ART_DEPOT_FILTER}
        onClose={() => console.log('[art-depot] close')}
        onDeleteSelected={(ids) => console.log('[art-depot] delArtFromDepot(', ids, ')')}
        onActivate={(id) => console.log('[art-depot] artActivate(', id, ')')}
        onSort={(type, size, scope) => console.log('[art-depot] artdepotsort(', type, size, scope, ')')}
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

  if (screen === 'army-create') {
    openWindow(
      <ArmyCreationWindow
        data={TEST_ARMY_CREATION}
        localUnits={TEST_LOCAL_UNITS}
        tabs={TEST_TABS_CREATE}
        onTabClick={(tabId) => console.log('[army-create] tab click ->', tabId)}
        onClose={() => console.log('[army-create] close')}
        onCreate={(name, units) => console.log('[army-create] create_army(', name, units, ')')}
      />,
    );
    (window as any).__renderDone = true;
    return;
  }

  if (screen === 'build-artefact') {
    openWindow(
      <BuildArtefactWindow
        data={TEST_BUILD_ARTEFACT}
        localArtefacts={TEST_LOCAL_ARTEFACTS}
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
        statistic={null}
        onClose={() => console.log('[cabinet] close')}
        onSelectService={(id) => console.log('[cabinet] getSubMenuCabinet(', id, ')')}
        onCloseSubmenu={() => console.log('[cabinet] closeSubMenu')}
        onSubmitSubmenu={(buttonId, values) => console.log('[cabinet] subMenuAction(', buttonId, values, ')')}
        onDismissPopup={() => console.log('[cabinet] dismiss popup')}
        onFilterStatistic={(typeId) => console.log('[cabinet] getAllStatistic(', typeId, ')')}
        onCloseStatistic={() => console.log('[cabinet] close statistic')}
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
        statistic={null}
        onClose={() => console.log('[cabinet] close')}
        onSelectService={(id) => console.log('[cabinet] getSubMenuCabinet(', id, ')')}
        onCloseSubmenu={() => console.log('[cabinet] closeSubMenu')}
        onSubmitSubmenu={(buttonId, values) => console.log('[cabinet] subMenuAction(', buttonId, values, ')')}
        onDismissPopup={() => console.log('[cabinet] dismiss popup')}
        onFilterStatistic={(typeId) => console.log('[cabinet] getAllStatistic(', typeId, ')')}
        onCloseStatistic={() => console.log('[cabinet] close statistic')}
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

  if (screen === 'cabinet-statistic') {
    openWindow(
      <CabinetWindow
        data={TEST_CABINET}
        openSubmenu={null}
        statistic={TEST_CABINET_STATISTIC}
        onClose={() => console.log('[cabinet] close')}
        onSelectService={(id) => console.log('[cabinet] getSubMenuCabinet(', id, ')')}
        onCloseSubmenu={() => console.log('[cabinet] closeSubMenu')}
        onSubmitSubmenu={(buttonId, values) => console.log('[cabinet] subMenuAction(', buttonId, values, ')')}
        onDismissPopup={() => console.log('[cabinet] dismiss popup')}
        onFilterStatistic={(typeId) => console.log('[cabinet] getAllStatistic(', typeId, ')')}
        onCloseStatistic={() => console.log('[cabinet] close statistic')}
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
  // Отладочный доступ для скриптов тестирования (см. scripts/live-map-click.ts) —
  // по образцу __renderDone/__renderError ниже.
  (window as any).__mapRenderer = renderer;

  if (!wantsLive || !login || !pass || !uiid) {
    await renderer.renderGround('G'.repeat(217));
    // 'bio' — тот же дефолт, что в оригинале при неизвестной расе (game.as:2354-2356).
    await renderer.renderBuildings(TEST_BUILDINGS, 'bio');
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
