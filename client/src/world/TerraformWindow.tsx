/**
 * Окно «Терраформинг» — terraform_class.as, см.
 * docs/client-economy-social-ui.md §1.9. Данные — terraformData.ts.
 */
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import { canCancelTerraform, type TerraformOption } from './terraformData';

export interface TerraformWindowProps {
  options: TerraformOption[];
  isPremium: boolean;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onStart: (type: string) => void;
  onCancel: (type: string) => void;
}

export function TerraformWindow({ options, isPremium, tabs, onTabClick, onClose, onStart, onCancel }: TerraformWindowProps) {
  return (
    <Window title="Терраформинг" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <table>
        <thead>
          <tr>
            <th>Тип грунта</th>
            <th>K/N/S/F/P/Раб</th>
            <th>Время</th>
            <th />
          </tr>
        </thead>
        <tbody>
          {options.map((opt) => {
            const inProgress = opt.allow === '2';
            const canCancel = canCancelTerraform(opt, isPremium);
            return (
              <tr key={opt.typeGround}>
                <td>{opt.typeGround}</td>
                <td>
                  {opt.kad}/{opt.nano}/{opt.stas}/{opt.food}/{opt.prod}/{opt.work}
                </td>
                <td>{opt.time}</td>
                <td>
                  {opt.allow === '1' && (
                    <button type="button" class="ds-popup__btn" onClick={() => onStart(opt.typeGround!)}>
                      Терраформировать
                    </button>
                  )}
                  {inProgress && (
                    <button
                      type="button"
                      class="ds-popup__btn"
                      disabled={!canCancel}
                      title={!canCancel ? 'Отмена терраформинга — премиум-фича' : undefined}
                      onClick={() => onCancel(opt.typeGround!)}
                    >
                      Остановить терраформинг
                    </button>
                  )}
                  {opt.allow !== '1' && !inProgress && <span class="ds-train-unit__unavailable">недоступно</span>}
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </Window>
  );
}
