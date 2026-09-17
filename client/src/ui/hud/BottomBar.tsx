/**
 * Нижняя панель (docs/main_screen.md §10). Реально подключена почта
 * (`hudActions.openMail`, тот же вход, что и в верхней навигации — дубль
 * по месту, как в оригинале, где иконка почты есть и в статус-баре, и в
 * круговом меню) и лог сообщений (см. ниже).
 *
 * Время — настоящее игровое серверное время (найдено 2026-09-17, живым
 * дампом полного ответа `<load locations="3"/>`): марки `stime` (Unix-мс)
 * + `userGMT` (часовой пояс игры, часов от UTC) присутствуют в КАЖДОМ
 * ответе сервера — подтверждено исходником, `parser.as:165-177`
 * (`parse_respond` → `init_clock(obj.stime)`), см.
 * `data/serverClockStore.ts`. Тикает раз в секунду локально от
 * синхронизированного смещения, без повторных запросов — тот же приём,
 * что и в оригинале. Пока `stime` ни разу не приходил (соединение только
 * устанавливается) — честно показывает "…", не подставляет системное
 * время под видом игрового.
 *
 * Значок почты (2026-09-17) — тоже настоящая марка (`newmessage`, см.
 * `mail/mailStore.ts`), но НЕ счётчик: сервер шлёт только "есть/нет"
 * непрочитанные, конкретное число никогда не присылается (`game.as:
 * 893-918`). docs/main_screen.md §10 описывает цифровой бейдж "до
 * 99/99+" — эта часть ТЗ не подтверждается протоколом; сделан честный
 * boolean-индикатор (подсветка значка), как в оригинале, без придуманной
 * цифры. "🔔" (уведомления) — отдельная, не запрошенная заглушка, не
 * трогалась.
 *
 * Меч/бинокль/шеврон (2026-09-17) — реальный функционал, найден по
 * скриншоту владельца с живого Flash-клиента (красный прямоугольник
 * вокруг иконок статус-бара рядом с часами) + живым кликом через Ruffle
 * (звезда рядом с ними оказалась чатом — вне периметра, не трогается) +
 * чтением `sys_mess_menu.as`: это фильтры лога накопленных сообщений
 * (`marks.sysmsg`, копится в `mail/sysMessageStore.ts` на каждый ответ
 * сервера) — меч = военные (WAR), бинокль = шпионские (SPY), шеврон =
 * общий системный лог (SYS), как в оригинале ("Просмотр лога разведки"
 * доступен только внутри SPY-окна при открытых воротах — см.
 * `protocol/dispatch.tsx: openSysMessageLog`). Раньше шеврон был кнопкой
 * "свернуть панель" — по прямому решению владельца эта функция убрана
 * совсем ("Шеврон = открыть системный лог (как в оригинале), сворачивание
 * убрать", 2026-09-17), панель больше не сворачивается.
 *
 * Строка последнего сообщения (между часами и меч/бинокль/шеврон) —
 * владелец прислал скриншот реального статус-бара: там это одна общая
 * полоса ВНУТРИ той же нижней панели, не отдельный плавающий элемент.
 * Первая попытка ("свёрнутый вид" отдельным доп. блоком над нижней
 * панелью) добавляла лишний ряд — поправка: "не в три [строки], а в
 * две. А надо в одну. В оригинале — в одну". `latestLogMessage`
 * (`mail/sysMessageStore.ts`) обновляется пассивно на каждое новое
 * `marks.sysmsg`, без клика — сами кнопки по-прежнему открывают полный
 * список (`actions.openSysLog`), как и раньше.
 */
import { useEffect, useState } from 'preact/hooks';
import { hudActions } from './hudActionsStore';
import { serverClock, getGameTimeInGameZone } from '../../data/serverClockStore';
import { hasNewMail } from '../../mail/mailStore';
import { latestLogMessage } from '../../mail/sysMessageStore';

function formatClock(d: Date): string {
  const hh = String(d.getUTCHours()).padStart(2, '0');
  const mm = String(d.getUTCMinutes()).padStart(2, '0');
  const ss = String(d.getUTCSeconds()).padStart(2, '0');
  return `${hh}:${mm}:${ss}`;
}

export function BottomBar() {
  const actions = hudActions.value;
  const [, forceTick] = useState(0);

  useEffect(() => {
    const id = setInterval(() => forceTick((n) => n + 1), 1000);
    return () => clearInterval(id);
  }, []);

  const gameTime = getGameTimeInGameZone();
  void serverClock.value; // подписка сигнала — перерисовать сразу, как только придёт первый stime

  return (
    <div class="ds-hud-bottombar">
      <div class="ds-hud-bottombar__left">
        <span class="ds-hud-bottombar__clock" title="Игровое серверное время (stime/userGMT)">
          {gameTime ? formatClock(gameTime) : '…'}
        </span>
      </div>
      <div class="ds-hud-bottombar__center">
        <span class="ds-hud-bottombar__log-preview" title={latestLogMessage.value ?? undefined}>
          {latestLogMessage.value ? <span dangerouslySetInnerHTML={{ __html: latestLogMessage.value }} /> : null}
        </span>
        <button
          type="button"
          class="ds-hud-bottombar__icon-btn"
          title="Военные сообщения"
          aria-label="Военные сообщения"
          onClick={() => actions?.openSysLog('WAR')}
        >
          ⚔
        </button>
        <button
          type="button"
          class="ds-hud-bottombar__icon-btn"
          title="Шпионские сообщения"
          aria-label="Шпионские сообщения"
          onClick={() => actions?.openSysLog('SPY')}
        >
          🔭
        </button>
        <button
          type="button"
          class="ds-hud-bottombar__icon-btn"
          title="Системные сообщения"
          aria-label="Системные сообщения"
          onClick={() => actions?.openSysLog('SYS')}
        >
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
            <path d="M5 15 L12 9 L19 15" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M5 10.5 L12 4.5 L19 10.5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" opacity="0.55" />
          </svg>
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
    </div>
  );
}
