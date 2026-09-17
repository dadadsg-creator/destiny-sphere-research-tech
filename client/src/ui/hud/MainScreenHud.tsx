/**
 * Главный экран (docs/main_screen.md) — каркас слоёв поверх PixiJS-карты
 * (та живёт в `#app`, этот компонент монтируется в `#ui-root`, тот же
 * слой, что и `WindowHost`/бывший `LiveToolbar`).
 *
 * `.ds-hud-root` — `pointer-events:none`, включается обратно на каждой
 * панели по отдельности (`pointer-events:auto`) — иначе прозрачные
 * промежутки между панелями блокировали бы клики по карте под ними.
 *
 * Панель выбранного здания (§9 ТЗ) — докнутая справа (BuildingPanel.tsx),
 * заменила модальное открытие BuildInfoWindow для живой сессии (модалка
 * осталась для `?screen=build-info`).
 */
import { ResourceBar } from './ResourceBar';
import { MainNav } from './MainNav';
import { PopulationPanel } from './PopulationPanel';
import { BottomBar } from './BottomBar';
import { RadialMenu } from './RadialMenu';
import { AccountMenu } from './AccountMenu';
import { BuildingPanel } from './BuildingPanel';
import { HudErrorBoundary } from './HudErrorBoundary';
import { hudActions } from './hudActionsStore';

export function MainScreenHud() {
  const actions = hudActions.value;
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

      <div class="ds-hud-account-wrap">
        <HudErrorBoundary label="Биллинг/премиум/FAQ">
          <AccountMenu />
        </HudErrorBoundary>
      </div>

      <div class="ds-hud-right">
        <HudErrorBoundary label="Панель здания">
          <BuildingPanel />
        </HudErrorBoundary>
      </div>

      <div class="ds-hud-bottom">
        <HudErrorBoundary label="Нижняя панель">
          <BottomBar />
        </HudErrorBoundary>
      </div>
    </div>
  );
}
