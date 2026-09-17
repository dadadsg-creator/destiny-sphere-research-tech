/**
 * Живое хранилище открытого списка почты — список (`messageoverview`) и
 * тело выбранного письма (`message`) приходят ДВУМЯ разными марками
 * (см. `protocol/dispatch.tsx`, `renderMailList`/`renderMailMessage`), а
 * `MailWindow.tsx` ожидает оба сразу одним пропом `openBody`. Храним
 * последний открытый список (+ вкладки того же ответа) здесь, чтобы при
 * получении отдельной марки `message` можно было заново собрать полный
 * набор пропов и вызвать `openWindow()` — Preact сохраняет внутренний
 * `useState` окна (выборка чекбоксов и т.п.) при повторном рендере того
 * же компонента с новыми пропами, так что открытие письма не сбрасывает
 * состояние списка.
 */
import { signal } from '@preact/signals';
import type { TabDef } from '../ui/tabs';
import type { MailListData } from './mailData';

export const mailListState = signal<{ data: MailListData; tabs: TabDef[] } | null>(null);
export const mailOpenBody = signal<string | null>(null);

/**
 * "Есть новая почта" — марка верхнего уровня `newmessage`, присутствует
 * в ответе, когда есть непрочитанные письма (см. `protocol/dispatch.tsx`).
 * НЕ число: оригинал (`game.as:893-918`, `init_mail_button`) проверяет
 * только `new_mail != undefined` — включает/выключает анимацию значка
 * почты (мигание), конкретного количества непрочитанных писем сервер
 * никогда не присылает. docs/main_screen.md §10 описывает "счётчик до
 * 99/99+" — эта часть спецификации не подтверждается протоколом, честно
 * реализован только boolean-индикатор (как в оригинале), без придуманной
 * цифры.
 */
export const hasNewMail = signal(false);

export function setMailList(data: MailListData, tabs: TabDef[]): void {
  mailListState.value = { data, tabs };
  mailOpenBody.value = null;
}

export function setMailOpenBody(body: string): void {
  mailOpenBody.value = body;
}
