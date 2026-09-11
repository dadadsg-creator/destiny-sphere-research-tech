/**
 * Окно «Калькулятор армии» — armycalcclass.as, см. docs/client-combat-ui.md
 * §2 и docs/client-side-computed-logic.md §2.7. Данные — armyCreationData.ts
 * (parseArmyCalculator). Чисто просмотровый расчёт — у оригинала НЕТ ни
 * одного исходящего запроса с этого экрана (см. докстринг
 * armyCreationData.ts), поэтому у окна нет ни одного onSend.
 *
 * **Дополнение (2026-09-11, категория «В» §2.7)**: сам калькулятор (ввод
 * количества юнита → пересчёт стоимости) раньше не был портирован —
 * окно показывало только статичную справочную таблицу цен без
 * возможности ввести количество. Портировано из `drawRes()`
 * (`armycalcclass.as:220-300`):
 *  - на юнит — количество (степпер, максимум `amount`), суммарная
 *    стоимость ресурсами (`power[R] × quant`) и продиум-эквивалент
 *    (`prodiumWeight × quant × koeff[R]`, по тому же R=K/N/S/F/P) —
 *    оригинал считает и показывает ОБЕ величины одновременно, не выбор;
 *  - общий итог по всем юнитам сразу — ТОЛЬКО продиум-эквивалент
 *    (`cost_Prod[R]`, армейски просуммированный по всем юнитам) и общий
 *    продиумный вес (`total_pro_weight`); аналогичная сумма по чистым
 *    ресурсам (`cost_Res`) в оригинале СЧИТАЕТСЯ, но её вывод на экран
 *    закомментирован (`armycalcclass.as:281-290`) — воспроизведено 1:1,
 *    здесь этого итога тоже нет;
 *  - «одинаковое количество для всех» (`amount_global`/`GlobalReCount`) и
 *    «сбросить всё» (`btn_reset`, буквально — установить 0 всем, без
 *    восстановления прежних значений — код восстановления в оригинале
 *    закомментирован).
 *
 * НЕ портировано (не критично, справочная информация, доступна игроку
 * в других местах интерфейса): статичный вывод текущего запаса игрока
 * по 5 ресурсам + серебру (`currentRes`/`rkad_prcK` и т.д.,
 * `armycalcclass.as:154-168`) — показывается один раз при открытии окна
 * и не входит в реактивный расчёт, требует отдельного источника данных
 * (запас ресурсов игрока), не приходящего в марке `armycalculator`.
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import type { TabDef } from '../ui/tabs';
import type { LocalUnit } from '../data/localData';
import type { ArmyCalculatorData, CalcUnit, UnitPower } from './armyCreationData';
import { ResourceList, ResourceValue, type ResourceCost } from '../ui/ResourceValue';
import { unitIconUrl } from '../data/resourceIcons';

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
/** koeff[] в том же порядке K/N/S/F/P, что и UnitPower (см. докстринг armyCreationData.ts / §2.7 доклада). */
const POWER_KEYS_ORDERED: Exclude<keyof UnitPower, 'sc'>[] = ['kaderium', 'nano', 'stasis', 'food', 'prodium'];

function PowerCell({ power }: { power: UnitPower }) {
  const entries = (Object.keys(POWER_RESOURCE_NAME) as (keyof typeof POWER_RESOURCE_NAME)[])
    .filter((k) => (power[k] ?? 0) > 0)
    .map((k) => <ResourceValue key={k} name={POWER_RESOURCE_NAME[k]} value={power[k]!} />);
  if (power.sc !== undefined && power.sc > 0) entries.push(<span key="sc">SC:{power.sc}</span>);
  return <span class="ds-resource-list">{entries.length > 0 ? entries : <span class="ds-resource-list__empty">—</span>}</span>;
}

/** armycalcclass.as:235-239 — Rkad_prcK и т.д., стоимость ресурсами при выбранном количестве. */
function unitResourceCost(unit: CalcUnit, quant: number): ResourceCost[] {
  return POWER_KEYS_ORDERED.map((key) => ({ name: POWER_RESOURCE_NAME[key], value: (unit.power[key] ?? 0) * quant }));
}

