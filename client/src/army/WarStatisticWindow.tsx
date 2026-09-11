/**
 * Окно «Пакетное производство юнитов» — warstatistic.as, см.
 * docs/client-combat-ui.md §5. Данные — warFactoryData.ts (parseWarComplex,
 * живые поля) + data/localData.ts (LocalData.units — статическая цена,
 * та же схема, что и в TrainUnitWindow).
 *
 * Клиентская валидация из оригинала (:157-175,286-327):
 *  - фабрика доступна для выбора, только если её `type` входит в список
 *    типов выбранного юнита, И (если фабрика занята) — только если
 *    производимый ею юнит совпадает с выбранным (можно "встать в очередь"
 *    к уже идущему производству ТОГО ЖЕ юнита, но не другого);
 *  - кнопка «Загрузить» шлёт `load_pack_war_factory(cellIds, unitId,
 *    amount)` только если выбрана хотя бы одна фабрика И amount > 0.
 *
 * `allowMax` (категория «В», формула подтверждена — см. докстринг
 * `trainUnitData.ts`) теперь ограничивает степпер количества для
 * выбранного юнита.
 *
 * **Дополнение (2026-09-11, категория «В» §2.2)**: `warstatistic.as:184-208`
 * на каждое изменение количества/выбора фабрик пересчитывает и
 * показывает — раньше отсутствовало целиком:
 *  - цену постройки ×amount (не делится на число фабрик — сама цена
 *    ресурсов одна и та же, распределение по фабрикам влияет только на
 *    время);
 *  - время постройки ОДНОЙ штуки (`ts`) и время всей партии (`tm`) —
 *    `Math.ceil(amount / count_factory)` "раундов" на выбранных
 *    фабриках параллельно, `/2` при `speedup=="1"` (премиум-ускорение).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import type { TabDef } from '../ui/tabs';
import { ResourceList, type ResourceCost } from '../ui/ResourceValue';
import { formatTurnTime } from '../data/turnTime';
import { unitIconUrl } from '../data/resourceIcons';
import type { WarComplexData, Factory, FactoryUnit } from './warFactoryData';
import {
  computeAllowMax,
  unitBuildPriceFromLocal,
  unitBuildTimeSeconds,
  GLOBAL_MAX_NUMBER,
  type PlayerResourcePool,
} from './trainUnitData';
import type { LocalUnit } from '../data/localData';

export interface WarStatisticWindowProps {
  data: WarComplexData;
  localUnits: Map<string, LocalUnit>;
  playerResources: PlayerResourcePool;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onLoad: (cellIds: string[], unitId: string, amount: number) => void;
}

function isFactorySelectable(factory: Factory, unit: FactoryUnit): boolean {
  if (!factory.type || !unit.types.includes(factory.type)) return false;
  if (factory.busy && factory.unit !== unit.unitname) return false;
  return true;
}

export function WarStatisticWindow({ data, localUnits, playerResources, tabs, onTabClick, onClose, onLoad }: WarStatisticWindowProps) {
  const [selectedUnitName, setSelectedUnitName] = useState<string>(data.units[0]?.unitname ?? '');
  const [selectedCells, setSelectedCells] = useState<Set<string>>(new Set());
  const [amount, setAmount] = useState(0);

  const selectedUnit = data.units.find((u) => u.unitname === selectedUnitName);
  const factories = [...data.factories.values()];
  const price = unitBuildPriceFromLocal(selectedUnit ? localUnits.get(selectedUnit.unitname) : undefined);
  const allowMax = selectedUnit ? computeAllowMax(GLOBAL_MAX_NUMBER, price, playerResources, selectedUnit.maxallow ?? undefined) : 0;

  const speedDivisor = data.speedup === '1' ? 2 : 1;
  const perUnitSeconds = unitBuildTimeSeconds(price.time, data.turn) / speedDivisor;
  const countFactory = selectedCells.size;
  const batchSeconds = countFactory > 0 ? perUnitSeconds * Math.ceil(amount / countFactory) : 0;
  const buildCosts: ResourceCost[] = [
    { name: 'KADERIUM', value: price.kad * amount },
    { name: 'NANO_CRYSTAL', value: price.nano * amount },
    { name: 'STASIS_ENERGY', value: price.energy * amount },
    { name: 'FOOD', value: price.food * amount },
    { name: 'PRODIUM', value: price.prod * amount },
    { name: 'WORKER', value: price.work * amount },
  ];

  function toggleCell(idCell: string) {
    const next = new Set(selectedCells);
    if (next.has(idCell)) next.delete(idCell);
    else next.add(idCell);
    setSelectedCells(next);
  }

  const canLoad = selectedCells.size > 0 && amount > 0 && !!selectedUnit;

  return (
    <Window title="Производство юнитов" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <div class="ds-war-statistic__units">
        {data.units.map((unit) => {
          const local = localUnits.get(unit.unitname);
          return (
            <label key={unit.unitname} class="ds-war-statistic__unit-choice">
              <input
                type="radio"
                name="unit"
                checked={unit.unitname === selectedUnitName}
                onChange={() => {
                  setSelectedUnitName(unit.unitname);
                  setSelectedCells(new Set());
                }}
              />
              <img class="ds-unit-icon" src={unitIconUrl(unit.unitname)} alt="" />
              {local?.ln ?? unit.unitname}
            </label>
          );
        })}
      </div>

      <div class="ds-war-statistic__factories">
        {factories.map((factory) => {
          const selectable = selectedUnit ? isFactorySelectable(factory, selectedUnit) : false;
          return (
            <label
              key={factory.idCell}
              class={
                'ds-war-statistic__factory' +
                (selectable ? '' : ' ds-war-statistic__factory--disabled') +
                (factory.busy ? ' ds-war-statistic__factory--busy' : '')
              }
            >
              <input
                type="checkbox"
                disabled={!selectable}
                checked={selectedCells.has(factory.idCell)}
                onChange={() => toggleCell(factory.idCell)}
              />
              #{factory.idCell}{' '}
              {factory.busy ? (
                <>
                  (<img class="ds-unit-icon" src={unitIconUrl(factory.unit ?? '')} alt="" /> {factory.unit} × {factory.count})
                </>
              ) : (
                '(свободна)'
              )}
            </label>
          );
        })}
      </div>

      <div class="ds-war-statistic__controls">
        <Stepper value={amount} min={0} max={allowMax} onChange={setAmount} />
        <ResourceList costs={buildCosts} />
        <span>1 шт.: {formatTurnTime(perUnitSeconds)}</span>
        <span>Партия: {formatTurnTime(batchSeconds)}</span>
        <button
          type="button"
          class="ds-popup__btn"
          disabled={!canLoad}
          onClick={() => onLoad([...selectedCells], selectedUnit!.id, amount)}
        >
          Загрузить
        </button>
      </div>
    </Window>
  );
}
