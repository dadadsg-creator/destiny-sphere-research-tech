/**
 * Панель массовой загрузки военных заводов (`warstatistic.as`), см.
 * docs/client-combat-ui.md §5. НЕ модальное окно — владелец (2026-09-18,
 * увидев первую версию, `WarStatisticWindow.tsx`, список чекбоксов в
 * обычной модалке поверх карты): "Ты сделал окно которое закрывает
 * карту... Надо делать как на флеше — отдельный интерфейс в игре с
 * отображением требуемых на строительство юнитов ресурсов, а не
 * всплывающее окно. А на карте показывать загрузку заводов."
 *
 * Оригинал держит панель настроек ПРИКОЛОЧЕННОЙ сверху экрана
 * (`top_war_unit_loader_tech/bio/psi`, `warstatistic.as:86-100`) и
 * подсвечивает сами фабрики ПРЯМО на карте зелёным/красным
 * (`glow_factory`/`factory_create`, `warstatistic.as:227-262`, через
 * отдельную схематичную гекс-сетку, не саму карту). Здесь — тот же
 * принцип на уже готовых частях порта: докнутая сверху полоса (через
 * `ui/windowStore.ts:topPanel`, тот же независимый слот, что и у лога
 * сообщений, только сверху) + подсветка через
 * `MapRenderer.setWarFactoryHighlights()` на РЕАЛЬНЫХ ячейках живой
 * карты (не дублируем отдельную гекс-сетку оригинала — тот же
 * практический результат).
 *
 * **Важное поведенческое отличие от первой версии, исправленное здесь**:
 * оригинал по умолчанию включает в пакет ВСЕ подходящие свободные
 * фабрики сразу (`glow_factory`: `active=true` для всех совпадающих по
 * типу), клик по фабрике ИСКЛЮЧАЕТ её из пакета
 * (`init_factory_cell`, `warstatistic.as:264-279`), а не наоборот.
 * Отсюда `excludedCells` (что исключено из уже полного пакета), не
 * `selectedCells` (что добавлено к пустому) — первая версия ошибочно
 * начинала с пустого набора.
 *
 * Цена/время — та же формула категории «В», что и раньше
 * (`trainUnitData.ts`), просто показана в двух видах одновременно (за
 * 1 шт. и за всю партию), как в оригинале (`KADERIUM`/…/`_A`-поля,
 * `warstatistic.as:116-129,181-193`), а не только партией.
 */
import { useEffect, useMemo, useState } from 'preact/hooks';
import { Stepper } from '../ui/Stepper';
import { ResourceList, type ResourceCost } from '../ui/ResourceValue';
import { formatTurnTime } from '../data/turnTime';
import { unitIconUrl } from '../data/resourceIcons';
import type { WarComplexData } from './warFactoryData';
import { isFactorySelectable } from './warFactoryData';
import {
  computeAllowMax,
  unitBuildPriceFromLocal,
  unitBuildTimeSeconds,
  GLOBAL_MAX_NUMBER,
  type PlayerResourcePool,
} from './trainUnitData';
import type { LocalUnit } from '../data/localData';
import type { MapRenderer } from '../map/renderer';

export interface WarFactoryPanelProps {
  data: WarComplexData;
  localUnits: Map<string, LocalUnit>;
  playerResources: PlayerResourcePool;
  renderer: MapRenderer;
  onClose: () => void;
  onLoad: (cellIds: string[], unitId: string, amount: number) => void;
}

