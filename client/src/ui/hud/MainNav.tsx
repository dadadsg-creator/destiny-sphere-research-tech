/**
 * Основная навигация (docs/main_screen.md §6) — 11 разделов. Реально
 * подключены 4 (Почта/Личный кабинет/Комплекс/Мои колонии — те же
 * команды, что были в бывшем `LiveToolbar.tsx`, см. `hudActionsStore.ts`).
 *
 * Остальные 7 (Магазин/Анализ/Рейтинг/Профиль/Форум/Портал/Игры) НЕ
 * имеют подтверждённой живой команды нигде в порте — ни разу не
 * встречались в живой сверке сценариев в этой сессии, протокол для них
 * не исследован. Придумывать XML-теги для непроверенных разделов
 * означало бы нарушить правило проекта "не выдумывать данные/команды" —
 * поэтому они присутствуют в макете (кнопки видны, как того требует
 * ТЗ), но помечены как нереализованные (`ds-hud-nav__btn--stub`,
 * подсказка при наведении) вместо того, чтобы притворяться рабочими.
 *
 * "Активный раздел" (§6) — упрощённо: локальное состояние по последнему
 * клику, без привязки к тому, закрылось ли открытое окно — полноценное
 * отслеживание "что сейчас реально открыто" потребовало бы отдельного
 * globally-известного "текущий раздел"-сигнала, которого в архитектуре
 * диспетчера пока нет (окна открываются через общий `openWindow()` без
 * тега раздела).
 */
import { useState } from 'preact/hooks';
import { hudActions } from './hudActionsStore';

interface NavItem {
  key: string;
  label: string;
  onClick?: () => void;
}

const STUB_ITEMS: string[] = ['Магазин', 'Анализ', 'Рейтинг', 'Профиль', 'Форум', 'Портал', 'Игры'];

export function MainNav() {
  const actions = hudActions.value;
  const [active, setActive] = useState<string | null>(null);

  if (!actions) return null;

  const items: NavItem[] = [
    { key: 'mail', label: 'Почта', onClick: actions.openMail },
    { key: 'cabinet', label: 'Личный кабинет', onClick: actions.openCabinet },
    { key: 'complex', label: 'Комплекс', onClick: actions.openComplex },
    { key: 'colonies', label: 'Мои колонии', onClick: actions.openColonies },
    ...STUB_ITEMS.map((label) => ({ key: label, label })),
  ];

  return (
    <nav class="ds-hud-nav">
      {items.map((item) => (
        <button
          key={item.key}
          type="button"
          class={`ds-hud-nav__btn${active === item.key ? ' ds-hud-nav__btn--active' : ''}${!item.onClick ? ' ds-hud-nav__btn--stub' : ''}`}
          title={item.onClick ? undefined : 'Раздел ещё не подключён к живому протоколу'}
          onClick={() => {
            if (!item.onClick) return;
            setActive(item.key);
            item.onClick();
          }}
        >
          {item.label}
          {!item.onClick && (
            <span class="ds-hud-nav__lock" aria-hidden="true">
              🔒
            </span>
          )}
        </button>
      ))}
    </nav>
  );
}
