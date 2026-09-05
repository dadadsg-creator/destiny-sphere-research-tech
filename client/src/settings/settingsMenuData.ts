/**
 * Ключи и дефолты для окна настроек — settingmenu.as, см.
 * docs/client-economy-social-ui.md §0.3. Весь файл не делает ни одного
 * сетевого вызова — чисто локальные визуальные переключатели.
 * Персистентность через ключи `addcheck`/`addRadio` (window.as:711-882):
 * чекбоксы — `"CHECK_BOX" + name`, дефолт `false` если не сохранено;
 * радио — `"RADIO_" + name`, дефолт `IDs[0]` если не сохранено или
 * сохранённое значение не входит в список ID.
 *
 * **Кнопка «Профиль» пропущена сознательно** — гейтится
 * `debug.link.PROFILEXIST`, но по клику ведёт на буквально захардкоженный
 * `http://www.microsoft.com` (`settingmenu.as:47`) — очевидный
 * тестовый/недоделанный остаток разработки, не реальная функциональность,
 * воспроизводить нечего.
 */
import { loadSetting, saveSetting } from './setting';

export interface CheckboxSetting {
  key: string;
  /** Локализационный ключ оригинала (game.getLocalNames), см. label для читаемого текста. */
  labelKey: string;
  label: string;
}

/** settingmenu.as:118-132 — порядок соответствует оригиналу. */
export const SETTINGS_CHECKBOXES: CheckboxSetting[] = [
  { key: 'ShowSotes', labelKey: 'SHOW_SOTES', label: 'Показывать соты' },
  { key: 'AlienComp', labelKey: 'ALIEN_COMP', label: 'Чужие постройки полупрозрачные' },
  { key: 'AnimateStatus', labelKey: 'ANIM_ON', label: 'Анимация статусов' },
  { key: 'DeleteSysMessage', labelKey: 'DELETE_SYS_MESSAGE', label: 'Удалять системные сообщения' },
  { key: 'ShowShadowMenu', labelKey: 'SHOW_SHADOW_MENU', label: 'Тень у окон меню' },
  { key: 'ShowShadowBuild', labelKey: 'SHOW_SHADOW_BUILD', label: 'Тень у построек' },
  { key: 'ShowResCounters', labelKey: 'RES_VIEW_SETTING', label: 'Счётчики ресурсов' },
  { key: 'ConfirmationsWin', labelKey: 'CONFIRM_WINDOW', label: 'Окна подтверждения действий' },
  { key: 'Advice', labelKey: 'VIEW_NUB_HELP', label: 'Показывать обучающие подсказки' },
  { key: 'Reducing', labelKey: 'SMALL_BUILDINGS', label: 'Уменьшенные постройки' },
];

export function loadCheckbox(key: string): boolean {
  return loadSetting<boolean>('CHECK_BOX' + key) ?? false;
}

export function saveCheckbox(key: string, value: boolean): void {
  saveSetting('CHECK_BOX' + key, value);
}

/** settingmenu.as:135-141 — качество Flash-рендера, влияет на debug.link._quality. */
export const QUALITY_IDS = ['LOW', 'MEDIUM', 'HIGH', 'BEST'] as const;
export type QualityId = (typeof QUALITY_IDS)[number];
export const QUALITY_LABELS: Record<QualityId, string> = {
  LOW: 'Низкое',
  MEDIUM: 'Среднее',
  HIGH: 'Высокое',
  BEST: 'Лучшее',
};

export function loadQuality(): QualityId {
  const saved = loadSetting<string>('RADIO_qualityFlash');
  return (QUALITY_IDS as readonly string[]).includes(saved ?? '') ? (saved as QualityId) : QUALITY_IDS[0];
}

export function saveQuality(id: QualityId): void {
  saveSetting('RADIO_qualityFlash', id);
}

/** settingmenu.as:67-76 butDefColor.onRelease — жёстко заданные "заводские" значения тона окон. */
export const DEFAULT_WINDOW_COLOR = { red: -14, green: -6, blue: 16 };

export function saveWindowColor(red: number, green: number, blue: number): void {
  saveSetting('color_red', red);
  saveSetting('color_green', green);
  saveSetting('color_blue', blue);
}

export function loadWindowColor(): { red: number; green: number; blue: number } {
  return {
    red: loadSetting<number>('color_red') ?? 0,
    green: loadSetting<number>('color_green') ?? 0,
    blue: loadSetting<number>('color_blue') ?? 0,
  };
}
