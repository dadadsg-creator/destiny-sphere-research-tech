/**
 * Основная навигация (docs/main_screen.md §6 указывал 11 разделов, было
 * так же реализовано изначально). Владелец, 2026-09-17: "анализ и игры
 * вовсе убери, мы их не будем переносить" — не "пока не подключены", а
 * осознанное решение не портировать эти 2 раздела вообще, поэтому их
 * здесь больше нет (не заглушки с замком, а отсутствующие пункты) —
 * итого 9 разделов, все реально подключены.
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
 * экране МАГАЗИН/АНАЛИЗ/РЕЙТИНГ/ПРОФИЛЬ/ФОРУМ/ПОРТАЛ/ИГРЫ — "Анализ" и
 * "Игры" среди них тоже реально существовали, просто сознательно не
 * переносятся).
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
    { key: 'ranking', label: 'Рейтинг', onClick: actions.openRanking },
    { key: 'profile', label: 'Профиль', onClick: actions.openProfile },
    { key: 'forum', label: 'Форум', onClick: actions.openForum },
    { key: 'portal', label: 'Портал', onClick: actions.openPortal },
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
