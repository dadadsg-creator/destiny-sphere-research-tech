/**
 * Разбор mark `allianceprivatepeace` — приватный (личный) мир (Фаза 3.6,
 * `peace_private.as`, источник: docs/client-economy-social-ui.md §2.5),
 * портировано 1:1 из `xmlparsers.as:610-654`
 * (`allianceprivatepeace_menu_parser`).
 *
 * <allianceprivatepeace>
 *   <request id_alliance="" tag="" member="" UID=""/>   (ждут ответа на СВОЁ предложение)
 *   <list id_alliance="" tag="" member="" joined="0|1" UID=""/>   (уже заключённые/входящие)
 *   ...
 * </allianceprivatepeace>
 *
 * Одна универсальная команда `getPPeace(targetName, UID, reason)` —
 * `reason` дословно совпадает с `UA_PrivatePeace` (см. §2.5).
 * **Забавная находка оригинала**: при первой заявке (UID получателя ещё
 * не известен) вторым параметром передаётся буквальный
 * литерал-заглушка `"DANONMUSTDIE"` — сохранено здесь как константа
 * `UNKNOWN_UID_PLACEHOLDER`, ради буквальной верности протоколу (не
 * баг, который стоит исправлять — сервер, видимо, ожидает эту строку
 * именно для этого случая).
 */
import { parseXmlFragment } from '../protocol/response';

export interface PrivatePeaceRequest {
  idAlliance: string | null;
  tag: string | null;
  member: string | null;
  uid: string | null;
}

export interface PrivatePeaceMember {
  idAlliance: string | null;
  tag: string | null;
  member: string | null;
  /** "1" — сторона уже согласилась (мир установлен), "0" — ждём ответа от них. */
  joined: string | null;
  uid: string | null;
}

export interface PrivatePeaceData {
  /** Заявки, ожидающие ответа на НАШЕ предложение. */
  requests: PrivatePeaceRequest[];
  /** Установленные связи мира + входящие предложения. */
  members: PrivatePeaceMember[];
}

export function parsePrivatePeace(xml: string): PrivatePeaceData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'allianceprivatepeace') {
    throw new Error(`Ожидался корневой тег <allianceprivatepeace>, получено: ${root?.nodeName}`);
  }

  const requests: PrivatePeaceRequest[] = [];
  const members: PrivatePeaceMember[] = [];

  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'request') {
      requests.push({
        idAlliance: el.getAttribute('id_alliance'),
        tag: el.getAttribute('tag'),
        member: el.getAttribute('member'),
        uid: el.getAttribute('UID'),
      });
    } else if (el.nodeName === 'list') {
      members.push({
        idAlliance: el.getAttribute('id_alliance'),
        tag: el.getAttribute('tag'),
        member: el.getAttribute('member'),
        joined: el.getAttribute('joined'),
        uid: el.getAttribute('UID'),
      });
    }
  }

  return { requests, members };
}

export type PrivatePeaceReason =
  | 'PRIVATEPEACE_REQUEST'
  | 'PEACE_CONFIRMATION'
  | 'PEACE_REJECTING'
  | 'LIST_REQUEST_CANCEL_REQUEST'
  | 'LIST_REQUEST_REJECT_PEACE';

/** peace_private.as:63 — placeholder, когда UID получателя ещё не известен клиенту (первая заявка по нику). */
export const UNKNOWN_UID_PLACEHOLDER = 'DANONMUSTDIE';

export interface GetPPeaceCommand {
  targetName: string;
  uid: string;
  reason: PrivatePeaceReason;
}

export function buildGetPPeaceCommand(targetName: string, uid: string | null, reason: PrivatePeaceReason): GetPPeaceCommand {
  return { targetName, uid: uid ?? UNKNOWN_UID_PLACEHOLDER, reason };
}
