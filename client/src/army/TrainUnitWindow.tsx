/**
 * Окно «Тренировка боевого юнита» — trainvarunitclass.as, см.
 * docs/client-combat-ui.md §6. Данные — trainUnitData.ts (parseTrainWarUnit,
 * живые поля) объединённые с data/localData.ts (LocalData.units —
 * статические характеристики/цена постройки, mark `loaddata`).
 *
 * Клиентская валидация из оригинала (:319-348):
 *  - кнопка «Тренировать» шлёт запрос только если count > 0;
 *  - кнопка «Отмена» активна только если inProgress — валидное
 *    положительное число, иначе показывается только текст FACTORY_IS_FREE.
 *
 * НЕ реализовано в этой версии: allowMax по запасу ресурсов игрока (нет
 * парсера mark `combres` — см. computeAllowMax() в trainUnitData.ts, шаг
 * оставлен на потом), подсветка красным недостающих ресурсов/уровней
 * науки (sctarg/sccur).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import type { TabDef } from '../ui/tabs';
import { ResourceList, type ResourceCost } from '../ui/ResourceValue';
import type { TrainUnitData } from './trainUnitData';
import type { LocalUnit } from '../data/localData';

export interface TrainUnitWindowProps {
  data: TrainUnitData;
  localUnits: Map<string, LocalUnit>;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onTrain: (idUnit: string, count: number) => void;
  onCancel: (idUnit: string) => void;
}

export function TrainUnitWindow({
  data,
  localUnits,
  tabs,
  onTabClick,
  onClose,
  onTrain,
  onCancel,
}: TrainUnitWindowProps) {
  const [counts, setCounts] = useState<Record<string, number>>({});

  return (
    <Window title="Тренировка юнитов" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <table>
        <thead>
          <tr>
            <th>Юнит</th>
            <th>Атака/Защита</th>
            <th>Урон</th>
            <th>Прочность</th>
            <th>Содержание</th>
            <th>В строю</th>
            <th>Заказать</th>
          </tr>
        </thead>
        <tbody>
          {data.units.map((unit) => {
            const local = localUnits.get(unit.idName);
            const available = unit.avail === '1';
            const count = counts[unit.idUnit] ?? 0;
            return (
              <tr key={unit.idUnit}>
                <td>{local?.ln ?? unit.idName}</td>
                <td>
                  {local?.attack ?? '?'} / {local?.defence ?? '?'}
                </td>
                <td>
                  {local?.damageMin ?? '?'}-{local?.damageMax ?? '?'}
                </td>
                <td>{local?.hitpoints ?? '?'}</td>
                <td>
                  <ResourceList
                    costs={[
                      { name: 'KADERIUM', value: unit.upkeep.kad },
                      { name: 'NANO_CRYSTAL', value: unit.upkeep.nano },
                      { name: 'STASIS_ENERGY', value: unit.upkeep.energy },
                      { name: 'FOOD', value: unit.upkeep.food },
                      { name: 'PRODIUM', value: unit.upkeep.prod },
                      { name: 'WORKER', value: unit.upkeep.work },
                    ] satisfies ResourceCost[]}
                  />
                </td>
                <td>
                  {unit.inProgress > 0 ? (
                    <>
                      {unit.inProgress}{' '}
                      <button type="button" class="ds-stepper__btn" onClick={() => onCancel(unit.idUnit)}>
                        ×
                      </button>
                    </>
                  ) : (
                    <span class="ds-train-unit__idle">свободно</span>
                  )}
                </td>
                <td>
                  {available ? (
                    <>
                      <Stepper value={count} min={0} onChange={(v) => setCounts({ ...counts, [unit.idUnit]: v })} />{' '}
                      <button
                        type="button"
                        class="ds-popup__btn"
                        disabled={count <= 0}
                        onClick={() => onTrain(unit.idUnit, count)}
                      >
                        Тренировать
                      </button>
                    </>
                  ) : (
                    <span class="ds-train-unit__unavailable">недоступно</span>
                  )}
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </Window>
  );
}
