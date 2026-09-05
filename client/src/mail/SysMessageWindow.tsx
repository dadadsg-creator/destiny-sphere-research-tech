/**
 * Попап очереди сообщений (системные/боевые/шпионские) — sys_mess_menu.as,
 * см. docs/client-economy-social-ui.md §4.5. Кнопка «Просмотр лога
 * разведки» видна только для типа SPY и при открытых Центральных Воротах
 * (`sys_mess_menu.as:18` — `type_win=="SPY" && Number(debug.link.gate)==1`,
 * перекликается с ролью CENTRAL_GATE из mechanics-buildings.md §6.3).
 */
import { Window } from '../ui/Window';

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
    <Window title={windowName} onClose={onClose}>
      {showSpyLogButton && <button onClick={onViewSpyLog}>Просмотр лога разведки</button>}
      <div class="ds-window__content" dangerouslySetInnerHTML={{ __html: messages.join('<br/>') }} />
    </Window>
  );
}
