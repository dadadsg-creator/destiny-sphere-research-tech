/**
 * Нижняя панель (docs/main_screen.md §10). Реально подключена только
 * почта (`hudActions.openMail`, тот же вход, что и в верхней навигации —
 * дубль по месту, как в оригинале, где иконка почты есть и в статус-баре,
 * и в круговом меню). "Переход к армии"/"к науке", быстрое действие и
 * уведомления — как и часть верхней навигации (MainNav.tsx), не имеют
 * подтверждённой живой команды НЕ через конкретную постройку (армия/
 * наука в этом порте открываются через `getbuildmenu` у конкретной
 * ячейки с MILITARY_BASE/RESEARCH_CENTER, обобщённого входа "не с
 * карты" для них ни разу не встречалось в живой сверке сценариев) —
 * помечены как заглушки (значок замка) по той же причине, что и 7
 * разделов навигации, но текст читаем как обычная кнопка, не "похож на
 * случайно ставший полупрозрачным" (замечание владельца, 2026-09-16).
 *
 * Время — реальное системное время браузера (тикает раз в секунду), НЕ
 * игровое серверное время: марка с игровым временем сервера в этом
 * порте нигде не парсится, подставлять браузерное под видом игрового
 * было бы обманом пользователя, поэтому подписано отдельно. Слева и
 * отдельно от остальных кнопок — как в оригинале, чтобы центр панели
 * оставался под игровые действия (было — время смешано с кнопками
 * одним блоком слева, справа пусто).
 */
import { useEffect, useState } from 'preact/hooks';
import { hudActions } from './hudActionsStore';

function formatClock(d: Date): string {
  return d.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

export interface BottomBarProps {
  collapsed: boolean;
  onToggleCollapsed: () => void;
}

export function BottomBar({ collapsed, onToggleCollapsed }: BottomBarProps) {
  const actions = hudActions.value;
  const [now, setNow] = useState(() => new Date());

  useEffect(() => {
    const id = setInterval(() => setNow(new Date()), 1000);
    return () => clearInterval(id);
  }, []);

  return (
    <div class={`ds-hud-bottombar${collapsed ? ' ds-hud-bottombar--collapsed' : ''}`}>
      <button
        type="button"
        class="ds-hud-bottombar__collapse"
        onClick={onToggleCollapsed}
        title={collapsed ? 'Развернуть нижнюю панель' : 'Свернуть нижнюю панель'}
        aria-label={collapsed ? 'Развернуть нижнюю панель' : 'Свернуть нижнюю панель'}
      >
        {collapsed ? '⌃' : '⌄'}
      </button>
      {!collapsed && (
        <>
          <div class="ds-hud-bottombar__left">
            <span class="ds-hud-bottombar__clock" title="Системное время (игровое время сервера в порте пока не читается)">
              {formatClock(now)}
            </span>
          </div>
          <div class="ds-hud-bottombar__center">
            <button type="button" class="ds-hud-bottombar__btn ds-hud-bottombar__btn--stub" title="Раздел ещё не подключён к живому протоколу">
              Армия <span class="ds-hud-nav__lock" aria-hidden="true">🔒</span>
            </button>
            <button type="button" class="ds-hud-bottombar__btn ds-hud-bottombar__btn--stub" title="Раздел ещё не подключён к живому протоколу">
              Анализ <span class="ds-hud-nav__lock" aria-hidden="true">🔒</span>
            </button>
            <button type="button" class="ds-hud-bottombar__btn ds-hud-bottombar__btn--stub" title="Раздел ещё не подключён к живому протоколу">
              Действие <span class="ds-hud-nav__lock" aria-hidden="true">🔒</span>
            </button>
          </div>
          <div class="ds-hud-bottombar__right">
            <button type="button" class="ds-hud-bottombar__icon-btn" title="Почта" onClick={actions?.openMail}>
              ✉
            </button>
            <button type="button" class="ds-hud-bottombar__icon-btn ds-hud-bottombar__icon-btn--stub" title="Уведомления — счётчик непрочитанных пока не подключён">
              🔔
            </button>
          </div>
        </>
      )}
    </div>
  );
}
