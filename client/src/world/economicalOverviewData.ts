/**
 * Разбор mark `table` — «Обзор экономики» соты, 3 связанные панели в
 * одном "гнезде" вкладок (`PN_EconomicalOverview`/
 * `...Incoming`/`...Outcoming`, `TL_MenuEconomicalOverview.class.php`).
 * Открывается зданием `SHELTER` (id=64 в клиентском реестре типов —
 * нет, реально id_building=8, см. живой тест).
 *
 * Формат ответа — общий для всех трёх, намеренно НЕ типизированный по
 * конкретным строкам (их состав и порядок разный у каждой панели и в
 * оригинале определяется хрупким подсчётом плоского числа ячеек,
 * `xmlparsers.as:3321-3394`, `repars_table_old_to_new`,
 * `table_type_1..4` по количеству `<td>`) — здесь вместо этого честный
 * разбор реальной структуры `<tr>/<td>`, не завязанный на магические
 * числа:
 *
 * <table>
 *   <tr><td> - </td><img>RESOURCE1</img>...<img>RESOURCEn</img></tr>
 *   <tr><td><b>{:LABEL_KEY:}</b></td><td><b>VALUE1</b></td>...</tr> (×N)
 *   <tr><td><b>{:BUY_RESOURCES:}</b></td></tr>  (заголовок раздела покупки за деньги — пропускается)
 *   <MSG .../> (×N, покупка ресурсов/бонусов за серебро — билинг, вне
 *               периметра порта, см. memory remaster_billing_plan —
 *               сознательно не портируется, как и весь кабинет)
 *   <alltabs>...</alltabs>
 * </table>
 *
 * `{:KEY:}` — токен локализации (`xmlparsers.as:3347-3355`,
 * `game.getLocalNames(KEY).ln`) — здесь НЕ резолвится (оставлен как
 * `labelKey`), резолвится в окне через уже существующий
 * `resolveLocalName()` (`interfaceStrings`), тем же путём, что и
 * остальные интерфейсные строки в этом порте.
 */
import { parseXmlFragment } from '../protocol/response';

export interface EconomicalOverviewRow {
  labelKey: string;
  /** Уже отформатированные сервером строки (разделители тысяч), выравнены по `resources`. */
  values: string[];
}

export interface EconomicalOverviewData {
  resources: string[];
  rows: EconomicalOverviewRow[];
}

export function parseEconomicalOverview(xml: string): EconomicalOverviewData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'table') {
    throw new Error(`Ожидался корневой тег <table>, получено: ${root?.nodeName}`);
  }

  const trs = Array.from(root.children).filter((el) => el.nodeName === 'tr');
  if (trs.length === 0) return { resources: [], rows: [] };

  const resources = Array.from(trs[0].children)
    .filter((el) => el.nodeName === 'img')
    .map((el) => el.textContent?.trim() ?? '');

  const rows: EconomicalOverviewRow[] = [];
  for (const tr of trs.slice(1)) {
    const tds = Array.from(tr.children).filter((el) => el.nodeName === 'td');
    // Строки-заголовки разделов покупки (BUY_RESOURCES/BUY_BONUSES) —
    // только 1 ячейка, не полноценная строка данных, пропускаем.
    if (tds.length !== resources.length + 1) continue;

    const labelRaw = tds[0].textContent?.trim() ?? '';
    const keyMatch = labelRaw.match(/\{:(.+?):\}/);
    const labelKey = keyMatch ? keyMatch[1] : labelRaw;

    rows.push({
      labelKey,
      values: tds.slice(1).map((td) => td.textContent?.trim() ?? ''),
    });
  }

  return { resources, rows };
}

/**
 * `activateall` (`reqvestdata.as:1036-1046`, `activateAllProducers`) —
 * массовая активация/деактивация всех производящих конкретный ресурс
 * зданий на соте сразу (все 3 слоя ячеек, `UA_ActivateAll.class.php`
 * сам крутит `<navigate>`+`<activatebuild>`/`<deactivatebuild>`
 * внутри, клиенту достаточно одной команды). `res=` — НЕ имя ресурса,
 * а числовой `id_attributename` (`ds_attributename`, подтверждено
 * живым запросом к БД 2026-09-16: KADERIUM=1, NANO_CRYSTAL=2,
 * STASIS_ENERGY=3, FOOD=4, PRODIUM=5) — оригинал берёт это число из
 * скомпилированного `.fla`-символа (`tablecreate.as`/`menuLibCode.as`
 * ссылаются на библиотечный клип `table_menu`, исходника которого нет
 * в дереве), поэтому здесь используется независимо подтверждённая по
 * БД таблица соответствия вместо реконструкции недоступного байткода.
 */
export const RESOURCE_ATTRIBUTE_ID: Record<string, number> = {
  KADERIUM: 1,
  NANO_CRYSTAL: 2,
  STASIS_ENERGY: 3,
  FOOD: 4,
  PRODIUM: 5,
};

export function buildActivateAllCommand(resource: string, activate: boolean): Record<string, string> {
  return { res: String(RESOURCE_ATTRIBUTE_ID[resource] ?? ''), switch: activate ? 'on' : 'off' };
}
