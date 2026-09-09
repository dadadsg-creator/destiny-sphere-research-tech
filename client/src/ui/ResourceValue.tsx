/**
 * Общий примитив «иконка ресурса + число» — вместо сырых `K/N/S/F/P/W`
 * через слэш или `STASIS_ENERGY:+800` строкой. Используется везде, где
 * отображается стоимость/бонус в игровых ресурсах (постройки, артефакты,
 * и т.д. — см. CLIENT_PORTING_PLAN.md, «Визуальный примитив ресурсов»).
 */
import { RESOURCE_NAME_RU, resourceIconUrl } from '../data/resourceIcons';

export interface ResourceValueProps {
  /** Внутреннее имя ресурса (KADERIUM, STASIS_ENERGY, ...) — неизвестное имя просто показывается как есть, без иконки. */
  name: string;
  value: string | number;
}

export function ResourceValue({ name, value }: ResourceValueProps) {
  const label = RESOURCE_NAME_RU[name];
  return (
    <span class="ds-resource" title={label ?? name}>
      {label && <img class="ds-resource__icon" src={resourceIconUrl(name)} alt="" />}
      <span class="ds-resource__value">{value}</span>
    </span>
  );
}

export interface ResourceCost {
  name: string;
  value: number;
}

/** Список ненулевых стоимостей — нулевые ресурсы не показываются (см. докстринг файла). */
export function ResourceList({ costs }: { costs: ResourceCost[] }) {
  const nonZero = costs.filter((c) => c.value > 0);
  if (nonZero.length === 0) return <span class="ds-resource-list__empty">—</span>;
  return (
    <span class="ds-resource-list">
      {nonZero.map((c) => (
        <ResourceValue key={c.name} name={c.name} value={c.value} />
      ))}
    </span>
  );
}
