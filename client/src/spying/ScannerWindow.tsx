/**
 * Окно «Журнал сканирования» — scannermenuclass.as, см.
 * docs/client-combat-ui.md §9. Данные — scannerData.ts (parseSpyLog).
 *
 * Клиентская валидация из оригинала (:307-379): лимит зондов на ОДНУ
 * запись при массовом пересканировании делится поровну между всеми
 * отмеченными записями (`maxAllowZond / отмеченных`) — клиент сам следит,
 * чтобы сумма не превысила общий лимит.
 *
 * **Фильтр (2026-09-18)** — данные (`data.filter`) были доступны, форма
 * не отрисовывалась. Имя игрока/колонии/типы действий — как в
 * оригинале; ось времени (`datefrom`/`dateto`) — см. докстринг
 * `scannerData.ts` про неустановленную точную семантику диапазона,
 * показана как секунды с явной подсказкой, не выдуманной календарной
 * датой.
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import type { SpyLogData } from './scannerData';
import type { ScannerFilterCommand } from './scannerData';

export interface ScannerWindowProps {
  data: SpyLogData;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onScanSelected: (ids: string[]) => void;
  onApplyFilter: (cmd: ScannerFilterCommand) => void;
}

function FilterPanel({ filter, onApply }: { filter: NonNullable<SpyLogData['filter']>; onApply: (cmd: ScannerFilterCommand) => void }) {
  const [name, setName] = useState(filter.defName ?? '');
  const [colonyIds, setColonyIds] = useState<Set<string>>(
    () => new Set(filter.colonys.filter((c) => c.active && c.id).map((c) => c.id as string)),
  );
  const [typeIds, setTypeIds] = useState<Set<string>>(
    () => new Set(filter.typesMove.filter((t) => t.active && t.id).map((t) => t.id as string)),
  );
  const [dateFrom, setDateFrom] = useState(0);
  const [dateTo, setDateTo] = useState(Number(filter.time.cur) || 0);

  function toggleSet(set: Set<string>, setSet: (s: Set<string>) => void, id: string) {
    const next = new Set(set);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    setSet(next);
  }

  return (
    <div class="ds-panel">
      <label class="ds-auction-filter__field">
        <span>Игрок</span>
        <input type="text" value={name} onInput={(e) => setName((e.target as HTMLInputElement).value)} placeholder="мин. 3 символа" />
      </label>
      <label class="ds-auction-filter__field">
        <span>С (сек.)</span>
        <input
          type="number"
          min={filter.time.min ?? undefined}
          max={filter.time.max ?? undefined}
          value={dateFrom}
          onInput={(e) => setDateFrom(Number((e.target as HTMLInputElement).value) || 0)}
        />
      </label>
      <label class="ds-auction-filter__field">
        <span>По (сек.)</span>
        <input
          type="number"
          min={filter.time.min ?? undefined}
          max={filter.time.max ?? undefined}
          value={dateTo}
          onInput={(e) => setDateTo(Number((e.target as HTMLInputElement).value) || 0)}
        />
      </label>

      {filter.colonys.length > 0 && (
        <div>
          <div class="ds-auction-filter__group-title">Колонии</div>
          {filter.colonys.map((c) => (
            <label key={c.id} class="ds-spying__choice">
              <input
                type="checkbox"
                checked={c.id ? colonyIds.has(c.id) : false}
                onChange={() => c.id && toggleSet(colonyIds, setColonyIds, c.id)}
              />
              {c.name} {c.addr}
            </label>
          ))}
        </div>
      )}

      {filter.typesMove.length > 0 && (
        <div>
          <div class="ds-auction-filter__group-title">Тип действия</div>
          {filter.typesMove.map((t) => (
            <label key={t.id} class="ds-spying__choice">
              <input
                type="checkbox"
                checked={t.id ? typeIds.has(t.id) : false}
                onChange={() => t.id && toggleSet(typeIds, setTypeIds, t.id)}
              />
              {t.name}
            </label>
          ))}
        </div>
      )}

      <button
        type="button"
        class="ds-popup__btn"
        onClick={() => onApply({ name, colonyIds: [...colonyIds], typeIds: [...typeIds], dateFrom, dateTo })}
      >
        Применить фильтр
      </button>
    </div>
  );
}

export function ScannerWindow({ data, tabs, onTabClick, onClose, onScanSelected, onApplyFilter }: ScannerWindowProps) {
  const [checked, setChecked] = useState<Set<string>>(new Set());

  const maxAllowZond = Number(data.filter?.maxAllowZond) || 0;
  const perEntryLimit = checked.size > 0 ? Math.floor(maxAllowZond / checked.size) : maxAllowZond;

  function toggle(id: string) {
    const next = new Set(checked);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    setChecked(next);
  }

  return (
    <Window title="Журнал сканирования" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      {data.filter && <FilterPanel filter={data.filter} onApply={onApplyFilter} />}
      {data.filter && (
        <p class="ds-train-unit__idle">
          Лимит зондов на запись: {perEntryLimit} (всего {maxAllowZond}, поделено между {checked.size || 1}{' '}
          выбранными)
        </p>
      )}
      <table>
        <thead>
          <tr>
            <th />
            <th>Время</th>
            <th>Действие</th>
            <th>Откуда</th>
            <th>Куда</th>
            <th>Данные</th>
          </tr>
        </thead>
        <tbody>
          {data.entries.map((entry) => (
            <tr key={entry.id} style={{ color: entry.zcolor ?? undefined }}>
              <td>
                {entry.id && (
                  <input type="checkbox" checked={checked.has(entry.id)} onChange={() => toggle(entry.id!)} />
                )}
              </td>
              <td>{entry.date}</td>
              <td>{entry.action}</td>
              <td>{entry.addrFrom}</td>
              <td>{entry.addrTo}</td>
              <td>
                {entry.typelog} {entry.zname}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      <button
        type="button"
        class="ds-popup__btn"
        disabled={checked.size === 0}
        onClick={() => onScanSelected([...checked])}
      >
        Пересканировать выбранное
      </button>
    </Window>
  );
}
