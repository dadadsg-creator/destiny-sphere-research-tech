/**
 * Окно «Силовой щит» — powershieldclass.as, см. docs/client-combat-ui.md
 * §7. Данные — powerShieldData.ts (parsePowerShield).
 *
 * State-machine из 5 состояний (`status`), здесь реализованы два реально
 * интерактивных ("1" — зарядить, "3" — выбрать время и активировать) —
 * остальные ("0"/"2"/"4"/default) в оригинале тоже дают только "в
 * действии" без интерактива (см. §7, возможно неполная реализация в этой
 * ревизии клиента, не наше упрощение).
 *
 * Активация ВСЕГДА требует подтверждения (createConfirmWindow,
 * `CONFIRM_POWERSHIELD`) — единственное действие из всех 12 разобранных
 * файлов с обязательным, не отключаемым подтверждением (в отличие от
 * большинства действий, где подтверждение — опциональный клиентский
 * тумблер `check_confirm_win()`).
 *
 * Время активации — `powershieldclass.as:168`: `time_f.text =
 * game.turnToTime(val*this.turn)`. Раньше не реализовывалось (докстринг
 * "не установлено, что делает turnToTime" — закрыто 2026-09-10 при
 * сверке `build_menu.as`, см. `data/turnTime.ts::formatTurnTime`).
 * `data.turn` — живой атрибут `<powershild turn="">`, уже в секундах
 * (`PN_PowerShild.class.php:59`: `TURN_TIME * 60`), доп. `*60` не нужно.
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import { ConfirmPopup } from '../ui/Popup';
import { pushPopup, popPopup } from '../ui/windowStore';
import type { TabDef } from '../ui/tabs';
import { formatTurnTime } from '../data/turnTime';
import type { PowerShieldData } from './powerShieldData';

export interface PowerShieldWindowProps {
  data: PowerShieldData;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onCharge: () => void;
  onActivate: (turns: number) => void;
}

export function PowerShieldWindow({ data, tabs, onTabClick, onClose, onCharge, onActivate }: PowerShieldWindowProps) {
  const [turns, setTurns] = useState(Number(data.minTurn) || 0);
  const totalPrice = turns * data.price;

  function confirmActivate() {
    pushPopup(
      <ConfirmPopup
        message={`Активировать силовой щит на ${turns} ход(ов)? Цена: ${totalPrice} стазиса.`}
        onYes={() => {
          popPopup();
          onActivate(turns);
        }}
        onNo={() => popPopup()}
      />,
    );
  }

  return (
    <Window title="Силовой щит" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      {data.desc1 && <p class="ds-power-shield__desc">{data.desc1}</p>}
      {data.desc2 && <p class="ds-power-shield__desc">{data.desc2}</p>}

      {data.status === '1' && (
        <button type="button" class="ds-popup__btn" onClick={onCharge}>
          Зарядить
        </button>
      )}

      {data.status === '3' && (
        <div class="ds-spying__row">
          <Stepper
            value={turns}
            min={Number(data.minTurn) || 0}
            max={Number(data.maxTurn) || 0}
            onChange={setTurns}
          />
          <span>Время: {formatTurnTime(turns * (Number(data.turn) || 0))}</span>
          <span>Цена: {totalPrice} стазиса</span>
          <button type="button" class="ds-popup__btn" disabled={turns <= 0} onClick={confirmActivate}>
            Активировать
          </button>
        </div>
      )}

      {data.status !== '1' && data.status !== '3' && <p class="ds-train-unit__idle">Щит в действии.</p>}
    </Window>
  );
}
