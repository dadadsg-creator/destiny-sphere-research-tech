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
 * `allowMax` (категория «В», формула подтверждена — см. докстринг
 * `trainUnitData.ts`) теперь ограничивает степпер количества: минимум из
 * глобальной "бесконечности" (2×10⁹), живого `maxallow` (на практике —
 * только спец-юниты захваченной колонии) и запаса 6 ресурсов игрока.
 *
 * **Дополнение (2026-09-11, категория «В» §2.2)**: `recalcUpkeep()`
 * (`trainvarunitclass.as:274-297`) на каждое изменение степпера
 * пересчитывает и показывает — раньше отсутствовало целиком:
 *  - цену постройки ×count (`obj.price.{kad,nano,...} * count`);
 *  - время постройки ×count, делённое на 2 при `speedup=="1"`
 *    (премиум-ускорение производства юнитов вдвое — живой корневой
 *    атрибут `<warunit speedup="">`, раньше нигде не использовался);
 *  - содержание ×count (было: фиксированное число за ОДИН юнит,
 *    не менявшееся при движении степпера — в оригинале это тоже
 *    `obj.upkeep.* * count`, т.е. суммарная нагрузка на ресурсы игрока
 *    от ВСЕЙ партии, не за штуку).
 *
 * НЕ реализовано: подсветка красным недостающих уровней науки
 * (sctarg/sccur).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import type { TabDef } from '../ui/tabs';
import { ResourceList, type ResourceCost } from '../ui/ResourceValue';
import { unitIconUrl } from '../data/resourceIcons';
import { formatTurnTime } from '../data/turnTime';
import {
  computeAllowMax,
  unitBuildPriceFromLocal,
  unitBuildTimeSeconds,
  GLOBAL_MAX_NUMBER,
  type PlayerResourcePool,
  type TrainUnitData,
} from './trainUnitData';
import type { LocalUnit } from '../data/localData';

export interface TrainUnitWindowProps {
  data: TrainUnitData;
  localUnits: Map<string, LocalUnit>;
  playerResources: PlayerResourcePool;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onTrain: (idUnit: string, count: number) => void;
  onCancel: (idUnit: string) => void;
}

export function TrainUnitWindow({
  data,
  localUnits,
  playerResources,
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
            <th>Цена постройки</th>
            <th>Время</th>
            <th>Содержание партии</th>
            <th>В строю</th>
            <th>Заказать</th>
          </tr>
        </thead>
        <tbody>
          {data.units.map((unit) => {
            const local = localUnits.get(unit.idName);
            const available = unit.avail === '1';
            const count = counts[unit.idUnit] ?? 0;
            const price = unitBuildPriceFromLocal(local);
            const allowMax = computeAllowMax(GLOBAL_MAX_NUMBER, price, playerResources, unit.maxallow ?? undefined);
            /** trainvarunitclass.as:279-283 — speedup=="1" делит время постройки пополам (премиум). */
            const buildSeconds = unitBuildTimeSeconds(price.time, data.turn) * count * (data.speedup === '1' ? 0.5 : 1);
            return (
              <tr key={unit.idUnit}>
                <td>
                  <div class="ds-artefact-row">
                    <img class="ds-unit-icon" src={unitIconUrl(unit.idName)} alt="" />
                    <span>{local?.ln ?? unit.idName}</span>
                  </div>
                </td>
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
                      { name: 'KADERIUM', value: price.kad * count },
                      { name: 'NANO_CRYSTAL', value: price.nano * count },
                      { name: 'STASIS_ENERGY', value: price.energy * count },
                      { name: 'FOOD', value: price.food * count },
                      { name: 'PRODIUM', value: price.prod * count },
                      { name: 'WORKER', value: price.work * count },
                    ] satisfies ResourceCost[]}
                  />
                </td>
                <td>{formatTurnTime(buildSeconds)}</td>
                <td>
                  <ResourceList
                    costs={[
                      { name: 'KADERIUM', value: unit.upkeep.kad * count },
                      { name: 'NANO_CRYSTAL', value: unit.upkeep.nano * count },
                      { name: 'STASIS_ENERGY', value: unit.upkeep.energy * count },
                      { name: 'FOOD', value: unit.upkeep.food * count },
                      { name: 'PRODIUM', value: unit.upkeep.prod * count },
                      { name: 'WORKER', value: unit.upkeep.work * count },
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
                      <Stepper
                        value={count}
                        min={0}
                        max={allowMax}
                        onChange={(v) => setCounts({ ...counts, [unit.idUnit]: v })}
                      />{' '}
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
