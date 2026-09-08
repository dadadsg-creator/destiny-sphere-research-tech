/**
 * Разбор mark `reincarnate` — утилизация собственных боевых юнитов в
 * ресурс (Фаза 3.8, `reincarnate_menu_class.as`, источник:
 * docs/client-economy-social-ui.md §3.4 и
 * docs/client-side-computed-logic.md §2.6), портировано 1:1 из
 * `xmlparsers.as:2436-2536` (`reincarnate_menu_parser`).
 *
 * **Механика отсутствует в серверной документации проекта на момент
 * этого разбора** — реконструирована только по клиенту (см. §3.4
 * доклада), требует отдельной сверки с PHP (`UA_Reincarnate*`/
 * `EH_Reincarnate*` — имена классов предположительные).
 *
 * <reincarnate maxProdium="" lostProdium="" biuldProdium=""
 *              reincProdium="" targetProdium="" res="0|1" sil="0|1">
 *   <lost w="" m=""/>    (статистика потерь продиума: за неделю/месяц)
 *   <build w="" m=""/>   (потрачено на стройку)
 *   <reinc w="" m=""/>   (получено реинкарнацией)
 *   <unit id="" unitname="" PW="" price="KA:v;NA:v;ST:v;FO:v;PR:v;SC:v"/>
 *   <reunit id="" unitname="" time="start|finish" amount=""/>   (очередь, уже реинкарнируется)
 * </reincarnate>
 *
 * Два независимых режима, включаемых мировыми флагами `res`/`sil`:
 * Res-режим (в Продиум) и Sera-режим (в SILVER_CREDIT) — переключение
 * само по себе клиентское, оба используют один и тот же
 * `<unit>`-список и ту же формулу `calcAmount()`.
 *
 * Панель продажи получившегося ресурса внутри этого же экрана
 * (`reincarnate.as` — ДРУГОЙ файл, не `reincarnate_menu_class.as`)
 * буквально дублирует код налога/коридора цены из `trade_menu_class.as`
 * (см. client-side-computed-logic.md §2.4) — переиспользуйте
 * `computeTradePriceCorridor()` из `trade/tradeData.ts`, отдельная копия
 * здесь не заводится.
 */
import { parseXmlFragment } from '../protocol/response';
import { combResCurrent, type CombRes } from '../data/combResData';

export type ReincarnatePriceMap = Partial<Record<'KA' | 'NA' | 'ST' | 'FO' | 'PR' | 'SC', number>>;

/**
 * Марка `combres` → массив в том же порядке, что ключи цены юнита
 * (`ReincarnateWindow.tsx` PRICE_ORDER: KA,NA,ST,FO,PR,SC), для
 * `calcReincarnateAmount()`.
 */
export function combResToPriceOrder(res: CombRes): number[] {
  return [
    combResCurrent(res, 'KADERIUM'),
    combResCurrent(res, 'NANO_CRYSTAL'),
    combResCurrent(res, 'STASIS_ENERGY'),
    combResCurrent(res, 'FOOD'),
    combResCurrent(res, 'PRODIUM'),
    combResCurrent(res, 'SILVER_CREDIT'),
  ];
}

export interface ReincarnateUnitOption {
  id: string | null;
  libname: string | null;
  /** "Вес продиума" одного юнита — знаменатель квоты targetProdium. */
  prodiumWeight: number;
  /** Стоимость возврата за один юнит (используется как "цена" в calcAmount — floor(currentResource/price)). */
  price: ReincarnatePriceMap;
}

export interface ReincarnateQueueEntry {
  id: string | null;
  libname: string | null;
  /** [start, finish] — сервер шлёт единой строкой "start|finish" (xmlparsers.as:2525, разбор через split("|")). */
  time: [string, string];
  amount: string | null;
}

export interface ReincarnateStatSeries {
  /** За неделю (week). */
  w: string | null;
  /** За месяц (month). */
  m: string | null;
}

export interface ReincarnateFork {
  maxProdium: number;
  lostProdium: number;
  buildProdium: number;
  reincProdium: number;
  /** До какого веса армии можно реинкарнировать — знаменатель квоты в calcAmount(). */
  targetProdium: number;
  /** Мировой флаг — доступен ли Res-режим (в Продиум). */
  resEnabled: boolean;
  /** Мировой флаг — доступен ли Sera-режим (в серебро). */
  silEnabled: boolean;
}

export interface ReincarnateData {
  fork: ReincarnateFork;
  /** [потери, стройка, реинкарнация] — по индексу 0/1/2, как в оригинале. */
  statsByWeek: [ReincarnateStatSeries, ReincarnateStatSeries, ReincarnateStatSeries];
  units: ReincarnateUnitOption[];
  queue: ReincarnateQueueEntry[];
}

