/**
 * Разбор mark `auction` — список лотов аукциона (Фаза 3.5,
 * `auctionmenuclass.as`, источник: docs/client-economy-social-ui.md §1.2),
 * портировано из `xmlparsers.as:1803-1983` (`auction_menu_parser`).
 *
 * <auction curpage="" pages="" onpage="" order="" trader="">
 *   <au id="" enddate="" amount="" getprice="K:v;N:v;S:v;F:v;P:v;T:v"
 *       price="" best_price="" seller="" tradeby="" tradescope=""
 *       immediate="0|1" libname="" resupkeep="" id_cat_on="1|2|3"
 *       death_message="" customer="" disable="0|1" nextprice=""
 *       maxprice=""/>
 *   <sort_place sortname="" lname="" cur="0|1"/>   (сортировка "где" — выпадающий scope_combo)
 *   <sort_buy sortname="" lname="" cur="0|1"/>     (сортировка "чем платить" — arrayBuyFrom)
 *   <sort_type sortname="" lname="" cur="0|1">     (категория товара — она же вкладка "что купить")
 *     <sort_name sortname="" lname="" cur="0|1"/>  (только когда категория готова целиком с сервера — живьём RESOURCES)
 *   </sort_type>
 *   <listf>(любые дети с id/name/stat="0|1")</listf>          (общий чек-лист фильтров, pages.allfilters)
 *   <sortl><listallow>(дети id/name)</listallow><sortf>(дети id/name)</sortf></sortl>  (варианты сортировки / активные 3 слота)
 * </auction>
 *
 * **Дерево фильтров аукциона (2026-09-18)** — портировано по
 * `auctionmenuclass.as` (`complete_filters()` строки 261-693,
 * `sendFilter()` строки 927-985, рекурсивный сериализатор `ArrToStr`) +
 * `xmlparsers.as:1803-1983` + `reqvestdata.as:646-661`
 * (`applyFiltersAuction`, точный формат `<setfilterauction/>`).
 *
 * Ключевая находка: `filters.arrayTestTree` (дерево "что купить") строится
 * по ТРЁМ разным схемам в зависимости от текущей вкладки категории
 * (`sort_type[i].id`, она же `typebuy` в команде):
 * - `RESOURCES` — сервер присылает готовый список прямо в этом же ответе
 *   (`sort_type`/вложенные `sort_name` → `AuctionCategoryOption.items`).
 * - `UNITS` — оригинал берёт из клиентского кэша `setting().loadSetting(
 *   "unit_table", ...)`, сгруппированного по race→level. В порте тот же
 *   результат строится из уже распарсенного `loaddata` (`LocalData.units`,
 *   у каждого юнита уже есть `race`/`level`) — см. `buildUnitCategoryTree()`.
 * - `ARTEFACTS` — оригинал берёт из кэша `art_filter_table`, который сам
 *   заполняется из `loaddata`-узлов `<artscope>/<artsize>/<artbonus>`
 *   (`UA_Load.class.php:195-249`) в БЕСПРЕЦЕДЕНТНОМ для остального
 *   `loaddata` формате — не атрибуты, а весь список ОДНОЙ строкой текста
 *   (`urlencode(implode(';', ...))`, поля через `|`). Клиентский разбор
 *   найден и портирован (2026-09-18, `parser.as:713-766`,
 *   `art_filter_arr`) — `<artscope>` = `"name|id|sizeIds(,)|bonusIds(,);..."`,
 *   `<artsize>`/`<artbonus>` = `"name|id;..."` (см. `LocalData.
 *   artefactScopes/artefactSizes/artefactBonuses`, `localData.ts`). Дерево
 *   строит `buildArtefactCategoryTree()` — scope → {SIZE→sizes,
 *   BONUS→bonuses}, 1:1 с `auctionmenuclass.as:598-663`.
 *
 * Формат отправки — `ArrToStr`: узел с непустыми детьми сериализуется как
 * `id[дети,через,запятую]`; лист без детей — как голый `id`, ТОЛЬКО если
 * `stat===true`; пустая ветка/лист без `stat` — не попадает в строку
 * вообще. См. `serializeFilterTree()`.
 */
import { parseXmlFragment } from '../protocol/response';
import type { LocalUnit, LocalArtefactScope, LocalSimpleEntry } from '../data/localData';
import { resourceIconUrl, hasResourceIcon, unitIconUrl, artefactScopeMaskUrl, artefactSizeColor } from '../data/resourceIcons';

