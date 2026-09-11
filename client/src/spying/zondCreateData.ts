/**
 * Разбор mark `zondcreate` — тренировка (постройка) зондов (Фаза 3.2,
 * `zondcreateclass.as`, источник: docs/client-combat-ui.md §11),
 * портировано 1:1 из `xmlparsers.as:3437-3499` (`zondcreate_parser`) и
 * серверного `ds/classes/TL/TL_SpyTrainUnit.class.php` (уже читан для
 * резолва риска №5 — `type`: `SPY_ZOND_SCOUT`=1, `SPY_ZOND_EXPLORER`=2).
 *
 * <zondcreate battle_xp="" alert="">
 *   <allowzond type="1|2" id="" count="" inaction="" already="" awail=""
 *              price="K:v,N:v,S:v,F:v,P:v,W:v,C:v"
 *              delta="K:v,N:v,S:v,F:v,P:v,W:v,C:v"/>
 *   ...
 * </zondcreate>
 *
 * Цена и её коэффициент роста — однобуквенные ключи (K/N/S/F/P/W/C),
 * запятая между парами (НЕ то же соглашение, что у `<armycalculator>`,
 * §2 — там точка с запятой и двухбуквенные KA/NA/ST/FO/PR/SC).
 *
 * **Подтверждено сервером (2026-09-08, `GL_SpyCountZondPrice.class.php`,
 * `TL_SpyTrainUnit.class.php:37`)**: реальный `delta` — ОДНО число на
 * тип зонда (`1 + worldSettings.get(<unit_name>_PRODUCTION_COST_INCREASE)`),
 * буквально продублированное в строке для K/N/S/F/P — не отдельное
 * значение на каждый ресурс, хотя формат ключ-значение это структурно
 * позволяет. `ZondPriceMap`/`parsePriceMap` здесь всё равно разбирают
 * позиционно-нейтрально (что прислали, то и легло в карту) — менять
 * код не нужно, но подставлять на UI "разную" дельту на ресурс не стоит
 * ожидать от живого сервера.
 */
import { parseXmlFragment } from '../protocol/response';

/**
 * `WR` (военный ранг) добавлен к обычным K/N/S/F/P/W/C — сервер шлёт его
 * в тех же `price="..."`/`delta="..."` картах (`zondcreateclass.as:104`,
 * `calculatePriceWithProgressiv(obj.price["WR"], obj.delta["WR"], ...,
 * false)`), просто `parsePriceMap()` ниже раньше не знал этот ключ в
 * типе (в рантайме он всё равно попадал в объект, раз парсер кладёт
 * "что пришло, то и легло" — но TS не давал явно на него сослаться).
 */
export type ZondPriceMap = Partial<Record<'K' | 'N' | 'S' | 'F' | 'P' | 'W' | 'C' | 'WR', number>>;

export interface AllowZondCreate {
  /** "1"/"2" — см. TL_SpyTrainUnit.class.php:17 (SPY_ZOND_SCOUT/EXPLORER). */
  type: string;
  /** Буквальный id типа ("Z1"/"Z2" судя по §10) — здесь это numeric id_unit из ds_unit (83/88), см. TL_SpyTrainUnit. */
  id: string;
  /** Сколько всего разрешено иметь. */
  count: number;
  /** Сколько сейчас строится. */
  inAction: number;
  /** Сколько уже готово. */
  already: number;
  awail: string | null;
  /** Базовая цена постройки ОДНОГО зонда (за первую единицу). */
  price: ZondPriceMap;
  /** Коэффициент роста цены на каждый следующий зонд, по каждому ресурсу отдельно. */
  delta: ZondPriceMap;
}

export interface ZondCreateData {
  battleXp: string | null;
  alert: string | null;
  allowZond: AllowZondCreate[];
}

function parsePriceMap(raw: string | null): ZondPriceMap {
  const map: ZondPriceMap = {};
  for (const pair of (raw ?? '').split(',').filter(Boolean)) {
    const [key, value] = pair.split(':');
    map[key as keyof ZondPriceMap] = Number(value);
  }
  return map;
}

export function parseZondCreate(xml: string): ZondCreateData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'zondcreate') {
    throw new Error(`Ожидался корневой тег <zondcreate>, получено: ${root?.nodeName}`);
  }

  const allowZond: AllowZondCreate[] = [];
  for (const el of Array.from(root.children)) {
    if (el.nodeName !== 'allowzond') continue;
    allowZond.push({
      type: el.getAttribute('type') ?? '',
      id: el.getAttribute('id') ?? '',
      count: Number(el.getAttribute('count')) || 0,
      inAction: Number(el.getAttribute('inaction')) || 0,
      already: Number(el.getAttribute('already')) || 0,
      awail: el.getAttribute('awail'),
      price: parsePriceMap(el.getAttribute('price')),
      delta: parsePriceMap(el.getAttribute('delta')),
    });
  }

  return {
    battleXp: root.getAttribute('battle_xp'),
    alert: root.getAttribute('alert'),
    allowZond,
  };
}

/**
 * Прогрессивная цена постройки — категория «В», уже задокументирована в
 * docs/client-side-computed-logic.md и docs/client-combat-ui.md §11.
 * Портировано 1:1 из `zondcreateclass.as:11-51` (`calculatePriceWithProgressiv`)
 * — построчно перепроверено 2026-09-11 (Фаза 4, «граничные значения не
 * только типичный случай»), нашлись два расхождения с прежней версией
 * этой функции:
 *
 * 1. **`keyRound`** (5-й параметр оригинала, по умолчанию `true` —
 *    `Math.ceil` применяется). Единственное место, где он явно передаётся
 *    `false` — вызов для `WR` (военный ранг, `:104`); все 6 денежно-
 *    ресурсных вызовов (K/N/S/F/P/W) и `C` (время) его не передают, то
 *    есть остаются на дефолтном округлении вверх. Раньше округление
 *    делалось СНАРУЖИ, безусловно для всех полей сразу
 *    (`ZondCreateWindow.tsx::formatPrice`) — для `WR` это разошлось бы с
 *    оригиналом (там `WR` НЕ округляется).
 * 2. **`countForCreate === 0` — не ноль, а "сколько стоят уже готовые"**
 *    (`:17-20,27-30`): при нулевом запрашиваемом количестве оригинал
 *    возвращает не 0, а `pricefull` — то же выражение, что и в общем
 *    случае, но с `count_for_create=0` внутри показателя степени, что
 *    математически равно `priceready` (сумме прогрессии ДО текущей
 *    позиции). Не "странная утечка", а буквально то, что написано в
 *    коде — воспроизведено 1:1, не округлено до интуитивно ожидаемого 0
 *    (см. общее правило сессии: воспроизводить находки категории «В»
 *    как есть, не "исправлять" на лету).
 */
export function calculateProgressivePrice(
  basePrice: number,
  deltaPrice: number,
  currentReady: number,
  countForCreate: number,
  keyRound = true,
): number {
  const geometricSum = (n: number) => (1 - deltaPrice ** n) / (1 - deltaPrice);
  let price: number;
  if (deltaPrice === 1) {
    price = countForCreate !== 0 ? countForCreate * basePrice : basePrice;
  } else {
    const priceFull = basePrice * geometricSum(currentReady + countForCreate);
    const priceReady = basePrice * geometricSum(currentReady);
    price = countForCreate !== 0 ? priceFull - priceReady : priceFull;
  }
  return keyRound ? Math.ceil(price) : price;
}
