/**
 * Разбор <battlinfo> — "наглядное поле боя" (warplateclass.as, открывается
 * командой getShowBattle). Портировано 1:1 из авторитетного источника
 * `xmlparsers.as:1152-1194` (battle_show_menu), см.
 * docs/client-combat-ui.md §4. Это НЕ иконки на гекс-карте — юниты/армии
 * в оригинале нигде не рисуются пространственно на sota, только здесь,
 * в списке по раундам боя (см. CLIENT_PORTING_PLAN.md, Фаза 2).
 *
 * <battlinfo id_battle="" nick="a,b,c">
 *   <round><unit side="A|D" amount="" name="" army=""/> ...</round>
 *   ...
 * </battlinfo>
 */
import { parseXmlFragment } from '../protocol/response';

export interface BattleUnit {
  /** Машинное имя юнита (= UNIT_NAMES из unitTypes.ts, ключ ассета). */
  libname: string;
  amount: number;
  /** Имя армии, к которой относится юнит (различает коалицию защитников). */
  army: string;
}

export interface BattleRound {
  sideA: BattleUnit[];
  sideD: BattleUnit[];
}

export interface BattleInfo {
  idBattle: string | null;
  nicks: string[];
  rounds: BattleRound[];
}

export function parseBattleInfo(xml: string): BattleInfo {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'battlinfo') {
    throw new Error(`Ожидался корневой тег <battlinfo>, получено: ${root?.nodeName}`);
  }

  const nickAttr = root.getAttribute('nick');
  const rounds: BattleRound[] = [];

  for (const roundEl of Array.from(root.children)) {
    if (roundEl.nodeName !== 'round') continue;

    const round: BattleRound = { sideA: [], sideD: [] };
    for (const unitEl of Array.from(roundEl.children)) {
      if (unitEl.nodeName !== 'unit') continue;
      const side = unitEl.getAttribute('side');
      const unit: BattleUnit = {
        libname: unitEl.getAttribute('name') ?? '',
        amount: Number(unitEl.getAttribute('amount')),
        army: unitEl.getAttribute('army') ?? '',
      };
      if (side === 'A') round.sideA.push(unit);
      else if (side === 'D') round.sideD.push(unit);
      // side ни "A" ни "D" — в оригинале текстовый узел без юнит-тегов, здесь не встречается (см. документацию).
    }
    rounds.push(round);
  }

  return {
    idBattle: root.getAttribute('id_battle'),
    nicks: nickAttr ? nickAttr.split(',') : [],
    rounds,
  };
}
