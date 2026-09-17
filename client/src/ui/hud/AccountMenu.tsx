/**
 * Круговое меню статус-бара, левый нижний угол (docs/main_screen.md,
 * не задокументировано отдельным номером параграфа) — владелец прислал
 * скриншот реального клиента (2026-09-17): кнопка выхода (значок
 * питания) отдельно сверху, дальше "диск" из БИЛЛИНГ/ПРЕМИУМ/SMS вокруг
 * центральной FAQ, и отдельно ещё две premium-кнопки ("Загрузка военных
 * заводов"/"Отображение артефактов в зданиях").
 *
 * Как и `RadialMenu.tsx` (круговое меню навигации, правый нижний угол) —
 * голая структура без градиентов/металлических рамок/самого дискового
 * SVG (см. докстринг того файла: "графика меню будет сделана отдельно
 * и подключена поверх готовой структуры позже"). У этого меню всего 2
 * рабочих сектора после удаления SMS — полноценный SVG-донат ради двух
 * клиньев не оправдан, здесь просто плоские круглые кнопки тем же
 * визуальным языком, что и остальной HUD (`.ds-hud-bottombar__icon-btn`).
 *
 * SMS — владелец явно попросил убрать совсем ("SMS нет, поэтому это
 * надо убрать"), не заглушка, ни одного упоминания в разметке.
 *
 * Логика всех 4 подключённых кнопок — см. докстринги `hudActionsStore.ts`
 * (`openBilling`/`openPremiumInfo`/`openFaq`/`logout`), сверено построчно
 * с `game.as`/`help_class.as`/`reqvestdata.as`.
 *
 * Два premium-переключателя ("Загрузка военных заводов"/"Отображение
 * артефактов в зданиях") — в декомпилированном AS2 не нашлось НИ ОДНОГО
 * упоминания ни в `game.as`, ни в `sota.as`, ни в `settingmenu.as`, ни в
 * `reqvestdata.as` (полный поиск, включая кириллические ключевые слова) —
 * похоже, что это логика, зашитая прямо в скомпилированный таймлайн
 * `interface.swf`, не попавшая в декомпил. Честные заглушки (замок),
 * не выдуманное поведение — ждём от владельца описания, что именно они
 * делают визуально на карте, прежде чем подключать.
 */
import { hudActions } from './hudActionsStore';

export function AccountMenu() {
  const actions = hudActions.value;
  if (!actions) return null;

  return (
    <div class="ds-hud-account">
      <button type="button" class="ds-hud-account__power" title="Выход" aria-label="Выход" onClick={actions.logout}>
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
          <path d="M12 4 V12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" />
          <path d="M7 6.5 A8 8 0 1 0 17 6.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" fill="none" />
        </svg>
      </button>

      <div class="ds-hud-account__dial">
        <button type="button" class="ds-hud-account__satellite" title="Биллинг" onClick={actions.openBilling}>
          Биллинг
        </button>
        <button type="button" class="ds-hud-account__center" title="FAQ" onClick={actions.openFaq}>
          FAQ
        </button>
        <button type="button" class="ds-hud-account__satellite" title="Премиум" onClick={actions.openPremiumInfo}>
          Премиум
        </button>
      </div>

      <div class="ds-hud-account__toggles">
        <button
          type="button"
          class="ds-hud-account__toggle"
          title="Загрузка военных заводов — не подключено, нет источника в декомпиле"
          disabled
        >
          🔒
        </button>
        <button
          type="button"
          class="ds-hud-account__toggle"
          title="Отображение артефактов в зданиях — не подключено, нет источника в декомпиле"
          disabled
        >
          🔒
        </button>
      </div>
    </div>
  );
}
