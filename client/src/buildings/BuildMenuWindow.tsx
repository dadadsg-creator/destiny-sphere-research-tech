/**
 * Окно «Список построек» — build_menu.as, см.
 * docs/client-economy-social-ui.md §1.7. Данные — buildMenuData.ts
 * (parseListBuild, computeBuildAllowed) + data/localData.ts
 * (LocalData.buildings — статические название/цена в K/N/S/F/P/W).
 *
 * Постройка недоступна, если computeBuildAllowed()==false — см. там же
 * категория «В» (нехватка ресурсов ИЛИ серверный запрет, если только
 * alwaysAllow не переопределяет). Денежная цена (GC/SC) реально
 * приходит живым атрибутом (в отличие от K/N/S/F/P/W) — клиент рендерит
 * её всегда, даже если списание денег на сервере на момент этой ревизии
 * не реализовано до конца (см. находка в докладе, mechanics-buildings.md
 * §2.3) — порт это не чинит и не скрывает, просто показывает как есть.
 *
 * Стоимость — единый список иконок (ResourceValue/ResourceList,
 * ui/ResourceValue.tsx) вместо сырых чисел через слэш; нулевые ресурсы
 * не показываются.
 */
import { useRef, useState } from 'preact/hooks';
import { createPortal } from 'preact/compat';
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import { buildTimeSeconds, computeBuildAllowed, formatBuildTime, type BuildMenuEntry, type PlayerResourcePoolFull } from './buildMenuData';
import type { LocalBuilding } from '../data/localData';
import { RESOURCE_LETTER_TO_NAME } from '../data/resourceIcons';
import { ResourceList, type ResourceCost } from '../ui/ResourceValue';
import { BUILDING_NAMES, buildingAssetPath, type Race } from '../map/buildingTypes';

export interface BuildMenuWindowProps {
  entries: BuildMenuEntry[];
  localBuildings: Map<number, LocalBuilding>;
  resources: PlayerResourcePoolFull;
  /** Раса игрока — определяет, какая картинка здания показывается (см. map/buildingTypes.ts). */
  race: Race;
  /** Живой атрибут корня `<getbuildmenu turn="">` — секунды на один "ход" мира, нужен для расчёта времени постройки. */
  turn: string | null;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onBuild: (id: number) => void;
}

function buildCosts(attrs: Record<string, string>, entry: BuildMenuEntry): ResourceCost[] {
  const costs: ResourceCost[] = Object.entries(RESOURCE_LETTER_TO_NAME).map(([letter, name]) => ({
    name,
    value: Number(attrs[letter]) || 0,
  }));
  costs.push({ name: 'GOLD_CREDIT', value: entry.goldCredit }, { name: 'SILVER_CREDIT', value: entry.silverCredit });
  return costs;
}

/**
 * Иконка здания + всплывающая подсказка при наведении —
 * `build_menu.as:154-155` (`text_info_build = "<b>"+name.toUpperCase()+
 * "</b>\n"+desc`, показывается через `game.init_decor_button(obj.win_build,
 * "ONLY_HINT_", ...)`). Оригинал переводит название в верхний регистр —
 * здесь сознательно НЕ повторяем (по просьбе владельца), обычный регистр
 * читается лучше.
 *
 * НЕ нативный атрибут `title` (была первая версия) — нативные тултипы
 * браузера не попадают в скриншот headless-браузера (рисуются вне render
 * tree), что не даёт визуально сверить результат со скриншотом
 * Flash-клиента, а не только текстом.
 *
 * НЕ просто CSS `:hover` + `position:absolute` внутри строки таблицы —
 * `.ds-window__content` (`window.css`) сознательно скроллится
 * (`overflow:auto`, список построек может быть длинным), а `overflow`
 * обрезает АБСОЛЮТНО позиционированных потомков по границе того же
 * контейнера независимо от `z-index`, если их containing block — тоже
 * внутри него. Подсказка для здания у нижних строк списка обрезалась
 * ровно на границе окна (проверено скриншотом до этого исправления).
 * Вместо этого — `createPortal` в `document.body` (`preact/compat`,
 * подмодуль уже установленного `preact`, отдельная зависимость не нужна)
 * с `position:fixed` и координатами, посчитанными от `getBoundingClientRect()`
 * иконки — не зависит от скролла/обрезки ни одного предка.
 */
function BuildingIcon({
  buildingname,
  race,
  local,
  fallbackId,
}: {
  buildingname: string;
  race: Race;
  local: LocalBuilding | undefined;
  fallbackId: number;
}) {
  const wrapRef = useRef<HTMLSpanElement>(null);
  const [pos, setPos] = useState<{ top: number; left: number } | null>(null);

  const show = () => {
    const rect = wrapRef.current?.getBoundingClientRect();
    if (!rect) return;
    const hintWidth = 320;
    const overflowsRight = rect.right + 10 + hintWidth > window.innerWidth;
    const left = overflowsRight ? rect.left - 10 - hintWidth : rect.right + 10;
    const top = Math.min(rect.top, Math.max(10, window.innerHeight - 330));
    setPos({ top, left });
  };

  const name = local?.ln ?? String(fallbackId);

  return (
    <span ref={wrapRef} class="ds-building-hint-wrap" onMouseEnter={show} onMouseLeave={() => setPos(null)}>
      <img class="ds-building-icon" src={buildingAssetPath(buildingname, race)} alt="" />
      {pos &&
        createPortal(
          <div class="ds-building-hint" style={{ top: `${pos.top}px`, left: `${pos.left}px` }}>
            <b>{name}</b>
            {local?.description && <div class="ds-building-hint__desc">{local.description}</div>}
          </div>,
          document.body,
        )}
    </span>
  );
}

export function BuildMenuWindow({
  entries,
  localBuildings,
  resources,
  race,
  turn,
  tabs,
  onTabClick,
  onClose,
  onBuild,
}: BuildMenuWindowProps) {
  return (
    <Window title="Список построек" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <table>
        <thead>
          <tr>
            <th />
            <th>Здание</th>
            <th>Стоимость</th>
            <th>Время</th>
            <th />
          </tr>
        </thead>
        <tbody>
          {entries.map((entry) => {
            const local = localBuildings.get(entry.id);
            const allowed = computeBuildAllowed(entry, local, resources);
            const attrs = local?.attrs ?? {};
            const buildingname = BUILDING_NAMES[entry.id];
            return (
              <tr key={entry.id}>
                <td>
                  {buildingname && <BuildingIcon buildingname={buildingname} race={race} local={local} fallbackId={entry.id} />}
                </td>
                <td>{local?.ln ?? entry.id}</td>
                <td>
                  <ResourceList costs={buildCosts(attrs, entry)} />
                </td>
                <td>{formatBuildTime(buildTimeSeconds(attrs, turn))}</td>
                <td>
                  <button type="button" class="ds-popup__btn" disabled={!allowed} onClick={() => onBuild(entry.id)}>
                    Построить
                  </button>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </Window>
  );
}