const PRICE_KEYS = ['KA', 'NA', 'ST', 'FO', 'PR', 'SC'] as const;

function parsePriceMap(raw: string | null): ReincarnatePriceMap {
  const map: ReincarnatePriceMap = {};
  if (!raw) return map;
  for (const pair of raw.split(';')) {
    const [key, value] = pair.split(':');
    if ((PRICE_KEYS as readonly string[]).includes(key)) {
      map[key as keyof ReincarnatePriceMap] = Number(value);
    }
  }
  return map;
}

export function parseReincarnate(xml: string): ReincarnateData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'reincarnate') {
    throw new Error(`Ожидался корневой тег <reincarnate>, получено: ${root?.nodeName}`);
  }

  const statsByWeek: [ReincarnateStatSeries, ReincarnateStatSeries, ReincarnateStatSeries] = [
    { w: null, m: null },
    { w: null, m: null },
    { w: null, m: null },
  ];
  const units: ReincarnateUnitOption[] = [];
  const queue: ReincarnateQueueEntry[] = [];

  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'lost') statsByWeek[0] = { w: el.getAttribute('w'), m: el.getAttribute('m') };
    else if (el.nodeName === 'build') statsByWeek[1] = { w: el.getAttribute('w'), m: el.getAttribute('m') };
    else if (el.nodeName === 'reinc') statsByWeek[2] = { w: el.getAttribute('w'), m: el.getAttribute('m') };
    else if (el.nodeName === 'unit') {
      units.push({
        id: el.getAttribute('id'),
        libname: el.getAttribute('unitname'),
        prodiumWeight: Number(el.getAttribute('PW')) || 0,
        price: parsePriceMap(el.getAttribute('price')),
      });
    } else if (el.nodeName === 'reunit') {
      const [start, finish] = (el.getAttribute('time') ?? '').split('|');
      queue.push({
        id: el.getAttribute('id'),
        libname: el.getAttribute('unitname'),
        time: [start ?? '', finish ?? ''],
        amount: el.getAttribute('amount'),
      });
    }
  }

  return {
    fork: {
      maxProdium: Number(root.getAttribute('maxProdium')) || 0,
      lostProdium: Number(root.getAttribute('lostProdium')) || 0,
      buildProdium: Number(root.getAttribute('biuldProdium')) || 0,
      reincProdium: Number(root.getAttribute('reincProdium')) || 0,
      targetProdium: Number(root.getAttribute('targetProdium')) || 0,
      resEnabled: root.getAttribute('res') === '1',
      silEnabled: root.getAttribute('sil') === '1',
    },
    statsByWeek,
    units,
    queue,
  };
}

/**
 * Категория «В» (`reincarnate_menu_class.as:636-653,996-1013`, портировано
 * буквально — "src"/"dat" в оригинале это массив текущих ресурсов игрока
 * и объект юнита с разложением цены по ресурсам): максимум юнитов данного
 * типа, доступных к реинкарнации — минимум из (а) хватает ли ресурсов по
 * каждому виду цены и (б) укладывается ли в оставшуюся квоту продиумного
 * веса. Комментарий разработчика (`:758,1119`) буквально называет эту
 * квоту "70% продиумного веса" — независимое клиентское подтверждение
 * формулы из mechanics-reincarnation.md (сам процент 70% в клиентском
 * коде не вычисляется, `targetProdium` уже приходит готовым от сервера).
 */
export function calcReincarnateAmount(
  playerResources: number[],
  unitPrices: number[],
  remainingProdiumQuota: number,
  unitProdiumWeight: number,
): number {
  let minAmount = 900_000_000;
  for (let i = 0; i < playerResources.length; i++) {
    const byResource = Math.floor(playerResources[i] / unitPrices[i]);
    if (byResource < minAmount) minAmount = byResource;
  }
  const byQuota = Math.floor(remainingProdiumQuota / unitProdiumWeight);
  if (byQuota < minAmount) minAmount = byQuota;
  return minAmount === 900_000_000 || minAmount < 0 ? 0 : minAmount;
}

/**
 * reqvestdata.as:85-89 — <getReincarnate for="Res"|"Sera" lst=""/>, где
 * lst — самодельный формат "id:count,id:count,...", собранный вручную
 * (не расчётный calcReincarnateAmount(), а фактически выбранные значения).
 */
export type ReincarnateMode = 'Res' | 'Sera';
export interface GetReincarnateCommand {
  for: ReincarnateMode;
  lst: string;
}
export function buildGetReincarnateCommand(mode: ReincarnateMode, selected: Array<{ id: string; count: number }>): GetReincarnateCommand {
  return { for: mode, lst: selected.map((s) => `${s.id}:${s.count}`).join(',') };
}
