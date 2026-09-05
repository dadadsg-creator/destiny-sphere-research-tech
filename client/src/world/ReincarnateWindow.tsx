/**
 * Окно «Реинкарнация» — reincarnate_menu_class.as, см.
 * docs/client-economy-social-ui.md §3.4. Данные — reincarnateData.ts.
 * Механика не задокументирована со стороны сервера (см. докстринг
 * reincarnateData.ts) — реконструкция только по клиенту.
 *
 * Панель продажи получившегося ресурса (отдельный файл `reincarnate.as`,
 * дублирующий формулу коридора цены `trade_menu_class.as`) здесь НЕ
 * реализована — за рамками "базовой" версии этого экрана.
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import type { TabDef } from '../ui/tabs';
import {
  buildGetReincarnateCommand,
  calcReincarnateAmount,
  type GetReincarnateCommand,
  type ReincarnateData,
  type ReincarnateMode,
} from './reincarnateData';

export interface ReincarnateWindowProps {
  data: ReincarnateData;
  /** [K, N, S, F, P, серебро] — запас игрока по тем же 6 ресурсам, что и price каждого юнита. */
  playerResources: number[];
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onSend: (command: GetReincarnateCommand) => void;
}

const PRICE_ORDER = ['KA', 'NA', 'ST', 'FO', 'PR', 'SC'] as const;

export function ReincarnateWindow({ data, playerResources, tabs, onTabClick, onClose, onSend }: ReincarnateWindowProps) {
  const initialMode: ReincarnateMode = data.fork.resEnabled ? 'Res' : 'Sera';
  const [mode, setMode] = useState<ReincarnateMode>(initialMode);
  const [counts, setCounts] = useState<Record<string, number>>({});

  const alreadyQueued = data.fork.reincProdium;
  const remainingQuota = Math.max(0, data.fork.targetProdium - alreadyQueued);

  const selected = Object.entries(counts).filter(([, c]) => c > 0);
  const canSend = selected.length > 0;

  return (
    <Window title="Реинкарнация" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <div class="ds-spying__row">
        <span class="ds-spying__label">Режим:</span>
        <label class="ds-spying__choice">
          <input type="radio" name="reinc-mode" disabled={!data.fork.resEnabled} checked={mode === 'Res'} onChange={() => setMode('Res')} />
          В Продиум
        </label>
        <label class="ds-spying__choice">
          <input type="radio" name="reinc-mode" disabled={!data.fork.silEnabled} checked={mode === 'Sera'} onChange={() => setMode('Sera')} />
          В серебро
        </label>
      </div>
      <p class="ds-train-unit__idle">
        Осталось квоты: {remainingQuota} (цель {data.fork.targetProdium}, уже реинкарнировано {data.fork.reincProdium})
      </p>

      <table>
        <thead>
          <tr>
            <th>Юнит</th>
            <th>Вес продиума</th>
            <th>Кол-во</th>
          </tr>
        </thead>
        <tbody>
          {data.units.map((unit) => {
            const prices = PRICE_ORDER.map((key) => unit.price[key] ?? 0);
            const maxAmount = calcReincarnateAmount(playerResources, prices, remainingQuota, unit.prodiumWeight || 1);
            return (
              <tr key={unit.id}>
                <td>{unit.libname}</td>
                <td>{unit.prodiumWeight}</td>
                <td>
                  <Stepper
                    value={counts[unit.id!] ?? 0}
                    min={0}
                    max={maxAmount}
                    onChange={(v) => setCounts({ ...counts, [unit.id!]: v })}
                  />
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>

      {data.queue.length > 0 && (
        <>
          <h4>В очереди</h4>
          <table>
            <thead>
              <tr>
                <th>Юнит</th>
                <th>Кол-во</th>
                <th>Начало</th>
                <th>Завершение</th>
              </tr>
            </thead>
            <tbody>
              {data.queue.map((q, i) => (
                <tr key={i}>
                  <td>{q.libname}</td>
                  <td>{q.amount}</td>
                  <td>{q.time[0]}</td>
                  <td>{q.time[1]}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </>
      )}

      <button
        type="button"
        class="ds-popup__btn"
        disabled={!canSend}
        onClick={() =>
          onSend(buildGetReincarnateCommand(mode, selected.map(([id, count]) => ({ id, count }))))
        }
      >
        Реинкарнировать
      </button>
    </Window>
  );
}
