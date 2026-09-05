/**
 * Семейство confirm/alert-попапов — порт `createConfirmWindow`/
 * `createAlertWindow`/`createPopupWindow` (window.as:4018-4471), см.
 * docs/client-window-framework.md §6. В оригинале НЕ используют
 * `game.lock(8500)` — рисуют собственный блокирующий прямоугольник,
 * поэтому здесь это отдельный стек (windowStore.popupStack), а не часть
 * единого модального слота `currentWindow`.
 *
 * Контракт "фабрика создаёт форму, вызывающий код навешивает callback"
 * (оригинал оставляет func_y/func_n неопределёнными) здесь — просто
 * пропсы onYes/onNo.
 *
 * Не портированы в этой версии (см. §6): createBUYWindow/
 * createBUYBONUSWindow (степпер количества + живой пересчёт цены) —
 * отдельная задача, когда понадобится конкретный экран покупки.
 */
export interface ConfirmPopupProps {
  message: string;
  onYes: () => void;
  /** Если не задан — попап ведёт себя как alert (только кнопка OK, надпись "Да" меняется на "OK"). */
  onNo?: () => void;
}

export function ConfirmPopup({ message, onYes, onNo }: ConfirmPopupProps) {
  return (
    <div class="ds-popup ds-popup--confirm">
      <p class="ds-popup__message">{message}</p>
      <div class="ds-popup__buttons">
        <button type="button" class="ds-popup__btn ds-popup__btn--yes" onClick={onYes}>
          {onNo ? 'Да' : 'OK'}
        </button>
        {onNo && (
          <button type="button" class="ds-popup__btn ds-popup__btn--no" onClick={onNo}>
            Нет
          </button>
        )}
      </div>
    </div>
  );
}

export interface InfoPopupProps {
  message: string;
  onClose: () => void;
}

/** createPopupWindow — простое информационное сообщение, одна кнопка OK. */
export function InfoPopup({ message, onClose }: InfoPopupProps) {
  return (
    <div class="ds-popup ds-popup--info">
      <p class="ds-popup__message">{message}</p>
      <div class="ds-popup__buttons">
        <button type="button" class="ds-popup__btn" onClick={onClose}>
          OK
        </button>
      </div>
    </div>
  );
}
