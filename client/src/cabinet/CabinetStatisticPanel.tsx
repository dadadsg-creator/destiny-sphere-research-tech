/**
 * "История операций" кабинета — кнопка "Вся статистика"
 * (`RightBlock.tsx: onShowAllStatistic` → `getAllStatistic()`,
 * `RightBlock.as:66-70`). Ответ (`action="operationStatistic"`) был
 * разобран (`cabinetMainData.ts: parseOperationStatistic`) ещё в
 * Фазе 3, но окна для него не строили — только `console.log`
 * (владелец, 2026-09-18, при обходе списка пробелов "по порядку").
 *
 * В оригинале (`menuLibCode.as:88-93`, `showCabinetStatistic()`)
 * ответ подменяет содержимое ГЛАВНОГО окна кабинета
 * (`cabinet_main_mov.showStatstic()`), а не открывает отдельное окно —
 * здесь тот же принцип: доп. секция ВНУТРИ `CabinetWindow`, тот же
 * приём, что и уже готовый `SubmenuForm`, не отдельная модалка.
 *
 * Фильтры (`<filter><types><type name="" id=""/></types></filter>`,
 * `parserCabinetData.as:44-82`) — один или несколько списков типов
 * операций; клик по типу пересылает `getAllStatistic(type)`
 * (`requestDataCabinet.as:51-59`) — сервер возвращает новый,
 * отфильтрованный список тем же марком.
 */
import type { OperationStatisticData } from './cabinetMainData';

export interface CabinetStatisticPanelProps {
  data: OperationStatisticData;
  onFilterSelect: (typeId: string | undefined) => void;
  onClose: () => void;
}

export function CabinetStatisticPanel({ data, onFilterSelect, onClose }: CabinetStatisticPanelProps) {
  const allTypes = data.filters.flatMap((f) => f.arrItems);

  return (
    <div class="ds-window">
      <div class="ds-window__titlebar">
        <span class="ds-window__title">История операций</span>
        <button type="button" class="ds-window__close" onClick={onClose} aria-label="Закрыть">
          ×
        </button>
      </div>
      <div class="ds-window__content">
        {allTypes.length > 0 && (
          <div class="ds-btn-row">
            <button type="button" class="ds-popup__btn" onClick={() => onFilterSelect(undefined)}>
              Все
            </button>
            {allTypes.map((t) => (
              <button key={t.id} type="button" class="ds-popup__btn" onClick={() => onFilterSelect(t.id)}>
                {t.ln}
              </button>
            ))}
          </div>
        )}
        <table>
          <thead>
            <tr>
              <th>Дата</th>
              <th>Операция</th>
              <th>Сумма</th>
            </tr>
          </thead>
          <tbody>
            {data.operations.length === 0 ? (
              <tr>
                <td colSpan={3} class="ds-resource-list__empty">
                  Операций нет
                </td>
              </tr>
            ) : (
              data.operations.map((op, i) => (
                <tr key={i}>
                  <td>{op.date}</td>
                  <td>{op.text}</td>
                  <td>{op.concurency}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
