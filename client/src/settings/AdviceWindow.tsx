/**
 * Окно обучающих подсказок «адвайзер» — adviceclass.as, см.
 * docs/client-economy-social-ui.md §4.7. «Назад» скрыта при number<=1,
 * «Вперёд» скрыта при number>=total (`drawHint()`, adviceclass.as:293-309).
 * Иллюстрация «было»/«станет» — просто подписи спрайтов (нет пайплайна
 * ассетов зданий в этом порте), маркер поверхности — текстовая метка.
 *
 * Вёрстка (2026-09-18) — список связанных тем был голым `<ul><a href="#">`
 * (синие подчёркнутые ссылки без единого класса) — владелец, увидев живой
 * скриншот: "ужасно выглядит". Заменено на кнопки в общем визуальном
 * языке порта (`.ds-advice__hint-item`, тот же приём "кликабельный пункт
 * списка", что и `.ds-cabinet-services__item`), текст подсказки и
 * "было/станет" — читаемым блоком, не подряд отдельными `<div>`.
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
      <div class="ds-advice__meta">
        Подсказка {hint.number} из {hint.total}
      </div>
      <div class="ds-advice__txt" dangerouslySetInnerHTML={{ __html: hint.txt }} />
      {(hint.build || hint.tobuild) && (
        <div class="ds-advice__buildinfo">
          {hint.build && <span>Было: {hint.build}</span>}
          {hint.tobuild && <span>Станет: {hint.tobuild}</span>}
        </div>
      )}
      <div class="ds-popup__buttons ds-btn-row">
        {showPrev && (
          <button type="button" class="ds-popup__btn" onClick={onPrev}>
            Назад
          </button>
        )}
        {showNext && (
          <button type="button" class="ds-popup__btn" onClick={onNext}>
            Вперёд
          </button>
        )}
      </div>
      {hint.relatedHints.length > 0 && (
        <ul class="ds-advice__hints">
          {hint.relatedHints.map((art) => (
            <li key={art.id}>
              <button type="button" class="ds-advice__hint-item" onClick={() => onJumpTo(art.id)}>
                {art.name}
              </button>
            </li>
          ))}
        </ul>
      )}
    </Window>
  );
}
