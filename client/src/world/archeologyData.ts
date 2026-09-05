/**
 * Разбор mark `archeology` — институт археологии (Фаза 3.7,
 * `archeology_class.as`, источник: docs/client-economy-social-ui.md
 * §1.3), портировано 1:1 из `xmlparsers.as:2842-2988` (`archeology_parser`).
 *
 * <archeology extendedtime="0|1">
 *   <team count="" dest="" enddate="" size=""/>   (активные экспедиции, повторяется)
 *   <train archeologistmax="" archeologistallow="" inprogress="" work=""
 *          W="" K="" F="" C=""/>   (turn читается с корня)
 *   <MSG .../>   (повторяется, как и в других доменах)
 * </archeology>
 *
 * НЕ портирован `arch_array` — в оригинале объявлен, но НИ РАЗУ не
 * заполняется ни в одной ветке `switch` этого парсера (мёртвый код,
 * не наша находка — проверено построчно).
 *
 * `train.archeologistallow` — категория «В», та же формула ужимания по
 * ресурсам + work, что и `computeScientistAllow`/`computeAllowMax` в
 * других доменах (`xmlparsers.as:2896-2907`).
 *
 * Список вариантов "куда"/"на сколько ходов отправить" в оригинале
 * генерируется НА КЛИЕНТЕ (`arrtimesend`/`arrlocalsend`) и зависит от
 * внешнего состояния (`game.turnToTime()`-форматирование,
 * `game.getGateStatus()`) — здесь это отдельные функции
 * (`computeSendTimeOptions`/`computeSendLocationOptions`), не часть
 * самого парсера, без попытки воспроизвести точный текстовый формат
 * `game.turnToTime()` (не установлен).
 */
import { parseXmlFragment } from '../protocol/response';

export interface Expedition {
  count: string | null;
  /** Машинное имя направления (game.getLocalNames(dest).ln — не вычислено здесь). */
  dest: string | null;
  endDate: string | null;
  minSize: string | null;
}

export interface ArcheologyTrainData {
  turn: string | null;
  archeologistMax: number;
  /** Живой атрибут — до клиентского ужимания, см. computeArcheologistAllow(). */
  archeologistAllowRaw: number;
  inProgress: number;
  work: number;
  priceWork: number;
  priceKad: number;
  priceFood: number;
  priceTime: number;
}

export interface ArcheologyMessage {
  id: string | null;
  titl: string | null;
  head: string | null;
  txt: string | null;
  desc: string | null;
  res: string | null;
  avail: string | null;
  price: string | null;
  status: string | null;
  rem: string | null;
}

export interface ArcheologyData {
  /** "1" — куплена отправка экспедиций на 24/48ч (см. computeSendTimeOptions). */
  extendedTime: boolean;
  expeditions: Expedition[];
  train: ArcheologyTrainData | null;
  messages: Record<string, ArcheologyMessage>;
}

export function parseArcheology(xml: string): ArcheologyData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'archeology') {
    throw new Error(`Ожидался корневой тег <archeology>, получено: ${root?.nodeName}`);
  }

  const expeditions: Expedition[] = [];
  let train: ArcheologyTrainData | null = null;
  const messages: Record<string, ArcheologyMessage> = {};

  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'team') {
      expeditions.push({
        count: el.getAttribute('count'),
        dest: el.getAttribute('dest'),
        endDate: el.getAttribute('enddate'),
        minSize: el.getAttribute('size'),
      });
    } else if (el.nodeName === 'train') {
      train = {
        turn: root.getAttribute('turn'),
        archeologistMax: Number(el.getAttribute('archeologistmax')) || 0,
        archeologistAllowRaw: Number(el.getAttribute('archeologistallow')) || 0,
        inProgress: Number(el.getAttribute('inprogress')) || 0,
        work: Number(el.getAttribute('work')) || 0,
        priceWork: Number(el.getAttribute('W')) || 0,
        priceKad: Number(el.getAttribute('K')) || 0,
        priceFood: Number(el.getAttribute('F')) || 0,
        priceTime: Number(el.getAttribute('C')) || 0,
      };
    } else if (el.nodeName === 'MSG') {
      const id = el.getAttribute('id') ?? '';
      messages[id] = {
        id: el.getAttribute('id'),
        titl: el.getAttribute('titl'),
        head: el.getAttribute('head'),
        txt: el.getAttribute('txt'),
        desc: el.getAttribute('desc'),
        res: el.getAttribute('res'),
        avail: el.getAttribute('avail'),
        price: el.getAttribute('price'),
        status: el.getAttribute('status'),
        rem: el.getAttribute('rem'),
      };
    }
  }

  return { extendedTime: root.getAttribute('extendedtime') === '1', expeditions, train, messages };
}

export interface PlayerKadFoodPool {
  kaderium: number;
  food: number;
}

/** Категория «В» (xmlparsers.as:2896-2907) — та же формула, что computeScientistAllow. */
export function computeArcheologistAllow(train: ArcheologyTrainData, resources: PlayerKadFoodPool): number {
  let allow = train.archeologistAllowRaw;
  if (train.priceKad * allow > resources.kaderium) {
    allow = Math.floor(resources.kaderium / train.priceKad);
  }
  if (train.priceFood * allow > resources.food) {
    allow = Math.floor(resources.food / train.priceFood);
  }
  if (allow > train.work) {
    allow = train.work;
  }
  return allow;
}

export interface SendTimeOption {
  /** Множитель хода (1/2/3/5/8/12) либо расчётное число ходов для купленных 24ч/48ч опций. */
  turns: number;
  /** true — это одна из двух премиум-опций (24ч/48ч), помечается "*" в оригинале. */
  purchased: boolean;
}

/** xmlparsers.as:2942-2965 — список длительностей отправки экспедиции, категория «В». */
export function computeSendTimeOptions(turnSeconds: number, extendedTimePurchased: boolean): SendTimeOption[] {
  const options: SendTimeOption[] = [1, 2, 3, 5, 8, 12].map((turns) => ({ turns, purchased: false }));
  if (extendedTimePurchased) {
    options.push({ turns: Math.round((24 * 60 * 60) / turnSeconds), purchased: true });
    options.push({ turns: Math.round((48 * 60 * 60) / turnSeconds), purchased: true });
  }
  return options;
}

/** xmlparsers.as:2968-2985 — HOME всегда доступен, CENTRAL/NEUTRAL только если открыты врата. */
export function computeSendLocationOptions(gateOpen: boolean): Array<{ id: 'HOME' | 'CENTRAL' | 'NEUTRAL' }> {
  const options: Array<{ id: 'HOME' | 'CENTRAL' | 'NEUTRAL' }> = [{ id: 'HOME' }];
  if (gateOpen) {
    options.push({ id: 'CENTRAL' }, { id: 'NEUTRAL' });
  }
  return options;
}

/**
 * reqvestdata.as:548-552 — <archeologyteam dest="" count="" time=""
 * size="" countgroup=""/>. Групповая отправка (countgroup>1) — только
 * премиум, у обычных аккаунтов countgroup жёстко = 1 (см. §1.3).
 */
export interface ArcheologyTeamCommand {
  dest: string;
  count: number;
  time: number;
  size: number;
  countGroup: number;
}
export function buildSendExpeditionCommand(
  dest: string,
  count: number,
  time: number,
  sizeMin: number,
  countGroup: number,
): ArcheologyTeamCommand {
  return { dest, count, time, size: sizeMin, countGroup };
}
