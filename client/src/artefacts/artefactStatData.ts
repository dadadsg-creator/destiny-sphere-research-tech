/**
 * Разбор mark `arthint` — панорамная карта артефактных слотов всей соты
 * (Фаза 3.9, `artefactstatclass.as`, источник:
 * docs/client-economy-social-ui.md §1.4), портировано 1:1 из
 * `xmlparsers.as:477-520` (`artefact_info_menu`).
 *
 * <arthint>
 *   <cell id="" type="" total="">
 *     <art id="" bonus="" id_art="" remain=""/>   (повторяется, по одному на вставленный артефакт)
 *     ...
 *   </cell>
 *   ...
 * </arthint>
 *
 * `remain` — оставшееся время жизни артефакта в секундах; премиум-фича
 * подсветки "скоро истекает" (`glowMinArt()`, xmlparsers.as не участвует,
 * чисто отрисовка) — см. `getExpiryColor()` ниже, пороги дословно из
 * `artefactstatclass.as:659-671`.
 */
import { parseXmlFragment } from '../protocol/response';

export interface ArtInBuild {
  id: string | null;
  /** Уже отформатировано как "NN%", если исходное значение содержало "s" (см. оригинал) — иначе как прислал сервер. */
  bonus: string | null;
  idArt: string | null;
  /** Оставшееся время жизни в секундах. */
  remain: string | null;
}

export interface ArtefactStatCell {
  /** Машинный тип слота — "COMBO", если сервер не прислал (дефолт оригинала). */
  type: string;
  maxSlots: number;
  fullSlots: number;
  artInBuild: ArtInBuild[];
}

/** Ключ — id ячейки (в оригинале — разреженный массив по тому же ключу). */
export type ArtefactStatData = Map<string, ArtefactStatCell>;

export function parseArtefactStat(xml: string): ArtefactStatData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'arthint') {
    throw new Error(`Ожидался корневой тег <arthint>, получено: ${root?.nodeName}`);
  }

  const cells: ArtefactStatData = new Map();
  for (const el of Array.from(root.children)) {
    if (el.nodeName !== 'cell') continue;

    const artInBuild: ArtInBuild[] = [];
    for (const artEl of Array.from(el.children)) {
      if (artEl.nodeName !== 'art') continue;
      let bonus = artEl.getAttribute('bonus');
      if (bonus && bonus.indexOf('s') > 0) {
        bonus = bonus.slice(0, bonus.indexOf('s')) + '%';
      }
      artInBuild.push({
        id: artEl.getAttribute('id'),
        bonus,
        idArt: artEl.getAttribute('id_art'),
        remain: artEl.getAttribute('remain'),
      });
    }

    const id = el.getAttribute('id') ?? '';
    cells.set(id, {
      type: el.getAttribute('type') ?? 'COMBO',
      maxSlots: Number(el.getAttribute('total')) || 0,
      fullSlots: artInBuild.length,
      artInBuild,
    });
  }

  return cells;
}

/** Таблица §1.4 доклада — спрайт ячейки по типу слота. */
export function getArtCellSprite(type: string): string {
  switch (type) {
    case 'STASIS_ENERGY':
      return 'STAT_CELL';
    case 'KADERIUM':
      return 'KAD_CELL';
    case 'NANO_CRYSTAL':
      return 'NANO_CELL';
    case 'FOOD':
      return 'FOOD_CELL';
    case 'PRODIUM':
      return 'PROD_CELL';
    case 'WAR':
      return 'WAR_CELL';
    case 'SOTES':
      return 'SOTA_CELL';
    default:
      return 'SOTA_CELL';
  }
}

export type ExpiryColor = 'green' | 'yellow' | 'red';

/** artefactstatclass.as:659-671 — премиум-фича "скоро истекает", пороги дословно. */
export function getExpiryColor(remainSeconds: number): ExpiryColor {
  if (remainSeconds >= 2_592_000) return 'green';
  if (remainSeconds >= 604_800) return 'yellow';
  return 'red';
}

/** Артефакт с минимальным remain среди вставленных в ячейку — то, что подсвечивается. */
export function findMinRemainArt(cell: ArtefactStatCell): ArtInBuild | null {
  let min: ArtInBuild | null = null;
  for (const art of cell.artInBuild) {
    const remain = Number(art.remain);
    if (Number.isNaN(remain)) continue;
    if (!min || remain < Number(min.remain)) min = art;
  }
  return min;
}

/** reqvestdata.as:191-196 — <getbuildmenu c="" tab="BuildingArtefact"/>. */
export function buildGetArtefactCommand(cellNum: string): { c: string; tab: 'BuildingArtefact' } {
  return { c: cellNum, tab: 'BuildingArtefact' };
}
