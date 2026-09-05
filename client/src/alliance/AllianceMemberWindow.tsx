/**
 * Окно «Участники альянса» — alliancememberclass.as, см.
 * docs/client-economy-social-ui.md §2.2. Данные — allianceMemberData.ts
 * (parseAllianceOverview, canKick).
 */
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import { canKick, type AllianceOverviewData } from './allianceMemberData';

export interface AllianceMemberWindowProps {
  data: AllianceOverviewData;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onKick: (userId: string) => void;
}

export function AllianceMemberWindow({ data, tabs, onTabClick, onClose, onKick }: AllianceMemberWindowProps) {
  return (
    <Window title="Участники альянса" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <table>
        <thead>
          <tr>
            <th>Игрок</th>
            <th>Рейтинг</th>
            <th>Координаты</th>
            <th>Статус</th>
            <th />
          </tr>
        </thead>
        <tbody>
          {data.members.map((member) => (
            <tr key={member.userId}>
              <td>{member.name}</td>
              <td>{member.ranking}</td>
              <td>{member.coord}</td>
              <td>{member.statusLn}</td>
              <td>
                {canKick(data, member) && (
                  <button type="button" class="ds-popup__btn" onClick={() => onKick(member.userId!)}>
                    Кикнуть
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
