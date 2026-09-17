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
 * Два состояния (владелец, 2026-09-17, по прямому UX-описанию — в
 * `sys_mess_menu.as` этого разделения нет явно, панель либо есть целиком,
 * либо удалена): свёрнуто (по умолчанию) — одна строка, последнее
 * сообщение; развёрнуто — полный прокручиваемый список. В развёрнутом
 * виде кнопка "Свернуть" вместо крестика — крестик лишний, раз есть
 * сворачивание (сам крестик остаётся только в свёрнутом виде — там это
 * единственный способ по-настоящему убрать панель).
 *
 * Свёрнутый вид — ОДНА строка целиком (заголовок+текст+кнопки в один
 * flex-row), не заголовок отдельной полосой над текстом — первая версия
 * так и делала (титлбар+тело как два визуальных яруса), владелец
 * поправил: "на флеше было размещено в одну [строку]". Кнопка
 * "Свернуть" в развёрнутом виде — только текст, без отдельного значка
 * стрелки рядом (было "Свернуть ▾" — читалось как две кнопки).
 */
import { useState } from 'preact/hooks';

export interface SysMessageWindowProps {
  messages: string[];
  windowName: string;
  typeWin: 'SYS' | 'SPY' | 'WAR';
  gateOpen: boolean;
  onClose: () => void;
  onViewSpyLog: () => void;
}

export function SysMessageWindow({ messages, windowName, typeWin, gateOpen, onClose, onViewSpyLog }: SysMessageWindowProps) {
  const [expanded, setExpanded] = useState(false);
  const showSpyLogButton = typeWin === 'SPY' && gateOpen;
  const latest = messages.length > 0 ? messages[messages.length - 1] : null;

  if (!expanded) {
    return (
      <div class="ds-syslog ds-syslog--collapsed">
        <span class="ds-syslog__collapsed-label">{windowName}:</span>
        <span class="ds-syslog__collapsed-text">
          {latest ? <span dangerouslySetInnerHTML={{ __html: latest }} /> : <span class="ds-syslog__empty">Сообщений пока нет.</span>}
        </span>
        <button type="button" class="ds-syslog__expand" onClick={() => setExpanded(true)} title="Развернуть" aria-label="Развернуть">
          ▲
        </button>
        <button type="button" class="ds-syslog__close" onClick={onClose} title="Закрыть" aria-label="Закрыть">
          ×
        </button>
      </div>
    );
  }

  return (
    <div class="ds-syslog">
      <div class="ds-syslog__titlebar">
        <span class="ds-syslog__title">{windowName}</span>
        <button type="button" class="ds-syslog__collapse" onClick={() => setExpanded(false)}>
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
