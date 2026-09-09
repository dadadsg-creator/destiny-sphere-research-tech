/**
 * Разбор `<warunit>` — панель тренировки боевого юнита (Фаза 3.1,
 * `trainvarunitclass.as`, источник: docs/client-combat-ui.md §6),
 * портировано 1:1 из `xmlparsers.as:396-474` (`trainwarunit_parser`).
 *
 * ВАЖНО (уже задокументировано в §6): характеристики юнита
 * (ATTACK/DEFENCE/HITPOINTS/DAMAGE_MIN/DAMAGE_MAX/название/описание) и
 * цена постройки (K/N/S/F/P/W/C) НЕ приходят живыми атрибутами в этом
 * ответе — они читаются из статической библиотеки `game.getLocalNames()`,
 * т.е. из mark `loaddata` (см. data/localData.ts, `LocalUnit`). Здесь
 * разбираются ТОЛЬКО живые поля этого конкретного ответа: доступность,
 * прогресс науки, текущее производство и содержание юнита за ход
 * (`upkeep` — единственное, что реально приходит живым атрибутом на
 * каждом `<unit>`, в отличие от цены постройки).
 *
 * `allowMax` (сколько максимально можно построить) в оригинале —
 * категория «В», формула ПОДТВЕРЖДЕНА дословно (`xmlparsers.as:396-474`,
 * `trainwarunit_parser`): `game.getMaxNumber()` — глобальная константа,
 * подтверждена как `2 000 000 000` (`game.as:375`, буквально
 * `return 2000000000`), опционально переопределяется живым атрибутом
 * `maxallow` (подтверждено `PN_TrainWarUnit.class.php:243,307` — этот
 * атрибут в реальности шлётся ТОЛЬКО для трёх спец-юнитов захваченной
 * колонии, id 98/99/93, значением "1" или "0" — не общий большой лимит,
 * а фактический потолок в 1 штуку для этих юнитов), затем ужимается по
 * каждому из 6 ресурсов игрока (`Math.floor(current/price)`, отдельно по
 * Kaderium/Nano/Prodium/Food/StasisEnergy/Worker). Цена постройки
 * (`price.kad`/`.nano`/... = `game.getLocalNames(unitname).K/N/S/F/P/W`)
 * — подтверждено `UA_Load.class.php:623`: атрибут на `<unit>` в
 * `loaddata` называется ПЕРВОЙ БУКВОЙ имени ресурса (K/N/S/F/P/W),
 * попадает в `LocalUnit.attrs` (см. `unitBuildPriceFromLocal()` ниже).
 */
import { parseXmlFragment } from '../protocol/response';
import type { LocalUnit } from '../data/localData';

export interface TrainUnitUpkeep {
  kad: number;
  nano: number;
  energy: number;
  food: number;
  prod: number;
  work: number;
}

export interface TrainUnitEntry {
  /** unitname — машинное имя (ключ в LocalData.units / UNIT_NAMES). */
  idName: string;
  /** Числовой id_unit. */
  idUnit: string;
  /** Роль поля не установлена по коду (не описана в client-combat-ui.md §6). */
  type: string | null;
  /** Сырая CSV-строка требуемых уровней наук (до 7 значений) — разделитель не подтверждён кодом этой функции. */
  sctarg: string | null;
  /** Сырая CSV-строка текущих уровней наук — красным подсвечивается там, где sccur < sctarg. */
  sccur: string | null;
  /** "1" — юнит доступен расе прямо сейчас. */
  avail: string | null;
  /** Сколько юнитов сейчас в производстве. */
  inProgress: number;
  endDate: string | null;
  upkeep: TrainUnitUpkeep;
  /** Живой атрибут maxallow — на практике шлётся только для спец-юнитов захваченной колонии (id 98/99/93), см. докстринг файла. */
  maxallow: number | null;
}

export interface TrainUnitData {
  turn: string | null;
  speedup: string | null;
  units: TrainUnitEntry[];
}

