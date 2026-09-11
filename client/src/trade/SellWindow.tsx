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
 *
 * **Дополнение (2026-09-11, категория «В» §2.2/§2.4)**: `reinitupkeep()`
 * (`trade_menu_class.as:853-869`) на каждое изменение количества/времени
 * пересчитывает и показывает готовое время лота + расход ресурсов на
 * его поддержание — раньше отсутствовало целиком, см.
 * `tradeData.ts::computeTradeUpkeep()`. Отдельно от этого — предпросмотр
 * налога с продажи (`setPrice()`, `trade_menu_class.as:1090-1096`) тоже
 * отсутствовал (был только коридор цены из того же `tax`) — добавлен,
 * см. `tradeData.ts::computeTradeTax()`.
 *
 * **Исправление (2026-09-11)**: категория "ресурс" (1) показывала сырое
 * машинное имя (`NANO_CRYSTAL`) и в списке "Товар", и в списке "Взамен"
 * — `TradeArticle.ln`/`TradeResourceColumn.ln` в этом формате XML
 * (`<reses>name:price:cat_id</reses>`, `<article libname="">`) сервер
 * никогда не присылает (`ln` всегда `null`, см. докстринг tradeData.ts).
 * Названия — НЕ отсебятина: `resourceDisplayName()`/`unitDisplayName()`
 * (`resourceIcons.ts`) читают живую локализацию из `loaddata`
 * (`<att>`/`<unit>`, зеркало `game.getLocalNames()`), тот же источник,
 * что уже используют иконки ресурсов/юнитов везде в порте.
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import type { TabDef } from '../ui/tabs';
import { ResourceList, type ResourceCost } from '../ui/ResourceValue';
import { RESOURCE_LETTER_TO_NAME, resourceDisplayName, unitDisplayName } from '../data/resourceIcons';
import { formatTurnTime } from '../data/turnTime';
import {
  buildSellCommand,
  computeTradePriceCorridor,
  computeTradeTax,
  computeTradeUpkeep,
  type SellCommand,
  type TradeArticle,
  type TradeMenuData,
} from './tradeData';

export interface SellWindowProps {
  data: TradeMenuData;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onSell: (command: SellCommand) => void;
}

const CATEGORY_LABELS: Record<number, string> = { 1: 'Ресурсы', 2: 'Юниты', 3: 'Артефакты' };

/** Категория 1 (ресурс)/2 (юнит) — libname это машинное имя, локализуем через живую loaddata (см. докстринг файла). Артефакты (3) — своя, ещё не подключённая локализация (см. docs), оставлены как есть. */
function articleLabel(a: TradeArticle): string {
  if (a.category === 1 && a.libname) return resourceDisplayName(a.libname);
  if (a.category === 2 && a.libname) return unitDisplayName(a.libname);
  return a.libname ?? a.id ?? '';
}

/** То же самое для колонки "Взамен" — тот же формат `<reses>`, тот же вечно-null `ln`. */
function resourceColumnLabel(name: string): string {
  return resourceDisplayName(name);
}

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
  const tax = data.flags.nalog && cell?.allow && cell.tax ? computeTradeTax(totalPrice, cell.tax) : null;
  const upkeep = article ? computeTradeUpkeep(article, amount, tradeTime) : {};
  const upkeepCosts: ResourceCost[] = Object.entries(upkeep).map(([letter, value]) => ({
    name: RESOURCE_LETTER_TO_NAME[letter] ?? letter,
    value: value ?? 0,
  }));

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
              {articleLabel(a)} (есть: {a.amount})
            </option>
          ))}
        </select>
      </div>

      <div class="ds-spying__row">
        <span class="ds-spying__label">Взамен:</span>
        <select value={tradeByIndex} onChange={(e) => setTradeByIndex(Number((e.target as HTMLSelectElement).value))}>
          {(data.matrix?.reses ?? []).map((res, i) => (
            <option key={i} value={i} disabled={!data.matrix?.data[tradesRowIndex]?.[i]?.allow}>
              {resourceColumnLabel(res.name)}
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

      {tax && (
        <div class="ds-spying__row">
          <span class="ds-spying__label">Налог с продажи:</span>
          <span class="ds-train-unit__idle">
            {tax.taxAmount} ({cell?.tax}%) — получите {tax.afterTax}
          </span>
        </div>
      )}

      <div class="ds-spying__row">
        <span class="ds-spying__label">Время (ходов):</span>
        <Stepper value={tradeTime} min={1} max={data.time2Trade} onChange={setTradeTime} />
        <span>{formatTurnTime(tradeTime * (Number(data.turn) || 0))}</span>
      </div>

      {upkeepCosts.length > 0 && (
        <div class="ds-spying__row">
          <span class="ds-spying__label">Расход на поддержание лота:</span>
          <ResourceList costs={upkeepCosts} />
        </div>
      )}

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
              class="ds-text-input"
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
