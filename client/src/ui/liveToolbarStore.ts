/**
 * Точки входа, не привязанные к клику по карте (Фаза "Сшивание", §5
 * CLIENT_PORTING_PLAN.md — почта и личный кабинет, в отличие от
 * альянса/биржи/науки и т.п., явно включены в этот первый проход,
 * "иконка (или прямой вызов)"). Настоящего статус-бара с графикой
 * оригинала в порте ещё нет (это отдельный, более крупный объём — см.
 * план), поэтому здесь только колбэки команд — `LiveToolbar.tsx` рисует
 * минимальные кнопки поверх карты. Заполняется один раз в `liveApp.ts`
 * после входа в мир, `null` вне живой сессии (`?screen=`-тесты, обычная
 * загрузка без `?live=1`).
 */
import { signal } from '@preact/signals';

export interface LiveToolbarActions {
  openMail: () => void;
  openCabinet: () => void;
}

export const liveToolbarActions = signal<LiveToolbarActions | null>(null);
