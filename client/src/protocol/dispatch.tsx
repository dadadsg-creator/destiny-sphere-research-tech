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
 * `trade`. Остальные домены пока только логируются в консоль — подключение
 * оставлено отдельным объёмом работы; часть из них (`archeology`,
 * `reincarnate`, будущий `BuildMenuWindow`) требует ещё не портированной
 * марки `combres` (пул ресурсов игрока) и/или неизвестного источника
 * премиум-статуса — сознательно не подключены, чтобы не подставлять
 * выдуманные данные вместо реальных.
 */
import type { ParsedMarks } from './response';
import { parseXmlFragment } from './response';
import { parseRefresh } from '../map/refreshData';
import { parseBuild } from '../map/buildData';
import type { MapRenderer } from '../map/renderer';
import { parseLocalData } from '../data/localData';
import { setLocalData } from '../data/localDataStore';
import { parseSysMsg } from '../mail/logData';
import { parseAdviceHint } from '../settings/adviceData';
import { openWindow, closeWindow, pushPopup, popPopup } from '../ui/windowStore';
import { InfoPopup } from '../ui/Popup';
import { BuildInfoWindow } from '../buildings/BuildInfoWindow';
import { parseInfoBuild } from '../buildings/buildInfoData';
import { tag, sellXml, transferResourceXml } from './xmlBuilder';
import { parseTabsArray, stripTabPrefix } from './tabsParser';
import { BuildArtefactWindow } from '../artefacts/BuildArtefactWindow';
import { TerraformWindow } from '../world/TerraformWindow';
import { ReligionWindow } from '../world/ReligionWindow';
import { TransferWindow } from '../trade/TransferWindow';
import { SellWindow } from '../trade/SellWindow';

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
import { parseResearch } from '../science/researchData';
import { parsePowerShield } from '../spying/powerShieldData';
import { parseProtectShield } from '../spying/protectShieldData';
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
}

/**
 * Реестр «корневой тег `xmldata` → парсер» — сверен построчно с
 * `xmldata_action` (parser.as:1413-1621+). Домены без записи здесь —
 * подтверждённые пробелы Фазы 3 (`spylog` пробел закрыт — парсер есть в
 * `scannerData.ts`, изначальная пометка в плане была ошибочной;
 * `colonynavigate` — реальный пробел, `colonynavigate.as` не портирован).
 */
const PARSERS: Record<string, (xml: string) => unknown> = {
  alliancemanagement: parseAllianceManagement,
  allianceoverview: parseAllianceOverview,
  allianceprivatepeace: parsePrivatePeace,
  alliancesettings: parseAllianceSettings,
  alliancepeace: parseAlliancePeaceList,
  alliancePeaceData: parseAlliancePeaceDetail,
  archeology: parseArcheology,
  archeology_artefact: parseArtDepot,
  armycreation: parseArmyCreation,
  armyoverview: parseArmyOverview,
  armycalculator: parseArmyCalculator,
  artefact_shop: parseArtefactShop,
  arthint: parseArtefactStat,
  auction: parseAuction,
  battleoverview: parseBattleOverview,
  battlinfo: parseBattleInfo,
  colonize: parseColonizationCenter,
  colonizestatus: parseColonizationCenter,
  createmessage: parseCreateNewMail,
  getbuildmenu: parseListBuild,
  log: parseLogWindow,
  messageoverview: parseMailList,
  message: parseMailMessage,
  otpusk: parseVacation,
  powershild: parsePowerShield,
  protectedfield: parseProtectShield,
  questmenu: parseQuestMenu,
  research: parseResearch,
  spylog: parseSpyLog,
  reincarnate: parseReincarnate,
  warunit: parseTrainWarUnit,
  warcomplex: parseWarComplex,
  spying: parseSpying,
  zondcreate: parseZondCreate,
  cabinet: parseCabinetResponse,
  // Не портировано (см. план, «Реестр доменов»):
  // colonynavigate — colonynavigate.as не читался в Фазе 3.
};

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

/** reqvestdata.as — кнопки BuildInfoWindow, все семь — <тег c=""/>, подтверждено ds_useractiontype (2026-09-08). */
function buildInfoActionCommand(actionTag: string, cellNum: string): string {
  return tag(actionTag, { c: cellNum });
}

