/**
 * Верхняя панель ресурсов (docs/main_screen.md §5). Данные — марка
 * `combres` (`data/combResData.ts`/`combResStore.ts`), уже подключена
 * ко всем ответам сервера, ничего заново парсить не нужно.
 *
 * Порядок — как в самой марке `<combres>` (пул соты, затем валюты
 * игрока): KADERIUM/NANO_CRYSTAL/PRODIUM/FOOD/STASIS_ENERGY/
 * GOLD_CREDIT/SILVER_CREDIT. Population-ресурсы (WORKER/SCIENTIST/
 * ARCHEOLOGIST) сюда не входят — они в левой панели (PopulationPanel.tsx).
 *
 * "Изменение за час" из ТЗ — сознательно не показано: марка `combres`
 * такого поля не содержит (подтверждено сервером, `UA_Refresh.class.php`)
 * и нигде в порте не парсится — придумывать динамику из головы значило
 * бы нарушить правило проекта "не выдумывать данные".
 */
import { combRes } from '../../data/combResStore';
import type { CombResName } from '../../data/combResData';
import { resourceDisplayName, resourceIconUrl } from '../../data/resourceIcons';

const RESOURCE_ORDER: CombResName[] = ['KADERIUM', 'NANO_CRYSTAL', 'PRODIUM', 'FOOD', 'STASIS_ENERGY', 'GOLD_CREDIT', 'SILVER_CREDIT'];

/** Компактный формат для узкого блока (§5 "длинные значения не должны выходить за границы") — точное число остаётся в подсказке. */
function formatCompact(n: number): string {
  if (n >= 1_000_000) {
    const m = n / 1_000_000;
    return `${m >= 100 ? Math.round(m) : m.toFixed(1).replace(/\.0$/, '')}M`;
  }
  if (n >= 1_000) return `${Math.round(n / 1000)}K`;
  return String(n);
}

const numberFormatter = new Intl.NumberFormat('ru-RU');

export function ResourceBar() {
  const res = combRes.value;
  const loading = Object.keys(res).length === 0;
  if (loading) {
    return (
      <div class="ds-hud-resbar ds-hud-resbar--loading" aria-busy="true">
        Загрузка ресурсов…
      </div>
    );
  }
  return (
    <div class="ds-hud-resbar">
      {RESOURCE_ORDER.map((name) => {
        const entry = res[name];
        if (!entry) return null;
        const label = resourceDisplayName(name);
        return (
          <div key={name} class="ds-hud-resbar__item" title={`${label}: ${numberFormatter.format(entry.current)}`}>
            <img class="ds-hud-resbar__icon" src={resourceIconUrl(name)} alt="" />
            <span class="ds-hud-resbar__value">{formatCompact(entry.current)}</span>
          </div>
        );
      })}
    </div>
  );
}
