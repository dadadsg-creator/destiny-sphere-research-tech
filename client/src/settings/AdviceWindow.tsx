/**
 * Окно обучающих подсказок «адвайзер» — adviceclass.as, см.
 * docs/client-economy-social-ui.md §4.7. «Назад» скрыта при number<=1,
 * «Вперёд» скрыта при number>=total (`drawHint()`, adviceclass.as:293-309).
 * Иллюстрация «было»/«станет» — просто подписи спрайтов (нет пайплайна
 * ассетов зданий в этом порте), маркер поверхности — текстовая метка.
 */
import { Window } from '../ui/Window';
import type { AdviceHint } from './adviceData';

export interface AdviceWindowProps {
  hint: AdviceHint;
  onClose: () => void;
  onNext: () => void;
  onPrev: () => void;
  onJumpTo: (id: string) => void;
}

export function AdviceWindow({ hint, onClose, onNext, onPrev, onJumpTo }: AdviceWindowProps) {
  const showPrev = hint.number > 1;
  const showNext = hint.number < hint.total;

  return (
    <Window title={hint.title} onClose={onClose}>
      <div>
        Подсказка {hint.number} из {hint.total}
      </div>
      <div dangerouslySetInnerHTML={{ __html: hint.txt }} />
      {hint.build && <div>Было: {hint.build}</div>}
      {hint.tobuild && <div>Станет: {hint.tobuild}</div>}
      <div>
        {showPrev && <button onClick={onPrev}>Назад</button>}
        {showNext && <button onClick={onNext}>Вперёд</button>}
      </div>
      {hint.relatedHints.length > 0 && (
        <ul>
          {hint.relatedHints.map((art) => (
            <li key={art.id}>
              <a
                href="#"
                onClick={(e) => {
                  e.preventDefault();
                  onJumpTo(art.id);
                }}
              >
                {art.name}
              </a>
            </li>
          ))}
        </ul>
      )}
    </Window>
  );
}
