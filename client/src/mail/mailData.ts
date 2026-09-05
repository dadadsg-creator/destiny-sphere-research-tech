/**
 * Разбор marks `messageoverview` (список писем) и `message` (тело одного
 * письма) — Фаза 3.11, `mailmenu.as`, источник: docs/client-economy-social-ui.md
 * §4.5. Портировано 1:1 из `xmlparsers.as:2990-3016` (`mailListParser`,
 * mark `messageoverview`, диспетчер `parser.as:1533-1535`) и
 * `xmlparsers.as:3063-3130` (`messageParser`, mark `message`,
 * `parser.as:1536-1538`). Запросы подтверждены `reqvestdata.as:380-397,
 * 508-534`.
 *
 * **Кодирование текста письма**: тема (в списке) и тело (при открытии
 * письма) передаются НЕ как обычный текст — каждый символ кодируется как
 * ТРИ шестнадцатеричных символа (старший/средний/младший полубайт кода
 * символа, `xmlparsers.as:3018-3061` `decodeMailMessage` / зеркальная
 * `game.as:2655-2690` `game.ENCRYPTTEXT`, используется реально при отправке
 * — `reqvestdata.as:500-501`). Если строка не раскодировалась триплетами
 * без остатка — считается уже обычным текстом (обратная совместимость со
 * старыми письмами, `key_check == "old"`).
 *
 * **Индекс списка писем предположительно не указан явно** — исходный
 * `mailListParser` не проверяет `nodeName` дочерних узлов `<messageoverview>`,
 * поэтому тег дочернего письма (вероятно `<message>`, по аналогии с
 * корневым тегом одиночного письма) не установлен достоверно.
 */
import { parseXmlFragment } from '../protocol/response';

const HEX_DIGITS = '0123456789ABCDEF';

/** xmlparsers.as:3018-3061 — раскодировать триплет-хекс текст письма (тема/тело). */
export function decodeMailMessage(encoded: string): string {
  let result = '';
  for (let i = 0; i < encoded.length; i += 3) {
    const triplet = encoded.slice(i, i + 3);
    let code = 0;
    let matched = 0;
    for (let pos = 0; pos < 3; pos++) {
      const digit = HEX_DIGITS.indexOf(triplet.charAt(pos));
      if (digit >= 0) {
        matched++;
        code += digit * (pos === 0 ? 256 : pos === 1 ? 16 : 1);
      }
    }
    if (matched !== 3) {
      return encoded;
    }
    result += String.fromCharCode(code);
  }
  return result;
}

/** game.as:2655-2690 — обратное кодирование при отправке письма. */
export function encodeMailMessage(text: string): string {
  let result = '';
  for (let i = 0; i < text.length; i++) {
    let code = text.charCodeAt(i);
    const char1 = HEX_DIGITS[Math.floor(code / 256)] ?? '0';
    code = code % 256;
    const char2 = HEX_DIGITS[Math.floor(code / 16)] ?? '0';
    const char3 = HEX_DIGITS[code % 16] ?? '0';
    result += char1 + char2 + char3;
  }
  return result;
}

export interface MailListEntry {
  id: string;
  name: string;
  datecreate: string;
  dateread: string;
  subj: string;
  lscopename: string;
  scopeid: string;
  deleteFlag: boolean;
}

export interface MailListData {
  type: 'in' | 'out';
  mails: MailListEntry[];
}

export function parseMailList(xml: string): MailListData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'messageoverview') {
    throw new Error(`Ожидался корневой тег <messageoverview>, получено: ${root?.nodeName}`);
  }

  const listAttr = root.getAttribute('list');
  const type: 'in' | 'out' = listAttr === 'out' ? 'out' : 'in';

  const mails: MailListEntry[] = Array.from(root.children).map((el) => ({
    id: el.getAttribute('id') ?? '',
    name: el.getAttribute('name') ?? '',
    datecreate: el.getAttribute('datecreate') ?? '',
    dateread: el.getAttribute('dateread') ?? '',
    subj: decodeMailMessage(el.getAttribute('subj') ?? ''),
    lscopename: el.getAttribute('lscopename') ?? '',
    scopeid: el.getAttribute('scopeid') ?? '',
    deleteFlag: false,
  }));

  return { type, mails };
}

export interface MailMessage {
  id: string;
  dateread: string;
  body: string;
}

export function parseMailMessage(xml: string): MailMessage {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'message') {
    throw new Error(`Ожидался корневой тег <message>, получено: ${root?.nodeName}`);
  }

  const body = decodeMailMessage(root.textContent ?? '');

  return {
    id: root.getAttribute('id') ?? '',
    dateread: root.getAttribute('dateread') ?? '',
    body,
  };
}

/** mailmenu.as:13-30 typearrcreate() — уникальные типы (lscopename) из уже пришедших писем, по умолчанию все выбраны. */
export function computeMailTypeFilters(mails: MailListEntry[]): Map<string, boolean> {
  const filters = new Map<string, boolean>();
  for (const mail of mails) {
    if (!filters.has(mail.lscopename)) filters.set(mail.lscopename, true);
  }
  return filters;
}

/**
 * mailmenu.as:211-222 selectMail() — индексы писем, чей тип отмечен в фильтре.
 *
 * **Исправление находки**: `delMessages()`/`readMessages()` в оригинале
 * (`mailmenu.as:329-367`) после этого отбора берут `mail_arr[i].id` вместо
 * `mail_arr[array_active[i]].id` — при активном фильтре (когда
 * `array_active` не совпадает 1:1 с индексами `mail_arr`) это шлёт на
 * сервер ID НЕ ТЕХ писем. Здесь эта индексация исправлена — используется
 * дословно `mails[activeIndices[i]]`, найденный баг не воспроизводится
 * (это баг клиентской логики выборки, а не протокола/формулы).
 */
export function selectActiveMailIndices(mails: MailListEntry[], typeFilters: Map<string, boolean>): number[] {
  const active: number[] = [];
  for (let i = 0; i < mails.length; i++) {
    if (typeFilters.get(mails[i].lscopename) !== false) active.push(i);
  }
  return active;
}

/** reqvestdata.as:380-388 — <getbuildmenu tab="MessageOverview" type="in|out"/>. */
export function buildGetMailCommand(type: 'in' | 'out'): { tab: 'MessageOverview'; type: 'in' | 'out' } {
  return { tab: 'MessageOverview', type };
}

/** reqvestdata.as:390-397 — <showmemail id=""/>. */
export function buildGetMailMessageCommand(id: string): { id: string } {
  return { id };
}

/** reqvestdata.as:508-520 — <deletemessage id_message="id1,id2,..."/>. */
export function buildDeleteMessagesCommand(ids: string[]): { idMessage: string } {
  return { idMessage: ids.join(',') };
}

/** reqvestdata.as:522-534 — <readmessage id_message="id1,id2,..."/>. */
export function buildReadMessagesCommand(ids: string[]): { idMessage: string } {
  return { idMessage: ids.join(',') };
}
