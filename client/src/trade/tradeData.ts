/**
 * Разбор mark `trade` — биржа/выставление лота на продажу (Фаза 3.5,
 * `trade_menu_class.as`, источник: docs/client-economy-social-ui.md
 * §1.1), портировано 1:1 из `xmlparsers.as:1985-2434` (`trade_menu_parser`).
 *
 * <trade turn="" minquntity="" time2trade="" notrend="0|1" nonalog="0|1"
 *        nopersonaly="0|1" anonymous="0|1">
 *   <matrix>
 *     <trades>id1,id2,...</trades>                  (категории "продаю")
 *     <reses>name:price:cat_id,...</reses>           (категории "хочу взамен")
 *     <data>min-max-tax,...</data>                   (плоская матрица
 *                                                      trades×reses, "-"
 *                                                      если меньше 3 частей
 *                                                      — комбинация запрещена)
 *   </matrix>
 *   <articles>
 *     <article cat="<категория>_<id>" id="" amount=""
 *              upkeep="K:v,N:v,..."
 *              [power="KA:v;NA:v;ST:v;FO:v;PR:v;SC:v;GC:v;WR:v"]
 *              [libname=""] [bonusvalue] [bonusname] [death_message] [datum]/>
 *     ...
 *   </articles>
 *   <scopes><allow id_tradescope="" tradescopename=""/>...</scopes>
 * </trade>
 *
 * Категории статьи (`cat` = "<категория>_<id>"): 1=ресурсы, 2=юниты,
 * 3=артефакты, 4=неизвестное (не установлено), 5/6/7=юниты
 * воранеров/лиенсу/псолао. У категорий 1/2/4/5/6/7 разбор `power`
 * ИДЕНТИЧЕН (одна и та же коммутация KA/NA/ST/FO/PR/SC/GC/WR) — здесь
 * это один общий парсер, не 6 копий, как в оригинале. У категорий 5/6/7
 * в оригинале сам разбор `power` вообще закомментирован (мёртвый код) —
 * здесь `power` для них закономерно останется пустым объектом, это не
 * баг порта. Категория 3 (артефакты) — особый случай: `libname`/`ln`
 * там в оригинале обогащаются через `game.getLocalNamesArt`/
 * `getLocalNamesUser` (статическая ПО-АРТЕФАКТНАЯ локализация — ОТДЕЛЬНАЯ
 * от общей `loaddata`, здесь не разобрана и не подключена) — здесь
 * `artId`/`datum` доступны сырыми, обогащение названием/бонусом
 * оставлено на будущее (аналог нерешённого `combres` в других доменах).
 */
import { parseXmlFragment } from '../protocol/response';

export type TradePowerMap = Partial<Record<'KA' | 'NA' | 'ST' | 'FO' | 'PR' | 'SC' | 'GC' | 'WR', number>>;

export interface TradeMatrixCell {
  allow: boolean;
  minOrder?: number;
  maxOrder?: number;
  /** Налог (в процентах?) — точная единица измерения не установлена по этому файлу. */
  tax?: number;
}

export interface TradeResourceColumn {
  /** Машинное имя (либо "K"/"N"/..., либо категория_id для юнитов/артов — как прислал сервер). */
  name: string;
  ln: string | null;
  price: number;
  category: string;
  id: string;
}

export interface TradeMatrix {
  /** Категории "продаю" — id по порядку столбцов строк матрицы. */
  trades: string[];
  reses: TradeResourceColumn[];
  /** [индекс trades][индекс reses] -> ячейка (может быть undefined, если сервер прислал меньше данных, чем trades×reses). */
  data: TradeMatrixCell[][];
}

export interface TradeArticle {
  category: number;
  idCat: string;
  id: string | null;
  amount: number;
  upkeep: Partial<Record<'K' | 'N' | 'P' | 'F' | 'S', number>>;
  power: TradePowerMap;
  libname: string | null;
  ln: string | null;
  /** true — категория 3 (артефакт). */
  isArtefact: boolean;
  /** Только для артефактов (категория 3) — сырой id для будущего обогащения через art-локализацию. */
  artId?: string;
  datum?: string;
}

export interface TradeScope {
  id: string | null;
  ln: string | null;
}

export interface TradeFlags {
  /** false — мировой модификатор отключает налог (nonalog=="0"). */
  nalog: boolean;
  trend: boolean;
  person: boolean;
  anonymous: boolean;
}

