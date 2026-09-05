/**
 * Разбор mark `cabinet` (все три экшена: enter/submenuopen/
 * operationStatistic) — Фаза 3.14, `parserCabinetData.as:18-42,44-102,
 * 356-506`, диспетчер `parser.as:1612-1614`. См.
 * docs/client-economy-social-ui.md §5.3.
 *
 * **Про два «пометить перед переносом» пункта плана (пароль/скрытое
 * IP-меню, см. docs/user-cabinet.md §13)** — оба НЕ реализуются на
 * клиенте: сервер САМ решает, слать ли проверку пароля при входе в
 * кабинет и включать ли 3 премиум-пункта в `<service_list>` для
 * конкретного IP/uid — ни то, ни другое не является клиентской логикой
 * (`UA_GetCabinet.class.php`, PHP). Клиент здесь просто рисует, что
 * пришло в `service_list`, без какой-либо фильтрации по IP/uid и без
 * запроса пароля — 1:1 как оригинальный AS2, который тоже ничего из
 * этого не делает. Решение "чинить или нет" относится к будущему
 * серверу ремастера, а не к этому клиентскому порту.
 */
import { parseXmlFragment } from '../protocol/response';
import { parseSubmenuFormFromCabinet, type SubmenuFormData } from './submenuData';

export interface CabinetWorld {
  name: string;
  id: string;
  gold: number;
  silver: number;
}

export interface CabinetUser {
  name: string;
  email: string;
  allowWorld: CabinetWorld[];
}

export type ServiceListItem =
  | { type: 'HEAD'; name: string }
  | { type: 'SPLITER' }
  | { type: 'SUB'; id: string; name: string };

export interface CabinetMainData {
  billingUrl: string;
  user: CabinetUser;
  currentStatus: { gold: number; silver: number };
  serviceList: ServiceListItem[];
  /** Всплывающее сообщение сразу при входе, если сервер его прислал. */
  popupMessage: string | null;
  lastOperation: { date: string; text: string } | null;
}

export function parseCabinetMain(root: Element): CabinetMainData {
  const data: CabinetMainData = {
    billingUrl: root.getAttribute('billing_url') ?? '',
    user: { name: '', email: '', allowWorld: [] },
    currentStatus: { gold: 0, silver: 0 },
    serviceList: [],
    popupMessage: null,
    lastOperation: null,
  };

  for (const el of Array.from(root.children)) {
    switch (el.nodeName) {
      case 'user': {
        data.user.name = el.getAttribute('name') ?? '';
        data.user.email = el.getAttribute('email') ?? '';
        for (const userChild of Array.from(el.children)) {
          if (userChild.nodeName === 'allow_worlds') {
            for (const worldEl of Array.from(userChild.children)) {
              if (worldEl.nodeName === 'world') {
                data.user.allowWorld.push({
                  name: worldEl.getAttribute('name') ?? '',
                  id: worldEl.getAttribute('id') ?? '',
                  gold: Number(worldEl.getAttribute('gold')) || 0,
                  silver: Number(worldEl.getAttribute('silver')) || 0,
                });
              }
            }
          }
        }
        break;
      }
      case 'current_stat':
        for (const statEl of Array.from(el.children)) {
          if (statEl.nodeName === 'money_gold') data.currentStatus.gold = Number(statEl.getAttribute('value')) || 0;
          else if (statEl.nodeName === 'money_silver') data.currentStatus.silver = Number(statEl.getAttribute('value')) || 0;
        }
        break;
      case 'service_list':
        for (const svcEl of Array.from(el.children)) {
          if (svcEl.nodeName === 'head') data.serviceList.push({ type: 'HEAD', name: svcEl.getAttribute('value') ?? '' });
          else if (svcEl.nodeName === 'split') data.serviceList.push({ type: 'SPLITER' });
          else if (svcEl.nodeName === 'sub')
            data.serviceList.push({ type: 'SUB', id: svcEl.getAttribute('id') ?? '', name: svcEl.getAttribute('name') ?? '' });
        }
        break;
      case 'popup':
        data.popupMessage = el.getAttribute('value') ?? '';
        break;
      case 'last_operation':
        data.lastOperation = { date: el.getAttribute('date') ?? '', text: el.getAttribute('text') ?? '' };
        break;
    }
  }

  return data;
}

