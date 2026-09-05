/**
 * Разбор mark `spying` — отправка зондов на задание (Фаза 3.2,
 * `spying_class.as`, источник: docs/client-combat-ui.md §10),
 * портировано 1:1 из `xmlparsers.as:199-323` (`spy_menu_parser`).
 *
 * <spying turn="" battle_xp="" numring="">
 *   <onbase time="csv" mintime="" maxtime="" deftime="" maxs="" mins=""
 *           defs="" type_zond="csv" allow="csv" id="csv"
 *           rings="строка из '0'/'1' по одному символу на кольцо">
 *     <action id="" name="" coord="0|1" time="0|1"/>   (может повторяться)
 *   </onbase>
 *   <inaction>
 *     <group id="" log="" action="" count="" time_ret="" addr=""
 *            zname="" zcolor=""/>   (уже отправленные группы зондов)
 *   </inaction>
 * </spying>
 *
 * Тип зонда: три параллельных CSV-списка на <onbase> (type_zond/allow/id)
 * — i-й элемент каждого описывает один тип зонда. Линковка графики
 * ("ZOND_"+раса+type_zond) и связь id="Z1"/"Z2" с SPY_ZOND_SCOUT/EXPLORER
 * — см. resolved-вопрос в army/... (риск №5 плана, TL_SpyTrainUnit.class.php).
 */
import { parseXmlFragment } from '../protocol/response';

export interface SpyAction {
  id: string;
  name: string | null;
  /** Живой атрибут "coord" — требует ли действие указания координат цели. */
  addrAllow: string | null;
  /** Живой атрибут "time" (на <action>, отдельно от общего диапазона на <onbase>) — требует ли действие длительности. */
  timeAllow: string | null;
}

export interface AllowedZond {
  /** Номер типа (1/2, см. TL_SpyTrainUnit.class.php) — используется в линковке "ZOND_"+раса+typeNum. */
  typeNum: string;
  /** "Z1"/"Z2" — буквальный id типа. */
  id: string;
  /** Локализованное имя (game.getLocalNames(id).ln) — здесь НЕ вычислено, только сырой id, см. data/localData.ts. */
  allowCount: number;
}

export interface RingAllow {
  /** ringnumber — из статической библиотеки (game.getLocalNames("RING_*")), здесь НЕ вычислено. */
  ringKey: 'RING_TECH' | 'RING_BIO' | 'RING_PSI' | 'RING_COLONIZE';
  allowed: boolean;
}

export interface ActiveZondGroup {
  id: string | null;
  log: string | null;
  action: string | null;
  count: number;
  /** Живой атрибут time_ret — время возврата группы. */
  timeReturn: string | null;
  coord: string | null;
  zname: string | null;
  zcolor: string | null;
}

export interface SpyingData {
  turn: string | null;
  battleXp: string | null;
  numRing: string | null;
  actions: SpyAction[];
  time: { min: string | null; max: string | null; def: string | null };
  speed: { min: string | null; max: string | null; def: string | null };
  allowZonds: AllowedZond[];
  ringAllow: RingAllow[];
  activeGroups: ActiveZondGroup[];
}

const RING_ORDER: RingAllow['ringKey'][] = ['RING_TECH', 'RING_BIO', 'RING_PSI', 'RING_COLONIZE'];

export function parseSpying(xml: string): SpyingData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'spying') {
    throw new Error(`Ожидался корневой тег <spying>, получено: ${root?.nodeName}`);
  }

  const data: SpyingData = {
    turn: root.getAttribute('turn'),
    battleXp: root.getAttribute('battle_xp'),
    numRing: root.getAttribute('numring'),
    actions: [],
    time: { min: null, max: null, def: null },
    speed: { min: null, max: null, def: null },
    allowZonds: [],
    ringAllow: [],
    activeGroups: [],
  };

  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'onbase') {
      data.time = { min: el.getAttribute('mintime'), max: el.getAttribute('maxtime'), def: el.getAttribute('deftime') };
      data.speed = { min: el.getAttribute('mins'), max: el.getAttribute('maxs'), def: el.getAttribute('defs') };

      for (const actionEl of Array.from(el.children)) {
        if (actionEl.nodeName !== 'action') continue;
        data.actions.push({
          id: actionEl.getAttribute('id') ?? '',
          name: actionEl.getAttribute('name'),
          addrAllow: actionEl.getAttribute('coord'),
          timeAllow: actionEl.getAttribute('time'),
        });
      }

      const typeZonds = (el.getAttribute('type_zond') ?? '').split(',').filter(Boolean);
      const allowCounts = (el.getAttribute('allow') ?? '').split(',');
      const ids = (el.getAttribute('id') ?? '').split(',');
      data.allowZonds = typeZonds.map((typeNum, i) => ({
        typeNum,
        id: ids[i] ?? '',
        allowCount: Number(allowCounts[i]) || 0,
      }));

      const rings = el.getAttribute('rings') ?? '';
      data.ringAllow = RING_ORDER.map((ringKey, i) => ({ ringKey, allowed: rings.charAt(i) !== '0' }));
    } else if (el.nodeName === 'inaction') {
      for (const groupEl of Array.from(el.children)) {
        if (groupEl.nodeName !== 'group') continue;
        data.activeGroups.push({
          id: groupEl.getAttribute('id'),
          log: groupEl.getAttribute('log'),
          action: groupEl.getAttribute('action'),
          count: Number(groupEl.getAttribute('count')) || 0,
          timeReturn: groupEl.getAttribute('time_ret'),
          coord: groupEl.getAttribute('addr'),
          zname: groupEl.getAttribute('zname'),
          zcolor: groupEl.getAttribute('zcolor'),
        });
      }
    }
  }

  return data;
}
