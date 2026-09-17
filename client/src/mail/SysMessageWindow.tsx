/**
 * Панель лога сообщений (системные/боевые/шпионские) — `sys_mess_menu.as`,
 * см. docs/client-economy-social-ui.md §4.5. Кнопка «Просмотр лога
 * разведки» видна только для типа SPY и при открытых Центральных Воротах
 * (`sys_mess_menu.as:18` — `type_win=="SPY" && Number(debug.link.gate)==1`,
 * перекликается с ролью CENTRAL_GATE из mechanics-buildings.md §6.3) —
 * подтверждено и авторским комментарием разработчика (`doc/Button SPYLOG
 * in SpyMessageWindow.txt`): "чтобы не переходить туда-сюда по сотам",
 * владелец подтвердил оставить (2026-09-17).
 *
 * НЕ обычное модальное окно (`ui/Window.tsx`) — в оригинале эта панель
 * "выезжает" снизу, прикреплённая к статус-бару (`sys_mess_menu.as:
 * 100-101`), а не всплывает по центру экрана. Открывается через отдельный
 * `logWindow`-слот (`ui/windowStore.ts`), не общий модальный
 * `currentWindow` — независимый always-on-top слой, как в оригинале.
 *
 * Только полный (развёрнутый) вид — свёрнутое состояние НЕ этот
 * компонент: по скриншоту реального статус-бара (владелец, 2026-09-17)
 * в оригинале между часами и кнопками меч/бинокль/шеврон есть одна общая
 * строка — она уже часть самой нижней панели (`BottomBar.tsx`,
 * `latestLogMessage` из `sysMessageStore.ts`), а не отдельный элемент
 * поверх неё. Первая попытка ("свёрнуто" как отдельная плавающая строка
 * над нижней панелью) добавляла лишний, второй ряд — владелец поправил:
 * "не в три, а в две строки. А надо в одну. В оригинале — в одну".
 * "Свернуть" здесь — просто закрыть эту панель (`onClose`), управление
 * возвращается к постоянной строке нижней панели.
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
        <button type="button" class="ds-syslog__collapse" onClick={onClose}>
          Свернуть
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
