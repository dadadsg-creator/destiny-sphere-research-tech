/**
 * Основная навигация (docs/main_screen.md §6) — 11 разделов. Реально
 * подключены 9 (Почта/Личный кабинет/Комплекс/Мои колонии — те же
 * команды, что были в бывшем `LiveToolbar.tsx`; плюс Магазин/Рейтинг/
 * Профиль/Форум/Портал — найдены 2026-09-16, см. ниже).
 *
 * Магазин/Рейтинг/Профиль/Форум/Портал — НЕ команды игрового
 * XML-протокола, а внешние ссылки из статичного `ds/address.php`
 * (root-тег `address`, `<links CABINET=".." ranking=".." profile=".."
 * forum=".." portal=".."/>`, `xmlparsers.init_interface_button`+
 * `game.init_links_button`, `game.as:1278-1326`, ветка "Классика") —
 * открываются `getURL(url, "_blank")` в оригинале, реализация — см.
 * `liveApp.ts`. "Магазин" — это атрибут `CABINET`: подтверждено
 * владельцем напрямую + перепроверено живым сетевым ответом
 * `address.php` (порядок атрибутов `CABINET, analizator, ranking,
 * profile, forum, portal, GAMES` совпадает 1:1 с порядком кнопок на
 * экране МАГАЗИН/АНАЛИЗ/РЕЙТИНГ/ПРОФИЛЬ/ФОРУМ/ПОРТАЛ/ИГРЫ).
 *
 * Анализ/Игры — ОСТАЮТСЯ заглушками, сознательно не исследовались в
 * этом заходе (владелец, 2026-09-16: убрать из объёма).
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

export function MainNav() {
  const actions = hudActions.value;
  const [active, setActive] = useState<string | null>(null);

  if (!actions) return null;

  const items: NavItem[] = [
    { key: 'mail', label: 'Почта', onClick: actions.openMail },
    { key: 'cabinet', label: 'Личный кабинет', onClick: actions.openCabinet },
    { key: 'complex', label: 'Комплекс', onClick: actions.openComplex },
    { key: 'colonies', label: 'Мои колонии', onClick: actions.openColonies },
    { key: 'shop', label: 'Магазин', onClick: actions.openShop },
    { key: 'analize-stub', label: 'Анализ' },
    { key: 'ranking', label: 'Рейтинг', onClick: actions.openRanking },
    { key: 'profile', label: 'Профиль', onClick: actions.openProfile },
    { key: 'forum', label: 'Форум', onClick: actions.openForum },
    { key: 'portal', label: 'Портал', onClick: actions.openPortal },
    { key: 'games-stub', label: 'Игры' },
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
