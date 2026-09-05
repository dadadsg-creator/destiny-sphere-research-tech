/**
 * Окно «Отправка зондов на задание» — spying_class.as, см.
 * docs/client-combat-ui.md §10. Данные — spyingData.ts (parseSpying).
 *
 * Условные поля по выбранному действию (см. §10, action.addrAllow —
 * реальный XML-атрибут `coord`, action.timeAllow — реальный `time`):
 *  - `addrAllow` — показать поля координат цели (кольцо/комплекс/сота);
 *  - `timeAllow` — показать длительность (в ходах) вместо скорости.
 *
 * Чекбокс ONE_ROUND_BATTLE — реален в оригинале (spying_class.as:198),
 * точный эффект НЕ установлен (открытый вопрос №6 плана) — здесь просто
 * прокидывается в onSend как есть, поведение не реализуется.
 *
 * НЕ реализовано: раздел "активные группы" (filter_obj.listGroup — уже
 * отправленные зонды) — данные (`data.activeGroups`) доступны, отдельный
 * список под них в этой версии не отрисован.
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import type { TabDef } from '../ui/tabs';
import type { SpyingData } from './spyingData';

export interface SendZondParams {
  zondId: string;
  actionId: string;
  count: number;
  ring: string;
  complex: number;
  sota: number;
  speedOrTime: number;
  oneRoundBattle: boolean;
}

export interface SpyingWindowProps {
  data: SpyingData;
  /** id зонда ("Z1"/"Z2") -> локализованное имя — из data/localData.ts LocalUnit.ln по arrLibToId, здесь передаётся готовым. */
  zondNames: Map<string, string>;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onSend: (params: SendZondParams) => void;
}

export function SpyingWindow({ data, zondNames, tabs, onTabClick, onClose, onSend }: SpyingWindowProps) {
  const [zondId, setZondId] = useState(data.allowZonds[0]?.id ?? '');
  const [actionId, setActionId] = useState(data.actions[0]?.id ?? '');
  const [count, setCount] = useState(0);
  const [ring, setRing] = useState(data.ringAllow.find((r) => r.allowed)?.ringKey ?? '');
  const [complex, setComplex] = useState(1);
  const [sota, setSota] = useState(1);
  const [speedOrTime, setSpeedOrTime] = useState(Number(data.speed.def) || 0);
  const [oneRoundBattle, setOneRoundBattle] = useState(false);

  const selectedZond = data.allowZonds.find((z) => z.id === zondId);
  const selectedAction = data.actions.find((a) => a.id === actionId);
  const needsCoord = selectedAction?.addrAllow === '1';
  const needsTime = selectedAction?.timeAllow === '1';

  const canSend = !!selectedZond && count > 0 && count <= selectedZond.allowCount && !!selectedAction;

  return (
    <Window title="Отправка зондов" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <div class="ds-spying__row">
        <span class="ds-spying__label">Тип зонда:</span>
        {data.allowZonds.map((z) => (
          <label key={z.id} class="ds-spying__choice">
            <input type="radio" name="zond" checked={z.id === zondId} onChange={() => setZondId(z.id)} />
            {zondNames.get(z.id) ?? z.id} ({z.allowCount} доступно)
          </label>
        ))}
      </div>

      <div class="ds-spying__row">
        <span class="ds-spying__label">Действие:</span>
        {data.actions.map((a) => (
          <label key={a.id} class="ds-spying__choice">
            <input type="radio" name="action" checked={a.id === actionId} onChange={() => setActionId(a.id)} />
            {a.name}
          </label>
        ))}
      </div>

      <div class="ds-spying__row">
        <span class="ds-spying__label">Количество:</span>
        <Stepper value={count} min={0} max={selectedZond?.allowCount ?? 0} onChange={setCount} />
      </div>

      <div class="ds-spying__row">
        <span class="ds-spying__label">Кольцо:</span>
        <select value={ring} onChange={(e) => setRing((e.target as HTMLSelectElement).value)}>
          {data.ringAllow
            .filter((r) => r.allowed)
            .map((r) => (
              <option key={r.ringKey} value={r.ringKey}>
                {r.ringKey}
              </option>
            ))}
        </select>
      </div>

      {needsCoord && (
        <div class="ds-spying__row">
          <span class="ds-spying__label">Цель (комплекс.сота):</span>
          <input
            type="number"
            class="ds-stepper__input"
            value={complex}
            onInput={(e) => setComplex(Number((e.target as HTMLInputElement).value))}
          />
          <input
            type="number"
            class="ds-stepper__input"
            value={sota}
            onInput={(e) => setSota(Number((e.target as HTMLInputElement).value))}
          />
        </div>
      )}

      <div class="ds-spying__row">
        <span class="ds-spying__label">{needsTime ? 'Длительность (ходов):' : 'Скорость:'}</span>
        <Stepper
          value={speedOrTime}
          min={Number(needsTime ? undefined : data.speed.min) || 0}
          max={Number(needsTime ? undefined : data.speed.max) || Number.MAX_SAFE_INTEGER}
          onChange={setSpeedOrTime}
        />
      </div>

      <label class="ds-spying__choice">
        <input type="checkbox" checked={oneRoundBattle} onChange={() => setOneRoundBattle(!oneRoundBattle)} />
        Бой в один раунд (эффект не установлен, см. открытые вопросы плана)
      </label>

      <button
        type="button"
        class="ds-popup__btn"
        disabled={!canSend}
        onClick={() =>
          onSend({
            zondId,
            actionId,
            count,
            ring,
            complex,
            sota,
            speedOrTime,
            oneRoundBattle,
          })
        }
      >
        Отправить
      </button>
    </Window>
  );
}