export function WarFactoryPanel({ data, localUnits, playerResources, renderer, onClose, onLoad }: WarFactoryPanelProps) {
  const [selectedUnitName, setSelectedUnitName] = useState<string>(data.units[0]?.unitname ?? '');
  const [excludedCells, setExcludedCells] = useState<Set<string>>(new Set());
  const [amount, setAmount] = useState(0);

  const selectedUnit = data.units.find((u) => u.unitname === selectedUnitName);
  const factories = useMemo(() => [...data.factories.values()], [data.factories]);

  const selectedCells = selectedUnit
    ? factories.filter((f) => isFactorySelectable(f, selectedUnit) && !excludedCells.has(f.idCell)).map((f) => f.idCell)
    : [];

  const price = unitBuildPriceFromLocal(selectedUnit ? localUnits.get(selectedUnit.unitname) : undefined);
  const allowMax = selectedUnit ? computeAllowMax(GLOBAL_MAX_NUMBER, price, playerResources, selectedUnit.maxallow ?? undefined) : 0;

  const speedDivisor = data.speedup === '1' ? 2 : 1;
  const perUnitSeconds = unitBuildTimeSeconds(price.time, data.turn) / speedDivisor;
  const countFactory = selectedCells.length;
  const batchSeconds = countFactory > 0 ? perUnitSeconds * Math.ceil(amount / countFactory) : 0;

  const perUnitCosts: ResourceCost[] = [
    { name: 'KADERIUM', value: price.kad },
    { name: 'NANO_CRYSTAL', value: price.nano },
    { name: 'STASIS_ENERGY', value: price.energy },
    { name: 'FOOD', value: price.food },
    { name: 'PRODIUM', value: price.prod },
    { name: 'WORKER', value: price.work },
  ];
  const batchCosts: ResourceCost[] = perUnitCosts.map((c) => ({ name: c.name, value: c.value * amount }));

  // Подсветка фабрик на реальной карте — синхронизирована с выбором юнита/исключений.
  useEffect(() => {
    if (!selectedUnit) {
      renderer.setWarFactoryHighlights(null);
      return;
    }
    const entries = factories
      .filter((f) => isFactorySelectable(f, selectedUnit))
      .map((f) => ({
        num: Number(f.idCell),
        active: !excludedCells.has(f.idCell),
        label: String(f.count),
        onClick: () => {
          setExcludedCells((prev) => {
            const next = new Set(prev);
            if (next.has(f.idCell)) next.delete(f.idCell);
            else next.add(f.idCell);
            return next;
          });
        },
      }));
    renderer.setWarFactoryHighlights(entries.length > 0 ? entries : null);
    return () => renderer.setWarFactoryHighlights(null);
  }, [factories, selectedUnit, excludedCells, renderer]);

  function selectUnit(name: string) {
    setSelectedUnitName(name);
    setExcludedCells(new Set());
    setAmount(0);
  }

  const canLoad = selectedCells.length > 0 && amount > 0 && !!selectedUnit;

  return (
    <div class="ds-war-panel">
      <div class="ds-war-panel__row">
        <div class="ds-war-panel__units">
          {data.units.map((unit) => {
            const local = localUnits.get(unit.unitname);
            return (
              <label key={unit.unitname} class="ds-war-panel__unit-choice">
                <input type="radio" name="war-panel-unit" checked={unit.unitname === selectedUnitName} onChange={() => selectUnit(unit.unitname)} />
                <img class="ds-unit-icon" src={unitIconUrl(unit.unitname)} alt="" />
                {local?.ln ?? unit.unitname}
              </label>
            );
          })}
        </div>
        <button type="button" class="ds-popup__btn" onClick={onClose}>
          Закрыть
        </button>
      </div>

      <div class="ds-war-panel__row">
        <span class="ds-war-panel__costs-label">За 1 шт.:</span>
        <ResourceList costs={perUnitCosts} />
        <span>{formatTurnTime(perUnitSeconds)}</span>

        <span class="ds-war-panel__costs-label">
          Партия ({countFactory} завод{countFactory === 1 ? '' : 'ов'}):
        </span>
        <ResourceList costs={batchCosts} />
        <span>{formatTurnTime(batchSeconds)}</span>
      </div>

      <div class="ds-war-panel__row">
        <Stepper value={amount} min={0} max={allowMax} onChange={setAmount} />
        <button type="button" class="ds-popup__btn" disabled={!canLoad} onClick={() => onLoad(selectedCells, selectedUnit!.id, amount)}>
          Загрузить
        </button>
        <span class="ds-war-panel__hint">Клик по подсвеченному заводу на карте — исключить/вернуть в пакет.</span>
      </div>
    </div>
  );
}
