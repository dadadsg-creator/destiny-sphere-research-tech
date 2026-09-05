/**
 * Окно «Приватный мир» — peace_private.as, см.
 * docs/client-economy-social-ui.md §2.5. Данные — privatePeaceData.ts
 * (parsePrivatePeace, buildGetPPeaceCommand).
 *
 * Два списка: заявки (ждём ответа на своё предложение) и уже
 * установленные/входящие связи (`joined=="0"` — входящее предложение,
 * можно подтвердить/отклонить; `joined=="1"` — мир уже действует).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import { buildGetPPeaceCommand, type GetPPeaceCommand, type PrivatePeaceData } from './privatePeaceData';

export interface PrivatePeaceWindowProps {
  data: PrivatePeaceData;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onSend: (command: GetPPeaceCommand) => void;
}

export function PrivatePeaceWindow({ data, tabs, onTabClick, onClose, onSend }: PrivatePeaceWindowProps) {
  const [targetName, setTargetName] = useState('');

  return (
    <Window title="Приватный мир" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <div class="ds-spying__row">
        <input
          type="text"
          class="ds-stepper__input"
          placeholder="Ник игрока"
          value={targetName}
          onInput={(e) => setTargetName((e.target as HTMLInputElement).value)}
        />
        <button
          type="button"
          class="ds-popup__btn"
          disabled={targetName.length === 0}
          onClick={() => onSend(buildGetPPeaceCommand(targetName, null, 'PRIVATEPEACE_REQUEST'))}
        >
          Предложить мир
        </button>
      </div>

      <h4>Ваши заявки (ждут ответа)</h4>
      <table>
        <thead>
          <tr>
            <th>Альянс</th>
            <th>Игрок</th>
            <th />
          </tr>
        </thead>
        <tbody>
          {data.requests.map((r) => (
            <tr key={r.uid}>
              <td>{r.tag}</td>
              <td>{r.member}</td>
              <td>
                <button
                  type="button"
                  class="ds-popup__btn"
                  onClick={() => onSend(buildGetPPeaceCommand(r.member ?? '', r.uid, 'LIST_REQUEST_CANCEL_REQUEST'))}
                >
                  Отменить
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>

      <h4>Связи мира</h4>
      <table>
        <thead>
          <tr>
            <th>Альянс</th>
            <th>Игрок</th>
            <th>Статус</th>
            <th />
          </tr>
        </thead>
        <tbody>
          {data.members.map((m) => (
            <tr key={m.uid}>
              <td>{m.tag}</td>
              <td>{m.member}</td>
              <td>{m.joined === '1' ? 'Мир заключён' : 'Входящее предложение'}</td>
              <td>
                {m.joined !== '1' && (
                  <>
                    <button
                      type="button"
                      class="ds-popup__btn"
                      onClick={() => onSend(buildGetPPeaceCommand(m.member ?? '', m.uid, 'PEACE_CONFIRMATION'))}
                    >
                      Принять
                    </button>{' '}
                    <button
                      type="button"
                      class="ds-popup__btn"
                      onClick={() => onSend(buildGetPPeaceCommand(m.member ?? '', m.uid, 'PEACE_REJECTING'))}
                    >
                      Отклонить
                    </button>
                  </>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </Window>
  );
}
