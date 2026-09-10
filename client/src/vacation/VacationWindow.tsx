/**
 * Окно режима отпуска — otpusk_class.as, см.
 * docs/client-economy-social-ui.md §4.2. Статус "2" сознательно не
 * рисует ни одной кнопки — как и в оригинале (назначение переходного
 * состояния не установлено).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { ConfirmPopup } from '../ui/Popup';
import type { TabDef } from '../ui/tabs';
import type { VacationData } from './vacationData';

export interface VacationWindowProps {
  data: VacationData;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onStart: (password: string) => void;
  onStop: (password: string) => void;
  onForgotPassword: () => void;
}

export function VacationWindow({ data, tabs, onTabClick, onClose, onStart, onStop, onForgotPassword }: VacationWindowProps) {
  const [password, setPassword] = useState('');
  const [confirming, setConfirming] = useState(false);

  return (
    <Window title="Отпуск" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <p>{data.dataText}</p>
      <p>{data.descText}</p>

      {(data.status === '1' || data.status === '3') && (
        <div class="ds-spying__row">
          <span class="ds-spying__label">Пароль:</span>
          <input
            type="password"
            class="ds-text-input"
            value={password}
            onInput={(e) => setPassword((e.target as HTMLInputElement).value)}
          />
        </div>
      )}

      {data.status === '1' && (
        <button type="button" class="ds-popup__btn" onClick={() => setConfirming(true)}>
          Уйти в отпуск
        </button>
      )}

      {data.status === '3' && (
        <>
          <button type="button" class="ds-popup__btn" onClick={() => onStop(password)}>
            Выйти из отпуска
          </button>
          <button type="button" class="ds-popup__btn" onClick={onForgotPassword}>
            Забыл пароль
          </button>
        </>
      )}

      {confirming && (
        <ConfirmPopup
          message="Вы уверены, что хотите уйти в отпуск?"
          onYes={() => {
            setConfirming(false);
            onStart(password);
          }}
          onNo={() => setConfirming(false)}
        />
      )}
    </Window>
  );
}
