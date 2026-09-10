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
                  {buildingname && <img class="ds-building-icon" src={buildingAssetPath(buildingname, race)} alt="" />}
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