export type AuctionPriceMap = Partial<Record<'K' | 'N' | 'S' | 'F' | 'P' | 'T', string>>;

export interface AuctionLot {
  id: string | null;
  endDate: string | null;
  amount: number;
  /** Разбивка предрасчитанной стоимости лота по 5 ресурсам + T (время?, не расшифровано). */
  getPrice: AuctionPriceMap;
  currentPrice: number;
  currentPriceOnce: string | null;
  seller: string | null;
  /** Машинное имя ресурса, которым покупается лот (ключ в debug.link.resourse_player). */
  tradeBy: string | null;
  tradeScope: string | null;
  /** "1" — покупка мгновенная (по currentPrice), иначе ставка по nextPrice. */
  immediate: string | null;
  libname: string | null;
  /**
   * "1"/"2"/"3" — тот же формат категории, что и в трансфере/бирже
   * (1=ресурс, 2=юнит, 3=артефакт) — подтверждено живыми примерами
   * (`AUCT_LOTS.xml`): `id_cat_on="2" libname="SOLDIER"/"GRENADIER"`,
   * `id_cat_on="1" libname="KADERIUM"`. Раньше не парсился вообще —
   * нужен, чтобы показывать иконку юнита только для реальных юнитов, не
   * для ресурсов/артефактов с тем же полем `libname`.
   */
  idCatOn: string | null;
  buyer: string | null;
  /** Живой атрибут — сервер уже сказал "недоступно"; см. computeLotDisabled() для доп. клиентской проверки по ресурсам. */
  serverDisabled: boolean;
  nextPrice: number;
  maxPrice: string | null;
}

export interface AuctionSortOption {
  id: string | null;
  ln: string | null;
  cur: boolean;
}

/** Категория товара ("что купить") — она же вкладка над деревом фильтров, `sort_type`+вложенные `sort_name`. */
export interface AuctionCategoryOption {
  id: string;
  ln: string | null;
  cur: boolean;
  /** Готовый список от сервера — заполнен только когда категория позволяет прислать его целиком (живьём — RESOURCES). */
  items: AuctionSortOption[];
}

/** Позиция общего чек-листа фильтров (`<listf>`, `pages.allfilters`) — смысл конкретных id/name определяется только живыми данными сервера. */
export interface AuctionFilterFlag {
  id: string | null;
  name: string | null;
  stat: boolean;
}

/** Вариант поля сортировки (`<sortl><listallow>`, `filters_other.allowFieldForSort`) — первый элемент всегда id="X" (плейсхолдер "выбрать", см. SORT_CHOOSE). */
export interface AuctionSortFieldOption {
  id: string;
  ln: string | null;
}

/** Изначально активный слот сортировки (`<sortl><sortf>`, `filters_other.CurrentSort`) — направление в оригинале не приходит с сервером, всегда "DOWN" по умолчанию. */
export interface AuctionActiveSort {
  id: string;
  ln: string | null;
}

/** Графика узла дерева фильтра — см. докстринг файла, "нужна графика, это игра". */
export type FilterTreeIcon =
  | { kind: 'img'; url: string }
  | { kind: 'swatch'; color: string }
  | { kind: 'scope'; maskUrl: string | null };

/**
 * Узел дерева фильтра — общий формат для ТРЁХ независимых деревьев
 * (категория товара/`arrayTestTree`, общий чек-лист/`allfilters`,
 * "чем платить"/`arrayBuyFrom`). Лист — без `children`; ветка — с
 * непустым `children` (тогда собственный `stat` листа не участвует в
 * сериализации, см. `serializeFilterTree()`).
 */
export interface FilterTreeNode {
  id: string;
  name: string | null;
  stat: boolean;
  children?: FilterTreeNode[];
  icon?: FilterTreeIcon;
}

export interface AuctionData {
  curPage: string | null;
  maxPages: string | null;
  onPage: string | null;
  order: string | null;
  trader: string | null;
  lots: AuctionLot[];
  sortPlace: AuctionSortOption[];
  sortBuy: AuctionSortOption[];
  categories: AuctionCategoryOption[];
  generalFilters: AuctionFilterFlag[];
  sortFieldOptions: AuctionSortFieldOption[];
  activeSort: AuctionActiveSort[];
}

function parsePriceMap(raw: string | null): AuctionPriceMap {
  const map: AuctionPriceMap = {};
  if (!raw) return map;
  for (const pair of raw.split(';')) {
    const [key, value] = pair.split(':');
    if (key === 'K' || key === 'N' || key === 'S' || key === 'F' || key === 'P' || key === 'T') {
      map[key] = value;
    }
  }
  return map;
}

