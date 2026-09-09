/**
 * Разбор `<warcomplex>` — пакетное производство юнитов на "фабриках"
 * военной базы (Фаза 3.1, `warstatistic.as`, источник:
 * docs/client-combat-ui.md §5), портировано 1:1 из
 * `xmlparsers.as:324-394` (`war_complex_menu`).
 *
 * Формат:
 * <warcomplex turn="" speedup="0|1">
 *   <unit id="" unitname="" type="" kad="" nano="" food="" energy=""
 *         prod="" maxallow=""/>   (может повторяться с разным type для
 *                                  одного unitname — сервер шлёт по одной
 *                                  записи на каждую пару юнит×тип фабрики,
 *                                  клиент схлопывает их в один unit_array[j]
 *                                  с массивом type[])
 *   <factory id_cell="" type="" status="0|1" unit="" count="" timeend=""/>
 *   ...
 * </warcomplex>
 *
 * ВАЖНО (как и в trainUnitData.ts): цена постройки (K/N/F/S/P/W/C) НЕ
 * приходит живым атрибутом здесь вообще — берётся из `loaddata`
 * (`game.getLocalNames(unitname).K/...`, см. data/localData.ts,
 * `unitBuildPriceFromLocal()`). Из живого `<unit>` читаются ТОЛЬКО
 * содержание (`kad`/`nano`/`food`/`energy`/`prod` — заметьте другие
 * имена атрибутов, не K/N/F/S/P!) и `maxallow`. Переиспользует
 * `computeAllowMax()` из trainUnitData.ts — та же формула (категория
 * «В», подключена в WarStatisticWindow.tsx через `combres`).
 */
import { parseXmlFragment } from '../protocol/response';

export interface FactoryUnit {
  id: string;
  unitname: string;
  /** Типы "фабрик", на которых можно строить этот юнит (см. комментарий выше про схлопывание). */
  types: string[];
  upkeep: { kad: number; nano: number; food: number; energy: number; prod: number };
  /** Живой атрибут maxallow, если сервер его прислал — иначе не задан (глобальный дефолт 2e9 — см. trainUnitData.ts computeAllowMax). */
  maxallow: number | null;
}

export interface Factory {
  idCell: string;
  /** Тип фабрики (какие юниты может производить — сверяется с FactoryUnit.types). */
  type: string | null;
  /** true — фабрика занята. */
  busy: boolean;
  /** unitname производимого юнита, если фабрика занята. */
  unit: string | null;
  count: number;
  timeEnd: string | null;
}

export interface WarComplexData {
  turn: string | null;
  speedup: string | null;
  units: FactoryUnit[];
  /** Ключ — id_cell (в оригинале — разреженный массив по тому же ключу). */
  factories: Map<string, Factory>;
}

export function parseWarComplex(xml: string): WarComplexData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'warcomplex') {
    throw new Error(`Ожидался корневой тег <warcomplex>, получено: ${root?.nodeName}`);
  }

  const unitsByName = new Map<string, FactoryUnit>();
  const factories = new Map<string, Factory>();

  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'unit') {
      const unitname = el.getAttribute('unitname') ?? '';
      const type = el.getAttribute('type') ?? '';
      let unit = unitsByName.get(unitname);
      if (!unit) {
        const maxallowAttr = el.getAttribute('maxallow');
        unit = {
          id: el.getAttribute('id') ?? '',
          unitname,
          types: [],
          upkeep: {
            kad: Number(el.getAttribute('kad')) || 0,
            nano: Number(el.getAttribute('nano')) || 0,
            food: Number(el.getAttribute('food')) || 0,
            energy: Number(el.getAttribute('energy')) || 0,
            prod: Number(el.getAttribute('prod')) || 0,
          },
          maxallow: maxallowAttr !== null ? Number(maxallowAttr) : null,
        };
        unitsByName.set(unitname, unit);
      }
      unit.types.push(type);
    } else if (el.nodeName === 'factory') {
      const idCell = el.getAttribute('id_cell') ?? '';
      factories.set(idCell, {
        idCell,
        type: el.getAttribute('type'),
        busy: Number(el.getAttribute('status')) === 1,
        unit: el.getAttribute('unit'),
        count: Number(el.getAttribute('count')) || 0,
        timeEnd: el.getAttribute('timeend'),
      });
    }
  }

  return {
    turn: root.getAttribute('turn'),
    speedup: root.getAttribute('speedup'),
    units: [...unitsByName.values()],
    factories,
  };
}
