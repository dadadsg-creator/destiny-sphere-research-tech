/**
 * Разбор марки `combres` — пул ресурсов текущей соты + баланс валют
 * игрока. Приходит почти при каждом ответе сервера (после `load`,
 * `refresh`, построек, атак и т.д. — см. protocol-client-server.md §7.1,
 * §7.3), не только при явном `<refresh/>`. В оригинале читается в
 * десятках мест `xmlparsers.as` напрямую как
 * `debug.link.resourse_player.<ИМЯ>.current`/`.max`
 * (`parser.combres_action()`, см. docs/client-engine-and-protocol.md §5).
 *
 * Живой формат подтверждён серверным кодом (`UA_Refresh.class.php:288-352`,
 * `_updateCombRes()`) и двумя фикстурами клиента
 * (`SPHERE_client/refresh.txt`, `refresh_test.txt`):
 * ```xml
 * <combres>
 *   <KADERIUM current="58062"/>
 *   <NANO_CRYSTAL current="1089110"/>
 *   <PRODIUM current="3891562"/>
 *   <FOOD current="14096492"/>
 *   <STASIS_ENERGY current="25614803"/>
 *   <WORKER current="18700" max="21250"/>
 *   <SCIENTIST current="24285" max="25500"/>
 *   <ARCHEOLOGIST current="88" max="19600"/>
 *   <GOLD_CREDIT current="0"/>
 *   <SILVER_CREDIT current="0"/>
 * </combres>
 * ```
 * Первые 8 элементов — `GB_ResourcesList::$allCombResources` (пул СОТЫ,
 * `UA_Refresh.class.php:310`), `max` есть только у 3 "населенческих"
 * (WORKER/SCIENTIST/ARCHEOLOGIST — у 5 базовых ресурсов явного лимита
 * нет). Последние 2 — баланс ВАЛЮТ ИГРОКА (не соты!), добавляются
 * отдельным циклом по `MU_EntityMoneySumm::$allowCreditName`
 * (`UA_Refresh.class.php:339-350`) — присутствуют, только если у игрока
 * вообще есть аккаунтные данные по этой валюте.
 */
import { parseXmlFragment } from '../protocol/response';

export type CombResName =
  | 'KADERIUM'
  | 'NANO_CRYSTAL'
  | 'PRODIUM'
  | 'FOOD'
  | 'STASIS_ENERGY'
  | 'WORKER'
  | 'SCIENTIST'
  | 'ARCHEOLOGIST'
  | 'GOLD_CREDIT'
  | 'SILVER_CREDIT';

export interface CombResEntry {
  current: number;
  max?: number;
}

export type CombRes = Partial<Record<CombResName, CombResEntry>>;

export function parseCombRes(xml: string): CombRes {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  const result: CombRes = {};
  if (!root || root.nodeName !== 'combres') return result;

  for (const el of Array.from(root.children)) {
    const current = Number(el.getAttribute('current')) || 0;
    const maxAttr = el.getAttribute('max');
    result[el.nodeName as CombResName] = maxAttr !== null ? { current, max: Number(maxAttr) || 0 } : { current };
  }
  return result;
}

/** Текущее значение ресурса, 0 если марка ещё не приходила или ресурс отсутствует в ответе. */
export function combResCurrent(res: CombRes, name: CombResName): number {
  return res[name]?.current ?? 0;
}
