/**
 * Общий попап отчёта/лога — logwindow.as, см.
 * docs/client-economy-social-ui.md §4.5. Кнопка «Написать письмо этому
 * игроку» намеренно ВСЕГДА отрисована притушенной и некликабельной —
 * это подтверждённо мёртвый код в оригинале (см. docstring в logData.ts),
 * а не недоделка HTML-порта.
 */
import { Window } from '../ui/Window';
import type { LogWindowData } from './logData';

export interface LogWindowProps {
  data: LogWindowData;
  onClose: () => void;
  onCopy: () => void;
}

export function LogWindow({ data, onClose, onCopy }: LogWindowProps) {
  return (
    <Window title={data.title} onClose={onClose}>
      <div dangerouslySetInnerHTML={{ __html: data.titleData }} />
      <div class="ds-window__content" dangerouslySetInnerHTML={{ __html: data.textData }} />
      <button onClick={onCopy}>Копировать в буфер</button>
      <button disabled title="Никогда не работало в оригинальном клиенте (logwindow.as)">
        Написать письмо
      </button>
    </Window>
  );
}