function parseSortOptions(root: Element, tagName: string): AuctionSortOption[] {
  return Array.from(root.children)
    .filter((el) => el.nodeName === tagName)
    .map((el) => ({
      id: el.getAttribute('sortname'),
      ln: el.getAttribute('lname'),
      cur: el.getAttribute('cur') === '1',
    }));
}

export function parseAuction(xml: string): AuctionData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'auction') {
    throw new Error(`Ожидался корневой тег <auction>, получено: ${root?.nodeName}`);
  }

  const lots: AuctionLot[] = [];
  for (const el of Array.from(root.children)) {
    if (el.nodeName !== 'au') continue;

    const nextPriceAttr = el.getAttribute('nextprice');
    const currentPrice = Number(el.getAttribute('price')) || 0;

    lots.push({
      id: el.getAttribute('id'),
      endDate: el.getAttribute('enddate'),
      amount: Number(el.getAttribute('amount')) || 0,
      getPrice: parsePriceMap(el.getAttribute('getprice')),
      currentPrice,
      currentPriceOnce: el.getAttribute('best_price'),
      seller: el.getAttribute('seller'),
      tradeBy: el.getAttribute('tradeby'),
      tradeScope: el.getAttribute('tradescope'),
      immediate: el.getAttribute('immediate'),
      libname: el.getAttribute('libname'),
      idCatOn: el.getAttribute('id_cat_on'),
      buyer: el.getAttribute('customer'),
      serverDisabled: el.getAttribute('disable') === '1',
      nextPrice: nextPriceAttr !== null ? Number(nextPriceAttr) : currentPrice + 1,
      maxPrice: el.getAttribute('maxprice'),
    });
  }

  const categories: AuctionCategoryOption[] = [];
  const generalFilters: AuctionFilterFlag[] = [];
  const sortFieldOptions: AuctionSortFieldOption[] = [];
  const activeSort: AuctionActiveSort[] = [];

  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'sort_type') {
      const items: AuctionSortOption[] = Array.from(el.children)
        .filter((c) => c.nodeName === 'sort_name')
        .map((c) => ({
          id: c.getAttribute('sortname'),
          ln: c.getAttribute('lname'),
          cur: c.getAttribute('cur') === '1',
        }));
      categories.push({
        id: el.getAttribute('sortname') ?? '',
        ln: el.getAttribute('lname'),
        cur: el.getAttribute('cur') === '1',
        items,
      });
    } else if (el.nodeName === 'listf') {
      // xmlparsers.as:1883-1889 не проверяет имя тега детей — берём все.
      for (const c of Array.from(el.children)) {
        generalFilters.push({
          id: c.getAttribute('id'),
          name: c.getAttribute('name'),
          stat: Number(c.getAttribute('stat')) > 0,
        });
      }
    } else if (el.nodeName === 'sortl') {
      for (const c of Array.from(el.children)) {
        if (c.nodeName === 'listallow') {
          // xmlparsers.as:1959-1961 — первый элемент всегда плейсхолдер "X"/SORT_CHOOSE.
          sortFieldOptions.push({ id: 'X', ln: null });
          for (const g of Array.from(c.children)) {
            sortFieldOptions.push({ id: g.getAttribute('id') ?? '', ln: g.getAttribute('name') });
          }
        } else if (c.nodeName === 'sortf') {
          for (const g of Array.from(c.children)) {
            activeSort.push({ id: g.getAttribute('id') ?? '', ln: g.getAttribute('name') });
          }
        }
      }
    }
  }

  return {
    curPage: root.getAttribute('curpage'),
    maxPages: root.getAttribute('pages'),
    onPage: root.getAttribute('onpage'),
    order: root.getAttribute('order'),
    trader: root.getAttribute('trader'),
    lots,
    sortPlace: parseSortOptions(root, 'sort_place'),
    sortBuy: parseSortOptions(root, 'sort_buy'),
    categories,
    generalFilters,
    sortFieldOptions,
    activeSort,
  };
}

/**
 * `ArrToStr` (`auctionmenuclass.as:938-967`) — узел с непустыми `children`
 * сериализуется как `id[дети]` (только если дети дали непустую строку),
 * лист — как голый `id`, только если `stat===true`. Соседи — через
 * запятую. Пустое дерево/все `stat=false` → "" (это и есть сигнал
 * "ничего не выбрано на этой оси", `sendFilter()` в оригинале тогда
 * вовсе не отправляет команду — см. `hasSelection` в AuctionWindow.tsx).
 */
