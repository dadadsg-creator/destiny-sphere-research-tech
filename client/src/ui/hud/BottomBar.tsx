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
 * Время — теперь настоящее игровое серверное время (найдено 2026-09-17,
 * живым дампом полного ответа `<load locations="3"/>`): марки `stime`
 * (Unix-мс) + `userGMT` (часовой пояс игры, часов от UTC) присутствуют
 * в КАЖДОМ ответе сервера — подтверждено исходником, `parser.as:165-177`
 * (`parse_respond` → `init_clock(obj.stime)`), см.
 * `data/serverClockStore.ts`. Тикает раз в секунду локально от
 * синхронизированного смещения, без повторных запросов — тот же приём,
 * что и в оригинале. Пока `stime` ни разу не приходил (соединение только
 * устанавливается) — честно показывает "…", не подставляет системное
 * время под видом игрового. Слева и отдельно от остальных кнопок — как
 * в оригинале, чтобы центр панели оставался под игровые действия (было
 * — время смешано с кнопками одним блоком слева, справа пусто).
 *
 * Значок почты (2026-09-17) — тоже настоящая марка (`newmessage`, см.
 * `mail/mailStore.ts`), но НЕ счётчик: сервер шлёт только "есть/нет"
 * непрочитанные, конкретное число никогда не присылается (`game.as:
 * 893-918`). docs/main_screen.md §10 описывает цифровой бейдж "до
 * 99/99+" — эта часть ТЗ не подтверждается протоколом; сделан честный
 * boolean-индикатор (подсветка значка), как в оригинале, без придуманной
 * цифры. "🔔" (уведомления) — отдельная, не запрошенная в этом заходе
 * заглушка, не трогалась.
 */
import { useEffect, useState } from 'preact/hooks';
import { hudActions } from './hudActionsStore';
import { serverClock, getGameTimeInGameZone } from '../../data/serverClockStore';
import { hasNewMail } from '../../mail/mailStore';

function formatClock(d: Date): string {
  const hh = String(d.getUTCHours()).padStart(2, '0');
  const mm = String(d.getUTCMinutes()).padStart(2, '0');
  const ss = String(d.getUTCSeconds()).padStart(2, '0');
  return `${hh}:${mm}:${ss}`;
}

export interface BottomBarProps {
  collapsed: boolean;
  onToggleCollapsed: () => void;
}

export function BottomBar({ collapsed, onToggleCollapsed }: BottomBarProps) {
  const actions = hudActions.value;
  const [, forceTick] = useState(0);

  useEffect(() => {
    const id = setInterval(() => forceTick((n) => n + 1), 1000);
    return () => clearInterval(id);
  }, []);

  const gameTime = getGameTimeInGameZone();
  void serverClock.value; // подписка сигнала — перерисовать сразу, как только придёт первый stime

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
            <span class="ds-hud-bottombar__clock" title="Игровое серверное время (stime/userGMT)">
              {gameTime ? formatClock(gameTime) : '…'}
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
            <button
              type="button"
              class={`ds-hud-bottombar__icon-btn${hasNewMail.value ? ' ds-hud-bottombar__icon-btn--new' : ''}`}
              title={hasNewMail.value ? 'Почта — есть непрочитанные' : 'Почта'}
              onClick={actions?.openMail}
            >
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