function renderBuildInfo(root: Element, xml: string, ctx: DispatchContext): void {
  const cellNum = ctx.activeCell;
  if (cellNum === undefined) return;
  const data = parseInfoBuild(xml);
  const act = (actionTag: string) => () => {
    void ctx.send(buildInfoActionCommand(actionTag, cellNum));
  };
  openWindow(
    <BuildInfoWindow
      data={data}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onCancelBuild={act('cancelbuild')}
      onDemolish={act('delbuild')}
      onCancelUpgrade={act('cancelupgrade')}
      onActivate={act('activatebuild')}
      onCancelActivate={act('cancelactivate')}
      onDeactivate={act('deactivatebuild')}
      onUpgrade={act('upgradebuild')}
    />,
  );
}

/** buildArtefactData.ts:81 — <attachartefact c="" id=""/> / <detachartefact c="" id=""/>. */
function renderBuildArtefact(root: Element, xml: string, ctx: DispatchContext): void {
  const cellNum = ctx.activeCell;
  if (cellNum === undefined) return;
  const data = parseBuildArtefact(xml);
  openWindow(
    <BuildArtefactWindow
      data={data}
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
      // Премиум-статус нигде не парсится (как и combres, см. докстринг
      // файла) — реальный пробел, пока считаем false (безопасный дефолт:
      // просто не покажет кнопку отмены терраформинга премиум-аккаунту).
      isPremium={false}
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

/** transferData.ts:81 — сериализация через transferResourceXml (имена атрибутов не совпадают с полями команды). */
function renderTransfer(root: Element, xml: string, ctx: DispatchContext): void {
  const data = parseTransferMenu(xml);
  openWindow(
    <TransferWindow
      data={data}
      tabs={parseTabsArray(root)}
      onTabClick={(tabId) => sendTabClick(ctx, tabId)}
      onClose={closeWindow}
      onTransfer={(command) => void ctx.send(transferResourceXml(command))}
      onCalculate={(id, category, amount, targetCombId) => {
        // UA_CalcTravel — тег есть в ds_useractiontype, но точный формат
        // запроса (какие поля, как называются атрибуты) в Фазе 3 не
        // сверялся — не отправляем непроверенную команду вслепую.
        console.log('[dispatch] calctravel — формат запроса не подтверждён, действие не отправлено', {
          id,
          category,
          amount,
          targetCombId,
        });
      }}
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

/**
 * Домены `getbuildmenu`-семейства (и `getbuildinfo` отдельным top-level
 * запросом с тем же "гнездом" вкладок) с полной проводкой — реальные
 * команды, реальные данные, реальные вкладки. Проверяется ДО `PARSERS`
 * (см. `handleResponse`).
 */
const RENDERERS: Record<string, (root: Element, xml: string, ctx: DispatchContext) => void> = {
  getbuildinfo: renderBuildInfo,
  buildingartefact: renderBuildArtefact,
  terraforming: renderTerraform,
  religion: renderReligion,
  transferresource: renderTransfer,
  trade: renderSell,
};

/**
 * Обработать один разобранный ответ `sendCommand()` — прямой аналог
 * `parse_respond`.
 */
export function handleResponse(marks: ParsedMarks, ctx: DispatchContext): void {
  if (marks.xmlerror) {
    const doc = parseXmlFragment(marks.xmlerror);
    pushPopup(<InfoPopup message={doc.documentElement?.textContent ?? marks.xmlerror} onClose={popPopup} />);
  }

  if (marks.refresh) {
    const refresh = parseRefresh(marks.refresh);
    void ctx.renderer.renderGround(refresh.sota);
    void ctx.renderer.renderBuildings(parseBuild(refresh.build));
  }

  if (marks.loaddata) {
    setLocalData(parseLocalData(marks.loaddata));
  }

  if (marks.sysmsg) {
    // Разбор одной порции — накопление в постоянный лог (аналог
    // debug.link.sys_msg/war_msg/spy_msg) оставлено объёмом следующего
    // прохода, см. logData.ts докстринг.
    const batch = parseSysMsg(marks.sysmsg);
    if (batch.sys.length || batch.war.length || batch.spy.length) {
      console.log('[dispatch] sysmsg', batch);
    }
  }

  if (marks.advice) {
    const hint = parseAdviceHint(marks.advice);
    console.log('[dispatch] advice hint (попап адвайзера пока не подключён к реальным командам)', hint);
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
