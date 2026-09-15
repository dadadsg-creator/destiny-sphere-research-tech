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
 *
 * **Дополнение (2026-09-11)** — панель действий по конкретной армии
 * (`army_overview_class.as:670-892`, `mov.radio_action`/`go_action`)
 * раньше отсутствовала целиком — не мелкая находка категории «В», а
 * реально не портированная функциональность (атака/защита/переброска/
 * укрытие армии), см. `ArmyActionPanel` ниже. Разобрано ТОЛЬКО ядро:
 *  - выбор действия (сервер сам присылает список разрешённых прямо
 *    сейчас в `army.allowAction` — не захардкожен);
 *  - для ATTACK/DEFEND/MOVE_TO_COMB — координаты цели (кольцо/комплекс/
 *    сота), скорость, ресурс для грабежа;
 *  - для HIDE_ARMY — длительность укрытия (в ходах, до `army.mst`) +
 *    живой предпросмотр времени (та самая находка §2.2, из-за которой
 *    всё это и раскопалось, `setTurnTime`/`formatTurnTime`).
 * НЕ реализовано (сознательно, отдельная задача по масштабу):
 *  - `calc_action`/`calctravel` — калькулятор ETA перелёта без отправки;
 *  - передача юнитов между армиями (`allowTransfer`/`trasfer_unit`);
 *  - сортировка юнитов в детальном списке;
 *  - `useArtefacts`/переименование армии — подтверждено полнотекстовым
 *    поиском, что этих двух фич в этой конкретной ревизии AS2-клиента
 *    НЕТ вообще (ни строки `rename`, ни `artefact` в файле), значит и
 *    портировать нечего;
 *  - тонкости `game.check_confirm_win()` (клиентский тумблер "спрашивать
 *    подтверждение") — здесь подтверждение всегда показывается для
 *    HIDE_ARMY/MOVE_TO_COMB/CANCEL_*, никогда — для ATTACK/DEFEND (это
 *    единственная часть правила, не зависящая от тумблера в оригинале).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import { ConfirmPopup } from '../ui/Popup';
import { pushPopup, popPopup } from '../ui/windowStore';
import type { TabDef } from '../ui/tabs';
import { formatTurnTime } from '../data/turnTime';
import { resourceDisplayName, unitDisplayName, unitIconUrl } from '../data/resourceIcons';
import {
  ARMY_RESOURCE_OPTIONS,
  buildArmyActionCommand,
  buildArmyActionHideCommand,
  isCancelAction,
  type Army,
  type ArmyActionCommand,
  type ArmyActionHideCommand,
  type ArmyOverview,
} from './armyData';

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
  onArmyAction: (command: ArmyActionCommand | ArmyActionHideCommand) => void;
  onTransferUnit: (idUnit: string, count: number, idArmyFrom: string, idArmyTo: string) => void;
}

/**
 * Передача юнитов между армиями — `allowTransfer`/`unit.id` парсятся
 * давно (см. armyData.ts), просто не были подключены к команде (Фаза
 * 3.1, найдено аудитом панелей 2026-09-16, CLIENT_PORTING_PLAN.md).
 */
function TransferUnitPanel({ army, onSend }: { army: Army; onSend: (idUnit: string, count: number, idArmyTo: string) => void }) {
  const [unitId, setUnitId] = useState(army.units[0]?.id ?? '');
  const [targetId, setTargetId] = useState(army.allowTransfer[0]?.id ?? '');
  const [count, setCount] = useState(1);

  if (army.units.length === 0 || army.allowTransfer.length === 0) return null;

  const unit = army.units.find((u) => u.id === unitId);
  const maxCount = unit?.amount ?? 1;

  return (
    <div class="ds-spying__row">
      <span class="ds-spying__label">Передать юнита:</span>
      <select value={unitId ?? ''} onChange={(e) => setUnitId((e.target as HTMLSelectElement).value)}>
        {army.units.map((u) => (
          <option key={u.id} value={u.id ?? ''}>
            {u.libname && unitDisplayName(u.libname)} ({u.amount})
          </option>
        ))}
      </select>
      <Stepper value={count} min={1} max={maxCount} onChange={setCount} />
      <span class="ds-spying__label">в армию:</span>
      <select value={targetId ?? ''} onChange={(e) => setTargetId((e.target as HTMLSelectElement).value)}>
        {army.allowTransfer.map((t) => (
          <option key={t.id} value={t.id ?? ''}>
            {t.armyName}
          </option>
        ))}
      </select>
      <button
        type="button"
        class="ds-popup__btn"
        disabled={!unitId || !targetId}
        onClick={() => unitId && targetId && onSend(unitId, count, targetId)}
      >
        Передать
      </button>
    </div>
  );
}

