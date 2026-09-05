/**
 * Разбор mark `archeology_artefact` — депо неприкреплённых артефактов
 * игрока (Фаза 3.3, `art_depot_menu.as`, источник:
 * docs/client-economy-social-ui.md §1.5), портировано 1:1 из
 * `xmlparsers.as:898-998` (`artefact_depot_menu_parser`).
 *
 * <archeology_artefact>
 *   <type>...</type>   (фильтр по типу — клиент не проверяет имя
 *   <scope>...</scope>  дочерних тегов внутри этих трёх контейнеров,
 *   <size>...</size>    просто читает id/ln/cur у каждого child)
 *   <artefact id="" prefix="" scope="" postfix="" death_message=""
 *             remove="0|1">
 *     <bonus name="" value=""/>   (может повторяться)
 *     <link id="" txt="">hinttext-текст</link>   (опционально)
 *   </artefact>
 *   ...
 * </archeology_artefact>
 *
 * lib_name артефакта собирается как `prefix+"_"+scope+"_"+postfix` —
 * ключ для локализации/ассета (game.getLocalNames). `link` — артефакт
 * активируется отдельной командой (`artActivate`), а не просто пассивный
 * бонус (см. находка в докладе).
 *
 * НЕ портирован скалярный `obj.name`/`obj.bonus` из оригинала — в
 * реальном коде это баг (переменные перезаписываются последним `bonus` в
 * цикле, реальные данные лежат в массиве `bonus_arr`/здесь `bonusArr`) —
 * не имеет смысла копировать явную ошибку без причины.
 */
import { parseXmlFragment } from '../protocol/response';

export interface ArtDepotFilterOption {
  id: string | null;
  ln: string | null;
  cur: boolean;
}

export interface ArtefactBonus {
  /** Локализованное имя атрибута — game.getLocalNames(name).ln, здесь НЕ вычислено, сырой ключ. */
  name: string | null;
  value: string | null;
}

export interface ArtefactLink {
  id: string | null;
  text: string | null;
  hintText: string;
}

export interface ArtDepotEntry {
  id: string | null;
  /** prefix+"_"+scope+"_"+postfix — ключ для локализации/ассета. */
  libName: string;
  prefix: string | null;
  scope: string | null;
  postfix: string | null;
  /** Непусто, если у артефакта скоро истекает срок действия. */
  expired: string;
  removeAllow: boolean;
  bonuses: ArtefactBonus[];
  /** Если задано — артефакт активируемый (одноразовый), не пассивный бонус. */
  link: ArtefactLink | null;
}

export interface ArtDepotFilter {
  ordersType: ArtDepotFilterOption[];
  ordersScope: ArtDepotFilterOption[];
  ordersSize: ArtDepotFilterOption[];
}

function parseFilterOptions(el: Element): ArtDepotFilterOption[] {
  return Array.from(el.children).map((child) => ({
    id: child.getAttribute('id'),
    ln: child.getAttribute('ln'),
    cur: child.getAttribute('cur') === '1',
  }));
}

export function parseArtDepot(xml: string): { entries: ArtDepotEntry[]; filter: ArtDepotFilter } {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'archeology_artefact') {
    throw new Error(`Ожидался корневой тег <archeology_artefact>, получено: ${root?.nodeName}`);
  }

  const filter: ArtDepotFilter = { ordersType: [], ordersScope: [], ordersSize: [] };
  const entries: ArtDepotEntry[] = [];

  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'type') filter.ordersType = parseFilterOptions(el);
    else if (el.nodeName === 'scope') filter.ordersScope = parseFilterOptions(el);
    else if (el.nodeName === 'size') filter.ordersSize = parseFilterOptions(el);
    else if (el.nodeName === 'artefact') {
      const prefix = el.getAttribute('prefix');
      const scope = el.getAttribute('scope');
      const postfix = el.getAttribute('postfix');

      const bonuses: ArtefactBonus[] = [];
      let link: ArtefactLink | null = null;
      for (const child of Array.from(el.children)) {
        if (child.nodeName === 'bonus') {
          bonuses.push({ name: child.getAttribute('name'), value: child.getAttribute('value') });
        } else if (child.nodeName === 'link') {
          link = {
            id: child.getAttribute('id'),
            text: child.getAttribute('txt'),
            hintText: child.textContent ?? '',
          };
        }
      }

      entries.push({
        id: el.getAttribute('id'),
        libName: `${prefix}_${scope}_${postfix}`,
        prefix,
        scope,
        postfix,
        expired: el.getAttribute('death_message') ?? '',
        removeAllow: el.getAttribute('remove') !== '0',
        bonuses,
        link,
      });
    }
  }

  return { entries, filter };
}
