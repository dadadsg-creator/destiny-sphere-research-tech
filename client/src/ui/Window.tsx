/**
 * Каркас окна — порт `createWindow`/`createInnerWindow` (window.as:595-664),
 * см. docs/client-window-framework.md §1. Два режима (§1.2):
 *  - стандартный титлбар (заголовок вычисляется из активной вкладки —
 *    getWindowName, НЕ отдельное поле — §1.4) + стандартная кнопка закрытия;
 *  - кастомная шапка (`customHeader`) — реальный пример из оригинала,
 *    tablecreate.as, вообще не создаёт стандартный титлбар.
 *
 * Визуально — упрощённая, не попиксельно совпадающая с оригинальным
 * 9-slice скином (углы/грани ассетов ugollu, block-серия, center_ и т.д.,
 * §1.1) — это сознательно отложено как отдельная задача полировки
 * внешнего вида, здесь только структура/поведение каркаса.
 */
import type { ComponentChildren } from 'preact';
import { TabBar } from './TabBar';
import { getWindowName, type TabDef } from './tabs';

export interface WindowProps {
  /** Если не задан явно — вычисляется из активной вкладки (как в оригинале, §1.4). */
  title?: string;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  /** Кастомная шапка вместо стандартного титлбара — режим tablecreate.as (§1.2). */
  customHeader?: ComponentChildren;
  children: ComponentChildren;
}

export function Window({ title, tabs, onTabClick, onClose, customHeader, children }: WindowProps) {
  const computedTitle = title ?? (tabs ? getWindowName(tabs) : '');

  return (
    <div class="ds-window">
      {customHeader ?? (
        <div class="ds-window__titlebar">
          <span class="ds-window__title">{computedTitle}</span>
          <button type="button" class="ds-window__close" onClick={onClose} aria-label="Закрыть">
            ×
          </button>
        </div>
      )}
      {tabs && tabs.length > 0 && <TabBar tabs={tabs} onTabClick={onTabClick} />}
      <div class="ds-window__content">{children}</div>
    </div>
  );
}
