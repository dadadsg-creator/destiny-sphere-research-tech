/**
 * Окно «Информация о здании» — buildinfo.as, см.
 * docs/client-economy-social-ui.md §1.8. Данные — buildInfoData.ts
 * (parseInfoBuild). Кнопки собраны по буквальной таблице поле→команда
 * из доклада (см. docstring buildInfoData.ts) — присутствие ключа в
 * `info` (не его значение) решает, показывать ли кнопку, кроме `ia`,
 * где дополнительно проверяется наличие `ac` (один и тот же флаг `ia`
 * означает и активацию, и деактивацию — различаются только по `ac`).
 */
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import type { BuildInfoData } from './buildInfoData';

export interface BuildInfoWindowProps {
  data: BuildInfoData;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onCancelBuild: () => void;
  onDemolish: () => void;
  onCancelUpgrade: () => void;
  onActivate: () => void;
  onCancelActivate: () => void;
  onDeactivate: () => void;
  onUpgrade: () => void;
}

export function BuildInfoWindow({
  data,
  tabs,
  onTabClick,
  onClose,
  onCancelBuild,
  onDemolish,
  onCancelUpgrade,
  onActivate,
  onCancelActivate,
  onDeactivate,
  onUpgrade,
}: BuildInfoWindowProps) {
  const { info, upgrade } = data;
  const hasUn = 'un' in info;
  const hasDestr = 'destr' in info;
  const hasUp = 'up' in info;
  const hasActivate = 'activate' in info;
  const hasIa = 'ia' in info;
  const hasAc = 'ac' in info;
  const hasDeact = 'deact' in info;
  const hasUpgrade = 'upgrade' in info && !!upgrade?.allow;

  return (
    <Window title="Информация о здании" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
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
          <p>Апгрейд: K:{upgrade.kad} N:{upgrade.nano} S:{upgrade.stat} F:{upgrade.food} P:{upgrade.prod} Раб:{upgrade.work} Время:{upgrade.time}</p>
          <button type="button" class="ds-popup__btn" disabled={!hasUpgrade} onClick={onUpgrade}>
            Апгрейд
          </button>
        </div>
      )}
    </Window>
  );
}
