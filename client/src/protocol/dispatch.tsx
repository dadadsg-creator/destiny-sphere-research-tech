/**
 * Центральный диспетчер ответов сервера — прямой порт `parse_respond`
 * (parser.as:142-231) + `xmldata_action` (parser.as:1413-1621+), см. план
 * «Сшивание HTML5-порта в единое рабочее приложение»
 * (CLIENT_PORTING_PLAN.md, 2026-09-08).
 *
 * Каждый ответ `ds/useraction.php` — плоский набор марок (`ParsedMarks`).
 * Часть марок — служебные с фиксированным смыслом (обрабатываются здесь
 * напрямую), содержимое конкретных окон приходит ОДНИМ общим ключом
 * `xmldata`, чей корневой XML-тег определяет парсер (см. REGISTRY ниже,
 * сверено построчно с живым `xmldata_action`).
 *
 * **Объём этого прохода (см. план)**: реестр разбора («тег → парсер»)
 * заполнен по всем доменам, где парсер уже есть из Фазы 3 — это чисто
 * механическая работа. Полная проводка в реальные окна с командами
 * реализована для вертикальных срезов из "гнезда" `getbuildmenu`-семейства
 * (карта → клик по соте → окно → команда → новый ответ), все — по
 * подтверждённому 2026-09-08 списку 37 классов `PN_*` (все расширяют
 * `UA_GetBuildMenu`, см. CLIENT_PORTING_PLAN.md): `getbuildinfo`,
 * `buildingartefact`, `terraforming`, `religion`, `transferresource`,
 * `trade`, `getbuildmenu`, `archeology`, `reincarnate`, `otpusk`,
 * `allianceprivatepeace`. Марка `combres` (пул ресурсов игрока/соты, см.
 * `data/combResData.ts`) разблокировала три домена (`getbuildmenu`,
 * `reincarnate`, `archeology`); `otpusk`/`allianceprivatepeace` не
 * привязаны к конкретной ячейке в своих действиях (подтверждено
 * `reqvestdata.as` — `<vacationstart/>`/`<getprivatepeace/>` без `c`),
 * в отличие от большинства этой "семьи". Остальные домены пока только
 * логируются в консоль — подключение оставлено отдельным объёмом
 * работы. Премиум-статус (`TerraformWindow`/`ArcheologyWindow`/
 * `ArtefactStatWindow` — `isPremium`) — марка верхнего уровня `mode`
 * (см. `data/premiumData.ts`/`premiumStore.ts`), найдена и подключена
 * 2026-09-16 по итогам полного аудита панелей (CLIENT_PORTING_PLAN.md).
 */
import type { ParsedMarks } from './response';
import { parseXmlFragment } from './response';
import { parseRefresh } from '../map/refreshData';
import { parseBuild } from '../map/buildData';
import type { MapRenderer } from '../map/renderer';
import type { Race } from '../map/buildingTypes';
import { parseLocalData } from '../data/localData';
import { setLocalData, localData } from '../data/localDataStore';
import { parseCombRes } from '../data/combResData';
import { setCombRes, combRes } from '../data/combResStore';
import { parseSysMsg } from '../mail/logData';
import { parsePremiumStatus } from '../data/premiumData';
import { setPremiumStatus, premiumStatus } from '../data/premiumStore';
import { serverClock } from '../data/serverClockStore';
import { parseOutsideAlert } from '../data/outsideAlertData';
import { appendSysMsgBatch } from '../mail/sysMessageStore';
import { AdviceWindow } from '../settings/AdviceWindow';
import { parseAdviceHint, buildGetNubHelpCommand } from '../settings/adviceData';
import { openWindow, closeWindow, pushPopup, popPopup } from '../ui/windowStore';
import { InfoPopup } from '../ui/Popup';
import { selectedBuilding, type SelectedBuildingActions } from '../ui/hud/selectedBuildingStore';
import { parseInfoBuild } from '../buildings/buildInfoData';
import {
  tag,
  sellXml,
  transferResourceXml,
  archeologyTeamXml,
  getPPeaceXml,
  colonizeXml,
  createAllianceXml,
  joinAllianceXml,
  leaveAllianceXml,
  createArmyXml,
  transferUnitXml,
  sendMessageXml,
  sendZondXml,
  buyArtefactInShopXml,
  cabinetRequestXml,
  changeAlliancePasswordXml,
  getAlliancePeaceXml,
  getInfoAboutPeaceXml,
} from './xmlBuilder';
import { parseTabsArray, stripTabPrefix } from './tabsParser';
import { BuildArtefactWindow } from '../artefacts/BuildArtefactWindow';
import { TerraformWindow } from '../world/TerraformWindow';
import { ReligionWindow } from '../world/ReligionWindow';
import { TransferWindow } from '../trade/TransferWindow';
import { SellWindow } from '../trade/SellWindow';
import { BuildMenuWindow } from '../buildings/BuildMenuWindow';
import { combResToPlayerPool } from '../buildings/buildMenuData';
import { ReincarnateWindow } from '../world/ReincarnateWindow';
import { combResToPriceOrder } from '../world/reincarnateData';
import { ArcheologyWindow } from '../world/ArcheologyWindow';
import { combResToKadFoodPool, buildSendExpeditionCommand } from '../world/archeologyData';
import { VacationWindow } from '../vacation/VacationWindow';
import { PrivatePeaceWindow } from '../alliance/PrivatePeaceWindow';
import { PowerShieldWindow } from '../spying/PowerShieldWindow';
import { ProtectShieldWindow } from '../spying/ProtectShieldWindow';
import { ResearchWindow } from '../science/ResearchWindow';
import { ColonizationWindow } from '../world/ColonizationWindow';
import { buildSendColonyModuleCommand } from '../world/colonizationData';
import { ComplexViewWindow } from '../world/ComplexViewWindow';
import { parseComplexView, buildComplexViewCommand } from '../world/complexViewData';
import { ColonyNavigateWindow } from '../world/ColonyNavigateWindow';
import { parseColonyNavigate, buildNavigateCommand, buildLeaveColonyCommand } from '../world/colonyNavigateData';
import { EconomicalOverviewWindow } from '../world/EconomicalOverviewWindow';
import { parseEconomicalOverview, buildActivateAllCommand } from '../world/economicalOverviewData';
import { ArmyOverviewWindow } from '../army/ArmyOverviewWindow';
import { AllianceMemberWindow } from '../alliance/AllianceMemberWindow';
import { AllianceManagementWindow } from '../alliance/AllianceManagementWindow';
import { CreateNewMailWindow } from '../mail/CreateNewMailWindow';
import { MailWindow } from '../mail/MailWindow';
import { mailListState, mailOpenBody, setMailList, setMailOpenBody } from '../mail/mailStore';
import { buildDeleteMessagesCommand, buildReadMessagesCommand } from '../mail/mailData';
import { TrainUnitWindow } from '../army/TrainUnitWindow';
import { ArmyCreationWindow } from '../army/ArmyCreationWindow';
import { buildKickCommand } from '../alliance/allianceMemberData';
import { WarStatisticWindow } from '../army/WarStatisticWindow';
import { QuestWindow } from '../quests/QuestWindow';
import { LogWindow } from '../mail/LogWindow';
import { ArtefactStatWindow } from '../artefacts/ArtefactStatWindow';
import { ArtDepotWindow } from '../buildings/ArtDepotWindow';
import { ArtefactShopWindow } from '../cabinet/ArtefactShopWindow';
import { buildBuyArtefactsCommand } from '../cabinet/artefactShopData';
import { AuctionWindow } from '../trade/AuctionWindow';
import { SpyingWindow } from '../spying/SpyingWindow';
import { ZondCreateWindow } from '../spying/ZondCreateWindow';
import { ScannerWindow } from '../spying/ScannerWindow';
import { CabinetWindow } from '../cabinet/CabinetWindow';
import {
  buildChangeGoldToSilverCommand,
  buildMoneyToWorldCommand,
  buildMoneyFromWorldCommand,
  buildGiftMoneyCommand,
  buildGetAllStatisticCommand,
} from '../cabinet/cabinetMainData';
import { buildOpenSubmenuCommand } from '../cabinet/submenuData';
import { cabinetMain, cabinetSubmenu, setCabinetMain, setCabinetSubmenu, dismissCabinetPopup } from '../cabinet/cabinetStore';
import { AllianceSettingsWindow } from '../alliance/AllianceSettingsWindow';
import { ArmyCalculatorWindow } from '../army/ArmyCalculatorWindow';
import { AlliancePeaceWindow } from '../alliance/AlliancePeaceWindow';
import { AlliancePeaceDetailWindow } from '../alliance/AlliancePeaceDetailWindow';
import { buildSendOfferPeaceCommand, buildSimplePeaceCommand } from '../alliance/alliancePeaceData';
import {
  alliancePeaceList,
  alliancePeaceDetail,
  setAlliancePeaceList,
  setAlliancePeaceDetail,
  clearAlliancePeaceDetail,
} from '../alliance/alliancePeaceStore';
import { BattleOverviewWindow } from '../battle/BattleOverviewWindow';
import { BattleWindow } from '../battle/BattleWindow';

