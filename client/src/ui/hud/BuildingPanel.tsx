/**
 * Панель выбранного здания (docs/main_screen.md §9) — докнутая справа
 * панель главного экрана, заменяет модальное открытие `BuildInfoWindow`
 * для живой сессии (см. `selectedBuildingStore.ts`/`protocol/dispatch.tsx`
 * про то, как она наполняется). Кнопки действий/стоимость апгрейда —
 * общий `BuildInfoActions.tsx`, тот же, что использует модалка (для
 * `?screen=`-тестов).
 *
 * Название/картинка/уровень: в этой игре нет числового "уровня" здания
 * как такового — апгрейд МЕНЯЕТ `id_building` целиком на другую
 * постройку (`upgradedata.id_next_build`), не инкрементирует счётчик
 * (подтверждено — ни в одном месте протокола, ни в БД нет generic
 * поля "level"; единственное исключение — ARTEFACT_TOWER, чей "ранг"
 * закодирован прямо в имени, `map/buildingTypes.ts`). Показывать
 * выдуманный уровень значило бы нарушить правило проекта "не
 * выдумывать данные" — вместо этого показано текущее название здания
 * (оно и есть "уровень" в терминах этой игры) плюс машинное имя мелким
 * текстом, если оно отличается от локализованного.
 */
import { selectedBuilding } from './selectedBuildingStore';
import { localData } from '../../data/localDataStore';
import { capitalizeFirst } from '../../data/localNameResolver';
import { BUILDING_NAMES, buildingAssetPath } from '../../map/buildingTypes';
import { BuildInfoActions } from '../../buildings/BuildInfoActions';
import { ResourceList, type ResourceCost } from '../ResourceValue';
import type { BuildStatus } from '../../map/buildData';

const STATE_LABEL: Record<string, string> = {
  '1': 'Строится',
  '2': 'Активируется',
  '3': 'Деактивируется',
  '4': 'Деактивировано',
  '5': 'Улучшается',
  OK: 'В норме',
};

function stateLabel(status: BuildStatus | undefined): string {
  if (status === undefined) return 'В норме';
  return STATE_LABEL[status] ?? 'В норме';
}

const RES_SHORT_TO_NAME: Record<'K' | 'N' | 'S' | 'F' | 'P', string> = {
  K: 'KADERIUM',
  N: 'NANO_CRYSTAL',
  S: 'STASIS_ENERGY',
  F: 'FOOD',
  P: 'PRODIUM',
};

function toResourceCosts(bag: Record<'K' | 'N' | 'S' | 'F' | 'P', string>): ResourceCost[] {
  return (Object.keys(bag) as Array<keyof typeof bag>).map((key) => ({
    name: RES_SHORT_TO_NAME[key],
    value: Number(bag[key]) || 0,
  }));
}

export function BuildingPanel() {
  const sel = selectedBuilding.value;
  if (!sel) return null;

  const buildingId = sel.info?.buildingId ?? sel.record?.id ?? null;
  const machineName = buildingId !== null ? BUILDING_NAMES[buildingId] : undefined;
  const catalogEntry = buildingId !== null ? localData.value?.buildings.get(buildingId) : undefined;
  const displayName = catalogEntry?.ln ? capitalizeFirst(catalogEntry.ln) : machineName ?? `Здание #${buildingId ?? '?'}`;
  const imageUrl = machineName ? buildingAssetPath(machineName, sel.race) : null;

  const status = sel.record?.status;
  const inProgress = status !== undefined && status !== 'OK';

  return (
    <div class="ds-hud-buildpanel">
      <div class="ds-hud-buildpanel__header">
        <h3 class="ds-hud-buildpanel__title">{displayName}</h3>
        <button type="button" class="ds-hud-buildpanel__close" onClick={() => (selectedBuilding.value = null)} aria-label="Закрыть">
          ×
        </button>
      </div>

      {machineName && displayName !== machineName && <div class="ds-hud-buildpanel__machine">{machineName}</div>}

      {imageUrl && (
        <div class="ds-hud-buildpanel__image-wrap">
          <img class="ds-hud-buildpanel__image" src={imageUrl} alt="" />
        </div>
      )}

      <div class="ds-hud-buildpanel__row">
        <span class="ds-hud-buildpanel__label">Ячейка</span>
        <span>№{sel.cellNum + 1}</span>
      </div>
      <div class="ds-hud-buildpanel__row">
        <span class="ds-hud-buildpanel__label">Состояние</span>
        <span>{stateLabel(status)}</span>
      </div>

      {inProgress && sel.record?.percent !== undefined && (
        <div class="ds-hud-buildpanel__progress">
          <div class="ds-hud-buildpanel__progress-bar">
            <div class="ds-hud-buildpanel__progress-fill" style={{ width: `${sel.record.percent}%` }} />
          </div>
          <span class="ds-hud-buildpanel__progress-text">
            {sel.record.percent}% {sel.record.dateEnd ? `· до ${sel.record.dateEnd}` : ''}
          </span>
        </div>
      )}

      {!sel.info && <div class="ds-hud-buildpanel__loading">Загрузка подробностей…</div>}

      {sel.info && (
        <>
          <div class="ds-hud-buildpanel__row ds-hud-buildpanel__row--stack">
            <span class="ds-hud-buildpanel__label">Производство</span>
            <ResourceList costs={toResourceCosts(sel.info.production)} />
          </div>
          <div class="ds-hud-buildpanel__row ds-hud-buildpanel__row--stack">
            <span class="ds-hud-buildpanel__label">Потребление</span>
            <ResourceList costs={toResourceCosts(sel.info.upkeep)} />
          </div>

          {sel.actions && <BuildInfoActions data={sel.info} {...sel.actions} />}
        </>
      )}
    </div>
  );
}
