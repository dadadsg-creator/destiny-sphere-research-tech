/**
 * Кнопки действий + стоимость апгрейда — общее тело `BuildInfoWindow.tsx`
 * (модалка, `?screen=build-info`) и `ui/hud/BuildingPanel.tsx` (докнутая
 * панель главного экрана, docs/main_screen.md §9) — вынесено отдельно,
 * чтобы не дублировать логику показа кнопок в двух местах (см. таблицу
 * поле→команда в докстринге `buildInfoData.ts`).
 */
import { ResourceList, type ResourceCost } from '../ui/ResourceValue';
import type { BuildInfoData } from './buildInfoData';

export interface BuildInfoActionsProps {
  data: BuildInfoData;
  onCancelBuild: () => void;
  onDemolish: () => void;
  onCancelUpgrade: () => void;
  onActivate: () => void;
  onCancelActivate: () => void;
  onDeactivate: () => void;
  onUpgrade: () => void;
}

export function BuildInfoActions({
  data,
  onCancelBuild,
  onDemolish,
  onCancelUpgrade,
  onActivate,
  onCancelActivate,
  onDeactivate,
  onUpgrade,
}: BuildInfoActionsProps) {
  const { info, upgrade } = data;
  const hasUn = 'un' in info;
  const hasDestr = 'destr' in info;
  const hasUp = 'up' in info;
  const hasActivate = 'activate' in info;
  const hasIa = 'ia' in info;
  const hasAc = 'ac' in info;
  const hasDeact = 'deact' in info;
  const hasUpgrade = 'upgrade' in info && !!upgrade?.allow;

  const upgradeCosts: ResourceCost[] = upgrade
    ? [
        { name: 'KADERIUM', value: Number(upgrade.kad) || 0 },
        { name: 'NANO_CRYSTAL', value: Number(upgrade.nano) || 0 },
        { name: 'STASIS_ENERGY', value: Number(upgrade.stat) || 0 },
        { name: 'FOOD', value: Number(upgrade.food) || 0 },
        { name: 'PRODIUM', value: Number(upgrade.prod) || 0 },
        { name: 'WORKER', value: Number(upgrade.work) || 0 },
      ]
    : [];

  return (
    <>
      <div class="ds-build-info__actions">
        {hasUn && (
          <button type="button" class="ds-popup__btn" onClick={onCancelBuild}>
            Отменить постройку
          </button>
        )}
        {hasDestr && (
          <button type="button" class="ds-popup__btn" onClick={onDemolish}>
            Снести
          </button>
        )}
        {hasUp && (
          <button type="button" class="ds-popup__btn" onClick={onCancelUpgrade}>
            Отменить апгрейд
          </button>
        )}
        {hasActivate && (
          <button type="button" class="ds-popup__btn" onClick={onActivate}>
            Активировать
          </button>
        )}
        {hasIa && hasAc && (
          <button type="button" class="ds-popup__btn" onClick={onCancelActivate}>
            Отменить активацию
          </button>
        )}
        {hasIa && !hasAc && <p class="ds-train-unit__idle">Деактивируется…</p>}
        {hasDeact && (
          <button type="button" class="ds-popup__btn" onClick={onDeactivate}>
            Деактивировать
          </button>
        )}
      </div>

      {upgrade && (
        <div class="ds-build-info__upgrade">
          <p>
            Апгрейд: <ResourceList costs={upgradeCosts} /> Время: {upgrade.time}
          </p>
          <button type="button" class="ds-popup__btn" disabled={!hasUpgrade} onClick={onUpgrade}>
            Апгрейд
          </button>
        </div>
      )}
    </>
  );
}
