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
 */
import { useState } from 'preact/hooks';
import { Stepper } from '../ui/Stepper';
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
    <div class="ds-window__content">
      <div>
        {user.name} — Золото: {currentStatus.gold}, Серебро: {currentStatus.silver}
      </div>
      <button onClick={handleBuyGold}>Купить золото</button>

      <div>
        <button disabled={worldIndex === 0} onClick={() => setWorldIndex((i) => Math.max(0, i - 1))}>
          ←
        </button>
        {world ? `${world.name}: золото ${world.gold}, серебро ${world.silver}` : 'Нет миров'}
        <button disabled={worldIndex >= user.allowWorld.length - 1} onClick={() => setWorldIndex((i) => Math.min(user.allowWorld.length - 1, i + 1))}>
          →
        </button>
      </div>

      <div>
        Обменять золото → серебро: <Stepper value={exchangeGold} min={0} max={currentStatus.gold} onChange={setExchangeGold} />
        <button onClick={() => exchangeGold > 0 && onChangeGoldToSilver(exchangeGold)}>Обменять</button>
      </div>

      <div>
        Перевести в мир: золото <Stepper value={toGold} min={0} max={currentStatus.gold} onChange={setToGold} />
        серебро <Stepper value={toSilver} min={0} max={currentStatus.silver} onChange={setToSilver} />
        <button onClick={() => (toGold > 0 || toSilver > 0) && world && onMoneyToWorld(world.id, toGold, toSilver)}>Перевести в мир</button>
      </div>

      <div>
        Перевести из мира: золото <Stepper value={fromGold} min={0} max={world?.gold ?? 0} onChange={setFromGold} />
        серебро <Stepper value={fromSilver} min={0} max={world?.silver ?? 0} onChange={setFromSilver} />
        <button onClick={() => (fromGold > 0 || fromSilver > 0) && world && onMoneyFromWorld(world.id, fromGold, fromSilver)}>Перевести из мира</button>
      </div>

      <div>
        Подарить: золото <Stepper value={giftGold} min={0} max={currentStatus.gold} onChange={setGiftGold} />
        серебро <Stepper value={giftSilver} min={0} max={currentStatus.silver} onChange={setGiftSilver} />
        <button onClick={() => (giftGold > 0 || giftSilver > 0) && onGiftMoney(giftGold, giftSilver)}>Подарить</button>
      </div>

      {lastOperation && (
        <div dangerouslySetInnerHTML={{ __html: `<b>${lastOperation.date}</b> ${lastOperation.text}` }} />
      )}
      <button onClick={onShowAllStatistic}>Вся статистика</button>
    </div>
  );
}
