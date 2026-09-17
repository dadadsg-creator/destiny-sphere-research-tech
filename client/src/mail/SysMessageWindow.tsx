/**
 * Панель лога сообщений (системные/боевые/шпионские) — `sys_mess_menu.as`,
 * см. docs/client-economy-social-ui.md §4.5. Кнопка «Просмотр лога
 * разведки» видна только для типа SPY и при открытых Центральных Воротах
 * (`sys_mess_menu.as:18` — `type_win=="SPY" && Number(debug.link.gate)==1`,
 * перекликается с ролью CENTRAL_GATE из mechanics-buildings.md §6.3).
 *
 * НЕ обычное модальное окно (`ui/Window.tsx`) — в оригинале эта панель
 * "выезжает" снизу, прикреплённая к статус-бару (`sys_mess_menu.as:
 * 100-101`: `this.mess_window._x/_y = debug.link.interface_mov.
 * bg_status_bar._x/_y`), а не всплывает по центру экрана. Первая версия
 * этого компонента ошибочно использовала `Window`/центральную модалку —
 * владелец поправил по скриншоту реального клиента ("в оригинале выезжала
 * панель с сообщениями"). Здесь — свой каркас, зафиксированный снизу
 * поверх карты, вплотную над `.ds-hud-bottombar` (60px, см. hud.css), и
 * открывается через отдельный `logWindow`-слот (`ui/windowStore.ts`), не
 * через общий модальный `currentWindow` — то же разделение, что и в
 * оригинале (`create_log_window` — независимый always-on-top слой).
 */
export interface SysMessageWindowProps {
  messages: string[];
  windowName: string;
  typeWin: 'SYS' | 'SPY' | 'WAR';
  gateOpen: boolean;
  onClose: () => void;
  onViewSpyLog: () => void;
}

export function SysMessageWindow({ messages, windowName, typeWin, gateOpen, onClose, onViewSpyLog }: SysMessageWindowProps) {
  const showSpyLogButton = typeWin === 'SPY' && gateOpen;

  return (
    <div class="ds-syslog">
      <div class="ds-syslog__titlebar">
        <span class="ds-syslog__title">{windowName}</span>
        <button type="button" class="ds-syslog__close" onClick={onClose} aria-label="Закрыть">
          ×
        </button>
      </div>
      {showSpyLogButton && (
        <div class="ds-syslog__actions">
          <button type="button" class="ds-popup__btn" onClick={onViewSpyLog}>
            Просмотр лога разведки
          </button>
        </div>
      )}
      <div class="ds-syslog__body">
        {messages.length > 0 ? (
          <div dangerouslySetInnerHTML={{ __html: messages.join('<br/>') }} />
        ) : (
          <span class="ds-syslog__empty">Сообщений пока нет.</span>
        )}
      </div>
    </div>
  );
}
