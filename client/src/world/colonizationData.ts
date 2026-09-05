/**
 * Разбор marks `colonize`/`colonizestatus` — панель здания
 * `COLONIZATION_CENTER` (Фаза 3.7, `colonyzationclass.as`, источник:
 * docs/client-economy-social-ui.md §3.1), портировано 1:1 из
 * `xmlparsers.as:2813-2839` (`colonycenter_parser`, используется и для
 * первого открытия, и для последующих update-ответов — оба mark'а
 * дают идентичную структуру).
 *
 * <colonize action="1|2|3|4" id_module="">
 *   <ring ringnumber="" ringname=""/>   (повторяется, список колец на выбор)
 *   текстовый узел без тега — текст статуса, если постройка/процесс уже идёт
 * </colonize>
 *
 * action: "1" — готов к настройке цели, "2" — уже в процессе (есть
 * кнопка отмены), "3"/"4" — завершено (всё скрыто).
 */
import { parseXmlFragment } from '../protocol/response';

export interface ColonizationRing {
  id: string | null;
  ln: string | null;
}

export interface ColonizationData {
  action: '1' | '2' | '3' | '4' | string;
  idModule: string | null;
  rings: ColonizationRing[];
  textStatus: string;
}

export function parseColonizationCenter(xml: string): ColonizationData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || (root.nodeName !== 'colonize' && root.nodeName !== 'colonizestatus')) {
    throw new Error(`Ожидался корневой тег <colonize>/<colonizestatus>, получено: ${root?.nodeName}`);
  }

  const rings: ColonizationRing[] = [];
  for (const el of Array.from(root.children)) {
    if (el.nodeName !== 'ring') continue;
    rings.push({ id: el.getAttribute('ringnumber'), ln: el.getAttribute('ringname') });
  }

  return {
    action: root.getAttribute('action') ?? '',
    idModule: root.getAttribute('id_module'),
    rings,
    textStatus: root.textContent?.trim() ?? '',
  };
}

/**
 * colonyzationclass.as:157-188 — 3-25 символов, restrict-паттерн
 * `"a-z A-Z 0-9 \\-\\"` (буквально так в оригинале — компилируется в
 * буква/цифра/дефис + висящий одиночный экранированный обратный слэш в
 * конце набора; подтверждённый мелкий дефект самого паттерна, не
 * артефакт декомпиляции — сохранён здесь как факт, но НЕ воспроизведён
 * в валидаторе ниже: висящий `\` в restrict-наборе Flash — это
 * практически no-op (набор без пары символов для диапазона), реального
 * разрешения бэкслеша как символа имени он не даёт).
 */
export function isValidColonyName(name: string): boolean {
  return name.length >= 3 && name.length <= 25 && /^[a-zA-Z0-9-]+$/.test(name);
}

/** Формат адреса цели — один из двух видов, см. докстринг выше. */
export function buildColonyDestination(ring: string, opts: { random: true } | { complex: number; sota: number }): string {
  if ('random' in opts) return `${ring}.rand`;
  return `${ring}.${opts.complex}.${opts.sota}`;
}

/** reqvestdata.as:565-569 — <colonize id_module="" destination=""><colonyname/></colonize>. */
export interface SendColonyModuleCommand {
  idModule: string;
  destination: string;
  colonyName: string;
}
export function buildSendColonyModuleCommand(idModule: string, destination: string, name: string): SendColonyModuleCommand {
  return { idModule, destination, colonyName: name };
}

/** reqvestdata.as:570-574 — <cancelcolonize id_module=""/>. */
export function buildCancelColonyModuleCommand(idModule: string): { idModule: string } {
  return { idModule };
}