// --- разбор конкретных доменов, известных, но пока не подключённых к живым окнам ---
import { parseAllianceManagement } from '../alliance/allianceData';
import { parseAllianceOverview } from '../alliance/allianceMemberData';
import { parseAlliancePeaceList, parseAlliancePeaceDetail } from '../alliance/alliancePeaceData';
import { parseAllianceSettings } from '../alliance/allianceSettingData';
import { parsePrivatePeace } from '../alliance/privatePeaceData';
import { parseArmyCreation, parseArmyCalculator } from '../army/armyCreationData';
import { parseArmyOverview } from '../army/armyData';
import { parseTrainWarUnit } from '../army/trainUnitData';
import { parseWarComplex } from '../army/warFactoryData';
import { parseArtefactStat } from '../artefacts/artefactStatData';
import { parseBuildArtefact } from '../artefacts/buildArtefactData';
import { parseBattleInfo } from '../battle/battleData';
import { parseBattleOverview } from '../battle/battleOverviewData';
import { parseArtDepot } from '../buildings/artDepotData';
import { parseListBuild } from '../buildings/buildMenuData';
import { parseArtefactShop } from '../cabinet/artefactShopData';
import { parseCabinetResponse } from '../cabinet/cabinetMainData';
import { parseCreateNewMail } from '../mail/createNewMailData';
import { parseLogWindow } from '../mail/logData';
import { parseMailList, parseMailMessage } from '../mail/mailData';
import { parseQuestMenu } from '../quests/questData';
import { parseResearch, combResToNanoFoodPool } from '../science/researchData';
import { parsePowerShield } from '../spying/powerShieldData';
import { parseProtectShield } from '../spying/protectShieldData';
import { combResCurrent } from '../data/combResData';
import { parseSpyLog } from '../spying/scannerData';
import { parseSpying } from '../spying/spyingData';
import { parseZondCreate } from '../spying/zondCreateData';
import { parseAuction } from '../trade/auctionData';
import { parseTradeMenu } from '../trade/tradeData';
import { parseTransferMenu } from '../trade/transferData';
import { parseVacation } from '../vacation/vacationData';
import { parseArcheology } from '../world/archeologyData';
import { parseColonizationCenter } from '../world/colonizationData';
import { parseReincarnate } from '../world/reincarnateData';
import { parseReligion } from '../world/religionData';
import { parseTerraforming } from '../world/terraformData';

export interface DispatchContext {
  renderer: MapRenderer;
  /** Отправить команду и обработать её ответ тем же диспетчером — используется колбэками окон. */
  send: (xml: string) => Promise<void>;
  /**
   * Ячейка, к которой привязано текущее `getbuildmenu`-семейство окон —
   * аналог `debug.link.active_cell` оригинала. Проставляется кодом,
   * инициирующим клик по соте (см. liveApp.ts), и переиспользуется кликами
   * по вкладкам внутри уже открытого окна (см. `sendTabClick` ниже) —
   * сервер не присылает номер ячейки обратно в ответе.
   */
  activeCell: string | undefined;
  /**
   * "Открыты ли ворота" текущей соты — `game.getGateStatus()` оригинала,
   * внешнее состояние, не часть ни одного доменного XML напрямую.
   * Единственный живой источник — атрибут `gate` корня марки `refresh`
   * (`map/refreshData.ts`, `<refresh gate="0|1">`, подтверждено фикстурой
   * `SPHERE_client/refresh.txt`) — обновляется в `handleResponse` при
   * каждом `refresh`, используется `ArcheologyWindow`.
   */
  gateOpen: boolean;
  /**
   * Раса владельца соты — определяет графику построек, не влияет на
   * протокол (`race` не приходит ни в одной XML-марке, только в FlashVars
   * при входе в мир, см. `gameProtocol.ts:enterWorld()`). Проставляется
   * один раз в `liveApp.ts` перед первым `renderBuildings()`.
   */
  race: Race;
}

/**
 * Реестр «корневой тег `xmldata` → парсер» — сверен построчно с
 * `xmldata_action` (parser.as:1413-1621+). Домены без записи здесь —
 * подтверждённые пробелы Фазы 3 (`spylog` пробел закрыт — парсер есть в
 * `scannerData.ts`, изначальная пометка в плане была ошибочной;
 * `colonynavigate` закрыт 2026-09-16, см. `RENDERERS` ниже — по итогам
 * полного аудита панелей, CLIENT_PORTING_PLAN.md).
 */
const PARSERS: Record<string, (xml: string) => unknown> = {};

/**
 * Клик по вкладке внутри уже открытого `getbuildmenu`-семейства окна —
 * общий для всех них (см. `RENDERERS` ниже), переиспользует `ctx.activeCell`
 * (та же сота, что открыла текущее "гнездо" окон). `tab.id` приходит с
 * префиксом класса (`PN_BuildingInfo`) — подтверждено живым тестом
 * 2026-09-08, см. tabsParser.ts.
 */
function sendTabClick(ctx: DispatchContext, tabId: string): void {
  if (ctx.activeCell === undefined) {
    console.warn('[dispatch] клик по вкладке без активной ячейки — окно открыто не через клик по карте?', tabId);
    return;
  }
  void ctx.send(tag('getbuildmenu', { c: ctx.activeCell, tab: stripTabPrefix(tabId) }));
}

/**
 * economicalOverviewData.ts — <table>, гнездо из 3 вкладок
 * (`PN_EconomicalOverview`/`...Incoming`/`...Outcoming`), открывается
 * зданием SHELTER. Реальный live-тест 2026-09-16 (см.
 * CLIENT_PORTING_PLAN.md) подтвердил, что все три панели действительно
 * шлют один и тот же корневой тег `table` — один рендерер на все три,
 * переключение через стандартный механизм вкладок.
 */
function renderEconomicalOverview(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseEconomicalOverview(xml);
  openWindow(
    <EconomicalOverviewWindow
      data={data}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onActivateAll={(resource, activate) => void ctx.send(tag('activateall', buildActivateAllCommand(resource, activate)))}
    />,
  );
}

/** reqvestdata.as — кнопки BuildInfoWindow, все семь — <тег c=""/>, подтверждено ds_useractiontype (2026-09-08). */
function buildInfoActionCommand(actionTag: string, cellNum: string): string {
  return tag(actionTag, { c: cellNum });
}

