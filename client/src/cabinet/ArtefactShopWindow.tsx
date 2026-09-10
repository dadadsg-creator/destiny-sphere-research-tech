/**
 * Магазин артефактов кабинета — artefact_shop_engine.as, см. docs/
 * client-economy-social-ui.md §5.5 и artefactShopData.ts (баг фильтра,
 * позиционное сопоставление filter_data, корзина).
 *
 * **Находка**: `artefactData.maxCount`, на который в оригинале опирается
 * степпер количества (`artefact_shop_engine.as:728`), НИГДЕ не
 * заполняется парсером ответа (`xmlparsers.as:1600-1664` не содержит
 * `art_obj.maxCount = ...`) — сервер, судя по всему, не присылает такой
 * атрибут вовсе (или клиент его не читает). Степпер количества в
 * оригинале фактически всегда без верхней границы — здесь то же самое
 * (см. `UNBOUNDED` ниже), а не придуманный лимит.
 */
import { useState } from 'preact/hooks';
import { Stepper } from '../ui/Stepper';
import {
  checkArtefactFilter,
  computeCartTotal,
  recomputeCart,
  shouldSkipFilterRefresh,
  type ArtefactShopData,
  type CartEntry,
} from './artefactShopData';

const UNBOUNDED = Number.MAX_SAFE_INTEGER;
const ALL_FILTER_ID = '0';

export interface ArtefactShopWindowProps {
  data: ArtefactShopData;
  onClose: () => void;
  onBuy: (cart: CartEntry[], concurrency: 'gold' | 'silver') => void;
}

export function ArtefactShopWindow({ data, onClose, onBuy }: ArtefactShopWindowProps) {
  const [appliedFilters, setAppliedFilters] = useState({ f1: ALL_FILTER_ID, f2: ALL_FILTER_ID });
  const [pendingF1, setPendingF1] = useState(ALL_FILTER_ID);
  const [pendingF2, setPendingF2] = useState(ALL_FILTER_ID);
  const [concurrency, setConcurrency] = useState<'gold' | 'silver'>('gold'); // changeConcurency(1) по умолчанию — золото
  const [amounts, setAmounts] = useState<Record<string, number>>({});
  const [cart, setCart] = useState<CartEntry[]>([]);
  const [showCart, setShowCart] = useState(false);

  const visible = data.artefacts.filter((a) => checkArtefactFilter(a, appliedFilters.f1, appliedFilters.f2));

  const applyFilter = () => {
    /** Сознательно воспроизведён баг оригинала — см. докстринг artefactShopData.ts. */
    if (shouldSkipFilterRefresh(appliedFilters, pendingF1, pendingF2)) return;
    setAppliedFilters({ f1: pendingF1, f2: pendingF2 });
  };

  const setAmount = (id: string, value: number) => {
    const nextAmounts = { ...amounts, [id]: value };
    setAmounts(nextAmounts);
    setCart(recomputeCart(cart, visible, nextAmounts));
  };

  const { count, totalPrice } = computeCartTotal(cart, concurrency);

  return (
    <div class="ds-window">
      <div class="ds-window__titlebar">
        <span class="ds-window__title">Магазин артефактов</span>
        <button type="button" class="ds-window__close" onClick={onClose} aria-label="Закрыть">
          ×
        </button>
      </div>
      <div class="ds-window__content">
        <div>
          <select value={pendingF1} onChange={(e) => setPendingF1((e.target as HTMLSelectElement).value)}>
            <option value={ALL_FILTER_ID}>Все</option>
            {data.filters[0]?.listTypes.map((t) => (
              <option key={t.id} value={t.id}>
                {t.name}
              </option>
            ))}
          </select>
          <select value={pendingF2} onChange={(e) => setPendingF2((e.target as HTMLSelectElement).value)}>
            <option value={ALL_FILTER_ID}>Все</option>
            {data.filters[1]?.listTypes.map((t) => (
              <option key={t.id} value={t.id}>
                {t.name}
              </option>
            ))}
          </select>
          <button type="button" class="ds-popup__btn" onClick={applyFilter}>
            Фильтр
          </button>

          <label style={{ marginLeft: '12px' }}>
            <input type="radio" name="concurrency" checked={concurrency === 'gold'} onChange={() => setConcurrency('gold')} /> Золото
          </label>
          <label>
            <input type="radio" name="concurrency" checked={concurrency === 'silver'} onChange={() => setConcurrency('silver')} /> Серебро
          </label>
        </div>

        <table>
          <thead>
            <tr>
              <th>Название</th>
              <th>Бонус</th>
              <th>Цена</th>
              <th>Кол-во</th>
            </tr>
          </thead>
          <tbody>
            {visible.map((art) => (
              <tr key={art.id} class="ds-army-row">
                <td>{art.name}</td>
                <td>{art.bonusText}</td>
                <td>{concurrency === 'gold' ? art.priceGold : art.priceSilver}</td>
                <td>
                  <Stepper value={amounts[art.id] ?? 0} min={0} max={UNBOUNDED} onChange={(v) => setAmount(art.id, v)} />
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        <div>
          В корзине: {count} шт. на сумму {totalPrice}
        </div>
        <button type="button" class="ds-popup__btn" disabled={cart.length === 0} onClick={() => setShowCart(true)}>
          Корзина / Купить
        </button>
      </div>

      {showCart && (
        <div class="ds-window">
          <div class="ds-window__titlebar">
            <span class="ds-window__title">Корзина</span>
            <button type="button" class="ds-window__close" onClick={() => setShowCart(false)} aria-label="Закрыть">
              ×
            </button>
          </div>
          <div class="ds-window__content">
            <table>
              <tbody>
                {cart.map((entry) => (
                  <tr key={entry.artefactId} class="ds-army-row">
                    <td>{entry.data.name}</td>
                    <td>{entry.count} шт.</td>
                  </tr>
                ))}
              </tbody>
            </table>
            <div>Итого: {totalPrice} {concurrency === 'gold' ? 'золота' : 'серебра'}</div>
            <button
              type="button"
              class="ds-popup__btn"
              onClick={() => {
                onBuy(cart, concurrency);
                setShowCart(false);
                setCart([]);
                setAmounts({});
              }}
            >
              Подтвердить покупку
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
