/**
 * Окно «Восстановление защитного поля колонии» — protectshieldclass.as,
 * см. docs/client-combat-ui.md §8. Данные — protectShieldData.ts
 * (parseProtectShield). Механизм именно для ЗАХВАЧЕННЫХ колоний
 * (буквальный комментарий разработчика в оригинале), не то же самое, что
 * силовой щит (PowerShieldWindow).
 *
 * Клиентская валидация из оригинала (:102-168):
 *  - скроллер по умолчанию = 100 - текущий % (предлагает восстановить
 *    сразу до полных 100%);
 *  - каждая из двух кнопок оплаты (ресурсами / серебром) блокируется
 *    НЕЗАВИСИМО по своей нехватке — если запас известен (см.
 *    `playerResources`, не обязателен — если не передан, обе кнопки
 *    доступны без проверки нехватки, отдельная задача так же, как
 *    allowMax в army/trainUnitData.ts);
 *  - обе кнопки заблокированы, если поле уже 100% или percent==0.
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import type { TabDef } from '../ui/tabs';
import type { ProtectShieldData } from './protectShieldData';

export interface ProtectShieldPlayerResources {
  kaderium: number;
  nano: number;
  stasis: number;
  food: number;
  prodium: number;
  silver: number;
}

export interface ProtectShieldWindowProps {
  data: ProtectShieldData;
  /** Если не передано — проверка нехватки ресурсов не выполняется (см. docstring). */
  playerResources?: ProtectShieldPlayerResources;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onRestoreWithResources: (percent: number) => void;
  onRestoreWithSilver: (percent: number) => void;
}

export function ProtectShieldWindow({
  data,
  playerResources,
  tabs,
  onTabClick,
  onClose,
  onRestoreWithResources,
  onRestoreWithSilver,
}: ProtectShieldWindowProps) {
  const [percent, setPercent] = useState(100 - data.currentPercent);

  const costRes = data.priceRes.map((p) => p * percent);
  const costSilver = data.priceSilver * percent;

  const canAffordRes =
    !playerResources ||
    (costRes[0] <= playerResources.kaderium &&
      costRes[1] <= playerResources.nano &&
      costRes[2] <= playerResources.stasis &&
      costRes[3] <= playerResources.food &&
      costRes[4] <= playerResources.prodium);
  const canAffordSilver = !playerResources || costSilver <= playerResources.silver;

  const isFull = data.currentPercent >= 100;

  return (
    <Window title="Защитное поле колонии" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <div class="ds-protect-shield__bar">
        <div class="ds-protect-shield__bar-fill" style={{ width: `${data.currentPercent}%` }} />
        <span class="ds-protect-shield__bar-label">{data.currentPercent}%</span>
      </div>

      {!isFull && (
        <>
          <div class="ds-spying__row">
            <span class="ds-spying__label">Восстановить на:</span>
            <Stepper value={percent} min={0} max={100 - data.currentPercent} onChange={setPercent} />
          </div>

          <div class="ds-spying__row">
            <span class="ds-spying__label">Цена ресурсами:</span>
            <span class={canAffordRes ? '' : 'ds-protect-shield__cost--unaffordable'}>
              K:{costRes[0]} N:{costRes[1]} S:{costRes[2]} F:{costRes[3]} P:{costRes[4]}
            </span>
            <button
              type="button"
              class="ds-popup__btn"
              disabled={percent <= 0 || !canAffordRes}
              onClick={() => onRestoreWithResources(percent)}
            >
              Восстановить ресурсами
            </button>
          </div>

          <div class="ds-spying__row">
            <span class="ds-spying__label">Цена серебром:</span>
            <span class={canAffordSilver ? '' : 'ds-protect-shield__cost--unaffordable'}>{costSilver}</span>
            <button
              type="button"
              class="ds-popup__btn"
              disabled={percent <= 0 || !canAffordSilver}
              onClick={() => onRestoreWithSilver(percent)}
            >
              Восстановить серебром
            </button>
          </div>
        </>
      )}
    </Window>
  );
}