function renderBuildInfo(_root: Element, xml: string, ctx: DispatchContext): void {
  const cellNum = ctx.activeCell;
  if (cellNum === undefined) return;
  const data = parseInfoBuild(xml);
  const act = (actionTag: string) => () => {
    void ctx.send(buildInfoActionCommand(actionTag, cellNum));
  };
  const actions: SelectedBuildingActions = {
    onCancelBuild: act('cancelbuild'),
    onDemolish: act('delbuild'),
    onCancelUpgrade: act('cancelupgrade'),
    onActivate: act('activatebuild'),
    onCancelActivate: act('cancelactivate'),
    onDeactivate: act('deactivatebuild'),
    onUpgrade: act('upgradebuild'),
  };
  // Панель здания (docs/main_screen.md §9) — докнутая справа панель в
  // главном экране, не модалка (см. ui/hud/BuildingPanel.tsx и
  // селектор selectedBuildingStore.ts). `record` уже был выставлен
  // оптимистично по клику (liveApp.ts) — здесь только дополняем его
  // данными `getbuildinfo`, не перезаписываем целиком, чтобы не
  // потерять `record`, если по какой-то причине он ещё не пришёл.
  selectedBuilding.value = {
    cellNum: Number(cellNum) - 1,
    record: selectedBuilding.value?.record ?? ctx.renderer.getBuildRecord(Number(cellNum) - 1) ?? null,
    info: data,
    actions,
    race: selectedBuilding.value?.race ?? ctx.race,
  };
}

/** buildArtefactData.ts:81 — <attachartefact c="" id=""/> / <detachartefact c="" id=""/>. */
function renderBuildArtefact(root: Element, xml: string, ctx: DispatchContext): void {
  const cellNum = ctx.activeCell;
  if (cellNum === undefined) return;
  const data = parseBuildArtefact(xml);
  openWindow(
    <BuildArtefactWindow
      data={data}
      localArtefacts={localData.value?.artefacts ?? new Map()}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onAttach={(id) => void ctx.send(tag('attachartefact', { c: cellNum, id }))}
      onDetach={(id) => void ctx.send(tag('detachartefact', { c: cellNum, id }))}
    />,
  );
}

/** terraformData.ts:74 — <terraform c="" type=""/> / <cancelterraform c="" type=""/>, теги подтверждены ds_useractiontype. */
function renderTerraform(root: Element, xml: string, ctx: DispatchContext): void {
  const cellNum = ctx.activeCell;
  if (cellNum === undefined) return;
  const options = parseTerraforming(xml);
  openWindow(
    <TerraformWindow
      options={options}
      isPremium={premiumStatus.value?.isPremium ?? false}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onStart={(type) => void ctx.send(tag('terraform', { c: cellNum, type }))}
      onCancel={(type) => void ctx.send(tag('cancelterraform', { c: cellNum, type }))}
    />,
  );
}

/** religionData.ts:93 — <religyconnect religy=""/> / <religycancel religy=""/> (имена тегов НЕ симметричны команде). */
function renderReligion(root: Element, xml: string, ctx: DispatchContext): void {
  const religions = parseReligion(xml);
  openWindow(
    <ReligionWindow
      religions={religions}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onConnect={(id) => void ctx.send(tag('religyconnect', { religy: id }))}
      onLeave={(id) => void ctx.send(tag('religycancel', { religy: id }))}
    />,
  );
}

/**
 * transferData.ts:81 — сериализация через transferResourceXml (имена
 * атрибутов не совпадают с полями команды). `onCalculate` — подтверждено
 * чтением `reqvestdata.as:638-641` (`calc_transfer_to_colony`):
 * `<calctravel mode="TRANSFER"|"TRANSFERRES" id_colony="" id_unit=""
 * amount=""/>`, `mode` зависит от категории (`id_cat===2` →
 * "TRANSFER", иначе "TRANSFERRES") — точное значение категории "2"
 * (юниты армии, а не ресурс) не установлено достоверно из одного
 * только `reqvestdata.as`, взято по буквальному условию оригинала.
 * Ответ (`<calctravel/>`) НИГДЕ не обрабатывается в найденном клиентском
 * коде (ни в `transfer_class.as`, ни в общем `xmldata_action`-свитче
 * `parser.as`) — похоже на нерабочий даже в оригинале раунд-трип
 * (см. тот же класс находок, что и "MAIL_TO всегда задизейблена"), но
 * сам ЗАПРОС теперь отправляется реальным, а не молчаливым placeholder'ом.
 */
function renderTransfer(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseTransferMenu(xml);
  openWindow(
    <TransferWindow
      data={data}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onTransfer={(command) => void ctx.send(transferResourceXml(command))}
      onCalculate={(id, category, amount, targetCombId) =>
        void ctx.send(
          tag('calctravel', {
            mode: category === 2 ? 'TRANSFER' : 'TRANSFERRES',
            id_colony: targetCombId,
            id_unit: id,
            amount,
          }),
        )
      }
    />,
  );
}

/** tradeData.ts:261 — сериализация через sellXml (id_* атрибуты не совпадают с camelCase-полями команды). */
function renderSell(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseTradeMenu(xml);
  openWindow(
    <SellWindow
      data={data}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onSell={(command) => void ctx.send(sellXml(command))}
    />,
  );
}

/** buildMenuData.ts:1-19 — <putbuild c="" b=""/>, тег подтверждён ds_useractiontype (2026-09-08, было "constructbuilding" — неподтверждённая догадка Фазы 3). */
function renderBuildMenu(root: Element, xml: string, ctx: DispatchContext): void {
  const cellNum = ctx.activeCell;
  if (cellNum === undefined) return;
  const entries = parseListBuild(xml);
  const localBuildings = localData.value?.buildings ?? new Map();
  openWindow(
    <BuildMenuWindow
      entries={entries}
      localBuildings={localBuildings}
      resources={combResToPlayerPool(combRes.value)}
      race={ctx.race}
      turn={root.getAttribute('turn')}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onBuild={(id) => void ctx.send(tag('putbuild', { c: cellNum, b: id }))}
    />,
  );
}

/** reincarnateData.ts:177-189 — <getReincarnate for="" lst=""/>, playerResources — из combres (KA,NA,ST,FO,PR,SC). */
function renderReincarnate(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseReincarnate(xml);
  openWindow(
    <ReincarnateWindow
      data={data}
      playerResources={combResToPriceOrder(combRes.value)}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onSend={(command) => void ctx.send(tag('getReincarnate', { for: command.for, lst: command.lst }))}
    />,
  );
}

/**
 * archeologyData.ts:1-27 — <archeologyteam .../> отправка экспедиции
 * (тег подтверждён ds_useractiontype). `onTrain` (обучение археологов) —
 * подтверждено чтением `reqvestdata.as:536-540` (`trainarchelogist`,
 * буквальная опечатка "archelogist" в самом клиенте): реально шлёт ТОТ
 * ЖЕ генерический `<trainunit c="" count=""/>`, что и набор учёных в
 * ResearchWindow — не отдельный тег под археологов, различает их только
 * ячейка/здание, в которое отправлена команда. Требует `c`.
 */
function renderArcheology(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseArcheology(xml);
  const cellNum = ctx.activeCell;
  openWindow(
    <ArcheologyWindow
      data={data}
      playerResources={combResToKadFoodPool(combRes.value)}
      isPremium={premiumStatus.value?.isPremium ?? false}
      gateOpen={ctx.gateOpen}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onTrain={(count) => cellNum !== undefined && void ctx.send(tag('trainunit', { c: cellNum, count }))}
      onSendExpedition={(dest, count, turns, sizeMin, countGroup) =>
        void ctx.send(archeologyTeamXml(buildSendExpeditionCommand(dest, count, turns, sizeMin, countGroup)))
      }
    />,
  );
}

/**
 * vacationData.ts — <vacationstart password=""/>/<vacationstop password=""/>/
 * <vacationsedpass/>, ни один не требует `c` (подтверждено `reqvestdata.as:
 * 873-887`) — в отличие от большинства доменов этой "семьи", действие
 * не привязано к конкретной ячейке.
 */
function renderVacation(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseVacation(xml);
  openWindow(
    <VacationWindow
      data={data}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onStart={(password) => void ctx.send(tag('vacationstart', { password }))}
      onStop={(password) => void ctx.send(tag('vacationstop', { password }))}
      onForgotPassword={() => void ctx.send(tag('vacationsedpass', {}))}
    />,
  );
}

