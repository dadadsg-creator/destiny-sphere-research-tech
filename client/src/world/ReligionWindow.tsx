/**
 * Окно «Религия» — religion_class.as, см.
 * docs/client-economy-social-ui.md §3.3. Данные — religionData.ts.
 * Показывает первую религию из списка (как и оригинал — только
 * `religiondata[0]`, см. докстринг religionData.ts).
 */
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import type { ReligionOption } from './religionData';

export interface ReligionWindowProps {
  religions: ReligionOption[];
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onConnect: (id: string) => void;
  onLeave: (id: string) => void;
}

export function ReligionWindow({ religions, tabs, onTabClick, onClose, onConnect, onLeave }: ReligionWindowProps) {
  const religion = religions[0];
  if (!religion) {
    return (
      <Window title="Религия" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
        <p class="ds-train-unit__idle">Нет доступных религий.</p>
      </Window>
    );
  }

  return (
    <Window title="Религия" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <p>{religion.name}</p>
      {religion.desc && <p class="ds-train-unit__idle">{religion.desc}</p>}

      <table>
        <thead>
          <tr>
            <th>Бонус</th>
            <th>Значение</th>
          </tr>
        </thead>
        <tbody>
          {religion.bonuses.map((b, i) => (
            <tr key={i}>
              <td>{b.name}</td>
              <td>{b.value}</td>
            </tr>
          ))}
        </tbody>
      </table>

      {religion.status === 'RELIGI_CONNECT' && (
        <button type="button" class="ds-popup__btn" onClick={() => onConnect(religion.id!)}>
          Вступить
        </button>
      )}
      {religion.status === 'RELIGI_KICK' && (
        <button type="button" class="ds-popup__btn" onClick={() => onLeave(religion.id!)}>
          Выйти
        </button>
      )}
      {religion.status === 'RELIGI_NOTACCESS' && <p class="ds-train-unit__unavailable">Недоступно</p>}
      {(religion.status === 'RELIGI_CONNECT_ACTION' || religion.status === 'RELIGI_KICK_ACTION') && (
        <p class="ds-train-unit__idle">
          В процессе: {religion.percent}%, завершение — {religion.date}
        </p>
      )}
    </Window>
  );
}
