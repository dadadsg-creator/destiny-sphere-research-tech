/**
 * Окно «Институт археологии» — archeology_class.as, см.
 * docs/client-economy-social-ui.md §1.3. Данные — archeologyData.ts.
 * Групповая отправка (`countGroup`) выше 1 — только премиум, у обычных
 * аккаунтов жёстко зафиксирована на 1 (см. §1.3).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import type { TabDef } from '../ui/tabs';
import {
  computeArcheologistAllow,
  computeSendLocationOptions,
  computeSendTimeOptions,
  type ArcheologyData,
  type PlayerKadFoodPool,
} from './archeologyData';

export interface ArcheologyWindowProps {
  data: ArcheologyData;
  playerResources: PlayerKadFoodPool;
  isPremium: boolean;
  gateOpen: boolean;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onTrain: (count: number) => void;
  onSendExpedition: (dest: string, count: number, turns: number, sizeMin: number, countGroup: number) => void;
}

export function ArcheologyWindow({
  data,
  playerResources,
  isPremium,
  gateOpen,
  tabs,
  onTabClick,
  onClose,
  onTrain,
  onSendExpedition,
}: ArcheologyWindowProps) {
  const [trainCount, setTrainCount] = useState(0);
  const [sendCount, setSendCount] = useState(0);
  const [sendTurns, setSendTurns] = useState(1);
  const [sendDest, setSendDest] = useState<'HOME' | 'CENTRAL' | 'NEUTRAL'>('HOME');
  const [countGroup, setCountGroup] = useState(1);

  const allow = data.train ? computeArcheologistAllow(data.train, playerResources) : 0;
  const timeOptions = data.train ? computeSendTimeOptions(Number(data.train.turn) || 1, data.extendedTime) : [];
  const locationOptions = computeSendLocationOptions(gateOpen);

  return (
    <Window title="Институт археологии" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <h4>Активные экспедиции</h4>
      <table>
        <thead>
          <tr>
            <th>Куда</th>
            <th>Кол-во</th>
            <th>Мин. размер</th>
            <th>Завершение</th>
          </tr>
        </thead>
        <tbody>
          {data.expeditions.map((exp, i) => (
            <tr key={i}>
              <td>{exp.dest}</td>
              <td>{exp.count}</td>
              <td>{exp.minSize}</td>
              <td>{exp.endDate}</td>
            </tr>
          ))}
        </tbody>
      </table>

      {data.train && (
        <>
          <h4>Тренировка археологов ({data.train.inProgress} в найме)</h4>
          <div class="ds-spying__row">
            <Stepper value={trainCount} min={0} max={allow} onChange={setTrainCount} />
            <button type="button" class="ds-popup__btn" disabled={trainCount <= 0} onClick={() => onTrain(trainCount)}>
              Нанять
            </button>
          </div>
        </>
      )}

      <h4>Отправить экспедицию</h4>
      <div class="ds-spying__row">
        <span class="ds-spying__label">Куда:</span>
        <select value={sendDest} onChange={(e) => setSendDest((e.target as HTMLSelectElement).value as typeof sendDest)}>
          {locationOptions.map((loc) => (
            <option key={loc.id} value={loc.id}>
              {loc.id}
            </option>
          ))}
        </select>
      </div>
      <div class="ds-spying__row">
        <span class="ds-spying__label">Длительность (ходов):</span>
        <select value={sendTurns} onChange={(e) => setSendTurns(Number((e.target as HTMLSelectElement).value))}>
          {timeOptions.map((t, i) => (
            <option key={i} value={t.turns}>
              {t.purchased ? '* ' : ''}
              {t.turns}
            </option>
          ))}
        </select>
      </div>
      <div class="ds-spying__row">
        <span class="ds-spying__label">Количество археологов:</span>
        <Stepper value={sendCount} min={0} onChange={setSendCount} />
      </div>
      {isPremium && (
        <div class="ds-spying__row">
          <span class="ds-spying__label">Групп одновременно (премиум, до 300):</span>
          <Stepper value={countGroup} min={1} max={300} onChange={setCountGroup} />
        </div>
      )}
      <button
        type="button"
        class="ds-popup__btn"
        disabled={sendCount <= 0}
        onClick={() => onSendExpedition(sendDest, sendCount, sendTurns, 0, isPremium ? countGroup : 1)}
      >
        Отправить
      </button>
    </Window>
  );
}
