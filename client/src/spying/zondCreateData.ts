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
 */
import { parseXmlFragment } from '../protocol/response';

export type ZondPriceMap = Partial<Record<'K' | 'N' | 'S' | 'F' | 'P' | 'W' | 'C', number>>;

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
 * docs/client-side-computed-logic.md и docs/client-combat-ui.md §11
 * (`calculatePriceWithProgressiv`, zondcreateclass.as:29-67). Каждый
 * следующий зонд дороже предыдущего в `deltaPrice` раз (сумма
 * геометрической прогрессии между позициями `ready` и `ready+count`).
 */
export function calculateProgressivePrice(
  basePrice: number,
  deltaPrice: number,
  currentReady: number,
  countForCreate: number,
): number {
  if (deltaPrice === 1) {
    return basePrice * countForCreate;
  }
  const geometricSum = (n: number) => (1 - deltaPrice ** n) / (1 - deltaPrice);
  return basePrice * (geometricSum(currentReady + countForCreate) - geometricSum(currentReady));
}
