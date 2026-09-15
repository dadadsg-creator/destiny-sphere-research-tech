/**
 * Минимальная замена статус-бара оригинала (`game.as`, иконки почты/
 * кабинета и т.п.) — только на время живых тестов "Сшивания" (см.
 * liveToolbarStore.ts). Не пиксель-в-пиксель со скином оригинала (это
 * отдельный больший объём, см. CLIENT_PORTING_PLAN.md §5, «Точки входа»)
 * — просто два реальных кликабельных входа в уже подключённые домены.
 */
import { liveToolbarActions } from './liveToolbarStore';

export function LiveToolbar() {
  const actions = liveToolbarActions.value;
  if (!actions) return null;
  return (
    <div class="ds-live-toolbar">
      <button type="button" class="ds-live-toolbar__btn" onClick={actions.openMail}>
        Почта
      </button>
      <button type="button" class="ds-live-toolbar__btn" onClick={actions.openCabinet}>
        Личный кабинет
      </button>
      <button type="button" class="ds-live-toolbar__btn" onClick={actions.openComplex}>
        Комплекс
      </button>
    </div>
  );
}
