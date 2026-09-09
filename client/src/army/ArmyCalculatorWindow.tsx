/**
 * Окно «Калькулятор армии» — armycalcclass.as, см. docs/client-combat-ui.md
 * §2. Данные — armyCreationData.ts (parseArmyCalculator). Чисто просмотровый
 * расчёт — у оригинала НЕТ ни одного исходящего запроса с этого экрана
 * (см. докстринг armyCreationData.ts), поэтому у окна нет ни одного onSend.
 */
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import type { LocalUnit } from '../data/localData';
import type { ArmyCalculatorData, UnitPower } from './armyCreationData';
import { ResourceValue } from '../ui/ResourceValue';

export interface ArmyCalculatorWindowProps {
  data: ArmyCalculatorData;
  localUnits: Map<string, LocalUnit>;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
}

/** power.sc — не установлено, что означает (см. докстринг armyCreationData.ts), показываем сырым "SC:" без иконки ресурса. */
const POWER_RESOURCE_NAME: Record<Exclude<keyof UnitPower, 'sc'>, string> = {
  kaderium: 'KADERIUM',
  nano: 'NANO_CRYSTAL',
  stasis: 'STASIS_ENERGY',
  food: 'FOOD',
  prodium: 'PRODIUM',
};

function PowerCell({ power }: { power: UnitPower }) {
  const entries = (Object.keys(POWER_RESOURCE_NAME) as (keyof typeof POWER_RESOURCE_NAME)[])
    .filter((k) => (power[k] ?? 0) > 0)
    .map((k) => <ResourceValue key={k} name={POWER_RESOURCE_NAME[k]} value={power[k]!} />);
  if (power.sc !== undefined && power.sc > 0) entries.push(<span key="sc">SC:{power.sc}</span>);
  return <span class="ds-resource-list">{entries.length > 0 ? entries : <span class="ds-resource-list__empty">—</span>}</span>;
}

export function ArmyCalculatorWindow({ data, localUnits, tabs, onTabClick, onClose }: ArmyCalculatorWindowProps) {
  return (
    <Window title="Калькулятор армии" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <p class="ds-train-unit__idle">Коэффициенты обмена: {data.koeff.join(' : ')}</p>
      <table>
        <thead>
          <tr>
            <th>Юнит</th>
            <th>Лимит</th>
            <th>Вес продиума</th>
            <th>Стоимость</th>
          </tr>
        </thead>
        <tbody>
          {data.units.map((u) => (
            <tr key={u.id}>
              <td>{localUnits.get(u.libname)?.ln ?? u.libname}</td>
              <td>{u.amount}</td>
              <td>{u.prodiumWeight}</td>
              <td>
                <PowerCell power={u.power} />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </Window>
  );
}
