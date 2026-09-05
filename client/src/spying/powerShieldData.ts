/**
 * Разбор mark `powershild` (буквально так, без «e» — опечатка/сокращение
 * в оригинале, не наша) — силовой щит военной базы (Фаза 3.2,
 * `powershieldclass.as`, источник: docs/client-combat-ui.md §7),
 * портировано 1:1 из `xmlparsers.as:1195-1222`.
 *
 * <powershild turn="" status="0".."4" price="" mint="" maxt="">
 *   <desc>...</desc>
 *   <desc1>...</desc1>
 * </powershild>
 *
 * status — явная state-machина из 5 состояний:
 *  "1" — можно зарядить (CHARGE), "3" — можно выбрать время и
 *  активировать (SELECT_TIME, диапазон minTurn..maxTurn), остальные
 *  ("0"/"2"/"4"/default) — просто "в действии" (см. §7, возможно
 *  неполная реализация в этой ревизии клиента).
 */
import { parseXmlFragment } from '../protocol/response';

export type PowerShieldStatus = '0' | '1' | '2' | '3' | '4' | string;

export interface PowerShieldData {
  turn: string | null;
  minTurn: string | null;
  maxTurn: string | null;
  status: PowerShieldStatus;
  /** Цена в стазисе за один ход активации — итоговая цена = value * price. */
  price: number;
  /** desc1 в клиентских переменных (§7) — реальный XML-тег <desc>. */
  desc1: string;
  /** desc2 в клиентских переменных — реальный XML-тег <desc1> (не опечатка, так в оригинале). */
  desc2: string;
}

export function parsePowerShield(xml: string): PowerShieldData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'powershild') {
    throw new Error(`Ожидался корневой тег <powershild>, получено: ${root?.nodeName}`);
  }

  let desc1 = '';
  let desc2 = '';
  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'desc') desc1 = el.textContent ?? '';
    else if (el.nodeName === 'desc1') desc2 = el.textContent ?? '';
  }

  return {
    turn: root.getAttribute('turn'),
    minTurn: root.getAttribute('mint'),
    maxTurn: root.getAttribute('maxt'),
    status: root.getAttribute('status') ?? '',
    price: Number(root.getAttribute('price')) || 0,
    desc1,
    desc2,
  };
}
