/**
 * Компактное главное меню поверх полноэкранной карты. Прежняя верхняя
 * строка полностью убрана: одна ненавязчивая кнопка раскрывает только
 * сервисные разделы. Переходы «Комплекс» и «Мои колонии» не дублируются
 * здесь — они уже находятся в круговом меню. Почта доступна в нижнем
 * статус-баре, поэтому повторно здесь тоже не показывается.
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

interface NavSection {
  label: string;
  items: NavItem[];
}

export function MainNav() {
  const actions = hudActions.value;
  const [open, setOpen] = useState(false);
  const [active, setActive] = useState<string | null>(null);

  if (!actions) return null;

  const sections: NavSection[] = [
    {
      label: 'Система',
      items: [
        { key: 'cabinet', label: 'Личный кабинет', onClick: actions.openCabinet },
        { key: 'shop', label: 'Магазин', onClick: actions.openShop },
        { key: 'ranking', label: 'Рейтинг', onClick: actions.openRanking },
        { key: 'profile', label: 'Профиль', onClick: actions.openProfile },
        { key: 'forum', label: 'Форум', onClick: actions.openForum },
        { key: 'portal', label: 'Портал', onClick: actions.openPortal },
      ],
    },
    {
      label: 'Аккаунт и сервисы',
      items: [
        { key: 'faq', label: 'FAQ', onClick: actions.openFaq },
        { key: 'billing', label: 'Биллинг', onClick: actions.openBilling },
        { key: 'premium', label: 'Премиум', onClick: actions.openPremiumInfo },
        { key: 'factories', label: 'Военные заводы', onClick: actions.openWarFactories },
        { key: 'artefacts', label: 'Артефакты в зданиях', onClick: actions.openArtefactStat },
        { key: 'logout', label: 'Выход', onClick: actions.logout },
      ],
    },
  ];

  return (
    <nav class={`ds-hud-mainmenu${open ? ' is-open' : ''}`} aria-label="Главное меню">
      <button
        type="button"
        class="ds-hud-mainmenu__toggle"
        aria-expanded={open}
        aria-label={open ? 'Закрыть главное меню' : 'Открыть главное меню'}
        title={open ? 'Закрыть меню' : 'Меню'}
        onClick={() => setOpen((value) => !value)}
      >
        <svg viewBox="0 0 40 40" aria-hidden="true">
          <circle cx="20" cy="20" r="13" />
          <circle cx="20" cy="20" r="3" />
          <path d="M20 7v6M20 27v6M7 20h6M27 20h6" />
        </svg>
      </button>

      <div class="ds-hud-mainmenu__panel" aria-hidden={!open}>
        {sections.map((section) => (
          <div class="ds-hud-mainmenu__section" key={section.label}>
            <div class="ds-hud-mainmenu__caption">{section.label}</div>
            {section.items.map((item) => (
              <button
                key={item.key}
                type="button"
                class={`ds-hud-mainmenu__item${active === item.key ? ' is-active' : ''}${item.key === 'logout' ? ' is-danger' : ''}`}
                onClick={() => {
                  if (!item.onClick) return;
                  setActive(item.key);
                  setOpen(false);
                  item.onClick();
                }}
              >
                <span>{item.label}</span>
                <span class="ds-hud-mainmenu__mark" aria-hidden="true">›</span>
              </button>
            ))}
          </div>
        ))}
      </div>
    </nav>
  );
}
