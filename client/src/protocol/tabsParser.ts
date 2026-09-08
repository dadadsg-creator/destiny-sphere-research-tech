/**
 * Разбор `<alltabs><tab id="" ln="" cur="0|1" icon="" nc=""/>...</alltabs>`
 * — общий для ВСЕХ доменов, диспетчеризуемых через `getbuildmenu`
 * (см. protocol-client-server.md §5.4: `PN_BuildingInfo`/`PN_TradePanel`/
 * и т.п., все возвращают один и тот же список вкладок текущего "гнезда"
 * окон). Раньше не выделялся отдельно — каждый доменный `*Data.ts`
 * сознательно не парсил `<alltabs>` сам (см. buildInfoData.ts докстринг),
 * тестирование шло на синтетических `TabDef[]`. Нужен для «Сшивания»
 * (2026-09-08) — единый разбор вместо копирования в каждый домен.
 *
 * **Важно**: `tab.id` в ответе сервера приходит С префиксом класса
 * (`PN_BuildingInfo`, `PN_BuildingArtefact`, ...) — подтверждено живым
 * тестом 2026-09-08 (Фаза 4). Для повторного запроса этот id нужно
 * превратить обратно в короткое имя таба (`BuildingInfo`) — см.
 * `stripTabPrefix()`.
 */
import type { TabDef } from '../ui/tabs';

export function parseTabsArray(root: Element): TabDef[] {
  const alltabs = Array.from(root.children).find((el) => el.nodeName === 'alltabs');
  if (!alltabs) return [];

  const tabs: TabDef[] = [];
  for (const el of Array.from(alltabs.children)) {
    if (el.nodeName !== 'tab') continue;
    tabs.push({
      id: el.getAttribute('id') ?? '',
      ln: el.getAttribute('ln') ?? '',
      cur: el.getAttribute('cur') === '1',
      icon: el.getAttribute('icon') ?? undefined,
      nc: el.getAttribute('nc') ?? undefined,
    });
  }
  return tabs;
}

/** PN_BuildingInfo -> BuildingInfo (см. докстринг файла). */
export function stripTabPrefix(tabId: string): string {
  return tabId.replace(/^(PN_|TI_|TL_)/, '');
}