export interface TradeMenuData {
  turn: string | null;
  /** Минимальный лот для ресурсов (по умолчанию 1, если сервер не прислал). */
  minQuantity: number;
  /** По умолчанию 99, если сервер не прислал. */
  time2Trade: number;
  flags: TradeFlags;
  matrix: TradeMatrix | null;
  /** Статьи сгруппированы по категории (1..7), как forTradeArray в оригинале. */
  articlesByCategory: Record<number, TradeArticle[]>;
  scopes: TradeScope[];
}

const POWER_KEYS = ['KA', 'NA', 'ST', 'FO', 'PR', 'SC', 'GC', 'WR'] as const;

function parsePower(raw: string | null): TradePowerMap {
  const power: TradePowerMap = {};
  if (!raw) return power;
  for (const pair of raw.split(';')) {
    const [key, value] = pair.split(':');
    if ((POWER_KEYS as readonly string[]).includes(key)) {
      power[key as keyof TradePowerMap] = Number(value);
    }
  }
  return power;
}

export function parseTradeMenu(xml: string): TradeMenuData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'trade') {
    throw new Error(`Ожидался корневой тег <trade>, получено: ${root?.nodeName}`);
  }

  const minQuantityAttr = root.getAttribute('minquntity');
  const time2tradeAttr = root.getAttribute('time2trade');

  const flags: TradeFlags = {
    nalog: root.getAttribute('nonalog') !== '0',
    trend: root.getAttribute('notrend') !== '0',
    person: root.getAttribute('nopersonaly') !== '0',
    anonymous: root.getAttribute('anonymous') === '1',
  };

  let matrix: TradeMatrix | null = null;
  const articlesByCategory: Record<number, TradeArticle[]> = {};
  const scopes: TradeScope[] = [];

  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'matrix') {
      const trades: string[] = [];
      const reses: TradeResourceColumn[] = [];
      const data: TradeMatrixCell[][] = [];

      const tradesEl = el.querySelector('trades');
      const resesEl = el.querySelector('reses');
      const dataEl = el.querySelector('data');

      if (tradesEl) trades.push(...(tradesEl.textContent ?? '').split(',').filter(Boolean));
      if (resesEl) {
        for (const entry of (resesEl.textContent ?? '').split(',').filter(Boolean)) {
          const [name, price, idCat] = entry.split(':');
          const [category, id] = (idCat ?? '').split('_');
          reses.push({ name, ln: null, price: Number(price), category, id });
        }
      }
      if (dataEl) {
        const cells = (dataEl.textContent ?? '').split(',').filter(Boolean);
        let cursor = 0;
        for (let i = 0; i < trades.length; i++) {
          data[i] = [];
          for (let j = 0; j < reses.length; j++) {
            const parts = (cells[cursor++] ?? '').split('-');
            if (parts.length < 3) {
              data[i][j] = { allow: false };
            } else {
              data[i][j] = { allow: true, minOrder: Number(parts[0]), maxOrder: Number(parts[1]), tax: Number(parts[2]) };
            }
          }
        }
      }

      matrix = { trades, reses, data };
    } else if (el.nodeName === 'articles') {
      for (const articleEl of Array.from(el.children)) {
        if (articleEl.nodeName !== 'article') continue;
        const idCat = articleEl.getAttribute('cat') ?? '';
        const category = Number(idCat.split('_')[0]);

        const upkeepRaw = articleEl.getAttribute('upkeep');
        const upkeep: TradeArticle['upkeep'] = { K: 0, N: 0, P: 0, F: 0, S: 0 };
        if (upkeepRaw) {
          for (const pair of upkeepRaw.split(',')) {
            const [key, value] = pair.split(':');
            if (key in upkeep) upkeep[key as keyof typeof upkeep] = Number(value);
          }
        }

        const isArtefact = category === 3;
        const article: TradeArticle = {
          category,
          idCat,
          id: articleEl.getAttribute('id'),
          amount: Number(articleEl.getAttribute('amount')) || 0,
          upkeep,
          power: category !== 5 && category !== 6 && category !== 7 ? parsePower(articleEl.getAttribute('power')) : {},
          libname: isArtefact ? null : articleEl.getAttribute('libname'),
          ln: null,
          isArtefact,
        };
        if (isArtefact) {
          article.artId = articleEl.getAttribute('id') ?? undefined;
          article.datum = articleEl.getAttribute('datum') ?? '';
        }

        (articlesByCategory[category] ??= []).push(article);
      }
    } else if (el.nodeName === 'scopes') {
      for (const allowEl of Array.from(el.children)) {
        if (allowEl.nodeName !== 'allow') continue;
        scopes.push({ id: allowEl.getAttribute('id_tradescope'), ln: allowEl.getAttribute('tradescopename') });
      }
    }
  }

  return {
    turn: root.getAttribute('turn'),
    minQuantity: minQuantityAttr !== null ? Number(minQuantityAttr) : 1,
    time2Trade: time2tradeAttr !== null ? Number(time2tradeAttr) : 99,
    flags,
    matrix,
    articlesByCategory,
    scopes,
  };
}

