/**
 * Глобальное состояние окон — порт модели из window.as/menuLibCode.as
 * (см. docs/client-window-framework.md §4.5):
 *  - `currentWindow` — ровно один активный модальный слот (все обычные
 *    окна-меню делят один и тот же лок `game.lock(8500)` в оригинале);
 *  - `logWindow` — независимый always-on-top слот (create_log_window,
 *    depth 120/124, свой собственный оверлей, не участвует в модальном слоте);
 *  - `popupStack` — confirm/alert/buy-попапы (§6): НЕ используют
 *    `game.lock`, рисуют свой блокирующий прямоугольник и накладываются
 *    поверх чего угодно — здесь стек, а не одиночный слот, т.к. вызывающее
 *    окно остаётся видимым под попапом.
 *
 * Идемпотентность открытия (не пересоздавать уже открытое окно) — не
 * ответственность этого модуля, как и в оригинале (см. §3.4): это решает
 * конкретный экран при вызове openWindow().
 */
import { signal } from '@preact/signals';
import type { ComponentChildren } from 'preact';

export const currentWindow = signal<ComponentChildren | null>(null);
export const logWindow = signal<ComponentChildren | null>(null);
export const popupStack = signal<ComponentChildren[]>([]);

export function openWindow(node: ComponentChildren): void {
  currentWindow.value = node;
}

/** Соответствует close_menu() базового класса window (сняли лок, убрали окно). */
export function closeWindow(): void {
  currentWindow.value = null;
}

export function openLogWindow(node: ComponentChildren): void {
  logWindow.value = node;
}

export function closeLogWindow(): void {
  logWindow.value = null;
}

export function pushPopup(node: ComponentChildren): void {
  popupStack.value = [...popupStack.value, node];
}

/** Закрывает верхний (последний открытый) попап — единственный сценарий в оригинале (стек, не произвольный доступ). */
export function popPopup(): void {
  popupStack.value = popupStack.value.slice(0, -1);
}
