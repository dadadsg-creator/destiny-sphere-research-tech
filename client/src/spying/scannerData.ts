/**
 * Разбор mark `spylog` — журнал сканирования/разведдействий (Фаза 3.2,
 * `scannermenuclass.as`, источник: docs/client-combat-ui.md §9),
 * портировано 1:1 из `xmlparsers.as:34-118` (`logs_zonds_menu`).
 *
 * <spylog filtermenu="0|1"> (filtermenu — прячет ли клиент блок фильтров)
 *   <object id="" action="" addrfrom="" addrto="" date="" typelog=""
 *           zname="" zcolor=""/>
 *   <filter> (атрибут maxzond читается с КОРНЯ <spylog>, не с <filter>!)
 *     <types><type id="" name="" cur="0|1"/>...</types>
 *     <name def=""/>
 *     <time val="" min="" max=""/>
 *     <colonys><colony id="" name="" addr="" cur="0|1"/>...</colonys>
 *   </filter>
 * </spylog>
 *
 * `obj.data` в клиентском коде проверяется на undefined, но нигде не
 * присваивается из XML — похоже на мёртвый остаток более ранней версии,
 * сюда не перенесено.
 *
 * **Фильтр (2026-09-18)** — данные (`SpyLogFilter`) парсились уже давно,
 * форма не была отрисована. Команда — `reqvestdata.filterScanner()`
 * (`scannermenuclass.as:227-251`, `reqvestdata.as:73-83`):
 * `<getbuildmenu {c} tab="PN_SpyLogs" [name=""] colony="id1,id2,..."
 * types="id1,id2,..." datefrom="" dateto=""/>` — `tab` здесь буквально
 * `"PN_SpyLogs"` С префиксом (не через общий `stripTabPrefix()`
 * механизм переключения вкладок — это отдельная, специально
 * закодированная команда фильтра); `name` уходит только если длина ≥3
 * символа (`namestr.length >= 3`, строка 76). `colony`/`types` — csv
 * отмеченных id (AS2 `.toString()` на массиве = join(",")).
 * `datefrom`/`dateto` — секунды (`дни*86400+часы*3600+мин*60+сек`,
 * строки 248-249) в диапазоне `filter.time.min..max`; **точная
 * семантика этого диапазона не установлена** (не определено, что именно
 * измеряет ось времени — абсолютный момент или сдвиг "назад от сейчас"),
 * `from` в оригинале по умолчанию 0, `to` — `filter.time.cur`.
 */
import { parseXmlFragment } from '../protocol/response';

export interface SpyLogEntry {
  id: string | null;
  action: string | null;
  addrFrom: string | null;
  addrTo: string | null;
  date: string | null;
  typelog: string | null;
  zname: string | null;
  zcolor: string | null;
}

export interface SpyLogFilterOption {
  id: string | null;
  name: string | null;
  active: boolean;
}

export interface SpyLogColonyOption extends SpyLogFilterOption {
  addr: string | null;
}

export interface SpyLogFilter {
  maxAllowZond: string | null;
  typesMove: SpyLogFilterOption[];
  colonys: SpyLogColonyOption[];
  defName: string | null;
  time: { cur: string | null; min: string | null; max: string | null };
}

export interface SpyLogData {
  /** false — клиент прячет блок фильтров (filtermenu=="0" на корне). */
  showFiltersMenu: boolean;
  entries: SpyLogEntry[];
  filter: SpyLogFilter | null;
}

export function parseSpyLog(xml: string): SpyLogData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'spylog') {
    throw new Error(`Ожидался корневой тег <spylog>, получено: ${root?.nodeName}`);
  }

  const entries: SpyLogEntry[] = [];
  let filter: SpyLogFilter | null = null;

  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'object') {
      entries.push({
        id: el.getAttribute('id'),
        action: el.getAttribute('action'),
        addrFrom: el.getAttribute('addrfrom'),
        addrTo: el.getAttribute('addrto'),
        date: el.getAttribute('date'),
        typelog: el.getAttribute('typelog'),
        zname: el.getAttribute('zname'),
        zcolor: el.getAttribute('zcolor'),
      });
    } else if (el.nodeName === 'filter') {
      filter = {
        maxAllowZond: root.getAttribute('maxzond'),
        typesMove: [],
        colonys: [],
        defName: null,
        time: { cur: null, min: null, max: null },
      };

      for (const filterChild of Array.from(el.children)) {
        if (filterChild.nodeName === 'types') {
          for (const typeEl of Array.from(filterChild.children)) {
            filter.typesMove.push({
              id: typeEl.getAttribute('id'),
              name: typeEl.getAttribute('name'),
              active: typeEl.getAttribute('cur') === '1',
            });
          }
        } else if (filterChild.nodeName === 'name') {
          filter.defName = filterChild.getAttribute('def');
        } else if (filterChild.nodeName === 'time') {
          filter.time = {
            cur: filterChild.getAttribute('val'),
            min: filterChild.getAttribute('min'),
            max: filterChild.getAttribute('max'),
          };
        } else if (filterChild.nodeName === 'colonys') {
          for (const colonyEl of Array.from(filterChild.children)) {
            filter.colonys.push({
              id: colonyEl.getAttribute('id'),
              name: colonyEl.getAttribute('name'),
              addr: colonyEl.getAttribute('addr'),
              active: colonyEl.getAttribute('cur') === '1',
            });
          }
        }
      }
    }
  }

  return {
    showFiltersMenu: root.getAttribute('filtermenu') !== '0',
    entries,
    filter,
  };
}

/** reqvestdata.as:73-83 (filterScanner) — атрибуты `<getbuildmenu tab="PN_SpyLogs" .../>`, без самого тега (см. dispatch.tsx). */
export interface ScannerFilterCommand {
  name: string;
  colonyIds: string[];
  typeIds: string[];
  dateFrom: number;
  dateTo: number;
}

export function buildScannerFilterCommand(cmd: ScannerFilterCommand): Record<string, string | number> {
  const attrs: Record<string, string | number> = {
    colony: cmd.colonyIds.join(','),
    types: cmd.typeIds.join(','),
    datefrom: cmd.dateFrom,
    dateto: cmd.dateTo,
  };
  if (cmd.name.length >= 3) attrs.name = cmd.name;
  return attrs;
}
