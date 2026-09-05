/**
 * Разбор mark `buildingartefact` — артефактные слоты КОНКРЕТНОГО здания
 * (Фаза 3.9, `buildartefactclass.as`, источник:
 * docs/client-economy-social-ui.md §1.6), портировано 1:1 из
 * `xmlparsers.as:1371-1428` (`building_artefact_menu`).
 *
 * <buildingartefact slots="">
 *   <artefact id="" prefix="" scope="" postfix="" [death_message=""]>
 *     <bonus name="" value=""/>   (может повторяться — доступные к вставке, из депо)
 *   </artefact>
 *   <artefact_attach id="" prefix="" scope="" postfix="" [death_message=""]>
 *     <bonus name="" value=""/>   (уже вставленные в это здание)
 *   </artefact_attach>
 *   ...
 * </buildingartefact>
 *
 * **Исправление находки декомпилированного чтения (уже в докладе)**:
 * `insert_artefact(id)`/`delete_artefact(id)` принимают ТОЛЬКО `id`, без
 * номера вкладки — привязка к зданию идёт через `debug.link.active_cell`
 * (атрибут `c`), общий для §1.4 и этого раздела.
 */
import { parseXmlFragment } from '../protocol/response';

export interface ArtefactBonusEntry {
  bonusType: string | null;
  bonusValue: string | null;
}

export interface BuildArtefact {
  id: string | null;
  /** prefix+"_"+scope+"_"+postfix — ключ локализации/ассета. */
  libname: string;
  bonuses: ArtefactBonusEntry[];
  /** Непусто, если у артефакта скоро истекает срок действия. */
  expired: string;
}

export interface BuildArtefactData {
  countSlots: number;
  /** Доступные к вставке (из депо). */
  available: BuildArtefact[];
  /** Уже вставленные в это здание. */
  attached: BuildArtefact[];
}

function parseArtefactEl(el: Element): BuildArtefact {
  const prefix = el.getAttribute('prefix');
  const scope = el.getAttribute('scope');
  const postfix = el.getAttribute('postfix');

  const bonuses: ArtefactBonusEntry[] = Array.from(el.children)
    .filter((child) => child.nodeName === 'bonus')
    .map((child) => ({ bonusType: child.getAttribute('name'), bonusValue: child.getAttribute('value') }));

  return {
    id: el.getAttribute('id'),
    libname: `${prefix}_${scope}_${postfix}`,
    bonuses,
    expired: el.getAttribute('death_message') ?? '',
  };
}

export function parseBuildArtefact(xml: string): BuildArtefactData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'buildingartefact') {
    throw new Error(`Ожидался корневой тег <buildingartefact>, получено: ${root?.nodeName}`);
  }

  const available: BuildArtefact[] = [];
  const attached: BuildArtefact[] = [];

  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'artefact') available.push(parseArtefactEl(el));
    else if (el.nodeName === 'artefact_attach') attached.push(parseArtefactEl(el));
  }

  return { countSlots: Number(root.getAttribute('slots')) || 0, available, attached };
}

/** reqvestdata.as:761-772 — <attachartefact c="" id=""/> / <detachartefact c="" id=""/>. Только id, без номера вкладки. */
export function buildAttachArtefactCommand(id: string): { id: string } {
  return { id };
}
export function buildDetachArtefactCommand(id: string): { id: string } {
  return { id };
}