/**
 * complexViewData.ts — <combcomplex>, тоже без `c` и без `<alltabs>`
 * (см. докстринг файла и `ComplexViewWindow.tsx`). Живая команда —
 * `<complexview direction="" ring="" complexnum=""/>`
 * (`reqvestdata.as:791-806`), не через `getbuildmenu`.
 */
function renderComplexView(_root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseComplexView(xml);
  openWindow(
    <ComplexViewWindow
      data={data}
      onClose={closeWindow}
      onNavigate={(cmd) => void ctx.send(tag('complexview', buildComplexViewCommand(cmd)))}
      onWriteMail={(nick) => void ctx.send(tag('getbuildmenu', { tab: 'CreateMessage', type: 'new', name: nick }))}
    />,
  );
}

/**
 * colonyNavigateData.ts — <colonynavigate>, открывается через
 * `<getbuildmenu tab="ColonyNavigatePanel"/>` (без `c`), но корень
 * ОТВЕТА — `colonynavigate`, не `getbuildmenu` (см. докстринг
 * файла). Клик "Перейти" шлёт `<navigate cell="1" dest="R.C.C"/>` —
 * у этой команды своего xmldata-ответа нет (`UA_Navigate::
 * performAction()` только вызывает `refresh()`), поэтому здесь же
 * закрываем окно: дальше пользователь смотрит на карту новой соты
 * через уже подключённую обработку марки `refresh`.
 */
function renderColonyNavigate(_root: Element, xml: string, ctx: DispatchContext): void {
  const colonies = parseColonyNavigate(xml);
  openWindow(
    <ColonyNavigateWindow
      colonies={colonies}
      onClose={closeWindow}
      onNavigate={(coords) => {
        void ctx.send(tag('navigate', buildNavigateCommand(coords)));
        closeWindow();
      }}
      onLeaveColony={(idComb) => void ctx.send(tag('leaveColony', buildLeaveColonyCommand(idComb)))}
    />,
  );
}

/** privatePeaceData.ts — <getprivatepeace nick="" definition="" reason=""/>, тоже без `c` (reqvestdata.as:134-149). */
function renderPrivatePeace(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parsePrivatePeace(xml);
  openWindow(
    <PrivatePeaceWindow
      data={data}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onSend={(command) => void ctx.send(getPPeaceXml(command))}
    />,
  );
}

/**
 * Домены `getbuildmenu`-семейства (и `getbuildinfo` отдельным top-level
 * запросом с тем же "гнездом" вкладок) с полной проводкой — реальные
 * команды, реальные данные, реальные вкладки. Проверяется ДО `PARSERS`
 * (см. `handleResponse`).
 */
/**
 * powerShieldData.ts — "Зарядить"/"Активировать" НЕ синонимы вопреки
 * ожиданию: `powershieldclass.as:118-146` подтверждает `onCharge` (без
 * параметров, без подтверждения) шлёт `<psactivation/>`, а `onActivate`
 * (с числом ходов, с обязательным подтверждением) — `<psstart time=""/>`.
 * Ни один не требует `c`.
 */
function renderPowerShield(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parsePowerShield(xml);
  openWindow(
    <PowerShieldWindow
      data={data}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onCharge={() => void ctx.send(tag('psactivation', {}))}
      onActivate={(turns) => void ctx.send(tag('psstart', { time: turns }))}
    />,
  );
}

/** protectShieldData.ts — <restoreProtection resource="RES|SILVER" amount="{percent}"/> (reqvestdata.as:900-904), без `c`. */
function renderProtectShield(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseProtectShield(xml);
  const res = combRes.value;
  openWindow(
    <ProtectShieldWindow
      data={data}
      playerResources={{
        kaderium: combResCurrent(res, 'KADERIUM'),
        nano: combResCurrent(res, 'NANO_CRYSTAL'),
        stasis: combResCurrent(res, 'STASIS_ENERGY'),
        food: combResCurrent(res, 'FOOD'),
        prodium: combResCurrent(res, 'PRODIUM'),
        silver: combResCurrent(res, 'SILVER_CREDIT'),
      }}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onRestoreWithResources={(percent) => void ctx.send(tag('restoreProtection', { resource: 'RES', amount: percent }))}
      onRestoreWithSilver={(percent) => void ctx.send(tag('restoreProtection', { resource: 'SILVER', amount: percent }))}
    />,
  );
}

/**
 * researchData.ts — <research id=""/>/<cancelresearch id=""/> без `c`,
 * но набор/отмена набора учёных — <trainunit c="" count=""/>/
 * <canceltrainunit c=""/>, оба ТРЕБУЮТ `c` (reqvestdata.as:415-444) —
 * та же генерическая пара тегов, что и для набора военных юнитов
 * (`trainunit`/`canceltrainunit` — не путать с маркой ответа `warunit`).
 */
function renderResearch(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseResearch(xml);
  const cellNum = ctx.activeCell;
  openWindow(
    <ResearchWindow
      data={data}
      playerResources={combResToNanoFoodPool(combRes.value)}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onStartResearch={(id) => void ctx.send(tag('research', { id }))}
      onCancelResearch={(id) => void ctx.send(tag('cancelresearch', { id }))}
      onTrainScientists={(count) => cellNum !== undefined && void ctx.send(tag('trainunit', { c: cellNum, count }))}
      onCancelTrainScientists={() => cellNum !== undefined && void ctx.send(tag('canceltrainunit', { c: cellNum }))}
    />,
  );
}

/**
 * colonizationData.ts — <colonize id_module="" destination=""><colonyname>
 * <![CDATA[name]]></colonyname></colonize> (reqvestdata.as:565-568) и
 * <cancelcolonize id_module=""/> (reqvestdata.as:570-574), `id_module`
 * берём из уже разобранных данных (`data.idModule`), не спрашиваем окно
 * ещё раз — ни одна из двух команд не требует `c`.
 */
function renderColonization(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseColonizationCenter(xml);
  openWindow(
    <ColonizationWindow
      data={data}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onSend={(destination, name) =>
        void ctx.send(colonizeXml(buildSendColonyModuleCommand(data.idModule ?? '', destination, name)))
      }
      onCancel={() => void ctx.send(tag('cancelcolonize', { id_module: data.idModule ?? '' }))}
    />,
  );
}

/** armyData.ts — <dismissArmies/> (reqvestdata.as:104-108), без параметров и без `c`. */
function renderArmyOverview(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseArmyOverview(xml);
  openWindow(
    <ArmyOverviewWindow
      overview={data}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onDismissAll={() => void ctx.send(tag('dismissArmies', {}))}
      onArmyAction={(command) => void ctx.send(tag(command.tag, command.attrs))}
      onTransferUnit={(idUnit, count, idArmyFrom, idArmyTo) => void ctx.send(transferUnitXml(idUnit, count, idArmyFrom, idArmyTo))}
    />,
  );
}

/** allianceMemberData.ts — <expelalliance id_alliance="" id_userinstance=""/> (reqvestdata.as:906-910), без `c`. */
function renderAllianceOverview(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseAllianceOverview(xml);
  openWindow(
    <AllianceMemberWindow
      data={data}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onKick={(userId) => {
        const cmd = buildKickCommand(data.idAlliance ?? '', userId);
        void ctx.send(tag('expelalliance', { id_alliance: cmd.idAlliance, id_userinstance: cmd.idUserinstance }));
      }}
    />,
  );
}

/**
 * allianceData.ts — три команды, все без `c`: `<createalliance>`/
 * `<joinalliance>` (вложенный CDATA, reqvestdata.as:587-608) и
 * `<leavealliance id_alliance=""/>` (reqvestdata.as:609-612).
 */
function renderAllianceManagement(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseAllianceManagement(xml);
  openWindow(
    <AllianceManagementWindow
      data={data}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onExit={(idAlliance) => void ctx.send(leaveAllianceXml(idAlliance))}
      onCreate={(command) => void ctx.send(createAllianceXml(command))}
      onJoin={(command) => void ctx.send(joinAllianceXml(command))}
    />,
  );
}

