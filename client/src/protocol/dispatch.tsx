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
 * реализована только для одного полностью проверенного вертикального
 * среза — `getbuildinfo` (карта → клик по постройке → BuildInfoWindow,
 * формат запроса подтверждён живым тестом 2026-09-08, см.
 * buildInfoData.ts). Остальные домены пока только логируются в консоль —
 * подключение конкретных окон с реальными командами оставлено отдельным
 * объёмом работы (у многих нужны ещё не портированные служебные марки,
 * например `combres` для пула ресурсов игрока).
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
import { tag } from './xmlBuilder';

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
  buildingartefact: parseBuildArtefact,
  colonize: parseColonizationCenter,
  colonizestatus: parseColonizationCenter,
  createmessage: parseCreateNewMail,
  getbuildmenu: parseListBuild,
  getbuildinfo: parseInfoBuild,
  log: parseLogWindow,
  messageoverview: parseMailList,
  message: parseMailMessage,
  otpusk: parseVacation,
  powershild: parsePowerShield,
  protectedfield: parseProtectShield,
  questmenu: parseQuestMenu,
  religion: parseReligion,
  research: parseResearch,
  spylog: parseSpyLog,
  trade: parseTradeMenu,
  reincarnate: parseReincarnate,
  transferresource: parseTransferMenu,
  terraforming: parseTerraforming,
  warunit: parseTrainWarUnit,
  warcomplex: parseWarComplex,
  spying: parseSpying,
  zondcreate: parseZondCreate,
  cabinet: parseCabinetResponse,
  // Не портировано (см. план, «Реестр доменов»):
  // colonynavigate — colonynavigate.as не читался в Фазе 3.
};

/** reqvestdata.as — кнопки BuildInfoWindow, все семь — <тег c=""/>, подтверждено ds_useractiontype (2026-09-08). */
function buildInfoActionCommand(actionTag: string, cellNum: string): string {
  return tag(actionTag, { c: cellNum });
}

/** Полная живая проводка — карта → клик по занятой ячейке → это окно. Единственный на сегодня domain с реальными командами. */
function renderBuildInfo(xml: string, cellNum: string, ctx: DispatchContext): void {
  const data = parseInfoBuild(xml);
  const act = (actionTag: string) => () => {
    void ctx.send(buildInfoActionCommand(actionTag, cellNum));
  };
  openWindow(
    <BuildInfoWindow
      data={data}
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

/**
 * Обработать один разобранный ответ `sendCommand()` — прямой аналог
 * `parse_respond`. `activeCell` — ячейка, по которой был клик, вызвавший
 * этот запрос (нужна для проводки BuildInfoWindow, см. выше) — аналог
 * `debug.link.active_cell` оригинала, здесь передаётся явно вызывающим
 * кодом, а не глобальным состоянием.
 */
export function handleResponse(marks: ParsedMarks, ctx: DispatchContext, activeCell?: string): void {
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
  const rootTag = doc.documentElement?.nodeName;
  if (!rootTag) return;

  if (rootTag === 'getbuildinfo' && activeCell !== undefined) {
    renderBuildInfo(marks.xmldata, activeCell, ctx);
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