/** armycalcclass.as:244-248 — kad_prcK и т.д., продиум-эквивалент при выбранном количестве. */
function unitProdiumEquivalent(unit: CalcUnit, quant: number, koeff: number[]): ResourceCost[] {
  return POWER_KEYS_ORDERED.map((key, i) => ({
    name: POWER_RESOURCE_NAME[key],
    value: unit.prodiumWeight * quant * (koeff[i] ?? 0),
  }));
}

export function ArmyCalculatorWindow({ data, localUnits, tabs, onTabClick, onClose }: ArmyCalculatorWindowProps) {
  const [quantities, setQuantities] = useState<Record<string, number>>({});
  const [globalQuantity, setGlobalQuantity] = useState(0);

  const qtyOf = (u: CalcUnit) => quantities[u.id] ?? 0;

  function setQuantity(unit: CalcUnit, value: number) {
    setQuantities((prev) => ({ ...prev, [unit.id]: Math.min(value, unit.amount) }));
  }

  /** armycalcclass.as:138-149 GlobalReCount — одно значение сразу всем юнитам, каждый всё равно ограничен своим max. */
  function setAllQuantities(value: number) {
    setGlobalQuantity(value);
    setQuantities(Object.fromEntries(data.units.map((u) => [u.id, Math.min(value, u.amount)])));
  }

  /** armycalcclass.as:110-134 btn_reset — сброс всех количеств в 0 (восстановление прежних значений закомментировано в оригинале). */
  function resetAll() {
    setGlobalQuantity(0);
    setQuantities({});
  }

  const totalProdiumEquivalent: ResourceCost[] = POWER_KEYS_ORDERED.map((key, i) => ({
    name: POWER_RESOURCE_NAME[key],
    value: data.units.reduce((sum, u) => sum + u.prodiumWeight * qtyOf(u) * (data.koeff[i] ?? 0), 0),
  }));
  const totalProdiumWeight = data.units.reduce((sum, u) => sum + u.prodiumWeight * qtyOf(u), 0);

  return (
    <Window title="Калькулятор армии" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <div class="ds-spying__row">
        <span class="ds-spying__label">Коэффициенты обмена:</span>
        <span>{data.koeff.join(' : ')}</span>
      </div>
      <div class="ds-spying__row">
        <span class="ds-spying__label">Кол-во сразу для всех:</span>
        <Stepper value={globalQuantity} min={0} onChange={setAllQuantities} />
        <button type="button" class="ds-popup__btn" onClick={resetAll}>
          Сбросить всё
        </button>
      </div>
      <table>
        <thead>
          <tr>
            <th>Юнит</th>
            <th>Лимит</th>
            <th>Вес продиума</th>
            <th>Цена за 1</th>
            <th>Количество</th>
            <th>Итого ресурсами</th>
            <th>Продиум-эквивалент</th>
          </tr>
        </thead>
        <tbody>
          {data.units.map((u) => (
            <tr key={u.id}>
              <td class="ds-artefact-row">
                <img class="ds-unit-icon" src={unitIconUrl(u.libname)} alt="" />
                <span>{localUnits.get(u.libname)?.ln ?? u.libname}</span>
              </td>
              <td>{u.amount}</td>
              <td>{u.prodiumWeight}</td>
              <td>
                <PowerCell power={u.power} />
              </td>
              <td>
                <Stepper value={qtyOf(u)} min={0} max={u.amount} onChange={(v) => setQuantity(u, v)} />
              </td>
              <td>
                <ResourceList costs={unitResourceCost(u, qtyOf(u))} />
              </td>
              <td>
                <ResourceList costs={unitProdiumEquivalent(u, qtyOf(u), data.koeff)} />
              </td>
            </tr>
          ))}
        </tbody>
        <tfoot>
          <tr>
            <td colSpan={5}>Итог (продиум-эквивалент), общий продиумный вес: {totalProdiumWeight}</td>
            <td colSpan={2}>
              <ResourceList costs={totalProdiumEquivalent} />
            </td>
          </tr>
        </tfoot>
      </table>
    </Window>
  );
}