/**
 * createNewMailData.ts — переиспользует уже готовый `sendMessageXml()`
 * (та же команда `<sendmessage>`, что и для "Ответить"/"Ответить с
 * цитатой" из уже открытой почты). `isVk` — сборка ВКонтакте, всегда
 * `false` здесь: этот клиент — основной сайт, не VK-версия (см.
 * CLIENT_PORTING_PLAN.md, решение "VK — переносить заново отдельно").
 */
function renderCreateMessage(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseCreateNewMail(xml);
  openWindow(
    <CreateNewMailWindow
      data={data}
      isVk={false}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onSend={(to, subject, body, typeScope) => void ctx.send(sendMessageXml(typeScope, to, subject, body))}
    />,
  );
}

/**
 * trainUnitData.ts — <trainwarunit c="" count="" id=""/>/
 * <canceltrainwarunit c=""/> (reqvestdata.as:554-563), оба требуют `c`.
 * `playerResources`/`computeAllowMax()` — теперь подключены:
 * `game.getMaxNumber()` подтверждён как 2×10⁹ (game.as:375), см.
 * докстринг trainUnitData.ts.
 */
function renderTrainWarUnit(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseTrainWarUnit(xml);
  const cellNum = ctx.activeCell;
  openWindow(
    <TrainUnitWindow
      data={data}
      localUnits={localData.value?.units ?? new Map()}
      playerResources={combResToPlayerPool(combRes.value)}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onTrain={(idUnit, count) =>
        cellNum !== undefined && void ctx.send(tag('trainwarunit', { c: cellNum, count, id: idUnit }))
      }
      onCancel={() => cellNum !== undefined && void ctx.send(tag('canceltrainwarunit', { c: cellNum }))}
    />,
  );
}

/** armyCreationData.ts — <createarmy><armyname>...<unit .../>...</createarmy> (reqvestdata.as:748-757), без `c`. */
function renderArmyCreation(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseArmyCreation(xml);
  openWindow(
    <ArmyCreationWindow
      data={data}
      localUnits={localData.value?.units ?? new Map()}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onCreate={(name, units) => void ctx.send(createArmyXml(name, units))}
    />,
  );
}

/**
 * mailData.ts — почта не привязана к ячейке нигде (`reqvestdata.as:
 * get_mail/getMailMessage/deleteMail/readMail/createnewmail` — ни один
 * не несёт `c`, подтверждено буквально). Список (`messageoverview`) и
 * тело письма (`message`) — ДВЕ РАЗНЫЕ марки; `mailStore.ts` хранит
 * последний список, чтобы марка `message` могла собрать окно заново
 * (Preact сохраняет `useState` окна при повторном рендере с новыми
 * пропами — выборка чекбоксов не теряется). "Написать"/"Ответить"/
 * "Ответить с цитатой" — ОДНА и та же команда `<getbuildmenu
 * tab="CreateMessage" type="new|reply|quote" [id=""]/>`
 * (`reqvestdata.as:448-463`, `createnewmail()`), ответ уходит в уже
 * подключённый `renderCreateMessage`.
 */
function renderMailList(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseMailList(xml);
  setMailList(data, parseTabsArray(root));
  renderMailWindow(ctx);
}

function renderMailMessage(_root: Element, xml: string, ctx: DispatchContext): void {
  const msg = parseMailMessage(xml);
  setMailOpenBody(msg.body);
  if (mailListState.value) renderMailWindow(ctx);
}

function renderMailWindow(ctx: DispatchContext): void {
  const state = mailListState.value;
  if (!state) return;
  openWindow(
    <MailWindow
      data={state.data}
      openBody={mailOpenBody.value}
      tabs={state.tabs}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onSwitchType={() => void ctx.send(tag('getbuildmenu', { tab: 'MessageOverview', type: state.data.type === 'in' ? 'out' : 'in' }))}
      onOpenMail={(id) => void ctx.send(tag('showmemail', { id }))}
      onDeleteSelected={(ids) => void ctx.send(tag('deletemessage', { id_message: buildDeleteMessagesCommand(ids).idMessage }))}
      onReadSelected={(ids) => void ctx.send(tag('readmessage', { id_message: buildReadMessagesCommand(ids).idMessage }))}
      onNewMail={() => void ctx.send(tag('getbuildmenu', { tab: 'CreateMessage', type: 'new' }))}
      onReply={(id) => void ctx.send(tag('getbuildmenu', { tab: 'CreateMessage', type: 'reply', id }))}
      onReplyQuote={(id) => void ctx.send(tag('getbuildmenu', { tab: 'CreateMessage', type: 'quote', id }))}
    />,
  );
}

/**
 * spyingData.ts / `PN_SpyAction` (getbuildmenu-семья, `extends
 * UA_GetBuildMenu`, требует `c`). Живьём подтверждено
 * (`TL_SpyAction.class.php:26-31,35`): атрибут `<onbase id="">` — это
 * comma-joined `unitname` (например "SPY_ZOND_SCOUT"), а НЕ буквальные
 * "Z1"/"Z2", как предполагала докстрока spyingData.ts — совпадает по
 * формату с ключом `LocalUnit.id`, поэтому зонды локализуются напрямую
 * через `localData.value.units`. `<sendzond .../>` — без `c`
 * (см. sendZondXml в xmlBuilder.ts).
 */
function renderSpying(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseSpying(xml);
  const units = localData.value?.units ?? new Map();
  const zondNames = new Map<string, string>();
  for (const z of data.allowZonds) zondNames.set(z.id, units.get(z.id)?.ln ?? z.id);
  openWindow(
    <SpyingWindow
      data={data}
      zondNames={zondNames}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onSend={(p) => {
        const action = data.actions.find((a) => a.id === p.actionId);
        void ctx.send(
          sendZondXml({
            id: p.zondId,
            count: p.count,
            action: p.actionId,
            needsCoord: action?.addrAllow === '1',
            ring: p.ring,
            complex: p.complex,
            sota: p.sota,
            oneRound: p.oneRoundBattle,
            speed: p.speedOrTime,
            time: p.speedOrTime,
          }),
        );
      }}
    />,
  );
}

/**
 * zondCreateData.ts / `PN_SpyTrainUnit` (getbuildmenu-семья, требует `c`,
 * подтверждено `debug.link.active_cell` в `trainzondunit()`,
 * reqvestdata.as:311-324 — реальный тег `trainspyunit`, НЕ `trainzondunit`
 * как называется сама клиентская функция-обёртка). Атрибут `id` у
 * `<allowzond>` — подтверждено `TL_SpyTrainUnit.class.php:27-31`: это
 * буквальный `unitname` (SPY_ZOND_SCOUT/EXPLORER), НЕ числовой `id_unit`,
 * как предполагала докстрока zondCreateData.ts — тоже совпадает с ключом
 * `LocalUnit.id`.
 */
function renderZondCreate(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseZondCreate(xml);
  const cellNum = ctx.activeCell;
  const units = localData.value?.units ?? new Map();
  const zondNames = new Map<string, string>();
  for (const z of data.allowZond) zondNames.set(z.id, units.get(z.id)?.ln ?? z.id);
  openWindow(
    <ZondCreateWindow
      data={data}
      zondNames={zondNames}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onTrain={(id, count) => cellNum !== undefined && void ctx.send(tag('trainspyunit', { c: cellNum, count, id }))}
    />,
  );
}

/**
 * scannerData.ts / `PN_SpyLogs` (getbuildmenu-семья, требует `c` для
 * открытия/фильтра — `filterScanner()`, reqvestdata.as:73-83), но само
 * действие "Пересканировать выбранное" — БЕЗ `c`
 * (`<scaninriver id="{csv}"/>`, reqvestdata.as:942-946, `requestScan`,
 * массив id сериализуется через запятую). В оригинале кнопка видна
 * только премиум-аккаунтам (`game.getPremium()`, scannermenuclass.as:135)
 * — источник премиум-статуса нигде не распарсен (как и в TerraformWindow/
 * ArcheologyWindow), здесь кнопка всегда доступна — безопасный дефолт.
 */
