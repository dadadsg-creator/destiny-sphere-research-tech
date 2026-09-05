/**
 * Окно «Биржа» (выставление лота на продажу) — trade_menu_class.as, см.
 * docs/client-economy-social-ui.md §1.1. Данные — tradeData.ts
 * (parseTradeMenu, computeTradePriceCorridor, buildSellCommand).
 *
 * Клиентская валидация — буквально из оригинала (init_button,
 * trade_menu_class.as:1069-1196):
 *  - товар выбран, цена/время/количество > 0, локация выбрана;
 *  - категория "ресурс" (1) — количество >= minQuantity (минимальный лот
 *    от сервера);
 *  - категория "артефакт" (3) — количество <= amount выбранного стека;
 *  - режим "личная продажа" — имя покупателя 1-20 символов, иначе поле
 *    молча очищается и флаг продажи "для всех" ставится автоматически
 *    (здесь: просто не даём отправить, тумблер не автопереключаем).
 *
 * Калькулятор (4-я вкладка, только премиум, drawcalc() — чисто локальный
 * расчёт без сети) — НЕ портирован в этой версии.
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import type { TabDef } from '../ui/tabs';
import { buildSellCommand, computeTradePriceCorridor, type SellCommand, type TradeMenuData } from './tradeData';

export interface SellWindowProps {
  data: TradeMenuData;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onSell: (command: SellCommand) => void;
}

const CATEGORY_LABELS: Record<number, string> = { 1: 'Ресурсы', 2: 'Юниты', 3: 'Артефакты' };

export function SellWindow({ data, tabs, onTabClick, onClose, onSell }: SellWindowProps) {
  const [category, setCategory] = useState(1);
  const articles = data.articlesByCategory[category] ?? [];
  const [articleId, setArticleId] = useState(articles[0]?.id ?? '');
  const [tradeByIndex, setTradeByIndex] = useState(0);
  const [amount, setAmount] = useState(0);
  const [price, setPrice] = useState(0);
  const [tradeTime, setTradeTime] = useState(data.time2Trade);
  const [scope, setScope] = useState(data.scopes[0]?.id ?? '');
  const [isPersonal, setIsPersonal] = useState(false);
  const [nick, setNick] = useState('');

  const article = articles.find((a) => a.id === articleId);
  const tradesRowIndex = data.matrix?.trades.findIndex((t) => Number(t) === category) ?? -1;
  const matrixRow = tradesRowIndex >= 0 ? data.matrix?.data[tradesRowIndex] : undefined;
  const resColumn = data.matrix?.reses[tradeByIndex];
  const cell = matrixRow?.[tradeByIndex];

  const totalPrice = price * amount;
  const corridor =
    cell?.allow && cell.minOrder !== undefined ? computeTradePriceCorridor(totalPrice, cell.minOrder) : null;

  const nameValid = !isPersonal || (nick.length >= 1 && nick.length <= 20);
  const quantityValid =
    (category !== 1 || amount >= data.minQuantity) && (category !== 3 || !article || amount <= article.amount);

  const canSell = !!article && !!resColumn && price > 0 && tradeTime > 0 && amount > 0 && !!scope && nameValid && quantityValid;

  function handleSell() {
    if (!article || !resColumn) return;
    onSell(
      buildSellCommand({
        scope,
        category,
        article: article.id ?? '',
        amount,
        tradeTime,
        tradeByCategory: resColumn.category,
        tradeByArticle: resColumn.id,
        price,
        isAuction: !isPersonal,
        isPersonal,
        nick: isPersonal ? nick : '',
      }),
    );
  }

  return (
    <Window title="Биржа" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <div class="ds-spying__row">
        <span class="ds-spying__label">Категория:</span>
        {[1, 2, 3].map((cat) => (
          <label key={cat} class="ds-spying__choice">
            <input
              type="radio"
              name="sell-category"
              checked={category === cat}
              onChange={() => {
                setCategory(cat);
                setArticleId(data.articlesByCategory[cat]?.[0]?.id ?? '');
              }}
            />
            {CATEGORY_LABELS[cat]}
          </label>
        ))}
      </div>

      <div class="ds-spying__row">
        <span class="ds-spying__label">Товар:</span>
        <select value={articleId} onChange={(e) => setArticleId((e.target as HTMLSelectElement).value)}>
          {articles.map((a) => (
            <option key={a.id} value={a.id ?? ''}>
              {a.libname ?? a.id} (есть: {a.amount})
            </option>
          ))}
        </select>
      </div>

      <div class="ds-spying__row">
        <span class="ds-spying__label">Взамен:</span>
        <select value={tradeByIndex} onChange={(e) => setTradeByIndex(Number((e.target as HTMLSelectElement).value))}>
          {(data.matrix?.reses ?? []).map((res, i) => (
            <option key={i} value={i} disabled={!data.matrix?.data[tradesRowIndex]?.[i]?.allow}>
              {res.name}
            </option>
          ))}
        </select>
      </div>

      <div class="ds-spying__row">
        <span class="ds-spying__label">Количество:</span>
        <Stepper value={amount} min={0} onChange={setAmount} />
        {category === 1 && <span class="ds-train-unit__idle">мин. лот: {data.minQuantity}</span>}
      </div>

      <div class="ds-spying__row">
        <span class="ds-spying__label">Цена за единицу:</span>
        <input
          type="number"
          class="ds-stepper__input"
          value={price}
          onInput={(e) => setPrice(Number((e.target as HTMLInputElement).value))}
        />
        {corridor && (
          <span class="ds-train-unit__idle">
            коридор: {corridor.min.toFixed(1)}–{corridor.max.toFixed(1)}
          </span>
        )}
      </div>

      <div class="ds-spying__row">
        <span class="ds-spying__label">Время (ходов):</span>
        <Stepper value={tradeTime} min={1} max={data.time2Trade} onChange={setTradeTime} />
      </div>

      <div class="ds-spying__row">
        <span class="ds-spying__label">Локация:</span>
        <select value={scope ?? ''} onChange={(e) => setScope((e.target as HTMLSelectElement).value)}>
          {data.scopes.map((s) => (
            <option key={s.id} value={s.id ?? ''}>
              {s.ln}
            </option>
          ))}
        </select>
      </div>

      {data.flags.person && (
        <div class="ds-spying__row">
          <label class="ds-spying__choice">
            <input type="checkbox" checked={isPersonal} onChange={() => setIsPersonal(!isPersonal)} />
            Личная продажа (не аукцион)
          </label>
          {isPersonal && (
            <input
              type="text"
              class="ds-stepper__input"
              placeholder="Ник покупателя"
              value={nick}
              onInput={(e) => setNick((e.target as HTMLInputElement).value)}
            />
          )}
        </div>
      )}

      <button type="button" class="ds-popup__btn" disabled={!canSell} onClick={handleSell}>
        Выставить лот
      </button>
    </Window>
  );
}
