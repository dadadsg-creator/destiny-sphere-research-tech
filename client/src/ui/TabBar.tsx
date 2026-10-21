/**
 * Вкладки верхнего уровня окна — порт `add_icon_tabs`
 * (window.as:2792-2940), см. docs/client-window-framework.md §2.
 *
 * ВАЖНО: клик по вкладке в оригинале — это ЗАПРОС НА СЕРВЕР
 * (`reqvestdata.call_tabs(id)` → `<getbuildmenu tab="ID"/>`), а не
 * локальное переключение видимого контента — окно закрывается сразу
 * после отправки, новое приходит с ответом сервера (§2.2). Поэтому
 * `onTabClick` здесь — controlled callback, а не внутренний стейт
 * компонента: сам TabBar не решает, что показывать дальше.
 *
 * Не путать с `add_second_tabs` (§2.4) — тот локальный, не ходит на
 * сервер; для него нужен отдельный, более простой компонент, когда
 * понадобится конкретный экран, использующий этот паттерн.
 */
import type { TabDef } from './tabs';
import { publicAssetUrl } from '../assets/publicAssetUrl';

export interface TabBarProps {
  tabs: TabDef[];
  onTabClick?: (tabId: string) => void;
}

function isActive(tab: TabDef): boolean {
  return tab.cur === 1 || tab.cur === true;
}

export function TabBar({ tabs, onTabClick }: TabBarProps) {
  return (
    <div class="ds-tabbar">
      {tabs.map((tab) => {
        const disabled = tab.act !== undefined && tab.act <= 0;
        return (
          <button
            key={tab.id}
            type="button"
            class={
              'ds-tabbar__tab' +
              (isActive(tab) ? ' ds-tabbar__tab--active' : '') +
              (tab.nc ? ' ds-tabbar__tab--link' : '')
            }
            title={tab.hint}
            disabled={disabled}
            onClick={() => {
              if (tab.nc) {
                // reqvestdata.openUrl — внешняя ссылка, close_menu() НЕ вызывается в оригинале.
                window.open(tab.nc, '_blank', 'noopener,noreferrer');
              } else {
                onTabClick?.(tab.id);
              }
            }}
          >
            {tab.icon && <img class="ds-tabbar__icon" src={publicAssetUrl(`/assets/icons/${tab.icon}.png`)} alt="" />}
            <span>{tab.ln}</span>
          </button>
        );
      })}
    </div>
  );
}