function renderSpyLog(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseSpyLog(xml);
  openWindow(
    <ScannerWindow
      data={data}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onScanSelected={(ids) => void ctx.send(tag('scaninriver', { id: ids.join(',') }))}
    />,
  );
}

/**
 * warFactoryData.ts — открывается автономной командой `<getwarfactory/>`
 * (reqvestdata.as:848-852, `request_war_statistic`), НЕ через
 * getbuildmenu-семью (класса `PN_WarComplex`/аналога среди `PN_*` нет) —
 * `onTabClick` сознательно не подключаем, нет привязанной ячейки.
 * `onLoad` шлёт `<trainwarunit c="{cell1,cell2,...}" id="" count=""/>`
 * (reqvestdata.as:863-866, `load_pack_war_factory`) — ТА ЖЕ команда, что
 * и в TrainUnitWindow, но `c` здесь — CSV выбранных ячеек-фабрик, а не
 * `ctx.activeCell`.
 */
function renderWarComplex(_root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseWarComplex(xml);
  openWindow(
    <WarStatisticWindow
      data={data}
      localUnits={localData.value?.units ?? new Map()}
      playerResources={combResToPlayerPool(combRes.value)}
      onClose={closeWindow}
      onLoad={(cellIds, unitId, amount) =>
        void ctx.send(tag('trainwarunit', { c: cellIds.join(','), id: unitId, count: amount }))
      }
    />,
  );
}

/**
 * questData.ts — read-only журнал заданий, без исходящих команд и без
 * вкладок (`QuestWindowProps` не принимает ни то, ни другое). Команда
 * открытия окна не подтверждена достоверно (см. `questData.ts` докстринг
 * — ни одной кнопки-триггера в клиенте найти не удалось).
 */
function renderQuestMenu(_root: Element, xml: string, _ctx: DispatchContext): void {
  const data = parseQuestMenu(xml);
  openWindow(<QuestWindow data={data} onClose={closeWindow} />);
}

/**
 * logData.ts — общий попап отчёта, без вкладок и исходящих команд.
 * "Копировать в буфер" — чисто клиентское действие (`logwindow.as:87-88`,
 * `game.copyToBuffer(REPORT_FIELD.text)` — AS2 `.text` отдаёт ПЛОСКИЙ
 * текст без разметки, не `.htmlText`), воспроизведено через
 * `textContent` промежуточного `<div>`, не сырой `textData` с тегами.
 */
function renderLogWindow(_root: Element, xml: string, _ctx: DispatchContext): void {
  const data = parseLogWindow(xml);
  openWindow(
    <LogWindow
      data={data}
      onClose={closeWindow}
      onCopy={() => {
        const tmp = document.createElement('div');
        tmp.innerHTML = data.textData;
        void navigator.clipboard?.writeText(tmp.textContent ?? '');
      }}
    />,
  );
}

/**
 * artefactStatData.ts — открывается автономной командой `<arthint/>`
 * (reqvestdata.as:843-847, `request_art_statistic`), не через
 * getbuildmenu-семью — `onTabClick` не подключаем. Клик по ячейке в
 * таблице — ДРУГАЯ, уже подключённая команда `getbuildmenu c=""
 * tab="BuildingArtefact"` (renderBuildArtefact), не связана с текущим
 * "гнездом" вкладок этого окна.
 */
function renderArtefactStat(_root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseArtefactStat(xml);
  openWindow(
    <ArtefactStatWindow
      data={data}
      isPremium={premiumStatus.value?.isPremium ?? false}
      onClose={closeWindow}
      onCellClick={(cellNum) => void ctx.send(tag('getbuildmenu', { c: cellNum, tab: 'BuildingArtefact' }))}
    />,
  );
}

/**
 * artDepotData.ts / `PN_ArcheologyArtefact` (getbuildmenu-семья, требует
 * `c` для открытия/сортировки — подтверждено `artdepotsort()`,
 * reqvestdata.as:868-871), но обе команды окна — БЕЗ `c`:
 * `<dropartefact id="{csv}"/>` (reqvestdata.as:828-834, `delArtFromDepot`)
 * и `<artefactactivate id=""/>` (reqvestdata.as:947-950, `artActivate`).
 * Фильтр по типу/размеру/scope (`data.filter`) не отрисован в
 * `ArtDepotWindow` (пробел Фазы 3) — `artdepotsort()` сюда сознательно не
 * подключён.
 */
function renderArtDepot(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseArtDepot(xml);
  openWindow(
    <ArtDepotWindow
      entries={data.entries}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onDeleteSelected={(ids) => void ctx.send(tag('dropartefact', { id: ids.join(',') }))}
      onActivate={(id) => void ctx.send(tag('artefactactivate', { id }))}
    />,
  );
}

/**
 * artefactShopData.ts — магазин артефактов ЛИЧНОГО КАБИНЕТА, не
 * getbuildmenu-семьи. `onBuy` переиспользует уже готовый
 * `buyArtefactInShopXml()` (requestDataCabinet.as:61-88 `buyArtInShop` —
 * единственная команда кабинета, НЕ обёрнутая в `<cabinetRequest>`, см.
 * xmlBuilder.ts). Открывается изнутри домена `cabinet` (теперь
 * подключён, см. renderCabinet) — но КАКОЙ именно пункт `service_list`
 * ведёт сюда (через `callsubmenu`, напрямую другим `command=`, или
 * отдельным путём) не сверялся с сервером — не гадаем, окно и команда
 * покупки готовы и сработают, как только маркой `artefact_shop` придёт
 * ответ, вне зависимости от того, как именно он был запрошен.
 */
function renderArtefactShop(_root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseArtefactShop(xml);
  openWindow(
    <ArtefactShopWindow
      data={data}
      onClose={closeWindow}
      onBuy={(cart, concurrency) => {
        const cmd = buildBuyArtefactsCommand(cart, concurrency);
        void ctx.send(buyArtefactInShopXml(cmd.artefacts));
      }}
    />,
  );
}

/**
 * auctionData.ts / `PN_AuctionPanel` (getbuildmenu-семья, требует `c` для
 * открытия). `onBuy` → `<buy id_trade="" newprice="" anonym="0|1"/>`
 * (reqvestdata.as:836-841, `buy_lot`) — `anonym` зависит от
 * несинхронизированного клиентского флага `debug.link.BYU_ANONYMOUSLY`
 * (отдельная галочка "анонимно"), здесь безопасный дефолт "0"
 * (неанонимно). `lot.tradeBy` — то же машинное имя ресурса, что и ключи
 * `combres` (`debug.link.resourse_player` оригинала — та же структура).
 */
function renderAuction(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseAuction(xml);
  const res = combRes.value;
  const playerResourceByName = new Map<string, number>(
    Object.entries(res).map(([name, entry]) => [name, entry?.current ?? 0]),
  );
  openWindow(
    <AuctionWindow
      lots={data.lots}
      playerResourceByName={playerResourceByName}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onBuy={(lotId, price) => void ctx.send(tag('buy', { id_trade: lotId, newprice: price, anonym: 0 }))}
    />,
  );
}

/** Спред полей команды кабинета (кроме `command`) как атрибутов `<cabinetRequest>` — все build*Command() кабинета возвращают такую форму. */
function sendCabinet(ctx: DispatchContext, cmd: { command: string } & Record<string, unknown>): void {
  const { command, ...attrs } = cmd;
  void ctx.send(cabinetRequestXml(command, attrs as Record<string, string | number | undefined>));
}

/**
 * cabinetMainData.ts / submenuData.ts — три под-действия ОДНОЙ марки
 * `cabinet` (`action="enter"|"submenuopen"|"operationStatistic"`,
 * `parseCabinetResponse` уже их различает). Открывается автономной
 * `<cabinetRequest command="open"/>` (reqvestdata.as:1017-1021,
 * `cabinetRequest()` — вызывается из `game.as` напрямую с иконки
 * статус-бара, БЕЗ платформенных условий; подтверждённый тег — "open",
 * а не "enter", как предполагал план до сверки), не через getbuildmenu-
 * семью — своя система команд `<cabinetRequest command="...">`.
 *
 * Список услуг (enter) и открытая подформа (submenuopen) — РАЗНЫЕ марки
 * одного корня, поэтому свой стор `cabinet/cabinetStore.ts` по тому же
 * паттерну, что и почта (см. `renderMailList`/`renderMailMessage`) —
 * открытие подформы не должно терять уже показанный список услуг.
 *
 * `operationStatistic` — окна для него в Фазе 3 не строили (нет в
 * реестре доменов плана), здесь только логируется, как и прочие
 * "распознан, но без окна" случаи (см. PARSERS).
 */
