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
 *
 * **Реальный баг живого сервера (найден 2026-09-18 живым дампом)**: для
 * ячеек с конкретным типом сервер шлёт атрибут `type` ДВАЖДЫ на одном
 * теге — `<cell id="3" total="2" type="STASIS_ENERGY" type="COMBO">`
 * (второе значение — тот же дефолт "COMBO", что клиент и сам подставил
 * бы при отсутствии атрибута, `xmlparsers.as:491`, но сервер зачем-то
 * пишет его ЯВНО рядом с настоящим типом). AS2 `XML`-класс оригинала
 * исторически терпим к такому и просто использует последнее значение —
 * строгий браузерный `DOMParser('text/xml')` вместо этого молча
 * возвращает пустой результат на всём документе (не бросает исключение,
 * просто ноль дочерних `<cell>`). Правка на живом PHP не в рамках этого
 * порта (не трогаем `old-sphere/` без явной просьбы) — `dedupeCellType()`
 * ниже убирает вторую копию перед парсингом, тот же класс обхода, что и
 * другие уже задокументированные особенности протокола этой сессии.
 */
import { parseXmlFragment } from '../protocol/response';

/** См. докстринг файла — обход бага сервера (дублирующийся атрибут `type`). */
function dedupeCellType(xml: string): string {
  return xml.replace(/<cell([^>]*)>/g, (_whole, attrs: string) => {
    let seenType = false;
    const cleaned = attrs.replace(/\s+type="[^"]*"/g, (match: string) => {
      if (seenType) return '';
      seenType = true;
      return match;
    });
    return `<cell${cleaned}>`;
  });
}

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
  const doc = parseXmlFragment(dedupeCellType(xml));
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

/**
 * Цвет ячейки на карте по типу здания — свой оттенок на каждый
 * ресурс, тот же смысл, что и разные спрайты `*_CELL` в оригинале
 * (`getArtCellSprite` выше), но как заливка гекса на живой карте, а не
 * отдельный спрайт схематичной сетки.
 */
export function getArtCellColor(type: string): number {
  switch (type) {
    case 'STASIS_ENERGY':
      return 0x29b6c6;
    case 'KADERIUM':
      return 0xc9a227;
    case 'NANO_CRYSTAL':
      return 0x3b6fd6;
    case 'FOOD':
      return 0x4caf50;
    case 'PRODIUM':
      return 0xc07830;
    case 'WAR':
      return 0xef5350;
    default:
      return 0x8092c8;
  }
}

/**
 * Типы, для которых оригинал ищет "самое слабое" здание (`glowMinArt`,
 * `artefactstatclass.as:621-629`, явная проверка именно этих 5 имён) —
 * WAR/SOTES(COMBO) сознательно не участвуют.
 */
const WEAKEST_HIGHLIGHT_TYPES = new Set(['STASIS_ENERGY', 'KADERIUM', 'NANO_CRYSTAL', 'FOOD', 'PRODIUM']);

/**
 * Ячейки с "самым слабым" (наименьший бонус ОДНОГО артефакта, не суммой)
 * зданием каждого типа — `artefactstatclass.as:590-619`, дословно та же
 * формула (глобальный минимум по всем (ячейка,арт) парам, сгруппированный
 * по типу здания, не по ячейке). Подсказывает, куда выгоднее всего
 * поставить новый/более сильный артефакт.
 */
export function findWeakestCellsPerType(data: ArtefactStatData): Set<string> {
  const minByType = new Map<string, { cellId: string; bonus: number }>();
  for (const [cellId, cell] of data) {
    if (!WEAKEST_HIGHLIGHT_TYPES.has(cell.type)) continue;
    for (const art of cell.artInBuild) {
      const bonus = parseFloat(art.bonus ?? '');
      if (Number.isNaN(bonus)) continue;
      const cur = minByType.get(cell.type);
      if (!cur || bonus < cur.bonus) minByType.set(cell.type, { cellId, bonus });
    }
  }
  return new Set([...minByType.values()].map((v) => v.cellId));
}

/** reqvestdata.as:191-196 — <getbuildmenu c="" tab="BuildingArtefact"/>. */
export function buildGetArtefactCommand(cellNum: string): { c: string; tab: 'BuildingArtefact' } {
  return { c: cellNum, tab: 'BuildingArtefact' };
}
