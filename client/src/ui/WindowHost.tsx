/**
 * Корневой хост окон — рендерит независимые слои из windowStore.ts
 * (один активный модальный слот, независимый лог-оверлей, докнутая
 * сверху панель, стек попапов), см. docs/client-window-framework.md §4.5.
 */
import { currentWindow, logWindow, topPanel, popupStack } from './windowStore';

export function WindowHost() {
  return (
    <>
      {currentWindow.value && <div class="ds-modal-overlay">{currentWindow.value}</div>}
      {logWindow.value}
      {topPanel.value}
      {popupStack.value.map((node, i) => (
        <div class="ds-popup-overlay" key={i}>
          {node}
        </div>
      ))}
    </>
  );
}
