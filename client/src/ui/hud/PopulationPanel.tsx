/**
 * Левая панель населения (docs/main_screen.md §7) — три population-
 * ресурса из той же марки `combres`, что и ResourceBar.tsx (WORKER/
 * SCIENTIST/ARCHEOLOGIST — единственные три с полем `max`, см.
 * `data/combResData.ts`).
 */
import { combRes } from '../../data/combResStore';
import type { CombResName } from '../../data/combResData';
import { resourceDisplayName, resourceIconUrl } from '../../data/resourceIcons';

const POPULATION_ORDER: CombResName[] = ['WORKER', 'SCIENTIST', 'ARCHEOLOGIST'];

function fillState(current: number, max: number): 'ok' | 'warn' | 'full' {
  if (max <= 0) return 'ok';
  const ratio = current / max;
  if (ratio >= 1) return 'full';
  if (ratio >= 0.9) return 'warn';
  return 'ok';
}

export function PopulationPanel() {
  const res = combRes.value;
  const loading = Object.keys(res).length === 0;
  if (loading) {
    return (
      <div class="ds-hud-population ds-hud-population--loading" aria-busy="true">
        Загрузка…
      </div>
    );
  }
  return (
    <div class="ds-hud-population">
      {POPULATION_ORDER.map((name) => {
        const entry = res[name];
        if (!entry || entry.max === undefined) return null;
        const state = fillState(entry.current, entry.max);
        const pct = entry.max > 0 ? Math.min(100, (entry.current / entry.max) * 100) : 0;
        return (
          <div key={name} class="ds-hud-population__item" title={resourceDisplayName(name)}>
            <img class="ds-hud-population__icon" src={resourceIconUrl(name)} alt="" />
            <div class="ds-hud-population__body">
              <div class="ds-hud-population__values">
                {entry.current.toLocaleString('ru-RU')}/{entry.max.toLocaleString('ru-RU')}
              </div>
              <div class="ds-hud-population__bar">
                <div class={`ds-hud-population__fill ds-hud-population__fill--${state}`} style={{ width: `${pct}%` }} />
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}
