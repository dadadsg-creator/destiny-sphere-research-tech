/**
 * Окно «Журнал сканирования» — scannermenuclass.as, см.
 * docs/client-combat-ui.md §9. Данные — scannerData.ts (parseSpyLog).
 *
 * Клиентская валидация из оригинала (:307-379): лимит зондов на ОДНУ
 * запись при массовом пересканировании делится поровну между всеми
 * отмеченными записями (`maxAllowZond / отмеченных`) — клиент сам следит,
 * чтобы сумма не превысила общий лимит.
 *
 * НЕ реализовано: сам блок фильтров (типы действий/колонии/диапазон
 * дат) — данные (`data.filter`) доступны, форма фильтрации не отрисована
 * в этой версии.
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import type { SpyLogData } from './scannerData';

export interface ScannerWindowProps {
  data: SpyLogData;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onScanSelected: (ids: string[]) => void;
}

export function ScannerWindow({ data, tabs, onTabClick, onClose, onScanSelected }: ScannerWindowProps) {
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