export interface OperationStatFilterType {
  id: string;
  ln: string;
}

export interface OperationStatFilter {
  arrItems: OperationStatFilterType[];
}

export interface OperationEntry {
  date: string;
  text: string;
  concurency: string;
}

export interface OperationStatisticData {
  filters: OperationStatFilter[];
  operations: OperationEntry[];
}

export function parseOperationStatistic(root: Element): OperationStatisticData {
  const filters: OperationStatFilter[] = [];
  const operations: OperationEntry[] = [];

  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'filter') {
      for (const typesEl of Array.from(el.children)) {
        if (typesEl.nodeName === 'types') {
          const arrItems: OperationStatFilterType[] = Array.from(typesEl.children)
            .filter((t) => t.nodeName === 'type')
            .map((t) => ({ ln: t.getAttribute('name') ?? '', id: t.getAttribute('id') ?? '' }));
          filters.push({ arrItems });
        }
      }
    } else if (el.nodeName === 'operationlist') {
      for (const opEl of Array.from(el.children)) {
        if (opEl.nodeName === 'operation') {
          operations.push({
            date: opEl.getAttribute('date') ?? '',
            text: opEl.getAttribute('text') ?? '',
            concurency: opEl.getAttribute('concurency') ?? '',
          });
        }
      }
    }
  }

  return { filters, operations };
}

export type CabinetParseResult =
  | { action: 'enter'; data: CabinetMainData }
  | { action: 'submenuopen'; form: SubmenuFormData | null }
  | { action: 'operationStatistic'; data: OperationStatisticData };

export function parseCabinetResponse(xml: string): CabinetParseResult {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'cabinet') {
    throw new Error(`Ожидался корневой тег <cabinet>, получено: ${root?.nodeName}`);
  }

  const action = root.getAttribute('action');
  if (action === 'enter') return { action: 'enter', data: parseCabinetMain(root) };
  if (action === 'operationStatistic') return { action: 'operationStatistic', data: parseOperationStatistic(root) };
  if (action === 'submenuopen') return { action: 'submenuopen', form: parseSubmenuFormFromCabinet(root) };
  throw new Error(`Неизвестный action у <cabinet>: ${action}`);
}

/** requestDataCabinet.as:19-22 — <cabinetRequest command="changeGoldTOSelver" count=""/> (опечатка "TOSelver" в самом клиенте). */
export function buildChangeGoldToSilverCommand(count: number): { command: 'changeGoldTOSelver'; count: number } {
  return { command: 'changeGoldTOSelver', count };
}

/** requestDataCabinet.as:24-27 — <cabinetRequest command="moneyToWorld" worldId="" gold="" silver=""/>. */
export function buildMoneyToWorldCommand(worldId: string, gold: number, silver: number) {
  return { command: 'moneyToWorld' as const, worldId, gold, silver };
}

/** requestDataCabinet.as:29-32 — опечатка в самом клиенте: moneyFormWorld, не moneyFromWorld (см. §9 доклада). Сохранена как есть. */
export function buildMoneyFromWorldCommand(worldId: string, gold: number, silver: number) {
  return { command: 'moneyFormWorld' as const, worldId, gold, silver };
}

/** requestDataCabinet.as:34-37 — <cabinetRequest command="giftMoney" gold="" silver=""/>. */
export function buildGiftMoneyCommand(gold: number, silver: number) {
  return { command: 'giftMoney' as const, gold, silver };
}

/** requestDataCabinet.as:51-59 — <cabinetRequest command="getAllStatistic" [type=""]/>. */
export function buildGetAllStatisticCommand(type?: string) {
  return type === undefined ? { command: 'getAllStatistic' as const } : { command: 'getAllStatistic' as const, type };
}
