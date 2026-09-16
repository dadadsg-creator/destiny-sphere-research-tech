/**
 * Панель выбранного здания (docs/main_screen.md §9) — докнутая справа
 * панель в главном экране вместо прежней модалки `BuildInfoWindow`
 * (которая остаётся как есть для `?screen=`-тестов, см. `BuildingPanel.tsx`).
 *
 * Двухэтапное наполнение: клик по занятой ячейке сразу даёт `record`
 * (кэш последнего `<build>`, `map/renderer.ts` `getBuildRecord()` — имя/
 * картинка/состояние/процент готовности уже известны БЕЗ ответа сервера),
 * `info`/`actions` заполняются чуть позже, когда придёт ответ
 * `getbuildinfo` (`protocol/dispatch.tsx`, `renderBuildInfo()`).
 */
import { signal } from '@preact/signals';
import type { BuildRecord } from '../../map/buildData';
import type { BuildInfoData } from '../../buildings/buildInfoData';
import type { Race } from '../../map/buildingTypes';

export interface SelectedBuildingActions {
  onCancelBuild: () => void;
  onDemolish: () => void;
  onCancelUpgrade: () => void;
  onActivate: () => void;
  onCancelActivate: () => void;
  onDeactivate: () => void;
  onUpgrade: () => void;
}

export interface SelectedBuilding {
  /** 0-based num ячейки (совпадает с BuildRecord.num/hexGrid num). */
  cellNum: number;
  record: BuildRecord | null;
  info: BuildInfoData | null;
  actions: SelectedBuildingActions | null;
  /** Раса ВЛАДЕЛЬЦА соты на момент клика — нужна для пути картинки (buildingAssetPath), см. map/buildingTypes.ts про расо-специфичную графику. */
  race: Race;
}

export const selectedBuilding = signal<SelectedBuilding | null>(null);
