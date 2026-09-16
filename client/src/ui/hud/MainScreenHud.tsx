/**
 * Главный экран (docs/main_screen.md) — каркас слоёв поверх PixiJS-карты
 * (та живёт в `#app`, этот компонент монтируется в `#ui-root`, тот же
 * слой, что и `WindowHost`/бывший `LiveToolbar`).
 *
 * `.ds-hud-root` — `pointer-events:none`, включается обратно на каждой
 * панели по отдельности (`pointer-events:auto`) — иначе прозрачные
 * промежутки между панелями блокировали бы клики по карте под ними.
 *
 * Сознательно НЕ реализовано в этом проходе (см. переписку с владельцем):
 * панель выбранного здания (§9 ТЗ) — по клику на постройку по-прежнему
 * открывается существующее модальное `BuildInfoWindow` (см.
 * `protocol/dispatch.tsx`), а не отдельная пристыкованная панель
 * справа — самый крупный из оставшихся пунктов ТЗ, отдельный проход.
 */
import { useState } from 'preact/hooks';
import { ResourceBar } from './ResourceBar';
import { MainNav } from './MainNav';
import { PopulationPanel } from './PopulationPanel';
import { BottomBar } from './BottomBar';
import { RadialMenu } from './RadialMenu';
import { HudErrorBoundary } from './HudErrorBoundary';
import { hudActions } from './hudActionsStore';

export function MainScreenHud() {
  const actions = hudActions.value;
  const [bottomCollapsed, setBottomCollapsed] = useState(false);
  if (!actions) return null;

  return (
    <div class="ds-hud-root">
      <div class="ds-hud-topstack">
        <HudErrorBoundary label="Ресурсы">
          <ResourceBar />
        </HudErrorBoundary>
        <HudErrorBoundary label="Навигация">
          <MainNav />
        </HudErrorBoundary>
      </div>

      <div class="ds-hud-left">
        <HudErrorBoundary label="Население">
          <PopulationPanel />
        </HudErrorBoundary>
      </div>

      <div class="ds-hud-radial-wrap">
        <HudErrorBoundary label="Круговое меню">
          <RadialMenu />
        </HudErrorBoundary>
      </div>

      <div class="ds-hud-bottom">
        <HudErrorBoundary label="Нижняя панель">
          <BottomBar collapsed={bottomCollapsed} onToggleCollapsed={() => setBottomCollapsed((c) => !c)} />
        </HudErrorBoundary>
      </div>
    </div>
  );
}
