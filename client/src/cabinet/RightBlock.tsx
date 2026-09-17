/**
 * Правая панель кабинета (логин/золото/серебро, листалка миров, 4
 * денежные операции) — RightBlock.as, см.
 * docs/client-economy-social-ui.md §5.4. Каждая операция шлётся, только
 * если хотя бы один из двух степперов (золото/серебро) > 0
 * (`changeGoldToSilver`/`moveMoneyToWorld`/`moveMoneyFromWorld`/
 * `giftMoney`, RightBlock.as:312-336). Степперы "из мира" ограничены
 * балансом ВЫБРАННОГО мира (`reinitAmountDependsOfWorld`), степперы
 * обмена/перевода-в-мир/подарка — балансом кабинета
 * (`this.currentStatus.gold/silver`).
 *
 * Вёрстка (2026-09-18) — первая версия была полностью без стилей (ни
 * одного `class`, кнопки/степперы просто подряд в потоке текста) —
 * владелец: "налипшие друг на друга кнопки без стиля". Каждая операция
 * теперь отдельная карточка (`.ds-cabinet-op`), золото/серебро — через
 * уже существующий `ResourceValue` (те же иконки, что и везде в игре,
 * не текст "Золото: N").
 */
import { useState } from 'preact/hooks';
import { Stepper } from '../ui/Stepper';
import { ResourceValue } from '../ui/ResourceValue';
import { resourceIconUrl } from '../data/resourceIcons';
import type { CabinetMainData } from './cabinetMainData';

export interface RightBlockProps {
  user: CabinetMainData['user'];
  currentStatus: CabinetMainData['currentStatus'];
  lastOperation: CabinetMainData['lastOperation'];
  /** RightBlock.as:201-218 — три разных пути монетизации золота по платформе. */
  buyGoldMode: 'vk' | 'pop-service' | 'pop-billing';
  billingUrl: string;
  onBuyGold: () => void;
  onOpenExternal: (url: string) => void;
  onChangeGoldToSilver: (count: number) => void;
  onMoneyToWorld: (worldId: string, gold: number, silver: number) => void;
  onMoneyFromWorld: (worldId: string, gold: number, silver: number) => void;
  onGiftMoney: (gold: number, silver: number) => void;
  onShowAllStatistic: () => void;
}

