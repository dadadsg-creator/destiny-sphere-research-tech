/**
 * Окно «Мои колонии» — список сот игрока для быстрого перехода между
 * ними (`PN_ColonyNavigatePanel`, см. `colonyNavigateData.ts`). Как и
 * `ComplexViewWindow`, без `<alltabs>` в ответе — своих вкладок нет.
 */
import { Window } from '../ui/Window';
import type { ColonyNavigateEntry } from './colonyNavigateData';

export interface ColonyNavigateWindowProps {
  colonies: ColonyNavigateEntry[];
  onClose: () => void;
  onNavigate: (coords: string) => void;
  onLeaveColony: (idComb: string) => void;
}

export function ColonyNavigateWindow({ colonies, onClose, onNavigate, onLeaveColony }: ColonyNavigateWindowProps) {
  return (
    <Window title="Мои колонии" onClose={onClose}>
      <table>
        <thead>
          <tr>
            <th>Сота</th>
            <th>Координаты</th>
            <th>База</th>
            <th />
          </tr>
        </thead>
        <tbody>
          {colonies.map((c) => (
            <tr key={c.idComb}>
              <td>{c.combName}</td>
              <td>{c.coords}</td>
              <td>{c.hasBase ? 'есть' : ''}</td>
              <td>
                {c.canNavigate ? (
                  <button type="button" class="ds-popup__btn" onClick={() => onNavigate(c.coords)}>
                    Перейти
                  </button>
                ) : (
                  <span>текущая</span>
                )}
                {c.captured && (
                  <button
                    type="button"
                    class="ds-popup__btn"
                    disabled={c.leaveDisabled}
                    title={c.leaveDisabled ? 'Защитное поле слишком высокое' : undefined}
                    onClick={() => onLeaveColony(c.idComb)}
                  >
                    Покинуть
                  </button>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </Window>
  );
}