/**
 * Коридор цены — категория «В», сверено с серверной
 * `GL_TradeCountMinAndMaxPrices` (см. mechanics-economy-trade.md,
 * client-source: trade_menu_class.as:988-999): ±`minOrder`% от полной
 * цены лота.
 */
export function computeTradePriceCorridor(totalPrice: number, minOrderPercent: number): { min: number; max: number } {
  const delta = (totalPrice * minOrderPercent) / 100;
  return { min: totalPrice - delta, max: totalPrice + delta };
}

/**
 * Категория «В» (§2.2/§2.4 доклада) — `reinitupkeep()`
 * (`trade_menu_class.as:853-869`, буквально дублируется в `reincarnate.as:559-575`
 * для встроенной панели продажи там, НЕ портированной — см. докстринг
 * `ReincarnateWindow.tsx`): время выставления лота — `turnToTime(turn *
 * tradeTime)`; расход ресурсов на поддержание лота за это время —
 * `amount * article.upkeep[K] * tradeTime` (округление вверх, отдельно
 * по каждому из K/N/P/F/S). Раньше не отображалось в `SellWindow.tsx`.
 */
export function computeTradeUpkeep(
  article: Pick<TradeArticle, 'upkeep'>,
  amount: number,
  tradeTime: number,
): Partial<Record<'K' | 'N' | 'P' | 'F' | 'S', number>> {
  const result: Partial<Record<'K' | 'N' | 'P' | 'F' | 'S', number>> = {};
  for (const key of ['K', 'N', 'P', 'F', 'S'] as const) {
    const perUnit = article.upkeep[key] ?? 0;
    const value = amount * perUnit * tradeTime;
    if (Number.isFinite(value) && value > 0) result[key] = Math.ceil(value);
  }
  return result;
}

export interface SellParams {
  scope: string;
  category: number;
  article: string;
  amount: number;
  tradeTime: number;
  tradeByCategory: string;
  tradeByArticle: string;
  price: number;
  /** false — обычный лот на аукционе; true в оригинале — НЕ отправляется (по умолчанию идёт как аукционный), см. isPersonal. */
  isAuction: boolean;
  isPersonal: boolean;
  nick: string;
  isAnonymous?: boolean;
}

/**
 * reqvestdata.as:617-626 — <sell id_tradescope="" id_fromcategory=""
 * id_fromarticle="" id_fromrealarticle="" amount="" id_tocategory=""
 * id_toarticle="" price="" tradetime="" isauction="" ispersonal=""
 * nick="" pass="" death="" [anonymous=""]/>. `pass` с этого экрана
 * всегда "" (задел под другой сценарий, не встреченный в этом файле).
 * `id_fromarticle`/`id_fromrealarticle` дублируют друг друга (задел под
 * будущую подмену лота, не реализован в этом клиенте).
 */
export interface SellCommand {
  idTradescope: string;
  idFromcategory: number;
  idFromarticle: string;
  idFromrealarticle: string;
  amount: number;
  idTocategory: string;
  idToarticle: string;
  price: number;
  tradetime: number;
  isauction: 0 | 1;
  ispersonal: 0 | 1;
  nick: string;
  pass: '';
  death: 0;
  anonymous?: 0 | 1;
}

export function buildSellCommand(params: SellParams): SellCommand {
  return {
    idTradescope: params.scope,
    idFromcategory: params.category,
    idFromarticle: params.article,
    idFromrealarticle: params.article,
    amount: params.amount,
    idTocategory: params.tradeByCategory,
    idToarticle: params.tradeByArticle,
    price: params.price,
    tradetime: params.tradeTime,
    isauction: params.isPersonal ? 0 : 1,
    ispersonal: params.isPersonal ? 1 : 0,
    nick: params.nick,
    pass: '',
    death: 0,
    ...(params.isAnonymous !== undefined ? { anonymous: params.isAnonymous ? 1 : 0 } : {}),
  };
}
