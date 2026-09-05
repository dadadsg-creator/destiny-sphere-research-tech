/**
 * Разбор mark `alliancemanagement` — вход/создание/вступление в альянс
 * (Фаза 3.6, `alliance_menu_class.as`, источник:
 * docs/client-economy-social-ui.md §2.1), портировано 1:1 из
 * `xmlparsers.as:556-589` (`alliance_menu_parser`).
 *
 * Одна панель на два состояния (`inal`):
 * <alliancemanagement>
 *   <alliancetojoin id_alliance="" alliancename="" tag="" leader=""/>  (если НЕ в альянсе — список для вступления)
 *   <alliancemember id_alliance="" alliancename="" tag="" religion="" status="" leave=""/>  (если В альянсе — карточка своего)
 * </alliancemanagement>
 *
 * **Найденный дефект протокола оригинала** (не наш): `connectAlliance`
 * строит XML с лишним `>` после `</joinpassword>` перед `</joinalliance>`
 * — реальный баг клиента, не копируется здесь (мы не строим XML-строки,
 * только структурированные команды, см. buildJoinAllianceCommand).
 */
import { parseXmlFragment } from '../protocol/response';

export interface AllianceToJoin {
  id: string | null;
  name: string | null;
  tag: string | null;
  leader: string | null;
}

export interface OwnAlliance {
  id: string | null;
  name: string | null;
  tag: string | null;
  religion: string | null;
  status: string | null;
  /** "1" — можно выйти. */
  leave: string | null;
}

export type AllianceManagementData =
  | { inAlliance: true; own: OwnAlliance }
  | { inAlliance: false; alliancesToJoin: AllianceToJoin[] };

export function parseAllianceManagement(xml: string): AllianceManagementData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'alliancemanagement') {
    throw new Error(`Ожидался корневой тег <alliancemanagement>, получено: ${root?.nodeName}`);
  }

  const alliancesToJoin: AllianceToJoin[] = [];
  let own: OwnAlliance | null = null;

  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'alliancetojoin') {
      alliancesToJoin.push({
        id: el.getAttribute('id_alliance'),
        name: el.getAttribute('alliancename'),
        tag: el.getAttribute('tag'),
        leader: el.getAttribute('leader'),
      });
    } else if (el.nodeName === 'alliancemember') {
      own = {
        id: el.getAttribute('id_alliance'),
        name: el.getAttribute('alliancename'),
        tag: el.getAttribute('tag'),
        religion: el.getAttribute('religion'),
        status: el.getAttribute('status'),
        leave: el.getAttribute('leave'),
      };
    }
  }

  return own ? { inAlliance: true, own } : { inAlliance: false, alliancesToJoin };
}

/**
 * Клиентская валидация оригинала (`alliance_menu_class.as:239-244`,
 * точное совпадение с серверной `VD_Alliance`): имя 3-50 символов, тег
 * 1-8 символов, пароль 3-30 символов.
 */
export function isValidAllianceName(name: string): boolean {
  return name.length >= 3 && name.length <= 50;
}
export function isValidAllianceTag(tag: string): boolean {
  return tag.length >= 1 && tag.length <= 8;
}
export function isValidAlliancePassword(pass: string): boolean {
  return pass.length >= 3 && pass.length <= 30;
}

/** reqvestdata.as:587-608 — <createalliance><name/><tag/><joinpassword/></createalliance>. */
export interface CreateAllianceCommand {
  name: string;
  tag: string;
  joinPassword: string;
}
export function buildCreateAllianceCommand(name: string, tag: string, pass: string): CreateAllianceCommand {
  return { name, tag, joinPassword: pass };
}

/** reqvestdata.as:587-608 — <joinalliance id_alliance=""><joinpassword/></joinalliance>. */
export interface JoinAllianceCommand {
  idAlliance: string;
  joinPassword: string;
}
export function buildJoinAllianceCommand(idAlliance: string, pass: string): JoinAllianceCommand {
  return { idAlliance, joinPassword: pass };
}
