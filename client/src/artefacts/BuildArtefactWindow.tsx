/**
 * Окно «Артефакты здания» — buildartefactclass.as, см.
 * docs/client-economy-social-ui.md §1.6. Данные — buildArtefactData.ts.
 * Две колонки: доступные к вставке (депо) и уже вставленные в здание —
 * insert_artefact(id)/delete_artefact(id) привязаны к активной соте через
 * общий debug.link.active_cell, номер вкладки не передаётся.
 */
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import type { BuildArtefact, BuildArtefactData } from './buildArtefactData';

export interface BuildArtefactWindowProps {
  data: BuildArtefactData;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onAttach: (id: string) => void;
  onDetach: (id: string) => void;
}

function ArtefactRow({ art, actionLabel, onAction }: { art: BuildArtefact; actionLabel: string; onAction: () => void }) {
  return (
    <tr class="ds-army-row">
      <td style={{ color: art.expired ? '#ff6666' : undefined }}>{art.libname}</td>
      <td>{art.bonuses.map((b) => `${b.bonusType}:${b.bonusValue}`).join(', ')}</td>
      <td>
        <button onClick={onAction}>{actionLabel}</button>
      </td>
    </tr>
  );
}

export function BuildArtefactWindow({ data, tabs, onTabClick, onClose, onAttach, onDetach }: BuildArtefactWindowProps) {
  const freeSlots = data.countSlots - data.attached.length;

  return (
    <Window title="Артефакты здания" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <p>
        Слотов занято: {data.attached.length}/{data.countSlots}
      </p>

      <h4>Вставлено</h4>
      <table>
        <tbody>
          {data.attached.map((art) => (
            <ArtefactRow key={art.id} art={art} actionLabel="Извлечь" onAction={() => art.id && onDetach(art.id)} />
          ))}
        </tbody>
      </table>

      <h4>Доступно (депо)</h4>
      <table>
        <tbody>
          {data.available.map((art) => (
            <ArtefactRow
              key={art.id}
              art={art}
              actionLabel={freeSlots > 0 ? 'Вставить' : 'Нет слотов'}
              onAction={() => freeSlots > 0 && art.id && onAttach(art.id)}
            />
          ))}
        </tbody>
      </table>
    </Window>
  );
}
