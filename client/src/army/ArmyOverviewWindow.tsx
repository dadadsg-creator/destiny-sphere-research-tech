/**
 * Окно «Обзор армий» — первый реальный экран Фазы 3.1, поверх каркаса
 * ui/Window.tsx. Данные — armyData.ts (parseArmyOverview), источник
 * docs/client-combat-ui.md §1.
 *
 * inaction (буквальный комментарий разработчика, xmlparsers.as:1504-1509):
 * 0 — стоит на соте, 1 — летит, можно вернуть, 2 — летит домой, ничего не
 * доступно, 3 — "домашка" (для UI неотличима от 0, см. §1 находка 4).
 *
 * Роспуск всех армий (DISMISS_ALL, только премиум в оригинале — здесь
 * кнопка показана всегда, проверка премиума не реализована в этой
 * версии) — reqvestdata.dismissArmies() без параметров означает "распустить
 * ВСЕ армии", отдельного частичного роспуска в протоколе нет (см.
 * client-combat-ui.md §1, находка про DISMISS_ALL).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { ConfirmPopup } from '../ui/Popup';
import { pushPopup, popPopup } from '../ui/windowStore';
import type { TabDef } from '../ui/tabs';
import type { ArmyOverview } from './armyData';

const INACTION_LABEL: Record<number, string> = {
  0: 'На соте',
  1: 'В полёте (можно вернуть)',
  2: 'Возвращается домой',
  3: 'Дома',
};

export interface ArmyOverviewWindowProps {
  overview: ArmyOverview;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onDismissAll: () => void;
}

export function ArmyOverviewWindow({ overview, tabs, onTabClick, onClose, onDismissAll }: ArmyOverviewWindowProps) {
  const [expandedId, setExpandedId] = useState<string | null>(null);

  function confirmDismissAll() {
    pushPopup(
      <ConfirmPopup
        message="Распустить ВСЕ армии? Частичный роспуск в этой версии протокола не поддерживается."
        onYes={() => {
          popPopup();
          onDismissAll();
        }}
        onNo={() => popPopup()}
      />,
    );
  }

  return (
    <Window title="Обзор армий" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <table class="ds-army-table">
        <thead>
          <tr>
            <th>Армия</th>
            <th>Статус</th>
            <th>Сила</th>
            <th>Скорость</th>
            <th>Юнитов</th>
          </tr>
        </thead>
        <tbody>
          {overview.armies.map((army) => (
            <>
              <tr
                key={army.idArmy}
                class="ds-army-row"
                onClick={() => setExpandedId(expandedId === army.idArmy ? null : army.idArmy)}
              >
                <td>{army.armyName}</td>
                <td>{INACTION_LABEL[army.inaction] ?? `?(${army.inaction})`}</td>
                <td>{army.armyPower}</td>
                <td>{army.speed}</td>
                <td>{army.units.reduce((sum, u) => sum + u.amount, 0)}</td>
              </tr>
              {expandedId === army.idArmy && (
                <tr class="ds-army-row__detail">
                  <td colSpan={5}>
                    <ul class="ds-army-units">
                      {army.units.map((unit) => (
                        <li key={unit.id}>
                          {unit.amount}× {unit.libname}
                        </li>
                      ))}
                    </ul>
                  </td>
                </tr>
              )}
            </>
          ))}
        </tbody>
      </table>
      <button type="button" class="ds-popup__btn" onClick={confirmDismissAll}>
        Распустить все армии
      </button>
    </Window>
  );
}