function renderCabinet(_root: Element, xml: string, ctx: DispatchContext): void {
  const result = parseCabinetResponse(xml);
  if (result.action === 'enter') {
    setCabinetMain(result.data);
  } else if (result.action === 'submenuopen') {
    setCabinetSubmenu(result.form);
  } else {
    console.log('[dispatch] cabinet operationStatistic разобран, окна для него в Фазе 3 нет:', result.data);
    return;
  }
  renderCabinetWindow(ctx);
}

/**
 * `buyGoldMode` — платформенный флаг (`_level0.socialNet`/`_level0.bill`,
 * `RightBlock.as:201-218`), задаётся embed-параметрами HTML-страницы, вне
 * игрового протокола. VK-режим — вне объёма этого порта (см. память
 * проекта, VK переносится отдельно с нуля), `pop-service` требует
 * непортированный `_level0.popmog_service`. Выбран `'pop-billing'` КАК
 * ПРЕДПОЛОЖЕНИЕ, не факт — единственный вариант, опирающийся только на
 * уже разобранные живые данные (`data.billingUrl` из самого ответа
 * `cabinet`), а не на непортированный внешний флаг.
 */
function renderCabinetWindow(ctx: DispatchContext): void {
  const data = cabinetMain.value;
  if (!data) return;
  openWindow(
    <CabinetWindow
      data={data}
      openSubmenu={cabinetSubmenu.value}
      onClose={closeWindow}
      onSelectService={(id) => sendCabinet(ctx, buildOpenSubmenuCommand(id))}
      onCloseSubmenu={() => {
        setCabinetSubmenu(null);
        renderCabinetWindow(ctx);
      }}
      onSubmitSubmenu={(buttonId, values) => {
        const idmenu = cabinetSubmenu.value?.idmenu ?? '';
        void ctx.send(cabinetRequestXml('confirmsubmenu', { button: buttonId, menu: idmenu, ...values }));
      }}
      onDismissPopup={() => {
        dismissCabinetPopup();
        renderCabinetWindow(ctx);
      }}
      rightBlock={{
        buyGoldMode: 'pop-billing',
        onBuyGold: () => console.log('[dispatch] onBuyGold — VK-режим вне объёма этого порта'),
        onOpenExternal: (url) => window.open(url, '_blank'),
        onChangeGoldToSilver: (count) => sendCabinet(ctx, buildChangeGoldToSilverCommand(count)),
        onMoneyToWorld: (worldId, gold, silver) => sendCabinet(ctx, buildMoneyToWorldCommand(worldId, gold, silver)),
        onMoneyFromWorld: (worldId, gold, silver) => sendCabinet(ctx, buildMoneyFromWorldCommand(worldId, gold, silver)),
        onGiftMoney: (gold, silver) => sendCabinet(ctx, buildGiftMoneyCommand(gold, silver)),
        onShowAllStatistic: () => sendCabinet(ctx, buildGetAllStatisticCommand()),
      }}
    />,
  );
}

/**
 * allianceSettingData.ts / `PN_AllianceSettings` (getbuildmenu-семья,
 * требует `c` для открытия), но обе живые команды окна — БЕЗ `c`:
 * `<changepassword><![CDATA[текст]]></changepassword>`
 * (reqvestdata.as:592-597) и `<changeleader newleader=""/>`
 * (reqvestdata.as:598-603).
 */
function renderAllianceSettings(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseAllianceSettings(xml);
  openWindow(
    <AllianceSettingsWindow
      data={data}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onChangePassword={(pwd) => void ctx.send(changeAlliancePasswordXml(pwd))}
      onChangeLeader={(id) => void ctx.send(tag('changeleader', { newleader: id }))}
    />,
  );
}

/**
 * armyCreationData.ts / `PN_WarCalculator` (getbuildmenu-семья) — чисто
 * просмотровый экран: у оригинала (`armycalcclass.as`) НЕТ ни одного
 * исходящего запроса, только отображение уже присланных чисел (см.
 * докстринг armyCreationData.ts).
 */
function renderArmyCalculator(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseArmyCalculator(xml);
  openWindow(
    <ArmyCalculatorWindow
      data={data}
      localUnits={localData.value?.units ?? new Map()}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
    />,
  );
}

/**
 * alliancePeaceData.ts / `PN_PeaceAlliance` (getbuildmenu-семья, требует
 * `c` для открытия). Список (`alliancepeace`) и открытая карточка одного
 * альянса (`alliancePeaceData`) — ДВЕ РАЗНЫЕ марки, свой стор
 * `alliancePeaceStore.ts` по тому же паттерну, что и почта/кабинет.
 * Детальный запрос — `<getInfoAboutPeace allianceId=""/>`
 * (`RequestDataAlli.as:13-16`), без `c`.
 */
function renderAlliancePeaceList(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseAlliancePeaceList(xml);
  setAlliancePeaceList(data, parseTabsArray(root));
  renderAlliancePeaceWindow(ctx);
}

function renderAlliancePeaceDetail(_root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseAlliancePeaceDetail(xml);
  setAlliancePeaceDetail(data);
  renderAlliancePeaceWindow(ctx);
}

/**
 * Восемь статусов `<getAlliancePeace status="..."/>` (`RequestDataAlli.as`)
 * покрыты пятью живыми флагами `data.buttons` — какая кнопка видна,
 * решает сервер; восьмой статус `cancelTerminate` ничем из пяти флагов
 * не гейтится очевидным образом — кнопка для него не гадается (см.
 * докстринг AlliancePeaceDetailWindow.tsx).
 */
function renderAlliancePeaceWindow(ctx: DispatchContext): void {
  const detail = alliancePeaceDetail.value;
  if (detail) {
    openWindow(
      <AlliancePeaceDetailWindow
        data={detail}
        onClose={closeWindow}
        onBack={() => {
          clearAlliancePeaceDetail();
          renderAlliancePeaceWindow(ctx);
        }}
        onOfferPeace={(my, he, time) =>
          void ctx.send(getAlliancePeaceXml(buildSendOfferPeaceCommand(detail.id ?? '', my, he, time)))
        }
        onCancelOffer={() => void ctx.send(getAlliancePeaceXml(buildSimplePeaceCommand(detail.id ?? '', 'cancelPeace')))}
        onConfirmPeace={() => void ctx.send(getAlliancePeaceXml(buildSimplePeaceCommand(detail.id ?? '', 'confirmPeace')))}
        onDeclinePeace={() => void ctx.send(getAlliancePeaceXml(buildSimplePeaceCommand(detail.id ?? '', 'declinePeace')))}
        onOfferTerminate={() => void ctx.send(getAlliancePeaceXml(buildSimplePeaceCommand(detail.id ?? '', 'terminatePeace')))}
        onConfirmTerminate={() =>
          void ctx.send(getAlliancePeaceXml(buildSimplePeaceCommand(detail.id ?? '', 'confirmTerminatePeace')))
        }
        onDeclineTerminate={() =>
          void ctx.send(getAlliancePeaceXml(buildSimplePeaceCommand(detail.id ?? '', 'declineTerminatePeace')))
        }
      />,
    );
    return;
  }

  const state = alliancePeaceList.value;
  if (!state) return;
  openWindow(
    <AlliancePeaceWindow
      data={state.data}
      tabs={state.tabs}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onOpenDetail={(id) => void ctx.send(getInfoAboutPeaceXml(id))}
    />,
  );
}