function ArmyActionPanel({
  army,
  turn,
  minSpeed,
  maxSpeed,
  onSend,
}: {
  army: Army;
  turn: string | null;
  minSpeed: number;
  maxSpeed: number;
  onSend: (command: ArmyActionCommand | ArmyActionHideCommand) => void;
}) {
  const [actionId, setActionId] = useState(army.allowAction[0]?.id ?? '');
  const [ring, setRing] = useState(1);
  const [complex, setComplex] = useState(0);
  const [sota, setSota] = useState(1);
  const [speed, setSpeed] = useState(Math.min(Math.max(army.speed, minSpeed), maxSpeed) || minSpeed);
  const [resource, setResource] = useState<string>(ARMY_RESOURCE_OPTIONS[0]);
  const [hideTurns, setHideTurns] = useState(1);

  if (army.allowAction.length === 0) return <p class="ds-train-unit__idle">Действия недоступны.</p>;

  const isHide = actionId === 'HIDE_ARMY';
  const isCancel = isCancelAction(actionId);
  const needsCoord = !isHide && !isCancel;
  const canSend = isHide || isCancel || complex > 0;

  function doSend() {
    if (isHide) {
      onSend(buildArmyActionHideCommand(army.idArmy ?? '', hideTurns));
    } else {
      onSend(buildArmyActionCommand(army.idArmy ?? '', actionId, ring, complex, sota, speed, resource));
    }
  }

  /** army_overview_class.as:824,851 — ATTACK/DEFEND уходят без подтверждения, всё остальное — с ним. */
  function handleSendClick() {
    if (!canSend) return;
    if (actionId === 'ATTACK' || actionId === 'DEFEND') {
      doSend();
      return;
    }
    pushPopup(
      <ConfirmPopup
        message="Подтвердить действие?"
        onYes={() => {
          popPopup();
          doSend();
        }}
        onNo={() => popPopup()}
      />,
    );
  }

  return (
    <div class="ds-spying__row" style={{ flexWrap: 'wrap' }}>
      <span class="ds-spying__label">Действие:</span>
      {army.allowAction.map((a) => (
        <label key={a.id} class="ds-spying__choice">
          <input type="radio" name={`action-${army.idArmy}`} checked={actionId === a.id} onChange={() => setActionId(a.id ?? '')} />
          {a.actionName}
        </label>
      ))}

      {needsCoord && (
        <>
          <span class="ds-spying__label">Кольцо:</span>
          <input type="number" class="ds-stepper__input" value={ring} onInput={(e) => setRing(Number((e.target as HTMLInputElement).value))} />
          <span class="ds-spying__label">Комплекс:</span>
          <input
            type="number"
            class="ds-stepper__input"
            value={complex}
            onInput={(e) => setComplex(Number((e.target as HTMLInputElement).value))}
          />
          <span class="ds-spying__label">Сота:</span>
          <Stepper value={sota} min={1} max={6} onChange={setSota} />
          <span class="ds-spying__label">Скорость:</span>
          <Stepper value={speed} min={minSpeed} max={maxSpeed} onChange={setSpeed} />
          <span class="ds-spying__label">Приоритетный ресурс:</span>
          <select value={resource} onChange={(e) => setResource((e.target as HTMLSelectElement).value)}>
            {ARMY_RESOURCE_OPTIONS.map((r) => (
              <option key={r} value={r}>
                {resourceDisplayName(r)}
              </option>
            ))}
          </select>
        </>
      )}

      {isHide && (
        <>
          <span class="ds-spying__label">Укрыть на (ходов):</span>
          <Stepper value={hideTurns} min={1} max={army.mst || 1} onChange={setHideTurns} />
          <span>{formatTurnTime(hideTurns * (Number(turn) || 0))}</span>
        </>
      )}

      <button type="button" class="ds-popup__btn" disabled={!canSend} onClick={handleSendClick}>
        Отправить
      </button>
    </div>
  );
}

export function ArmyOverviewWindow({
  overview,
  tabs,
  onTabClick,
  onClose,
  onDismissAll,
  onArmyAction,
  onTransferUnit,
}: ArmyOverviewWindowProps) {
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
                        <li key={unit.id} class="ds-army-units__row">
                          {unit.libname && <img class="ds-unit-icon" src={unitIconUrl(unit.libname)} alt="" />}
                          {unit.amount}× {unit.libname && unitDisplayName(unit.libname)}
                        </li>
                      ))}
                    </ul>
                    <ArmyActionPanel
                      army={army}
                      turn={overview.turn}
                      minSpeed={overview.minSpeed}
                      maxSpeed={overview.maxSpeed}
                      onSend={onArmyAction}
                    />
                    <TransferUnitPanel
                      army={army}
                      onSend={(idUnit, count, idArmyTo) => onTransferUnit(idUnit, count, army.idArmy ?? '', idArmyTo)}
                    />
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
