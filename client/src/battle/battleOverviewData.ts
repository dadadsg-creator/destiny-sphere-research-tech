/**
 * Разбор mark `battleoverview` — список боёв игрока с фильтрами (Фаза 3.1,
 * источник: docs/client-combat-ui.md §3, портировано 1:1 из
 * авторитетного `xmlparsers.as:1052-1150`, `battle_overview_menu`).
 *
 * <battleoverview ra="" reward="">
 *   <battle id="" starttime="" endtime="" battleaction="A|D|E" winner=""
 *           ra="" battlecomb="имя_соты номер_комплекса"
 *           opponent="ник [имя_соты_противника номер_комплекса]"
 *           opponent_race="" ranking="" rc=""/>
 *   ...
 *   <filter status="" winner="">
 *     <sot>соты,через,запятую</sot>
 *     <opp>противники,через,запятую</opp>
 *     <dates cur="startday-endday">MONTH_KEY:d1-d2,...</dates>
 *   </filter>
 * </battleoverview>
 *
 * battleaction (буквальный алфавит DE_Battle, + третий вариант "E" —
 * xmlparsers.as:1094-1097, комментарий разработчика "Если армия на защите
 * другой соты участвовала в бою" — коалиционная защита, opponent_* не
 * заполняются вообще):
 *   "A" — вы атаковали, "D" — вы защищались, "E" — участвовали в защите
 *   чужой соты (коалиция).
 */
import { parseXmlFragment } from '../protocol/response';

export type BattleAction = 'A' | 'D' | 'E' | string;

export interface BattleListEntry {
  idBattle: number;
  startBattle: string | null;
  endBattle: string;
  typeBattle: BattleAction;
  winnerBattle: string;
  /** "1" — бой ещё идёт (показывать капитуляцию/отступление, а не отчёт). */
  ra: string | null;
  combBattleName: string;
  combBattleComplex: string;
  opponentName: string | null;
  opponentComplexName: string | null;
  opponentComplexNum: string;
  opponentRace: string | null;
  youRanking: string | null;
  battleRoundNum: number;
}

export interface BattleFilterAllowedDay {
  ln: string;
}

export interface BattleFilters {
  status: string | null;
  winner: string | null;
  opponents: string[];
  sotes: string[];
  allowdays: BattleFilterAllowedDay[];
  startday: string | null;
  endday: string | null;
  /** "1" — есть непринятая награда за неравный бой (кнопка BUT_TROFEI/getRewards). */
  reward: string | null;
  /** "1" — хотя бы у одного боя в списке ra=="1" (глобальный флаг с корня). */
  ra: string | null;
}

export interface BattleOverview {
  battles: BattleListEntry[];
  filters: BattleFilters;
}

export function parseBattleOverview(xml: string): BattleOverview {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'battleoverview') {
    throw new Error(`Ожидался корневой тег <battleoverview>, получено: ${root?.nodeName}`);
  }

  const battles: BattleListEntry[] = [];
  const filters: BattleFilters = {
    status: null,
    winner: null,
    opponents: [],
    sotes: [],
    allowdays: [],
    startday: null,
    endday: null,
    reward: root.getAttribute('reward'),
    ra: root.getAttribute('ra'),
  };

  for (const child of Array.from(root.children)) {
    if (child.nodeName === 'battle') {
      const typeBattle = (child.getAttribute('battleaction') ?? '') as BattleAction;
      const [combBattleName, combBattleComplex] = (child.getAttribute('battlecomb') ?? '').split(' ');

      let opponentName: string | null = null;
      let opponentComplexName: string | null = null;
      let opponentComplexNum = '';

      if (typeBattle === 'D') {
        const parts = (child.getAttribute('opponent') ?? '').split(' ');
        opponentName = parts[0] ?? null;
        opponentComplexName = parts[1] ?? null;
        opponentComplexNum = parts[2] ?? '';
      } else if (typeBattle === 'A') {
        opponentName = child.getAttribute('opponent');
        opponentComplexName = combBattleName;
        opponentComplexNum = combBattleComplex;
      }
      // typeBattle === "E" — оппонент не заполняется, см. комментарий выше.

      battles.push({
        idBattle: Number(child.getAttribute('id')),
        startBattle: child.getAttribute('starttime'),
        endBattle: child.getAttribute('endtime') ?? '',
        typeBattle,
        winnerBattle: child.getAttribute('winner') ?? '',
        ra: child.getAttribute('ra'),
        combBattleName,
        combBattleComplex,
        opponentName,
        opponentComplexName,
        opponentComplexNum,
        opponentRace: child.getAttribute('opponent_race'),
        youRanking: child.getAttribute('ranking'),
        battleRoundNum: Number(child.getAttribute('rc')),
      });
    } else if (child.nodeName === 'filter') {
      filters.status = child.getAttribute('status');
      filters.winner = child.getAttribute('winner');

      for (const filterChild of Array.from(child.children)) {
        if (filterChild.nodeName === 'sot') {
          filters.sotes = (filterChild.textContent ?? '').split(',').filter(Boolean);
        } else if (filterChild.nodeName === 'opp') {
          filters.opponents = (filterChild.textContent ?? '').split(',').filter(Boolean);
        } else if (filterChild.nodeName === 'dates') {
          const cur = filterChild.getAttribute('cur') ?? '';
          [filters.startday, filters.endday] = cur.split('-');

          // Оригинал показывает "j1 + NameMonth", где NameMonth = локализованное
          // имя месяца из game.getLocalNames(monthKey) — этой таблицы локализации
          // у нас пока нет, поэтому здесь остаётся сырой ключ месяца, не перевод.
          const monthEntries = (filterChild.textContent ?? '').split(',').filter(Boolean);
          for (const entry of monthEntries) {
            const [monthKey, dayRange] = entry.split(':');
            const [fromDay, toDay] = (dayRange ?? '').split('-').map(Number);
            for (let day = fromDay; day <= toDay; day++) {
              filters.allowdays.push({ ln: `${day} ${monthKey}` });
            }
          }
        }
      }
    }
  }

  return { battles, filters };
}