export function serializeFilterTree(nodes: FilterTreeNode[]): string {
  const parts: string[] = [];
  for (const node of nodes) {
    if (node.children && node.children.length > 0) {
      const childStr = serializeFilterTree(node.children);
      if (childStr.length > 0) parts.push(`${node.id}[${childStr}]`);
    } else if (node.stat) {
      parts.push(node.id);
    }
  }
  return parts.join(',');
}

/** Плоский список `allfilters` как дерево без вложенности — для общего `FilterTreeSelect`. */
export function flagsToFilterTree(flags: AuctionFilterFlag[]): FilterTreeNode[] {
  return flags.filter((f) => f.id !== null).map((f) => ({ id: f.id as string, name: f.name, stat: f.stat }));
}

/** Плоский список `arrayBuyFrom` (`sortBuy`/`allow_buy_res`, поля `ln`/`cur`, а не `name`/`stat`) как дерево — это всегда ресурсы, есть иконка. */
export function sortOptionsToFilterTree(options: AuctionSortOption[]): FilterTreeNode[] {
  return options
    .filter((o) => o.id !== null)
    .map((o) => ({
      id: o.id as string,
      name: o.ln,
      stat: o.cur,
      icon: hasResourceIcon(o.id as string) ? { kind: 'img' as const, url: resourceIconUrl(o.id as string) } : undefined,
    }));
}

/**
 * Дерево категории UNITS — race→level→юнит (`auctionmenuclass.as:569-597`,
 * `unitdata[i].race/level/id/name` из клиентского кэша `unit_table`). В
 * порте та же группировка строится прямо из `LocalData.units` (те же
 * поля `race`/`level` уже распарсены из `loaddata`, отдельный кэш не
 * нужен). `resolveGroupName` — резолвер `race_{race}`/`level_{level}` в
 * `game.getLocalNames()`-эквиваленте (`resolveLocalName`), с читаемым
 * фолбэком, если такой строки в живых данных не нашлось.
 */
export function buildUnitCategoryTree(
  units: Map<string, LocalUnit>,
  resolveGroupName: (id: string) => string | null,
): FilterTreeNode[] {
  const byRace = new Map<string, Map<string, FilterTreeNode[]>>();
  for (const unit of units.values()) {
    const race = unit.race ?? '';
    const level = unit.level ?? '';
    if (!byRace.has(race)) byRace.set(race, new Map());
    const byLevel = byRace.get(race)!;
    if (!byLevel.has(level)) byLevel.set(level, []);
    byLevel.get(level)!.push({ id: unit.id, name: unit.ln, stat: true, icon: { kind: 'img', url: unitIconUrl(unit.id) } });
  }

  const tree: FilterTreeNode[] = [];
  for (const [race, byLevel] of byRace) {
    const levelNodes: FilterTreeNode[] = [];
    for (const [level, leaves] of byLevel) {
      const levelKey = `level_${level}`;
      levelNodes.push({ id: levelKey, name: resolveGroupName(levelKey) ?? `${level} уровень`, stat: true, children: leaves });
    }
    const raceKey = `race_${race}`;
    tree.push({ id: raceKey, name: resolveGroupName(raceKey) ?? `Раса ${race}`, stat: true, children: levelNodes });
  }
  return tree;
}

/**
 * Дерево категории ARTEFACTS — scope→{SIZE→sizes, BONUS→bonuses},
 * буквальный порт `auctionmenuclass.as:598-663` (см. докстринг файла про
 * найденный формат `<artscope>/<artsize>/<artbonus>`). Ветка scope не
 * создаётся вовсе без детей вообще НЕ получает `children` (как и в
 * оригинале — `obj.childArr` там просто не создаётся), а не пустой
 * массив — иначе `serializeFilterTree()` принял бы её за пустую ветку и
 * никогда не сериализовал (см. её же комментарий про `children.length>0`).
 * Графика: SIZE — цветной кружок (`artefactSizeColor`, тот же словарь,
 * что раскрашивает scope-рамку у обычных артефактов); BONUS — иконка
 * ресурса, только если бонус реально совпадает с известным ресурсом
 * (`hasResourceIcon`) — большинство атрибутов-бонусов (ATTACK и т.п.)
 * иконки не имеют, не выдумываем.
 */
