/**
 * Корневой хост окон — рендерит три независимых слоя из windowStore.ts
 * (один активный модальный слот, независимый лог-оверлей, стек попапов),
 * см. docs/client-window-framework.md §4.5.
 */
import { currentWindow, logWindow, popupStack } from './windowStore';

export function WindowHost() {
  return (
    <>
      {currentWindow.value && <div class="ds-modal-overlay">{currentWindow.value}</div>}
      {logWindow.value}
      {popupStack.value.map((node, i) => (
        <div class="ds-popup-overlay" key={i}>
          {node}
        </div>
      ))}
    </>
  );
}
