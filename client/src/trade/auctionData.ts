/**
 * Разбор mark `auction` — список лотов аукциона (Фаза 3.5,
 * `auctionmenuclass.as`, источник: docs/client-economy-social-ui.md §1.2),
 * портировано из `xmlparsers.as:1803-1983` (`auction_menu_parser`).
 *
 * <auction curpage="" pages="" onpage="" order="" trader="">
 *   <au id="" enddate="" amount="" getprice="K:v;N:v;S:v;F:v;P:v;T:v"
 *       price="" best_price="" seller="" tradeby="" tradescope=""
 *       immediate="0|1" libname="" resupkeep="" id_cat_on=""
 *       death_message="" customer="" disable="0|1" nextprice=""
 *       maxprice=""/>
 *   <sort_place sortname="" lname="" cur="0|1"/>   (сортировка "где")
 *   <sort_buy sortname="" lname="" cur="0|1"/>     (сортировка "чем платить")
 *   ...
 * </auction>
 *
 * НЕ портированы в этой версии (см. §1.2 — дерево фильтров по трём
 * разным схемам в зависимости от категории товара, сериализуется в
 * клиентский, не XML-протокол формат `ArrToStr` только при ОТПРАВКЕ):
 * `listf`, `sort_type`/вложенные `sort_name`, `sortl`/`listallow`/`sortf`
 * — сам список лотов и покупка полностью рабочие без них, форма фильтра
 * — отдельная задача.
 */
import { parseXmlFragment } from '../protocol/response';

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

export interface AuctionData {
  curPage: string | null;
  maxPages: string | null;
  onPage: string | null;
  order: string | null;
  trader: string | null;
  lots: AuctionLot[];
  sortPlace: AuctionSortOption[];
  sortBuy: AuctionSortOption[];
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
      buyer: el.getAttribute('customer'),
      serverDisabled: el.getAttribute('disable') === '1',
      nextPrice: nextPriceAttr !== null ? Number(nextPriceAttr) : currentPrice + 1,
      maxPrice: el.getAttribute('maxprice'),
    });
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
  };
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