export function buildArtefactCategoryTree(
  scopes: Map<string, LocalArtefactScope>,
  sizes: Map<string, LocalSimpleEntry>,
  bonuses: Map<string, LocalSimpleEntry>,
  resolveGroupName: (id: string) => string | null,
): FilterTreeNode[] {
  const tree: FilterTreeNode[] = [];
  for (const scope of scopes.values()) {
    const children: FilterTreeNode[] = [];

    const sizeLeaves = scope.sizeIds
      .map((sid) => sizes.get(sid))
      .filter((s): s is LocalSimpleEntry => !!s)
      .map((s) => {
        const color = artefactSizeColor(s.id);
        return { id: s.id, name: s.ln, stat: true, icon: color ? ({ kind: 'swatch', color } as const) : undefined };
      });
    if (sizeLeaves.length > 0) {
      children.push({ id: 'SIZE', name: resolveGroupName('SIZE') ?? 'Размер', stat: true, children: sizeLeaves });
    }

    const bonusLeaves = scope.bonusIds
      .map((bid) => bonuses.get(bid))
      .filter((b): b is LocalSimpleEntry => !!b)
      .map((b) => ({
        id: b.id,
        name: b.ln,
        stat: true,
        icon: hasResourceIcon(b.id) ? ({ kind: 'img', url: resourceIconUrl(b.id) } as const) : undefined,
      }));
    if (bonusLeaves.length > 0) {
      children.push({ id: 'BONUS', name: resolveGroupName('BONUS') ?? 'Бонус', stat: true, children: bonusLeaves });
    }

    tree.push({
      id: scope.id,
      name: scope.name,
      stat: true,
      ...(children.length > 0 ? { children } : {}),
      icon: { kind: 'scope', maskUrl: artefactScopeMaskUrl(scope.id) },
    });
  }
  return tree;
}

/**
 * Дерево категории "что купить" по текущей вкладке (`category.id`) —
 * см. докстринг файла про три схемы.
 */
export function buildCategoryTree(
  category: AuctionCategoryOption | undefined,
  units: Map<string, LocalUnit>,
  artefactScopes: Map<string, LocalArtefactScope>,
  artefactSizes: Map<string, LocalSimpleEntry>,
  artefactBonuses: Map<string, LocalSimpleEntry>,
  resolveGroupName: (id: string) => string | null,
): FilterTreeNode[] {
  if (!category) return [];
  if (category.id === 'UNITS') return buildUnitCategoryTree(units, resolveGroupName);
  if (category.id === 'ARTEFACTS') return buildArtefactCategoryTree(artefactScopes, artefactSizes, artefactBonuses, resolveGroupName);
  if (category.id === 'RESOURCES') {
    return category.items
      .filter((i) => i.id !== null)
      .map((i) => ({
        id: i.id as string,
        name: i.ln,
        stat: true,
        icon: hasResourceIcon(i.id as string) ? ({ kind: 'img', url: resourceIconUrl(i.id as string) } as const) : undefined,
      }));
  }
  return [];
}

export interface AuctionFilterCommand {
  /** typebuy — id текущей вкладки категории (sort_type[i].id). */
  category: string;
  page: number;
  pageSize: number;
  /** select — сериализация дерева категории (arrayTestTree). */
  select: string;
  /** filter — сериализация общего чек-листа (pages.allfilters). */
  filter: string;
  /** buy_by — сериализация списка "чем платить" (arrayBuyFrom), несмотря на имя параметра "sortarr" в оригинале. */
  buyBy: string;
  /** sort_place — id выбранного scope (allow_scope_array/sortPlace). */
  scope: string;
  /** "{id1}|{dir1},{id2}|{dir2},{id3}|{dir3}" — reqvestdata.as:653. */
  sort: string;
  /** Отправляется только если длина > 3 (reqvestdata.as:655). */
  trader: string;
}

/**
 * Категория «В» (`xmlparsers.as:1866-1871`) — лот дополнительно
 * запрещён к покупке на клиенте, если у игрока не хватает ресурса
 * `tradeBy` на цену покупки (мгновенную, если `immediate=="1"`, иначе
 * следующую ставку `nextPrice`) — даже если сервер прислал `disable=0`.
 */
export function computeLotDisabled(lot: AuctionLot, playerResourceCurrent: number): boolean {
  if (lot.serverDisabled) return true;
  const priceToBuy = lot.immediate === '1' ? lot.currentPrice : lot.nextPrice;
  return playerResourceCurrent < priceToBuy;
}
