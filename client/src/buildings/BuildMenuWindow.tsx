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
 */
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import { computeBuildAllowed, type BuildMenuEntry, type PlayerResourcePoolFull } from './buildMenuData';
import type { LocalBuilding } from '../data/localData';

export interface BuildMenuWindowProps {
  entries: BuildMenuEntry[];
  localBuildings: Map<number, LocalBuilding>;
  resources: PlayerResourcePoolFull;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onBuild: (id: number) => void;
}

export function BuildMenuWindow({
  entries,
  localBuildings,
  resources,
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
            <th>Здание</th>
            <th>K/N/S/F/P/W</th>
            <th>GC/SC</th>
            <th />
          </tr>
        </thead>
        <tbody>
          {entries.map((entry) => {
            const local = localBuildings.get(entry.id);
            const allowed = computeBuildAllowed(entry, local, resources);
            const attrs = local?.attrs ?? {};
            return (
              <tr key={entry.id}>
                <td>{local?.ln ?? entry.id}</td>
                <td>
                  {attrs.K ?? '0'}/{attrs.N ?? '0'}/{attrs.S ?? '0'}/{attrs.F ?? '0'}/{attrs.P ?? '0'}/
                  {attrs.W ?? '0'}
                </td>
                <td>
                  {entry.goldCredit}/{entry.silverCredit}
                </td>
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