/**
 * battleOverviewData.ts — `ShowBattle()` подтверждает getbuildmenu-семью
 * буквально (`<getbuildmenu c="" tab="BattleOverview" .../>`,
 * reqvestdata.as:807-812), хотя отдельного `PN_BattleOverview`-класса
 * среди найденных `PN_*` нет (возможно, под другим именем — не
 * критично, сам wire-формат подтверждён). Фильтр — та же команда с доп.
 * атрибутами `days`/`fromd`/`opp`/`sot`/`stat`/`winner` (подтверждённые
 * enum'ы — battleoverviewclass.as:246-266), требует `c`. "Показать бой"
 * (`getBattleInfo`) и "Сдаться"/"Отступить" (`retreat`) — без `c`
 * (reqvestdata.as:814-827,918-927).
 */
function renderBattleOverview(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseBattleOverview(xml);
  const cellNum = ctx.activeCell;
  openWindow(
    <BattleOverviewWindow
      data={data}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onApplyFilter={(v) =>
        cellNum !== undefined &&
        void ctx.send(
          tag('getbuildmenu', {
            c: cellNum,
            tab: 'BattleOverview',
            days: v.days,
            fromd: v.fromDays,
            opp: v.opp,
            sot: v.sot,
            stat: v.stat,
            winner: v.winner,
          }),
        )
      }
      onViewBattle={(id) => void ctx.send(tag('getBattleInfo', { id_battle: id }))}
      onSurrender={(id) => void ctx.send(tag('retreat', { id_battle: id }))}
      onWriteToOpponent={(nick) => void ctx.send(tag('getbuildmenu', { tab: 'CreateMessage', type: 'new', name: nick }))}
    />,
  );
}

/**
 * battleData.ts — `<battlinfo>` приходит в ответ на уже отправляемый из
 * BattleOverviewWindow `getBattleInfo id_battle=""` (без `c`), рендерится
 * в canvas через PixiJS (`BattleWindow`/`battleRenderer.ts`), а не
 * обычной HTML-формой — "наглядное поле боя" в оригинале тоже отдельный
 * визуальный режим, не табличный список.
 */
function renderBattleInfo(_root: Element, xml: string, _ctx: DispatchContext): void {
  const data = parseBattleInfo(xml);
  openWindow(<BattleWindow data={data} onClose={closeWindow} />);
}

const RENDERERS: Record<string, (root: Element, xml: string, ctx: DispatchContext) => void> = {
  getbuildinfo: renderBuildInfo,
  buildingartefact: renderBuildArtefact,
  terraforming: renderTerraform,
  religion: renderReligion,
  transferresource: renderTransfer,
  trade: renderSell,
  getbuildmenu: renderBuildMenu,
  reincarnate: renderReincarnate,
  archeology: renderArcheology,
  otpusk: renderVacation,
  allianceprivatepeace: renderPrivatePeace,
  powershild: renderPowerShield,
  protectedfield: renderProtectShield,
  research: renderResearch,
  colonize: renderColonization,
  colonizestatus: renderColonization,
  combcomplex: renderComplexView,
  colonynavigate: renderColonyNavigate,
  table: renderEconomicalOverview,
  armyoverview: renderArmyOverview,
  allianceoverview: renderAllianceOverview,
  alliancemanagement: renderAllianceManagement,
  createmessage: renderCreateMessage,
  warunit: renderTrainWarUnit,
  armycreation: renderArmyCreation,
  messageoverview: renderMailList,
  message: renderMailMessage,
  spying: renderSpying,
  zondcreate: renderZondCreate,
  spylog: renderSpyLog,
  warcomplex: renderWarComplex,
  questmenu: renderQuestMenu,
  log: renderLogWindow,
  arthint: renderArtefactStat,
  archeology_artefact: renderArtDepot,
  artefact_shop: renderArtefactShop,
  auction: renderAuction,
  cabinet: renderCabinet,
  alliancesettings: renderAllianceSettings,
  armycalculator: renderArmyCalculator,
  alliancepeace: renderAlliancePeaceList,
  alliancePeaceData: renderAlliancePeaceDetail,
  battleoverview: renderBattleOverview,
  battlinfo: renderBattleInfo,
};

/**
 * Обработать один разобранный ответ `sendCommand()` — прямой аналог
 * `parse_respond`.
 */
export function handleResponse(marks: ParsedMarks, ctx: DispatchContext): void {
  // Игровое серверное время — `stime`/`userGMT`, top-level марки в
  // КАЖДОМ ответе (не вложенный XML), см. serverClockStore.ts.
  if (marks.stime !== undefined) {
    serverClock.value = {
      offsetMs: Number(marks.stime) - Date.now(),
      gmtHours: marks.userGMT !== undefined ? Number(marks.userGMT) : 0,
    };
  }

  if (marks.xmlerror) {
    const doc = parseXmlFragment(marks.xmlerror);
    pushPopup(<InfoPopup message={doc.documentElement?.textContent ?? marks.xmlerror} onClose={popPopup} />);
  }

  if (marks.outside) {
    const alert = parseOutsideAlert(marks.outside);
    const message = alert.title ? `${alert.title}\n\n${alert.text}` : alert.text;
    pushPopup(<InfoPopup message={message} onClose={popPopup} />);
  }

  if (marks.refresh) {
    const refresh = parseRefresh(marks.refresh);
    void ctx.renderer.renderGround(refresh.sota);
    void ctx.renderer.renderBuildings(parseBuild(refresh.build), ctx.race);
    ctx.gateOpen = refresh.gate;
    // Декоративное окружение вокруг соты разное для HOME/CENTRAL/NEUTRAL
    // (sota.as:298-367, см. map/renderer.ts) — переключаем на каждый
    // refresh, не только на входе в мир, иначе вид "Центр" показывал бы
    // окружение главного экрана (владелец, 2026-09-16).
    void ctx.renderer.setEnvironmentLocation(refresh.location);
  }

  if (marks.loaddata) {
    setLocalData(parseLocalData(marks.loaddata));
  }

  if (marks.combres) {
    setCombRes(parseCombRes(marks.combres));
  }

  if (marks.mode) {
    setPremiumStatus(parsePremiumStatus(marks.mode));
  }

  if (marks.sysmsg) {
    // Копим в постоянный лог (аналог debug.link.sys_msg/war_msg/spy_msg,
    // см. mail/sysMessageStore.ts) — раньше порция молча терялась после
    // console.log. Автопоказ попапа НЕ воспроизводим: в оригинале
    // sys_mess_menu.as открывается кнопкой статус-бара по требованию
    // игрока, а не всплывает сама на каждое сообщение.
    appendSysMsgBatch(parseSysMsg(marks.sysmsg));
  }

  if (marks.advice) {
    const hint = parseAdviceHint(marks.advice);
    openWindow(
      <AdviceWindow
        hint={hint}
        onClose={closeWindow}
        onNext={() => void ctx.send(tag('getNubHelp', buildGetNubHelpCommand(hint.number + 1)))}
        onPrev={() => void ctx.send(tag('getNubHelp', buildGetNubHelpCommand(hint.number - 1)))}
        onJumpTo={(id) => void ctx.send(tag('getNubHelp', buildGetNubHelpCommand(Number(id))))}
      />,
    );
  }

  if (!marks.xmldata) return;

  const doc = parseXmlFragment(marks.xmldata);
  const root = doc.documentElement;
  if (!root) return;
  const rootTag = root.nodeName;

  const renderer = RENDERERS[rootTag];
  if (renderer) {
    renderer(root, marks.xmldata, ctx);
    return;
  }

  const parser = PARSERS[rootTag];
  if (!parser) {
    console.warn('[dispatch] неизвестный корневой тег xmldata (нет в реестре):', rootTag);
    return;
  }

  // Домен опознан и разобран, но живое окно для него ещё не подключено
  // (см. докстринг файла) — логируем разобранные данные, чтобы это было
  // видно и проверяемо, вместо тихого игнорирования.
  console.log(`[dispatch] ${rootTag} разобран, живое окно ещё не подключено:`, parser(marks.xmldata));
}
