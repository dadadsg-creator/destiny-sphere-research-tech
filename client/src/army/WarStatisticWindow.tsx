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
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import type { TabDef } from '../ui/tabs';
import type { WarComplexData, Factory, FactoryUnit } from './warFactoryData';
import { computeAllowMax, unitBuildPriceFromLocal, GLOBAL_MAX_NUMBER, type PlayerResourcePool } from './trainUnitData';
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
  const allowMax = selectedUnit
    ? computeAllowMax(
        GLOBAL_MAX_NUMBER,
        unitBuildPriceFromLocal(localUnits.get(selectedUnit.unitname)),
        playerResources,
        selectedUnit.maxallow ?? undefined,
      )
    : 0;

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
              #{factory.idCell} {factory.busy ? `(${factory.unit} × ${factory.count})` : '(свободна)'}
            </label>
          );
        })}
      </div>

      <div class="ds-war-statistic__controls">
        <Stepper value={amount} min={0} max={allowMax} onChange={setAmount} />
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
