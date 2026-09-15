/**
 * Окно «Обзор экономики» — данные economicalOverviewData.ts. Одно окно
 * на все 3 панели (`PN_EconomicalOverview`/`...Incoming`/
 * `...Outcoming`) — переключение через уже существующий механизм
 * вкладок `getbuildmenu`-семейства (`tabs`/`onTabClick`), как и в
 * остальных многовкладочных окнах порта.
 */
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import type { EconomicalOverviewData } from './economicalOverviewData';
import { localData } from '../data/localDataStore';
import { resolveLocalName, capitalizeFirst } from '../data/localNameResolver';
import { resourceDisplayName } from '../data/resourceIcons';

export interface EconomicalOverviewWindowProps {
  data: EconomicalOverviewData;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
}

function rowLabel(key: string): string {
  const ln = resolveLocalName(localData.value, key);
  return ln ? capitalizeFirst(ln) : key;
}

export function EconomicalOverviewWindow({ data, tabs, onTabClick, onClose }: EconomicalOverviewWindowProps) {
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
    </Window>
  );
}
