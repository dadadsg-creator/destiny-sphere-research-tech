/**
 * Точки входа главного экрана, не привязанные к клику по карте — почта,
 * личный кабинет, комплекс, колонии (перенесено сюда из бывшего
 * `liveToolbarStore.ts`/`LiveToolbar.tsx`, временной заглушки статус-бара
 * времён "Сшивания", см. CLIENT_PORTING_PLAN.md — теперь это реальный
 * статус-бар/навигация главного экрана, docs/main_screen.md), плюс три
 * новых действия кругового меню (Домой/Центр/Обновить — те же команды,
 * что уже подтверждены `reqvestdata.as:267-292`/`world/colonyNavigateData.ts`
 * для "Мои колонии", просто другой `dest`).
 *
 * Заполняется один раз в `liveApp.ts` после входа в мир, `null` вне живой
 * сессии (`?screen=`-тесты, обычная загрузка без `?live=1`).
 */
import { signal } from '@preact/signals';

export interface HudActions {
  openMail: () => void;
  openCabinet: () => void;
  openComplex: () => void;
  openColonies: () => void;
  /** `<navigate cell="1" dest="HOME"/>` — вернуться на собственную соту. */
  navigateHome: () => void;
  /** `<navigate cell="1" dest="CENTRAL"/>` — центральный слой соты. */
  navigateCentral: () => void;
  /** `<navigate cell="1" dest="NEUTRAL"/>` — нейтральный слой соты (пятый сектор кругового меню оригинала). */
  navigateNeutral: () => void;
  /** Перезапрос текущего состояния — тот же `<load locations="3"/>`, что и при первом входе. Promise — центр кругового меню показывает индикатор загрузки, пока запрос летит. */
  refresh: () => Promise<void>;
}

export const hudActions = signal<HudActions | null>(null);
