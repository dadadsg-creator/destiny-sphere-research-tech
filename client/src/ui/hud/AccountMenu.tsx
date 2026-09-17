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
 * Логика всех 6 подключённых кнопок — см. докстринги `hudActionsStore.ts`
 * (`openBilling`/`openPremiumInfo`/`openFaq`/`logout`/`openWarFactories`/
 * `openArtefactStat`), сверено построчно с `game.as`/`help_class.as`/
 * `reqvestdata.as`.
 *
 * Два premium-переключателя ("Загрузка военных заводов"/"Отображение
 * артефактов в зданиях") — первый заход ошибочно решил, что источника
 * нет вообще (искал ровно эти слова в `sota.as`/`game.as`). Оказалось:
 * команды `<getwarfactory/>`/`<arthint/>` (`reqvestdata.as:843-852`) и
 * их полные парсер+окно (`WarStatisticWindow.tsx`/`ArtefactStatWindow.tsx`)
 * уже существовали в порте с более ранней фазы, зарегистрированы в
 * `dispatch.tsx`, просто ничто их не вызывало — владелец справедливо
 * поправил ("это обычный функционал, плохо искал").
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
          title="Загрузка военных заводов"
          aria-label="Загрузка военных заводов"
          onClick={actions.openWarFactories}
        >
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true">
            <path
              d="M3 18 V10 L8 13 V10 L13 13 V10 L18 13 V6 L21 8 V18 Z"
              stroke="currentColor"
              stroke-width="1.6"
              stroke-linejoin="round"
              fill="none"
            />
          </svg>
        </button>
        <button
          type="button"
          class="ds-hud-account__toggle"
          title="Отображение артефактов в зданиях"
          aria-label="Отображение артефактов в зданиях"
          onClick={actions.openArtefactStat}
        >
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true">
            <path
              d="M12 3 L14.2 9.3 L21 12 L14.2 14.7 L12 21 L9.8 14.7 L3 12 L9.8 9.3 Z"
              stroke="currentColor"
              stroke-width="1.6"
              stroke-linejoin="round"
              fill="none"
            />
          </svg>
        </button>
      </div>
    </div>
  );
}
