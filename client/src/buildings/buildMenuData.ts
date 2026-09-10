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
 * Время постройки — `xmlparsers.as:2592`: `game.turnToTime(game.getLocalNames(id_build).C * xml.attributes.turn)`.
 * `attrs.C` — CONSTRUCTION_TIME здания в турнах (`loaddata`), `turn` —
 * живой атрибут корня `<getbuildmenu turn="">`, УЖЕ в секундах
 * (`PN_AllowBuildings.class.php:278`: `set_attribute("turn",
 * $this->worldSettings->get('TURN_TIME') * 60)`) — доп. `*60` из формулы
 * `docs/mechanics-buildings.md` §4.2 сюда добавлять НЕ нужно, она уже
 * учтена в живом `turn`.
 */
export function buildTimeSeconds(attrs: Record<string, string>, turn: string | null): number {
  const c = Number(attrs.C);
  const t = Number(turn);
  if (!Number.isFinite(c) || !Number.isFinite(t)) return 0;
  return c * t;
}

/**
 * Порт `game.turnToTime()` (`game.as:2537-2598`) — секунды в строку вида
 * "Xд Yч Zм Wс", показывая только ненулевые разряды, от старшего к
 * младшему (подтверждено визуально скриншотом реального Flash-клиента,
 * 2026-09-10: постройка с `C=2` турна на мире со `turn=12000` — "4ч 20м").
 * Суффиксы "д"/"ч"/"м"/"с" — оригинал берёт их из живого `game.getLocalNames
 * ("D_"/"H_"/"M_"/"S_")`, но это НЕ per-building loaddata-атрибут, а
 * отдельная строковая таблица имён, которую порт ещё не разбирает —
 * значения зафиксированы буквально по тому же скриншоту.
 */
export function formatBuildTime(totalSeconds: number): string {
  let turn = Math.floor(totalSeconds);
  if (turn <= 0) return `0с`;

  const sec = turn % 60;
  turn = Math.floor(turn / 60);
  const mins = turn % 60;
  turn = Math.floor(turn / 60);
  const hour = turn % 24;
  turn = Math.floor(turn / 24);
  const days = turn;

  const parts: string[] = [];
  if (days > 0) parts.push(`${days}д`);
  if (hour > 0) parts.push(`${hour}ч`);
  if (mins > 0) parts.push(`${mins}м`);
  if (sec > 0) parts.push(`${sec}с`);
  return parts.join(' ');
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
