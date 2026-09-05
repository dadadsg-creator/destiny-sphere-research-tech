/**
 * Разбор mark `religion` — вступление/выход из религии (Фаза 3.7,
 * `religion_class.as`, источник: docs/client-economy-social-ui.md §3.3),
 * портировано 1:1 из `xmlparsers.as:2538-2575` (`relygion_parser_update`).
 *
 * <religion>
 *   <religy ID="" status="" name="" percent="" date="">
 *     <bonus name="" value=""/>   (может повторяться)
 *     <desc>текст</desc>
 *   </religy>
 *   ...
 * </religion>
 *
 * Панель в оригинале показывает только `religiondata[0]` — первый
 * элемент, без переключения между несколькими религиями внутри самого
 * файла (открытый вопрос доклада — обрабатывается ли выбор где-то
 * снаружи). Здесь весь массив доступен, выбор первого элемента — на
 * усмотрение вызывающего экрана.
 *
 * Иконка жёстко закодирована только для двух имён в оригинале
 * (`RELIGI_WOLKENENDER` — своя иконка, ЛЮБОЕ другое имя, включая третью
 * религию, если она существует — дефолтная иконка `RELIGI_WOLKENSTURMER`)
 * — см. `getReligionIcon()` ниже, портировано именно так, а не через
 * общий словарь.
 */
import { parseXmlFragment } from '../protocol/response';

export type ReligionStatus =
  | 'RELIGI_KICK'
  | 'RELIGI_CONNECT'
  | 'RELIGI_NOTACCESS'
  | 'RELIGI_CONNECT_ACTION'
  | 'RELIGI_KICK_ACTION'
  | string;

export interface ReligionBonus {
  name: string | null;
  value: number;
}

export interface ReligionOption {
  id: string | null;
  status: ReligionStatus;
  /** Машинное имя религии — ключ для game.getLocalNames() (см. data/localData.ts) и для getReligionIcon(). */
  name: string | null;
  /** Заполнено только для статусов ...ACTION (процесс идёт). */
  percent: string | null;
  /** Дата завершения процесса (только для статусов ...ACTION). */
  date: string | null;
  bonuses: ReligionBonus[];
  desc: string;
}

export function parseReligion(xml: string): ReligionOption[] {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'religion') {
    throw new Error(`Ожидался корневой тег <religion>, получено: ${root?.nodeName}`);
  }

  const options: ReligionOption[] = [];
  for (const el of Array.from(root.children)) {
    if (el.nodeName !== 'religy') continue;

    const bonuses: ReligionBonus[] = [];
    let desc = '';
    for (const child of Array.from(el.children)) {
      if (child.nodeName === 'bonus') {
        bonuses.push({ name: child.getAttribute('name'), value: Number(child.getAttribute('value')) || 0 });
      } else if (child.nodeName === 'desc') {
        desc = child.textContent ?? '';
      }
    }

    options.push({
      id: el.getAttribute('ID'),
      status: el.getAttribute('status') ?? '',
      name: el.getAttribute('name'),
      percent: el.getAttribute('percent'),
      date: el.getAttribute('date'),
      bonuses,
      desc,
    });
  }
  return options;
}

/** religion_class.as:173-182 — только 2 узнаваемые по имени иконки, любое другое имя -> дефолт WOLKENSTURMER. */
export function getReligionIcon(name: string | null): 'RELIGI_WOLKENENDER' | 'RELIGI_WOLKENSTURMER' {
  return name === 'RELIGI_WOLKENENDER' ? 'RELIGI_WOLKENENDER' : 'RELIGI_WOLKENSTURMER';
}

/** reqvestdata.as:575-584 — XML-теги НЕ симметричны команде: religyconnect/religycancel (не religyout). */
export function buildReligyConnectCommand(id: string): { religy: string } {
  return { religy: id };
}
export function buildReligyOutCommand(id: string): { religy: string } {
  return { religy: id };
}
