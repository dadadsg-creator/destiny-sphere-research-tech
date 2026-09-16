/**
 * Кнопки действий + стоимость апгрейда — общее тело `BuildInfoWindow.tsx`
 * (модалка, `?screen=build-info`) и `ui/hud/BuildingPanel.tsx` (докнутая
 * панель главного экрана, docs/main_screen.md §9) — вынесено отдельно,
 * чтобы не дублировать логику показа кнопок в двух местах (см. таблицу
 * поле→команда в докстринге `buildInfoData.ts`).
 *
 * Порядок — по прямому указанию владельца (2026-09-16): "Деактивировать"
 * первой (основное действие в норме), "Снести" — отдельно, НИЖЕ, с явным
 * опасным оформлением и подтверждением перед отправкой (необратимое
 * действие — раньше выглядело как обычная кнопка в общем ряду).
 */
import { useState } from 'preact/hooks';
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
  const [confirmingDemolish, setConfirmingDemolish] = useState(false);

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
        {hasDeact && (
          <button type="button" class="ds-popup__btn" onClick={onDeactivate}>
            Деактивировать
          </button>
        )}
        {hasActivate && (
          <button type="button" class="ds-popup__btn" onClick={onActivate}>
            Активировать
          </button>
        )}
        {hasUn && (
          <button type="button" class="ds-popup__btn" onClick={onCancelBuild}>
            Отменить постройку
          </button>
        )}
        {hasUp && (
          <button type="button" class="ds-popup__btn" onClick={onCancelUpgrade}>
            Отменить апгрейд
          </button>
        )}
        {hasIa && hasAc && (
          <button type="button" class="ds-popup__btn" onClick={onCancelActivate}>
            Отменить активацию
          </button>
        )}
        {hasIa && !hasAc && <p class="ds-train-unit__idle">Деактивируется…</p>}
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

      {hasDestr && (
        <div class="ds-build-info__danger">
          {!confirmingDemolish ? (
            <button type="button" class="ds-popup__btn ds-popup__btn--danger" onClick={() => setConfirmingDemolish(true)}>
              Снести
            </button>
          ) : (
            <div class="ds-build-info__confirm">
              <span>Точно снести здание? Действие необратимо.</span>
              <button
                type="button"
                class="ds-popup__btn ds-popup__btn--danger"
                onClick={() => {
                  setConfirmingDemolish(false);
                  onDemolish();
                }}
              >
                Да, снести
              </button>
              <button type="button" class="ds-popup__btn" onClick={() => setConfirmingDemolish(false)}>
                Отмена
              </button>
            </div>
          )}
        </div>
      )}
    </>
  );
}
