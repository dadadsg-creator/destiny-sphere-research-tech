/**
 * Окно «Обзор экономики» — данные economicalOverviewData.ts. Одно окно
 * на все 3 панели (`PN_EconomicalOverview`/`...Incoming`/
 * `...Outcoming`) — переключение через уже существующий механизм
 * вкладок `getbuildmenu`-семейства (`tabs`/`onTabClick`), как и в
 * остальных многовкладочных окнах порта.
 */
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import { RESOURCE_ATTRIBUTE_ID, type EconomicalOverviewData } from './economicalOverviewData';
import { localData } from '../data/localDataStore';
import { resolveLocalName, capitalizeFirst } from '../data/localNameResolver';
import { resourceDisplayName } from '../data/resourceIcons';

export interface EconomicalOverviewWindowProps {
  data: EconomicalOverviewData;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  /** undefined — кнопки "Активировать/деактивировать все" не показываются (только на панели "Обзор экономики" в оригинале, tablecreate.as). */
  onActivateAll?: (resource: string, activate: boolean) => void;
}

function rowLabel(key: string): string {
  const ln = resolveLocalName(localData.value, key);
  return ln ? capitalizeFirst(ln) : key;
}

export function EconomicalOverviewWindow({ data, tabs, onTabClick, onClose, onActivateAll }: EconomicalOverviewWindowProps) {
  return (
    <Window title="Обзор экономики" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <table>
        <thead>
          <tr>
            <th />
            {data.resources.map((r) => (
              <th key={r}>{resourceDisplayName(r)}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {data.rows.map((row) => (
            <tr key={row.labelKey}>
              <td>{rowLabel(row.labelKey)}</td>
              {row.values.map((v, i) => (
                <td key={i} style={v.trim().startsWith('-') ? { color: '#DD0000' } : undefined}>
                  {v}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
      {onActivateAll && (
        <div class="ds-btn-row">
          {data.resources
            .filter((r) => r in RESOURCE_ATTRIBUTE_ID)
            .map((r) => (
              <span key={r} class="ds-spying__choice">
                {resourceDisplayName(r)}:
                <button type="button" class="ds-popup__btn" onClick={() => onActivateAll(r, true)}>
                  Включить все
                </button>
                <button type="button" class="ds-popup__btn" onClick={() => onActivateAll(r, false)}>
                  Выключить все
                </button>
              </span>
            ))}
        </div>
      )}
    </Window>
  );
}
