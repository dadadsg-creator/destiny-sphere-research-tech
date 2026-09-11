/**
 * Разбор mark `armyoverview` — обзор армий игрока (Фаза 3.1, источник:
 * docs/client-combat-ui.md §1, портировано 1:1 из авторитетного
 * `xmlparsers.as:1461-1568`, `army_overview_menu`).
 *
 * <armyoverview turn="" maxspeed="" minspeed="">
 *   <army id_army="" armyname="" basecomb="ring.complex.sota" armypower=""
 *         speed="" res="" inaction="0|1|2|3" lastcoord="" lastspeed=""
 *         mst="" PW="">
 *     <unit id_unit="" unitname="" amount=""/>
 *     <transfers><transer_to id_army="" armyname=""/>...</transfers>
 *     <actions><possible id_action="" actionname=""/>...</actions>
 *   </army>
 *   ...
 * </armyoverview>
 *
 * inaction (xmlparsers.as:1504-1509, буквальный комментарий разработчика):
 *   0 — стоит на соте, 1 — летит куда-то, можно вернуть,
 *   2 — летит домой, ничего не доступно, 3 — "домашка" (для UI неотличима от 0).
 *
 * НЕ включены в этот парсер: `ln`/`damage`/`defence`/`hitpoint`/`attack`/
 * `level`/`race` юнита — в оригинале это НЕ данные сервера, а результат
 * `game.getLocalNames(unitname)`, статической клиентской таблицы
 * характеристик юнитов (см. docs/client-combat-ui.md §1, находка 5) —
 * этой таблицы у нас пока нет, отдельная задача.
 */
import { parseXmlFragment } from '../protocol/response';

export interface ArmyTransferTarget {
  id: string | null;
  armyName: string | null;
}

export interface ArmyAction {
  id: string | null;
  actionName: string | null;
}

export interface ArmyUnit {
  id: string | null;
  /** unitname — машинное имя (= UNIT_NAMES из map/unitTypes.ts). */
  libname: string | null;
  amount: number;
}

export interface Army {
  idArmy: string | null;
  armyName: string | null;
  /** "ring.complex.sota" — адрес базы армии. */
  basecomb: string | null;
  armyPower: number;
  speed: number;
  res: string | null;
  inaction: number;
  lastcoord: string | null;
  lastspeed: number | null;
  /** Минимальное время (в ходах?) для HIDE_ARMY. */
  mst: number | null;
  /** "Вес продиума" — виден только премиум-аккаунтам. */
  prodoweight: number | null;
  units: ArmyUnit[];
  allowTransfer: ArmyTransferTarget[];
  allowAction: ArmyAction[];
}

export interface ArmyOverview {
  turn: string | null;
  maxSpeed: number;
  minSpeed: number;
  armies: Army[];
}

export function parseArmyOverview(xml: string): ArmyOverview {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'armyoverview') {
    throw new Error(`Ожидался корневой тег <armyoverview>, получено: ${root?.nodeName}`);
  }

  const maxSpeed = Number(root.getAttribute('maxspeed'));
  const minSpeed = Number(root.getAttribute('minspeed'));

  const armies: Army[] = [];
  for (const armyEl of Array.from(root.children)) {
    if (armyEl.nodeName !== 'army') continue;

    const units: ArmyUnit[] = [];
    const allowTransfer: ArmyTransferTarget[] = [];
    const allowAction: ArmyAction[] = [];

    for (const child of Array.from(armyEl.children)) {
      if (child.nodeName === 'unit') {
        units.push({
          id: child.getAttribute('id_unit'),
          libname: child.getAttribute('unitname'),
          amount: Number(child.getAttribute('amount')),
        });
      } else if (child.nodeName === 'transfers') {
        for (const transferEl of Array.from(child.children)) {
          if (transferEl.nodeName !== 'transer_to') continue;
          allowTransfer.push({
            id: transferEl.getAttribute('id_army'),
            armyName: transferEl.getAttribute('armyname'),
          });
        }
      } else if (child.nodeName === 'actions') {
        for (const actionEl of Array.from(child.children)) {
          if (actionEl.nodeName !== 'possible') continue;
          allowAction.push({
            id: actionEl.getAttribute('id_action'),
            actionName: actionEl.getAttribute('actionname'),
          });
        }
      }
    }

    const inaction = Number(armyEl.getAttribute('inaction'));
    armies.push({
      idArmy: armyEl.getAttribute('id_army'),
      armyName: armyEl.getAttribute('armyname'),
      basecomb: armyEl.getAttribute('basecomb'),
      armyPower: Number(armyEl.getAttribute('armypower')),
      speed: Number(armyEl.getAttribute('speed')),
      res: armyEl.getAttribute('res'),
      inaction: Number.isNaN(inaction) ? 0 : inaction,
      lastcoord: armyEl.getAttribute('lastcoord'),
      lastspeed: armyEl.getAttribute('lastspeed') !== null ? Number(armyEl.getAttribute('lastspeed')) : null,
      mst: armyEl.getAttribute('mst') !== null ? Number(armyEl.getAttribute('mst')) : null,
      prodoweight: armyEl.getAttribute('PW') !== null ? Number(armyEl.getAttribute('PW')) : null,
      units,
      allowTransfer,
      allowAction,
    });
  }

  return {
    turn: root.getAttribute('turn'),
    maxSpeed: Number.isNaN(maxSpeed) ? 150 : maxSpeed,
    minSpeed: Number.isNaN(minSpeed) ? 10 : minSpeed,
    armies,
  };
}

