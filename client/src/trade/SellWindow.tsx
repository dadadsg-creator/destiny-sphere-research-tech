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
 * **Калькулятор (2026-09-18)** — 4-я вкладка, только премиум (`game.
 * getPremium()`, `trade_menu_class.as:255-262`), `drawcalc()` — чисто
 * локальный расчёт без сети: "сколько ресурсов нужно на N экземпляров
 * юнита выбранной расы", с наценкой и конвертацией через курсы обмена
 * (`tradeData.ts::computeTradeCalculator`). Список юнитов расы — те же
 * `articlesByCategory[5|6|7]` (Воранеры/Лиенсу/Псолао, см. докстринг
 * tradeData.ts), содержание ресурсов — `LocalData.units.attrs`, не
 * живые поля статьи (у категорий 5-7 они в оригинале пустые). Стартовые
 * значения наценки/курсов — нейтральный плейсхолдер (0%/1:1, не найден
 * в декомпиле), сразу редактируемый.
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
 *
 * **Исправление (2026-09-18)**: категория "артефакт" (3) тоже теперь
 * локализована — `artefactDisplayName()` (`resourceIcons.ts`) читает
 * `LocalData.artefacts` (та же `loaddata`, оказалась уже разобрана,
 * см. докстринг функции — предыдущая запись про "отдельную, не
 * подключённую локализацию" была неверной).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import type { TabDef } from '../ui/tabs';
import { ResourceList, type ResourceCost } from '../ui/ResourceValue';
import { RESOURCE_LETTER_TO_NAME, resourceDisplayName, unitDisplayName, artefactDisplayName } from '../data/resourceIcons';
import { localData } from '../data/localDataStore';
import { resolveLocalName } from '../data/localNameResolver';
import { formatTurnTime } from '../data/turnTime';
import type { LocalUnit } from '../data/localData';
import type { Race } from '../map/buildingTypes';
import {
  buildSellCommand,
  computeTradePriceCorridor,
  computeTradeTax,
  computeTradeUpkeep,
  computeTradeCalculator,
  type SellCommand,
  type TradeArticle,
  type TradeMenuData,
  type TradeCalculatorRates,
} from './tradeData';

export interface SellWindowProps {
  data: TradeMenuData;
  localUnits: Map<string, LocalUnit>;
  isPremium: boolean;
  playerRace: Race;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onSell: (command: SellCommand) => void;
}

const CATEGORY_LABELS: Record<number, string> = { 1: 'Ресурсы', 2: 'Юниты', 3: 'Артефакты', 4: 'Калькулятор' };

/** Категория статьи расового списка юнитов калькулятора (`articlesByCategory[5|6|7]`, см. докстринг tradeData.ts). */
const RACE_TO_CALC_CATEGORY: Record<Race, number> = { tech: 5, bio: 6, psi: 7 };
const CALC_RACE_ORDER: Race[] = ['tech', 'bio', 'psi'];
/** `game.getLocalNames("TECH"/"BIO"/"PSI").ln` буквально (`trade_menu_class.as:364-366`) — те же ключи резолвит resolveLocalName. */
const CALC_RACE_KEY: Record<Race, string> = { tech: 'TECH', bio: 'BIO', psi: 'PSI' };
const CALC_RACE_FALLBACK_LABEL: Record<Race, string> = { tech: 'Воранеры', bio: 'Лиенсу', psi: 'Псолао' };

function raceLabel(race: Race): string {
  return resolveLocalName(localData.value, CALC_RACE_KEY[race]) ?? CALC_RACE_FALLBACK_LABEL[race];
}

/** `truncFloat()` (`trade_menu_class.as:438-457`) — 2 знака после запятой. */
function round2(value: number): number {
  return Math.round(value * 100) / 100;
}

/**
 * Калькулятор ресурсов на N юнитов расы — см. докстринг файла и
 * `tradeData.ts::computeTradeCalculator`. Наценка/курсы — локальный
 * стейт, не сеть.
 */
function TradeCalculatorPanel({
  data,
  localUnits,
  playerRace,
}: {
  data: TradeMenuData;
  localUnits: Map<string, LocalUnit>;
  playerRace: Race;
}) {
  const [race, setRace] = useState<Race>(playerRace);
  const items = data.articlesByCategory[RACE_TO_CALC_CATEGORY[race]] ?? [];
  const [itemId, setItemId] = useState(items[0]?.id ?? '');
  const [quantity, setQuantity] = useState(1);
  const [rates, setRates] = useState<TradeCalculatorRates>({
    markupPercent: 0,
    kaderium: 1,
    nanoCrystal: 1,
    stasisEnergy: 1,
    food: 1,
    prodium: 1,
  });

  function selectRace(next: Race) {
    setRace(next);
    setItemId((data.articlesByCategory[RACE_TO_CALC_CATEGORY[next]] ?? [])[0]?.id ?? '');
  }

  const item = items.find((a) => a.id === itemId);
  const unit = item?.libname ? localUnits.get(item.libname) : undefined;
  const result = computeTradeCalculator(unit?.attrs ?? {}, quantity, rates);

  function rateField(label: string, key: keyof TradeCalculatorRates, min = 0.01) {
    return (
      <label class="ds-auction-filter__field">
        <span>{label}</span>
        <input
          type="number"
          class="ds-art-depot__select"
          min={min}
          step="0.01"
          value={rates[key]}
          onInput={(e) => setRates({ ...rates, [key]: Number((e.target as HTMLInputElement).value) || min })}
        />
      </label>
    );
  }

  return (
    <>
      <div class="ds-spying__row">
        <span class="ds-spying__label">Раса:</span>
        {CALC_RACE_ORDER.map((r) => (
          <label key={r} class="ds-spying__choice">
            <input type="radio" name="calc-race" checked={race === r} onChange={() => selectRace(r)} />
            {raceLabel(r)}
          </label>
        ))}
      </div>

      <div class="ds-spying__row">
        <span class="ds-spying__label">Юнит:</span>
        <select value={itemId} onChange={(e) => setItemId((e.target as HTMLSelectElement).value)}>
          {items.map((a) => (
            <option key={a.id} value={a.id ?? ''}>
              {a.libname ? unitDisplayName(a.libname) : a.id}
            </option>
          ))}
        </select>
      </div>

      <div class="ds-spying__row">
        <span class="ds-spying__label">Количество:</span>
        <Stepper value={quantity} min={1} onChange={setQuantity} />
      </div>

      <div class="ds-auction-filter__row-line">
        {rateField('Наценка, %', 'markupPercent', 0)}
        {rateField('Курс кадериума', 'kaderium')}
        {rateField('Курс нано-кристаллов', 'nanoCrystal')}
        {rateField('Курс энергии', 'stasisEnergy')}
        {rateField('Курс еды', 'food')}
        {rateField('Курс продиума', 'prodium')}
      </div>

      <div class="ds-spying__row">
        <span class="ds-spying__label">Нужно ресурсов (без наценки):</span>
        <ResourceList
          costs={[
            { name: 'KADERIUM', value: round2(result.raw.kaderium) },
            { name: 'NANO_CRYSTAL', value: round2(result.raw.nanoCrystal) },
            { name: 'STASIS_ENERGY', value: round2(result.raw.stasisEnergy) },
            { name: 'FOOD', value: round2(result.raw.food) },
            { name: 'PRODIUM', value: round2(result.raw.prodium) },
          ]}
        />
      </div>

      <div class="ds-spying__row">
        <span class="ds-spying__label">С наценкой:</span>
        <ResourceList
          costs={[
            { name: 'KADERIUM', value: round2(result.withMarkup.kaderium) },
            { name: 'NANO_CRYSTAL', value: round2(result.withMarkup.nanoCrystal) },
            { name: 'STASIS_ENERGY', value: round2(result.withMarkup.stasisEnergy) },
            { name: 'FOOD', value: round2(result.withMarkup.food) },
            { name: 'PRODIUM', value: round2(result.withMarkup.prodium) },
          ]}
        />
      </div>

      <div class="ds-spying__row">
        <span class="ds-spying__label">Итоговая стоимость (в одном ресурсе):</span>
        <ResourceList
          costs={[
            { name: 'PRODIUM', value: round2(result.totalInProdium) },
            { name: 'KADERIUM', value: round2(result.totalAsKaderium) },
            { name: 'NANO_CRYSTAL', value: round2(result.totalAsNanoCrystal) },
            { name: 'STASIS_ENERGY', value: round2(result.totalAsStasisEnergy) },
            { name: 'FOOD', value: round2(result.totalAsFood) },
          ]}
        />
      </div>
    </>
  );
}

/**
 * Категория 1 (ресурс)/2 (юнит) — libname это машинное имя, локализуем
 * через живую loaddata (см. докстринг файла). Категория 3 (артефакт) —
 * `libname` для неё сервер не шлёт вовсе (см. `tradeData.ts:212`,
 * всегда `null`), реальный идентификатор — `artId` (тот самый `id`,
 * что в оригинале передаётся в `getLocalNamesArt`, `xmlparsers.as:2160`).
 */
function articleLabel(a: TradeArticle): string {
  if (a.category === 1 && a.libname) return resourceDisplayName(a.libname);
  if (a.category === 2 && a.libname) return unitDisplayName(a.libname);
  if (a.category === 3 && a.artId) return artefactDisplayName(a.artId);
  return a.libname ?? a.id ?? '';
}

/** То же самое для колонки "Взамен" — тот же формат `<reses>`, тот же вечно-null `ln`. */
function resourceColumnLabel(name: string): string {
  return resourceDisplayName(name);
}

export function SellWindow({ data, localUnits, isPremium, playerRace, tabs, onTabClick, onClose, onSell }: SellWindowProps) {
  const [category, setCategory] = useState(1);
  const articles = category <= 3 ? data.articlesByCategory[category] ?? [] : [];
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
        {(isPremium ? [1, 2, 3, 4] : [1, 2, 3]).map((cat) => (
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

      {category === 4 ? (
        <TradeCalculatorPanel data={data} localUnits={localUnits} playerRace={playerRace} />
      ) : (
        <>
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
        </>
      )}
    </Window>
  );
}
