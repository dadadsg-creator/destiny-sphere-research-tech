/**
 * Разбор mark `createmessage` — форма написания письма (Фаза 3.11,
 * `create_new_mail.as`), источник: docs/client-economy-social-ui.md §4.5.
 * Портировано 1:1 из `xmlparsers.as:2734-2803` (`createNewMail`, диспетчер
 * `parser.as:1521-1523`).
 *
 * Список скоупов получателя (`<messagescope id="" name="" ln=""/>`)
 * приходит от сервера динамически — НЕ фиксированный enum на клиенте.
 * Предзаполнение получателя (`<name>`) — обычный текст, НЕ через
 * `decodeMailMessage` (в отличие от `<subj>`/`<quotatext>`,
 * `xmlparsers.as:2750,2753,2755`).
 */
import { parseXmlFragment } from '../protocol/response';
import { decodeMailMessage } from './mailData';

export interface MessageScope {
  id: string;
  name: string;
  /** Локализованное отображаемое имя скоупа. */
  ln: string;
}

export interface CreateNewMailData {
  scopes: MessageScope[];
  /** Предзаполненный получатель (ответ на письмо конкретного игрока) — эхо параметра `name` запроса `createnewmail`. */
  recipientName: string;
  quoteSubject: string;
  /** Раскодированный цитируемый текст (при "Ответить с цитированием") — уже готов для оборачивания в `<font color="#992222"><b>...</b></font>` (create_new_mail.as:221). */
  quoteBody: string;
}

export function parseCreateNewMail(xml: string): CreateNewMailData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'createmessage') {
    throw new Error(`Ожидался корневой тег <createmessage>, получено: ${root?.nodeName}`);
  }

  const scopes: MessageScope[] = [];
  let recipientName = '';
  let quoteSubject = '';
  let quoteBody = '';

  for (const el of Array.from(root.children)) {
    switch (el.nodeName) {
      case 'messagescope':
        scopes.push({
          id: el.getAttribute('id') ?? '',
          name: el.getAttribute('name') ?? '',
          ln: el.getAttribute('ln') ?? '',
        });
        break;
      case 'name':
        recipientName = el.textContent ?? '';
        break;
      case 'quotatext':
        quoteBody = decodeMailMessage(el.textContent ?? '');
        break;
      case 'subj':
        quoteSubject = decodeMailMessage(el.textContent ?? '');
        break;
    }
  }

  return { scopes, recipientName, quoteSubject, quoteBody };
}

export interface NewMailValidation {
  toValid: boolean;
  subjectValid: boolean;
  bodyValid: boolean;
  allValid: boolean;
}

/**
 * create_new_mail.as:332-353 (`but_send.onRelease`) — клиентская
 * валидация перед отправкой. `to` не обязателен, если скоуп не «личное»
 * (`typeScope !== 1`, ровно как в оригинале — сравнение с числом 1).
 */
export function validateNewMail(to: string, subject: string, body: string, typeScope: number): NewMailValidation {
  const toValid = to.length >= 3 || typeScope !== 1;
  const subjectValid = subject.length >= 4;
  const bodyValid = body.length >= 4;
  return { toValid, subjectValid, bodyValid, allValid: toValid && subjectValid && bodyValid };
}

/** reqvestdata.as:448-464 — <getbuildmenu tab="CreateMessage" type="" [id=""] [name=""]/>. */
export function buildCreateNewMailCommand(
  type: 'new' | 'reply' | 'quote',
  id?: string,
  name?: string,
): { tab: 'CreateMessage'; type: string; id?: string; name?: string } {
  const cmd: { tab: 'CreateMessage'; type: string; id?: string; name?: string } = { tab: 'CreateMessage', type };
  if (id !== undefined) cmd.id = id;
  if (name !== undefined) cmd.name = name;
  return cmd;
}
