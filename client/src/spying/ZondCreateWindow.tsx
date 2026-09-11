/**
 * Окно «Тренировка (постройка) зондов» — zondcreateclass.as, см.
 * docs/client-combat-ui.md §11. Данные — zondCreateData.ts
 * (parseZondCreate) + calculateProgressivePrice() (категория «В» —
 * каждый следующий зонд дороже предыдущего в `delta` раз).
 *
 * Показывает итоговую цену за выбранное количество (пересчитывается на
 * лету через прогрессивную формулу, не просто `basePrice*count`).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import type { TabDef } from '../ui/tabs';
import { calculateProgressivePrice, type ZondCreateData, type ZondPriceMap } from './zondCreateData';

export interface ZondCreateWindowProps {
  data: ZondCreateData;
  zondNames: Map<string, string>;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onTrain: (id: string, count: number) => void;
}

/** Округление теперь делает сама calculateProgressivePrice() (keyRound), значения сюда приходят уже готовые. */
function formatPrice(price: ZondPriceMap): string {
  const parts = (Object.entries(price) as [string, number][]).filter(([, v]) => v > 0).map(([k, v]) => `${k}:${v}`);
  return parts.length > 0 ? parts.join(' ') : '—';
}

export function ZondCreateWindow({ data, zondNames, tabs, onTabClick, onClose, onTrain }: ZondCreateWindowProps) {
  const [counts, setCounts] = useState<Record<string, number>>({});

  return (
    <Window title="Тренировка зондов" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <table>
        <thead>
          <tr>
            <th>Зонд</th>
            <th>Готово/в строю</th>
            <th>Заказано</th>
            <th>Количество</th>
            <th>Итоговая цена</th>
            <th />
          </tr>
        </thead>
        <tbody>
          {data.allowZond.map((zond) => {
            const count = counts[zond.id] ?? 0;
            const totalPrice: ZondPriceMap = {};
            for (const key of Object.keys(zond.price) as (keyof ZondPriceMap)[]) {
              const base = zond.price[key] ?? 0;
              const delta = zond.delta[key] ?? 1;
              /**
               * `currentReady` — `Number(obj.ready)+Number(obj.inact)`
               * (`zondcreateclass.as:59` и далее), НЕ только `already`:
               * цена следующего зонда зависит от уже забронированной
               * позиции в прогрессии, включая те, что ещё строятся.
               * `keyRound=false` только для WR (военный ранг) — :104.
               */
              totalPrice[key] = calculateProgressivePrice(base, delta, zond.already + zond.inAction, count, key !== 'WR');
            }
            return (
              <tr key={zond.id}>
                <td>{zondNames.get(zond.id) ?? zond.id}</td>
                <td>
                  {zond.already}/{zond.inAction}
                </td>
                <td>{zond.count}</td>
                <td>
                  <Stepper value={count} min={0} onChange={(v) => setCounts({ ...counts, [zond.id]: v })} />
                </td>
                <td>{formatPrice(totalPrice)}</td>
                <td>
                  <button
                    type="button"
                    class="ds-popup__btn"
                    disabled={count <= 0}
                    onClick={() => onTrain(zond.id, count)}
                  >
                    Тренировать
                  </button>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </Window>
  );
}