export function RightBlock({
  user,
  currentStatus,
  lastOperation,
  buyGoldMode,
  billingUrl,
  onBuyGold,
  onOpenExternal,
  onChangeGoldToSilver,
  onMoneyToWorld,
  onMoneyFromWorld,
  onGiftMoney,
  onShowAllStatistic,
}: RightBlockProps) {
  const [worldIndex, setWorldIndex] = useState(0);
  const [exchangeGold, setExchangeGold] = useState(0);
  const [toGold, setToGold] = useState(0);
  const [toSilver, setToSilver] = useState(0);
  const [fromGold, setFromGold] = useState(0);
  const [fromSilver, setFromSilver] = useState(0);
  const [giftGold, setGiftGold] = useState(0);
  const [giftSilver, setGiftSilver] = useState(0);

  const world = user.allowWorld[worldIndex];

  const handleBuyGold = () => {
    if (buyGoldMode === 'vk') onBuyGold();
    else if (buyGoldMode === 'pop-billing') onOpenExternal(billingUrl);
    else onOpenExternal('POP_SERVICE_URL');
  };

  return (
    <div class="ds-cabinet-right-inner">
      <div class="ds-cabinet-header">
        <div>
          <div class="ds-cabinet-header__name">{user.name}</div>
          <div class="ds-cabinet-header__balances">
            <ResourceValue name="GOLD_CREDIT" value={currentStatus.gold} />
            <ResourceValue name="SILVER_CREDIT" value={currentStatus.silver} />
          </div>
        </div>
        <button type="button" class="ds-popup__btn ds-popup__btn--primary" onClick={handleBuyGold}>
          Купить золото
        </button>
      </div>

      <div class="ds-cabinet-world">
        <button type="button" class="ds-stepper__btn" disabled={worldIndex === 0} onClick={() => setWorldIndex((i) => Math.max(0, i - 1))}>
          ←
        </button>
        <span class="ds-cabinet-world__name">
          {world ? (
            <>
              {world.name} — <ResourceValue name="GOLD_CREDIT" value={world.gold} /> <ResourceValue name="SILVER_CREDIT" value={world.silver} />
            </>
          ) : (
            'Нет миров'
          )}
        </span>
        <button
          type="button"
          class="ds-stepper__btn"
          disabled={worldIndex >= user.allowWorld.length - 1}
          onClick={() => setWorldIndex((i) => Math.min(user.allowWorld.length - 1, i + 1))}
        >
          →
        </button>
      </div>

      <div class="ds-cabinet-op">
        <span class="ds-cabinet-op__label">Обменять золото → серебро</span>
        <div class="ds-cabinet-op__fields">
          <Stepper value={exchangeGold} min={0} max={currentStatus.gold} onChange={setExchangeGold} />
          <button type="button" class="ds-popup__btn" disabled={exchangeGold <= 0} onClick={() => onChangeGoldToSilver(exchangeGold)}>
            Обменять
          </button>
        </div>
      </div>

      <div class="ds-cabinet-op">
        <span class="ds-cabinet-op__label">Перевести в мир</span>
        <div class="ds-cabinet-op__fields">
          <span class="ds-cabinet-op__field">
            <img class="ds-resource__icon" src={resourceIconUrl('GOLD_CREDIT')} alt="Золото" /> <Stepper value={toGold} min={0} max={currentStatus.gold} onChange={setToGold} />
          </span>
          <span class="ds-cabinet-op__field">
            <img class="ds-resource__icon" src={resourceIconUrl('SILVER_CREDIT')} alt="Серебро" /> <Stepper value={toSilver} min={0} max={currentStatus.silver} onChange={setToSilver} />
          </span>
          <button
            type="button"
            class="ds-popup__btn"
            disabled={!world || (toGold <= 0 && toSilver <= 0)}
            onClick={() => world && onMoneyToWorld(world.id, toGold, toSilver)}
          >
            Перевести в мир
          </button>
        </div>
      </div>

      <div class="ds-cabinet-op">
        <span class="ds-cabinet-op__label">Перевести из мира</span>
        <div class="ds-cabinet-op__fields">
          <span class="ds-cabinet-op__field">
            <img class="ds-resource__icon" src={resourceIconUrl('GOLD_CREDIT')} alt="Золото" /> <Stepper value={fromGold} min={0} max={world?.gold ?? 0} onChange={setFromGold} />
          </span>
          <span class="ds-cabinet-op__field">
            <img class="ds-resource__icon" src={resourceIconUrl('SILVER_CREDIT')} alt="Серебро" /> <Stepper value={fromSilver} min={0} max={world?.silver ?? 0} onChange={setFromSilver} />
          </span>
          <button
            type="button"
            class="ds-popup__btn"
            disabled={!world || (fromGold <= 0 && fromSilver <= 0)}
            onClick={() => world && onMoneyFromWorld(world.id, fromGold, fromSilver)}
          >
            Перевести из мира
          </button>
        </div>
      </div>

      <div class="ds-cabinet-op">
        <span class="ds-cabinet-op__label">Подарить</span>
        <div class="ds-cabinet-op__fields">
          <span class="ds-cabinet-op__field">
            <img class="ds-resource__icon" src={resourceIconUrl('GOLD_CREDIT')} alt="Золото" /> <Stepper value={giftGold} min={0} max={currentStatus.gold} onChange={setGiftGold} />
          </span>
          <span class="ds-cabinet-op__field">
            <img class="ds-resource__icon" src={resourceIconUrl('SILVER_CREDIT')} alt="Серебро" /> <Stepper value={giftSilver} min={0} max={currentStatus.silver} onChange={setGiftSilver} />
          </span>
          <button type="button" class="ds-popup__btn" disabled={giftGold <= 0 && giftSilver <= 0} onClick={() => onGiftMoney(giftGold, giftSilver)}>
            Подарить
          </button>
        </div>
      </div>

      <div class="ds-cabinet-footer">
        {lastOperation && (
          <span class="ds-cabinet-footer__last" dangerouslySetInnerHTML={{ __html: `<b>${lastOperation.date}</b> ${lastOperation.text}` }} />
        )}
        <button type="button" class="ds-popup__btn" onClick={onShowAllStatistic}>
          Вся статистика
        </button>
      </div>
    </div>
  );
}