export function parseTrainWarUnit(xml: string): TrainUnitData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'warunit') {
    throw new Error(`Ожидался корневой тег <warunit>, получено: ${root?.nodeName}`);
  }

  const units: TrainUnitEntry[] = [];
  for (const el of Array.from(root.children)) {
    if (el.nodeName !== 'unit') continue;
    const maxallowAttr = el.getAttribute('maxallow');
    units.push({
      idName: el.getAttribute('unitname') ?? '',
      idUnit: el.getAttribute('id') ?? '',
      type: el.getAttribute('type'),
      sctarg: el.getAttribute('sctarg'),
      sccur: el.getAttribute('sccur'),
      avail: el.getAttribute('avail'),
      inProgress: Number(el.getAttribute('count')),
      endDate: el.getAttribute('enddate'),
      maxallow: maxallowAttr !== null ? Number(maxallowAttr) : null,
      upkeep: {
        kad: Number(el.getAttribute('kad')) || 0,
        nano: Number(el.getAttribute('nano')) || 0,
        energy: Number(el.getAttribute('energy')) || 0,
        food: Number(el.getAttribute('food')) || 0,
        prod: Number(el.getAttribute('prod')) || 0,
        work: Number(el.getAttribute('work')) || 0,
      },
    });
  }

  return {
    turn: root.getAttribute('turn'),
    speedup: root.getAttribute('speedup'),
    units,
  };
}

/** Однобуквенные ключи цены юнита (game.getLocalNames(unitname), см. data/localData.ts LocalUnit.attrs). */
export interface UnitBuildPrice {
  kad: number;
  nano: number;
  energy: number;
  food: number;
  prod: number;
  work: number;
}

/** game.as:375 — `static function getMaxNumber() { return 2000000000; }`, буквальная константа-"бесконечность". */
export const GLOBAL_MAX_NUMBER = 2_000_000_000;

/** UA_Load.class.php:623 — атрибут цены на `<unit>` в loaddata это первая буква имени ресурса (K/N/S/F/P/W), лежит в LocalUnit.attrs. */
export function unitBuildPriceFromLocal(local: LocalUnit | undefined): UnitBuildPrice {
  const attrs = local?.attrs ?? {};
  return {
    kad: Number(attrs.K) || 0,
    nano: Number(attrs.N) || 0,
    energy: Number(attrs.S) || 0,
    food: Number(attrs.F) || 0,
    prod: Number(attrs.P) || 0,
    work: Number(attrs.W) || 0,
  };
}

export interface PlayerResourcePool {
  kaderium: number;
  nanoCrystal: number;
  prodium: number;
  food: number;
  stasisEnergy: number;
  worker: number;
}

/**
 * Категория «В» из xmlparsers.as:426-449 — сколько максимально можно
 * построить с учётом запаса ресурсов игрока. `maxallowFromServer` — живой
 * атрибут `maxallow`, если сервер его прислал (переопределяет globalMax).
 */
export function computeAllowMax(
  globalMax: number,
  price: UnitBuildPrice,
  resources: PlayerResourcePool,
  maxallowFromServer?: number,
): number {
  let allowMax = maxallowFromServer ?? globalMax;
  allowMax = Math.min(allowMax, price.kad > 0 ? Math.floor(resources.kaderium / price.kad) : allowMax);
  allowMax = Math.min(allowMax, price.nano > 0 ? Math.floor(resources.nanoCrystal / price.nano) : allowMax);
  allowMax = Math.min(allowMax, price.prod > 0 ? Math.floor(resources.prodium / price.prod) : allowMax);
  allowMax = Math.min(allowMax, price.food > 0 ? Math.floor(resources.food / price.food) : allowMax);
  allowMax = Math.min(allowMax, price.energy > 0 ? Math.floor(resources.stasisEnergy / price.energy) : allowMax);
  allowMax = Math.min(allowMax, price.work > 0 ? Math.floor(resources.worker / price.work) : allowMax);
  return allowMax;
}
