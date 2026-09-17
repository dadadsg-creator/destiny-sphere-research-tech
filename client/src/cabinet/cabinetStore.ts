/**
 * Живое хранилище личного кабинета — главный экран (`action="enter"`) и
 * открытая подформа услуги (`action="submenuopen"`) приходят ДВУМЯ
 * разными марками одного корня `cabinet` (см. `protocol/dispatch.tsx`,
 * `renderCabinet`), а `CabinetWindow.tsx` ожидает оба сразу одним набором
 * пропов — тот же паттерн, что и `mail/mailStore.ts` для списка/тела
 * письма. Всплывающее сообщение (`popupMessage`) приходит частью самого
 * `data` (не отдельной маркой) — `dismissCabinetPopup()` просто обнуляет
 * поле в уже сохранённом объекте, без нового запроса к серверу.
 */
import { signal } from '@preact/signals';
import type { CabinetMainData, OperationStatisticData } from './cabinetMainData';
import type { SubmenuFormData } from './submenuData';

export const cabinetMain = signal<CabinetMainData | null>(null);
export const cabinetSubmenu = signal<SubmenuFormData | null>(null);
/**
 * "Вся статистика" (`action="operationStatistic"`) — владелец попросил
 * пройтись по списку пробелов "по порядку", это был следующий пункт
 * ("окна для него в Фазе 3 не строили"). В оригинале
 * `showCabinetStatistic()` (`menuLibCode.as:88-93`) подменяет
 * содержимое ГЛАВНОГО окна кабинета, не открывает отдельное — тот же
 * принцип здесь, доп. секция внутри `CabinetWindow`, как уже готовый
 * `SubmenuForm`.
 */
export const cabinetStatistic = signal<OperationStatisticData | null>(null);

export function setCabinetMain(data: CabinetMainData): void {
  cabinetMain.value = data;
  cabinetSubmenu.value = null;
}

export function setCabinetSubmenu(form: SubmenuFormData | null): void {
  cabinetSubmenu.value = form;
}

export function setCabinetStatistic(data: OperationStatisticData | null): void {
  cabinetStatistic.value = data;
}

export function dismissCabinetPopup(): void {
  if (cabinetMain.value) cabinetMain.value = { ...cabinetMain.value, popupMessage: null };
}
