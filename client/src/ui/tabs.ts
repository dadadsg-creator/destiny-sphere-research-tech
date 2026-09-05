/**
 * Форма `tabs_array` и логика имени/id окна — портировано 1:1 из
 * `window.as:90-126` (`getNameWin`/`getIdWin`), см.
 * docs/client-window-framework.md §1.4, §2.1.
 */

export interface TabDef {
  /** Отправляется на сервер при клике (reqvestdata.call_tabs). */
  id: string;
  /** Готовый локализованный текст вкладки (не ключ локализации). */
  ln: string;
  /** `1`/`true` — активна. Оба варианта валидны в оригинале (разнобой сериализации сервера). */
  cur: boolean | 0 | 1;
  /** Имя линковки иконки — обязательно для add_icon_tabs*, не используется в add_second_tabs. */
  icon?: string;
  /** Если задано — вкладка становится внешней ссылкой (reqvestdata.openUrl), не серверной навигацией. */
  nc?: string;
  /** Вложенные вкладки — рендерер видимо не найден в оригинале (не установлено), см. docs §9. */
  childarray?: TabDef[];
  /** Только add_second_tabs: <=0 — вкладка серая и некликабельная. */
  act?: number;
  /** Только add_second_tabs: текст тултипа. */
  hint?: string;
}

function isCur(tab: TabDef): boolean {
  return tab.cur === 1 || tab.cur === true;
}

/** window.as:90-104 (getNameWin) — имя окна = конкатенация ln всех активных вкладок на любом уровне, аплкейс. */
export function getWindowName(tabs: TabDef[], acc = ''): string {
  let text = acc;
  for (const tab of tabs) {
    if (isCur(tab)) text += tab.ln;
    if (tab.childarray) text = getWindowName(tab.childarray, text);
  }
  return text.toUpperCase();
}

/**
 * window.as:106-126 (getIdWin) — id первой найденной активной вкладки.
 * ОСОБЕННОСТЬ ОРИГИНАЛА (не баг порта): если активна вложенная вкладка
 * (childarray), возвращается id РОДИТЕЛЬСКОЙ, а не дочерней — см. §9
 * client-window-framework.md, не установлено, баг это или нет в оригинале.
 */
export function getWindowId(tabs: TabDef[]): string {
  for (const tab of tabs) {
    if (isCur(tab)) return tab.id;
    if (tab.childarray) {
      for (const child of tab.childarray) {
        if (isCur(child)) return tab.id;
      }
    }
  }
  return '';
}
