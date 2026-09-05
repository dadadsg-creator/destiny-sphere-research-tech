/**
 * Окно «Артефактная статистика соты» — artefactstatclass.as, см.
 * docs/client-economy-social-ui.md §1.4. Данные — artefactStatData.ts.
 *
 * Оригинал рисует полную гексагональную мозаику соты (панорамный вид) —
 * здесь список ячеек таблицей, без гекс-раскладки (это HTML-меню, не
 * канвас-экран Фазы 2; при желании раскладку можно взять из
 * map/hexGrid.ts, здесь сознательно не подключено).
 */
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import { findMinRemainArt, getExpiryColor, type ArtefactStatData } from './artefactStatData';

export interface ArtefactStatWindowProps {
  data: ArtefactStatData;
  /** true — показывать премиум-подсветку скорого истечения артефакта. */
  isPremium: boolean;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onCellClick: (cellNum: string) => void;
}

const EXPIRY_COLOR_CSS: Record<string, string> = { green: '#33cc66', yellow: '#ffcc66', red: '#ff6666' };

export function ArtefactStatWindow({ data, isPremium, tabs, onTabClick, onClose, onCellClick }: ArtefactStatWindowProps) {
  return (
    <Window title="Артефакты соты" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <table>
        <thead>
          <tr>
            <th>Ячейка</th>
            <th>Тип</th>
            <th>Слоты</th>
            <th />
          </tr>
        </thead>
        <tbody>
          {[...data.entries()].map(([id, cell]) => {
            const minArt = isPremium ? findMinRemainArt(cell) : null;
            const color = minArt ? EXPIRY_COLOR_CSS[getExpiryColor(Number(minArt.remain))] : undefined;
            return (
              <tr key={id} class="ds-army-row" onClick={() => onCellClick(id)}>
                <td style={{ color }}>#{id}</td>
                <td>{cell.type}</td>
                <td>
                  {cell.fullSlots}/{cell.maxSlots}
                </td>
                <td>
                  {cell.artInBuild.map((a) => a.bonus).filter(Boolean).join(', ')}
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </Window>
  );
}
