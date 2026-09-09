/**
 * Окно «Создание армии» — armycreationclass.as, см.
 * docs/client-combat-ui.md §2. Данные — armyCreationData.ts
 * (parseArmyCreation, mark `armycreation`).
 *
 * Клиентская валидация из оригинала (armycreationclass.as:242): кнопка
 * реально шлёт запрос только если имя армии непусто И есть хотя бы один
 * юнит с количеством >0 — никакой проверки "хватает ли ресурсов" на
 * клиенте нет, это целиком серверная ответственность.
 *
 * Не реализовано в этой версии (см. §2 находка про премиум): MAKE_PROSLOY
 * (уменьшить на 1 во всех выбранных типах сразу) и общее поле количества
 * AMS — обе фичи премиум-аккаунта.
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import type { TabDef } from '../ui/tabs';
import type { ArmyCreationData } from './armyCreationData';
import type { LocalUnit } from '../data/localData';
import { unitIconUrl } from '../data/resourceIcons';

export interface ArmyCreationWindowProps {
  data: ArmyCreationData;
  /** Локализованные названия юнитов (mark `loaddata`, ключ — то же machine-имя, что `unit.libname`) — если не пришло/нет записи, показываем сырое имя. */
  localUnits?: Map<string, LocalUnit>;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onCreate: (name: string, units: { id: string; count: number }[]) => void;
}

export function ArmyCreationWindow({ data, localUnits, tabs, onTabClick, onClose, onCreate }: ArmyCreationWindowProps) {
  const [name, setName] = useState('');
  const [counts, setCounts] = useState<Record<string, number>>({});

  const selectedUnits = Object.entries(counts).filter(([, count]) => count > 0);
  const canCreate = name.length > 0 && selectedUnits.length > 0;

  function handleCreate() {
    onCreate(
      name,
      selectedUnits.map(([id, count]) => ({ id, count })),
    );
  }

  return (
    <Window title={`Создание армии (${data.cur}/${data.max})`} tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <label class="ds-army-creation__name">
        Название армии:{' '}
        <input
          type="text"
          value={name}
          onInput={(e) => setName((e.target as HTMLInputElement).value)}
        />
      </label>
      <table>
        <thead>
          <tr>
            <th>Юнит</th>
            <th>Доступно</th>
            <th>Количество</th>
          </tr>
        </thead>
        <tbody>
          {data.units.map((unit) => (
            <tr key={unit.id}>
              <td>
                <div class="ds-artefact-row">
                  <img class="ds-unit-icon" src={unitIconUrl(unit.libname)} alt="" />
                  <span>{localUnits?.get(unit.libname)?.ln ?? unit.libname}</span>
                </div>
              </td>
              <td>{unit.amount}</td>
              <td>
                <Stepper
                  value={counts[unit.id] ?? 0}
                  min={0}
                  max={unit.amount}
                  onChange={(v) => setCounts({ ...counts, [unit.id]: v })}
                />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      <button type="button" class="ds-popup__btn" disabled={!canCreate} onClick={handleCreate}>
        Создать армию
      </button>
    </Window>
  );
}
