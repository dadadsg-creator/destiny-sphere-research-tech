/**
 * Окно «Наука» — researchmenu.as, см. docs/client-economy-social-ui.md
 * §4.1. Данные — researchData.ts (parseResearch). Визуальное дерево
 * (`tree_scients/tree.as`) здесь НЕ реализовано — см. докстринг
 * researchData.ts, это отдельная задача полировки; здесь плоский список
 * 7 веток, как и в самом `researchmenu.as` (плитки, не дерево — дерево,
 * судя по коду, рисуется отдельным, не входящим в этот файл, компонентом
 * поверх тех же данных).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import type { TabDef } from '../ui/tabs';
import { researchBranchIconUrl, researchBranchName } from '../data/resourceIcons';
import { ResourceList, type ResourceCost } from '../ui/ResourceValue';
import { formatTurnTime } from '../data/turnTime';
import {
  computeScientistAllow,
  getCancelResearchId,
  getStartResearchId,
  type PlayerNanoFoodPool,
  type ResearchData,
} from './researchData';

export interface ResearchWindowProps {
  data: ResearchData;
  playerResources?: PlayerNanoFoodPool;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onStartResearch: (id: string) => void;
  onCancelResearch: (id: string) => void;
  onTrainScientists: (count: number) => void;
  onCancelTrainScientists: () => void;
}

export function ResearchWindow({
  data,
  playerResources,
  tabs,
  onTabClick,
  onClose,
  onStartResearch,
  onCancelResearch,
  onTrainScientists,
  onCancelTrainScientists,
}: ResearchWindowProps) {
  const [trainCount, setTrainCount] = useState(0);
  const scientistAllow =
    data.train && playerResources ? computeScientistAllow(data.train, playerResources) : (data.train?.scientistAllowRaw ?? 0);

  return (
    <Window title="Наука" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <table>
        <thead>
          <tr>
            <th>Ветка</th>
            <th>Уровень</th>
            <th>Бонус</th>
            <th>Итого</th>
            <th>Время/завершение</th>
            <th />
          </tr>
        </thead>
        <tbody>
          {data.branches.map((branch) => {
            const inProgress = branch.inProgressUntil !== null;
            const disabled = branch.allow === '0';
            return (
              <tr key={branch.id}>
                <td>
                  <div class="ds-artefact-row">
                    {researchBranchIconUrl(branch.id) && (
                      <img class="ds-resource__icon" src={researchBranchIconUrl(branch.id)!} alt="" />
                    )}
                    <span>{branch.name ?? researchBranchName(branch.id)}</span>
                  </div>
                </td>
                <td>{branch.level ?? '-'}</td>
                <td>{branch.bonus}</td>
                <td>{branch.bonusAll}</td>
                <td>{inProgress ? branch.inProgressUntil : branch.time}</td>
                <td>
                  {inProgress ? (
                    <button
                      type="button"
                      class="ds-popup__btn"
                      onClick={() => onCancelResearch(getCancelResearchId(branch))}
                    >
                      Отменить
                    </button>
                  ) : (
                    <button
                      type="button"
                      class="ds-popup__btn"
                      disabled={disabled}
                      onClick={() => onStartResearch(getStartResearchId(branch))}
                    >
                      Исследовать
                    </button>
                  )}
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>

      {data.train && (
        <div class="ds-spying__row" style={{ marginTop: '12px' }}>
          <span class="ds-spying__label">Учёные ({data.train.inProgress} в найме):</span>
          <Stepper value={trainCount} min={0} max={scientistAllow} onChange={setTrainCount} />
          <ResourceList
            costs={
              [
                { name: 'WORKER', value: trainCount * data.train.priceWork },
                { name: 'NANO_CRYSTAL', value: trainCount * data.train.priceNano },
                { name: 'FOOD', value: trainCount * data.train.priceFood },
              ] satisfies ResourceCost[]
            }
          />
          <span>{formatTurnTime(trainCount * data.train.priceTime * (Number(data.train.turn) || 0))}</span>
          <button
            type="button"
            class="ds-popup__btn"
            disabled={trainCount <= 0}
            onClick={() => onTrainScientists(trainCount)}
          >
            Нанять
          </button>
          {data.train.inProgress > 0 && (
            <button type="button" class="ds-popup__btn" onClick={onCancelTrainScientists}>
              Отменить набор
            </button>
          )}
        </div>
      )}
    </Window>
  );
}
