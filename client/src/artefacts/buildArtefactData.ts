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
 *
 * **Живой баг оригинала, сознательно исправленный в ремастере (2026-09-09)**:
 * `bonus.value` приходит буквальными символами `%2B`/`%25` вместо `+`/`%`
 * (подтверждено И живым перехватом ответа, И `PN_BuildingArtefact.
 * class.php:164-174` — сервер строит `"%2B".$bonus` и, для BONUS_RELATIVE,
 * добавляет суффикс `"%25"`). Оригинальный AS2-клиент (`xmlparsers.as:
 * 1396,1418`, `obj2.bonus_value = ....attributes.value`) читает этот
 * атрибут БЕЗ какой-либо распаковки — то есть баг исторический, виден и в
 * оригинале, но для нового клиента показывать игроку сырой escape-код
 * вместо "+800" не имеет смысла — см. `fixBonusPercent()`.
 *
 * **Картинка артефакта — три слоя, не одна (найдено декомпиляцией
 * `art_lib.swf`/`artefact.lib.swf`, `frame_1/DoAction.as:createArtefact`,
 * 2026-09-09)**: `art_clear` (круглый тёмный фон слота) снизу,
 * ПОВЕРХ — форма-маска по `scope` (`SCOPE_MOV`, красится сплошным цветом
 * по `prefix`/размеру через `Color.setRGB()` — таблица цветов
 * `resourceIcons.ts:ARTEFACT_SIZE_COLOR`, воспроизведена CSS `mask-image`
 * вместо честного Flash Color.setRGB), и НА САМОМ ВЕРХУ — сама иконка
 * (`TYPE_MOV`, по `postfix`). Раньше портировался только 1 слой (иконка) +
 * фон слота, без цветной scope-рамки — отсюда жалоба владельца
 * "обводка не та".
 */
import { parseXmlFragment } from '../protocol/response';

export interface ArtefactBonusEntry {
  bonusType: string | null;
  /** Уже показываемая строка ("+800"/"+50%"), не сырое "%2B800"/"%2B50%25" — см. докстринг файла. */
  bonusValue: string | null;
}

export interface BuildArtefact {
  id: string | null;
  /** prefix+"_"+scope+"_"+postfix — ключ локализации в LocalData.artefacts. */
  libname: string;
  /** Размер/редкость (MIGHTY, SMALL, ...) — красит рамку scope, см. resourceIcons.ts:ARTEFACT_SIZE_COLOR. */
  prefix: string | null;
  /** "Область" (SPHERE, WAR, BUILDING, ...) — форма цветной рамки, resourceIcons.ts:artefactScopeMaskUrl. */
  scope: string | null;
  /** Базовый вид артефакта — ключ иконки (LocalData.artefacts не всегда доступна/заполнена по этому конкретному id, иконка привязана к postfix напрямую, см. resourceIcons.ts:artefactIconUrl). */
  postfix: string | null;
  bonuses: ArtefactBonusEntry[];
  /** Непусто, если у артефакта скоро истекает срок действия. */
  expired: string;
}

/** "%2B800" -> "+800", "%2B50%25" -> "+50%" — см. докстринг файла. */
function fixBonusPercent(value: string | null): string | null {
  if (value === null) return null;
  return value.replace(/^%2B/, '+').replace(/%25$/, '%');
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
    .map((child) => ({ bonusType: child.getAttribute('name'), bonusValue: fixBonusPercent(child.getAttribute('value')) }));

  return {
    id: el.getAttribute('id'),
    libname: `${prefix}_${scope}_${postfix}`,
    prefix,
    scope,
    postfix,
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
