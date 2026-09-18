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
 *
 * Фильтр по типу/области/размеру (2026-09-18) — `filter.ordersType/
 * ordersScope/ordersSize` парсились ещё в Фазе 3 (`artDepotData.ts`),
 * но не отрисовывались — `artdepotsort()` (`reqvestdata.as:868-871`)
 * шлёт РОВНО `<getbuildmenu c="" tab="ArcheologyArtefact" sorttype=""
 * sortsize="" sortscope=""/>` — та же `getbuildmenu`-команда, что и
 * открытие самого окна, просто с 3 доп. атрибутами; сервер в ответ
 * присылает НОВЫЙ отфильтрованный список тем же марком.
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import type { ArtDepotEntry, ArtDepotFilter, ArtDepotFilterOption } from './artDepotData';

export interface ArtDepotWindowProps {
  entries: ArtDepotEntry[];
  filter: ArtDepotFilter;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onDeleteSelected: (ids: string[]) => void;
  onActivate: (id: string) => void;
  onSort: (type: string, size: string, scope: string) => void;
}

function currentOptionId(options: ArtDepotFilterOption[]): string {
  return options.find((o) => o.cur)?.id ?? options[0]?.id ?? '';
}

function FilterSelect({ label, options, value, onChange }: { label: string; options: ArtDepotFilterOption[]; value: string; onChange: (id: string) => void }) {
  if (options.length === 0) return null;
  return (
    <label class="ds-spying__choice">
      {label}:{' '}
      <select value={value} onChange={(e) => onChange((e.target as HTMLSelectElement).value)}>
        {options.map((o) => (
          <option key={o.id} value={o.id ?? ''}>
            {o.ln ?? o.id}
          </option>
        ))}
      </select>
    </label>
  );
}

export function ArtDepotWindow({ entries, filter, tabs, onTabClick, onClose, onDeleteSelected, onActivate, onSort }: ArtDepotWindowProps) {
  const [checked, setChecked] = useState<Set<string>>(new Set());
  const [sortType, setSortType] = useState(() => currentOptionId(filter.ordersType));
  const [sortSize, setSortSize] = useState(() => currentOptionId(filter.ordersSize));
  const [sortScope, setSortScope] = useState(() => currentOptionId(filter.ordersScope));

  function toggle(id: string) {
    const next = new Set(checked);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    setChecked(next);
  }

  function applySort(type: string, size: string, scope: string) {
    setSortType(type);
    setSortSize(size);
    setSortScope(scope);
    onSort(type, size, scope);
  }

  const hasFilters = filter.ordersType.length > 0 || filter.ordersScope.length > 0 || filter.ordersSize.length > 0;

  return (
    <Window title="Депо артефактов" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      {hasFilters && (
        <div class="ds-spying__row">
          <FilterSelect label="Тип" options={filter.ordersType} value={sortType} onChange={(v) => applySort(v, sortSize, sortScope)} />
          <FilterSelect label="Область" options={filter.ordersScope} value={sortScope} onChange={(v) => applySort(sortType, sortSize, v)} />
          <FilterSelect label="Размер" options={filter.ordersSize} value={sortSize} onChange={(v) => applySort(sortType, v, sortScope)} />
        </div>
      )}
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
