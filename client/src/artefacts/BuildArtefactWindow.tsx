/**
 * Окно «Артефакты здания» — buildartefactclass.as, см.
 * docs/client-economy-social-ui.md §1.6. Данные — buildArtefactData.ts.
 * Две колонки: доступные к вставке (депо) и уже вставленные в здание —
 * insert_artefact(id)/delete_artefact(id) привязаны к активной соте через
 * общий debug.link.active_cell, номер вкладки не передаётся.
 *
 * Иконка/название — из статического каталога `LocalData.artefacts`
 * (`data/localData.ts`, `<artefact>` из `loaddata`, ключ — тот же
 * libname `prefix_scope_postfix`), иконка конкретно по `art.postfix`
 * (см. `resourceIcons.ts:artefactIconUrl` — один "базовый вид" на набор
 * префикс/scope-модификаторов силы). Если каталог ещё не пришёл или
 * артефакта в нём нет — показываем сырой libname текстом без иконки,
 * не выдумывая замену.
 */
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import type { LocalArtefact } from '../data/localData';
import { artefactIconUrl, ARTEFACT_SLOT_FRAME_URL } from '../data/resourceIcons';
import { ResourceValue } from '../ui/ResourceValue';
import type { BuildArtefact, BuildArtefactData } from './buildArtefactData';

export interface BuildArtefactWindowProps {
  data: BuildArtefactData;
  localArtefacts: Map<string, LocalArtefact>;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onAttach: (id: string) => void;
  onDetach: (id: string) => void;
}

function ArtefactRow({
  art,
  local,
  actionLabel,
  onAction,
}: {
  art: BuildArtefact;
  local: LocalArtefact | undefined;
  actionLabel: string;
  onAction: () => void;
}) {
  return (
    <tr class="ds-army-row">
      <td>
        <div class="ds-artefact-row">
          <span class="ds-artefact-row__icon-wrap">
            <img class="ds-artefact-row__frame" src={ARTEFACT_SLOT_FRAME_URL} alt="" />
            {art.postfix && <img class="ds-artefact-row__icon" src={artefactIconUrl(art.postfix)} alt="" />}
          </span>
          <span class="ds-artefact-row__name" style={{ color: art.expired ? '#ff6666' : undefined }}>
            {local?.ln ?? art.libname}
          </span>
          <span class="ds-artefact-row__bonuses">
            {art.bonuses.map((b, i) => (
              <ResourceValue key={i} name={b.bonusType ?? ''} value={b.bonusValue ?? ''} />
            ))}
          </span>
        </div>
      </td>
      <td>
        <button onClick={onAction}>{actionLabel}</button>
      </td>
    </tr>
  );
}

export function BuildArtefactWindow({
  data,
  localArtefacts,
  tabs,
  onTabClick,
  onClose,
  onAttach,
  onDetach,
}: BuildArtefactWindowProps) {
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
            <ArtefactRow
              key={art.id}
              art={art}
              local={localArtefacts.get(art.libname)}
              actionLabel="Извлечь"
              onAction={() => art.id && onDetach(art.id)}
            />
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
              local={localArtefacts.get(art.libname)}
              actionLabel={freeSlots > 0 ? 'Вставить' : 'Нет слотов'}
              onAction={() => freeSlots > 0 && art.id && onAttach(art.id)}
            />
          ))}
        </tbody>
      </table>
    </Window>
  );
}
