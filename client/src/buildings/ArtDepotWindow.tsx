/**
 * Окно «Депо артефактов» — art_depot_menu.as, см.
 * docs/client-economy-social-ui.md §1.5. Данные — artDepotData.ts
 * (parseArtDepot).
 *
 * Артефакты с `link` — активируемые одноразовые предметы (кнопка шлёт
 * `artActivate(id)`), а не пассивные бонусы — отдельная находка доклада.
 * `removeAllow==false` скрывает чекбокс удаления для конкретного
 * артефакта (нет клиентской проверки "хватает ли" — просто нельзя
 * выбрать).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import type { ArtDepotEntry } from './artDepotData';

export interface ArtDepotWindowProps {
  entries: ArtDepotEntry[];
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onDeleteSelected: (ids: string[]) => void;
  onActivate: (id: string) => void;
}

export function ArtDepotWindow({ entries, tabs, onTabClick, onClose, onDeleteSelected, onActivate }: ArtDepotWindowProps) {
  const [checked, setChecked] = useState<Set<string>>(new Set());

  function toggle(id: string) {
    const next = new Set(checked);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    setChecked(next);
  }

  return (
    <Window title="Депо артефактов" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <table>
        <thead>
          <tr>
            <th />
            <th>Артефакт</th>
            <th>Бонусы</th>
            <th />
          </tr>
        </thead>
        <tbody>
          {entries.map((entry) => (
            <tr key={entry.id}>
              <td>
                {entry.removeAllow && entry.id && (
                  <input type="checkbox" checked={checked.has(entry.id)} onChange={() => toggle(entry.id!)} />
                )}
              </td>
              <td>
                {entry.libName}
                {entry.expired && <span class="ds-protect-shield__cost--unaffordable"> ({entry.expired})</span>}
              </td>
              <td>{entry.bonuses.map((b) => `${b.name}:${b.value}`).join(', ') || '—'}</td>
              <td>
                {entry.link && entry.id && (
                  <button type="button" class="ds-popup__btn" onClick={() => onActivate(entry.id!)}>
                    {entry.link.text}
                  </button>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      <button
        type="button"
        class="ds-popup__btn"
        disabled={checked.size === 0}
        onClick={() => onDeleteSelected([...checked])}
      >
        Удалить выбранное
      </button>
    </Window>
  );
}
