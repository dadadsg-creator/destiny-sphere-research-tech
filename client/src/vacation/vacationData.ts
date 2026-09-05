/**
 * Разбор mark `otpusk` — режим отпуска (вакация), Фаза 3.15,
 * `otpusk_class.as`, источник: docs/client-economy-social-ui.md §4.2.
 * Портировано 1:1 из `xmlparsers.as:3415-3432` (`otpusk_menu`, диспетчер
 * `parser.as:1539-1541`). Запросы — `reqvestdata.as:873-887`.
 *
 * Три статуса (`otpusk_class.as:76-109`):
 *  - `"1"` — не в отпуске: кнопка «Уйти в отпуск», требует подтверждения
 *    (CONFIRM_VACATION) и ввода ОТДЕЛЬНОГО пароля отпуска.
 *  - `"3"` — в отпуске: кнопка «Выйти» (тот же пароль) + «Забыл пароль».
 *  - `"2"` — НИКАКИХ кнопок не рисуется вовсе (переходное/заблокированное
 *    состояние — назначение не установлено достоверно по этому файлу).
 *
 * **Опечатка в самом клиенте, сохранена дословно**: команда «забыл
 * пароль» — `<vacationsedpass />` (не "sendpass"), закомментирована в
 * оригинале прямо над вызовом как напоминание разработчика
 * (`otpusk_class.as:98`).
 */
import { parseXmlFragment } from '../protocol/response';

export type VacationStatus = '1' | '2' | '3';

export interface VacationData {
  status: VacationStatus;
  dataText: string;
  descText: string;
}

export function parseVacation(xml: string): VacationData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'otpusk') {
    throw new Error(`Ожидался корневой тег <otpusk>, получено: ${root?.nodeName}`);
  }

  let status: VacationStatus = '2';
  let dataText = '';
  let descText = '';

  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'data') {
      const s = el.getAttribute('status');
      if (s === '1' || s === '2' || s === '3') status = s;
      dataText = el.textContent ?? '';
    } else if (el.nodeName === 'desc') {
      descText = el.textContent ?? '';
    }
  }

  return { status, dataText, descText };
}

/** reqvestdata.as:873-877 — <vacationstart password=""/>. */
export function buildStartVacationCommand(password: string): { command: 'vacationstart'; password: string } {
  return { command: 'vacationstart', password };
}

/** reqvestdata.as:878-882 — <vacationstop password=""/>. */
export function buildStopVacationCommand(password: string): { command: 'vacationstop'; password: string } {
  return { command: 'vacationstop', password };
}

/** reqvestdata.as:883-887 — <vacationsedpass/>, опечатка сохранена как в оригинале. */
export function buildForgotVacationPasswordCommand(): { command: 'vacationsedpass' } {
  return { command: 'vacationsedpass' };
}
