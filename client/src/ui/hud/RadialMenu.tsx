/**
 * Круговое меню (docs/main_screen.md §11) — 4 внешние секции + кнопка
 * "Обновить" в центре. Команды — `hudActionsStore.ts` (Домой/Центр —
 * новые, Колонии/Комплекс — уже существовавшие в бывшем LiveToolbar).
 *
 * Реализация этого прохода — круглые кнопки, разложенные по окружности
 * абсолютным позиционированием (N/E/S/W), не честные SVG-секторы:
 * по прямому указанию владельца в этом проходе форма/красота вторична
 * ("делай близко к макету, а красоту и эффекты наведём позже") —
 * геометрия секторов при этом не финальна.
 */
import { useState } from 'preact/hooks';
import { hudActions } from './hudActionsStore';

export function RadialMenu() {
  const actions = hudActions.value;
  const [active, setActive] = useState<string | null>(null);
  if (!actions) return null;

  const sections = [
    { key: 'home', label: 'Домой', pos: 'n', onClick: actions.navigateHome },
    { key: 'central', label: 'Центр', pos: 'e', onClick: actions.navigateCentral },
    { key: 'complex', label: 'Комплекс', pos: 's', onClick: actions.openComplex },
    { key: 'colonies', label: 'Колонии', pos: 'w', onClick: actions.openColonies },
  ] as const;

  return (
    <div class="ds-hud-radial">
      <div class="ds-hud-radial__ring" />
      {sections.map((s) => (
        <button
          key={s.key}
          type="button"
          class={`ds-hud-radial__btn ds-hud-radial__btn--${s.pos}${active === s.key ? ' ds-hud-radial__btn--active' : ''}`}
          onClick={() => {
            setActive(s.key);
            s.onClick();
          }}
        >
          {s.label}
        </button>
      ))}
      <button
        type="button"
        class="ds-hud-radial__center"
        title="Обновить"
        onClick={() => {
          setActive(null);
          actions.refresh();
        }}
      >
        Обновить
      </button>
    </div>
  );
}
