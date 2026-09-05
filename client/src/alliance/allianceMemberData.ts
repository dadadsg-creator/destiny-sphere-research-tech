/**
 * Разбор mark `allianceoverview` — список участников альянса (Фаза 3.6,
 * `alliancememberclass.as`, источник: docs/client-economy-social-ui.md
 * §2.2), портировано 1:1 из `xmlparsers.as:526-555`
 * (`allianceoverview_menu_parser`).
 *
 * <allianceoverview curpage="" pages="" leader="0|1" id_alliance="">
 *   <member name="" id_userinstance="" ranking="" coord="" artefact=""
 *           lstatus="" status="" delete_button_disabled="0|1"/>
 *   ...
 * </allianceoverview>
 *
 * Кнопка «Кикнуть» видна только если (буквально из оригинала): текущий
 * игрок лидер (`leader=="1"`) И цель не сам лидер (`status!="LEADER"`) И
 * `delete_button_disabled` не установлен — см. `canKick()` ниже.
 * `delete_button_disabled` — не расшифровано, что именно выставляет этот
 * флаг на сервере (открытый вопрос доклада, возможно защита участников
 * глобального альянса).
 */
import { parseXmlFragment } from '../protocol/response';

export interface AllianceMember {
  name: string | null;
  userId: string | null;
  ranking: string | null;
  coord: string | null;
  artefact: string | null;
  statusLn: string | null;
  status: string | null;
  deleteButtonDisabled: boolean;
}

export interface AllianceOverviewData {
  curPage: string | null;
  maxPage: string | null;
  /** "1" — текущий игрок лидер альянса. */
  leader: string | null;
  idAlliance: string | null;
  members: AllianceMember[];
}

export function parseAllianceOverview(xml: string): AllianceOverviewData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'allianceoverview') {
    throw new Error(`Ожидался корневой тег <allianceoverview>, получено: ${root?.nodeName}`);
  }

  const members: AllianceMember[] = [];
  for (const el of Array.from(root.children)) {
    if (el.nodeName !== 'member') continue;
    members.push({
      name: el.getAttribute('name'),
      userId: el.getAttribute('id_userinstance'),
      ranking: el.getAttribute('ranking'),
      coord: el.getAttribute('coord'),
      artefact: el.getAttribute('artefact'),
      statusLn: el.getAttribute('lstatus'),
      status: el.getAttribute('status'),
      deleteButtonDisabled: el.getAttribute('delete_button_disabled') === '1',
    });
  }

  return {
    curPage: root.getAttribute('curpage'),
    maxPage: root.getAttribute('pages'),
    leader: root.getAttribute('leader'),
    idAlliance: root.getAttribute('id_alliance'),
    members,
  };
}

export function canKick(data: AllianceOverviewData, member: AllianceMember): boolean {
  return data.leader === '1' && member.status !== 'LEADER' && !member.deleteButtonDisabled;
}

/** reqvestdata.as:906-910 — <expelalliance id_alliance="" id_userinstance=""/>. */
export interface KickCommand {
  idAlliance: string;
  idUserinstance: string;
}
export function buildKickCommand(idAlliance: string, userId: string): KickCommand {
  return { idAlliance, idUserinstance: userId };
}