/**
 * `res_list` — комбобокс "за какой ресурс разрешить грабёж" при
 * атаке/защите/переброске (`army_overview_class.as:17`, класс-переменная
 * с буквальным комментарием-подсказкой прямо в объявлении: `= new
 * Array("ALL_RES","KADERIUM","NANO_CRYSTAL","STASIS_ENERGY","FOOD",
 * "PRODIUM")` — сама переменная заполняется снаружи класса (не найдено
 * место вызова в этой ревизии), но комментарий и есть авторитетный
 * список значений, других источников нет).
 */
export const ARMY_RESOURCE_OPTIONS = ['ALL_RES', 'KADERIUM', 'NANO_CRYSTAL', 'STASIS_ENERGY', 'FOOD', 'PRODIUM'] as const;

/**
 * `reqvestdata.as:670-709` (`army_action`) — словарь id_action → тег
 * команды. `ATTACK_CONFIRM` — подтверждение атаки соты, которую нельзя
 * колонизировать (обычная атака, просто другой повод её отправки).
 */
const ARMY_ACTION_TAG: Record<string, string> = {
  CANCEL_ATTACK: 'cancelattack',
  CANCEL_DEFEND: 'canceldefend',
  ATTACK: 'attack',
  ATTACK_CONFIRM: 'attack_confirm',
  DEFEND: 'defend',
  MOVE_TO_COMB: 'movetocomb',
};

/** `action.indexOf("CANCEL") >= 0` (`army_overview_class.as:821`) — единственная проверка валидности отправки для этих действий. */
export function isCancelAction(actionId: string): boolean {
  return actionId.includes('CANCEL');
}

/**
 * `reqvestdata.as:706` — `<attack|defend|movetocomb|cancelattack|canceldefend
 * res="" speed="" destination="ring.complex.sota" id_army=""/>`. Валидность
 * отправки (`complex_action>0 || action=="HIDE_ARMY" || action содержит
 * "CANCEL"`) — на вызывающей стороне (см. `ArmyOverviewWindow.tsx`).
 */
export interface ArmyActionCommand {
  tag: string;
  attrs: { res: string; speed: number; destination: string; id_army: string };
}
export function buildArmyActionCommand(
  idArmy: string,
  actionId: string,
  ring: number,
  complex: number,
  sota: number,
  speed: number,
  resource: string,
): ArmyActionCommand {
  return {
    tag: ARMY_ACTION_TAG[actionId] ?? `ERROR:${actionId}:`,
    attrs: { res: resource, speed, destination: `${ring}.${complex}.${sota}`, id_army: idArmy },
  };
}

/** `reqvestdata.as:711-728` (`army_action_hide`) — `<hide_army id_army="" timehide=""/>`. */
export interface ArmyActionHideCommand {
  tag: 'hide_army';
  attrs: { id_army: string; timehide: number };
}
export function buildArmyActionHideCommand(idArmy: string, timeHide: number): ArmyActionHideCommand {
  return { tag: 'hide_army', attrs: { id_army: idArmy, timehide: timeHide } };
}
