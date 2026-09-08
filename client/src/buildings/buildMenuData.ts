/**
 * Разбор mark `getbuildmenu` — список зданий, доступных к постройке на
 * выбранной пустой ячейке (Фаза 3.3, `build_menu.as`, источник:
 * docs/client-economy-social-ui.md §1.7), портировано 1:1 из
 * `xmlparsers.as:2577-2627` (`ParseListBuild`).
 *
 * <getbuildmenu turn="">
 *   <b id="" s="0|1" SC="" GC="" alwaisallow="0|1"/>
 *   ...
 * </getbuildmenu>
 *
 * ВАЖНО (как и в других доменах): название/описание/цена в ресурсах
 * (K/N/S/F/P/W/C) НЕ приходят живыми атрибутами — из `loaddata`
 * (`data/localData.ts`, `LocalBuilding.attrs`). Живые поля — только
 * `s` (0 — сервер запрещает саму постройку, независимо от ресурсов),
 * `SC`/`GC` (денежная цена — премиум-валюты, приходят живым атрибутом
 * ИМЕННО здесь, в отличие от K/N/S/F/P/W) и `alwaisallow` (переопределяет
 * проверку ресурсов, всегда разрешает постройку).
 */
import { parseXmlFragment } from '../protocol/response';
import type { LocalBuilding } from '../data/localData';
import { combResCurrent, type CombRes } from '../data/combResData';

export interface BuildMenuEntry {
  id: number;
  /** Сервер запрещает саму постройку (не связано с нехваткой ресурсов). */
  serverAllowed: boolean;
  /** Денежная цена в GOLD_CREDIT/SILVER_CREDIT — живые атрибуты, 0 если сервер не прислал. */
  goldCredit: number;
  silverCredit: number;
  /** Переопределяет проверку ресурсов игрока — постройка всегда разрешена. */
  alwaysAllow: boolean;
}

export function parseListBuild(xml: string): BuildMenuEntry[] {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'getbuildmenu') {
    throw new Error(`Ожидался корневой тег <getbuildmenu>, получено: ${root?.nodeName}`);
  }

  const entries: BuildMenuEntry[] = [];
  for (const el of Array.from(root.children)) {
    if (el.nodeName !== 'b') continue;
    const gc = Number(el.getAttribute('GC'));
    const sc = Number(el.getAttribute('SC'));
    entries.push({
      id: Number(el.getAttribute('id')),
      serverAllowed: el.getAttribute('s') !== '0',
      goldCredit: Number.isNaN(gc) ? 0 : gc,
      silverCredit: Number.isNaN(sc) ? 0 : sc,
      alwaysAllow: el.getAttribute('alwaisallow') === '1',
    });
  }
  return entries;
}

export interface PlayerResourcePoolFull {
  kaderium: number;
  nanoCrystal: number;
  stasisEnergy: number;
  food: number;
  prodium: number;
  worker: number;
  silverCredit: number;
  goldCredit: number;
}

/** Марка `combres` (см. combResData.ts) → форма, которую ждёт computeBuildAllowed()/BuildMenuWindow. */
export function combResToPlayerPool(res: CombRes): PlayerResourcePoolFull {
  return {
    kaderium: combResCurrent(res, 'KADERIUM'),
    nanoCrystal: combResCurrent(res, 'NANO_CRYSTAL'),
    stasisEnergy: combResCurrent(res, 'STASIS_ENERGY'),
    food: combResCurrent(res, 'FOOD'),
    prodium: combResCurrent(res, 'PRODIUM'),
    worker: combResCurrent(res, 'WORKER'),
    silverCredit: combResCurrent(res, 'SILVER_CREDIT'),
    goldCredit: combResCurrent(res, 'GOLD_CREDIT'),
  };
}

/**
 * Категория «В» — итоговая доступность постройки для игрока
 * (`xmlparsers.as:2606-2620`): запрещена, если сервер сказал `s==0`,
 * ИЛИ если игроку не хватает хотя бы одного ресурса из его цены (K/N/S/F/
 * P/W/SC/GC) — если только `alwaysAllow` не переопределяет обе проверки
 * (в оригинале `alwaisallow` проверяется ПОСЛЕДНИМ и красит `allow=true`
 * безусловно, даже поверх `s==0`).
 */
export function computeBuildAllowed(
  entry: BuildMenuEntry,
  local: LocalBuilding | undefined,
  resources: PlayerResourcePoolFull,
): boolean {
  if (entry.alwaysAllow) return true;
  if (!entry.serverAllowed) return false;

  const attrs = local?.attrs ?? {};
  const need = (key: string) => Number(attrs[key]);

  if (!Number.isNaN(need('K')) && need('K') > resources.kaderium) return false;
  if (!Number.isNaN(need('N')) && need('N') > resources.nanoCrystal) return false;
  if (!Number.isNaN(need('S')) && need('S') > resources.stasisEnergy) return false;
  if (!Number.isNaN(need('F')) && need('F') > resources.food) return false;
  if (!Number.isNaN(need('P')) && need('P') > resources.prodium) return false;
  if (!Number.isNaN(need('W')) && need('W') > resources.worker) return false;
  if (entry.silverCredit > resources.silverCredit) return false;
  if (entry.goldCredit > resources.goldCredit) return false;

  return true;
}
