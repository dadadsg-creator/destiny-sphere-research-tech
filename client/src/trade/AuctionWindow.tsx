/**
 * Окно «Аукцион» — auctionmenuclass.as, см.
 * docs/client-economy-social-ui.md §1.2. Данные — auctionData.ts
 * (parseAuction, computeLotDisabled).
 *
 * **Исправление (2026-09-11)**: лот-ресурс (`idCatOn === '1'`) и лот-юнит
 * (`idCatOn === '2'`) показывали сырое машинное имя (`NANO_CRYSTAL`/`TANK`)
 * вместо названия — исправлено через `resourceDisplayName()`/
 * `unitDisplayName()` (живая локализация из `loaddata`, см. `resourceIcons.ts`),
 * не выдуманные строки.
 *
 * **Исправление (2026-09-18)**: артефакты (3) тоже теперь локализованы —
 * `artefactDisplayName()` (`resourceIcons.ts`, та же каталожная
 * локализация — `LocalData.artefacts`, раньше считалась отдельной
 * неподключённой таблицей). Сам оригинал (`xmlparsers.as: auction_menu_parser`)
 * ни разу не резолвит `.ln` для лотов ВООБЩЕ (ни для одной категории —
 * только сырой `libname` в протоколе аукциона) — уже существующее
 * улучшение для категорий 1/2 читаемости ради, категория 3 теперь
 * получает то же самое, тем же приёмом.
 *
 * **Дерево фильтров (2026-09-18)** — см. подробный докстринг
 * `auctionData.ts` про три схемы категории "что купить" и формат
 * `ArrToStr`. UI — упрощённый (но семантически эквивалентный) чекбокс-
 * дерево с раскрытием и "выбрать всё" на каждом уровне, вместо
 * покадровой MovieClip-анимации оригинала (`window.as:5407+`,
 * `createTree`/`createListQWER`) — сама протокольная семантика (что
 * именно попадает в `select=`/`filter=`/`buy_by=`) сохранена буквально.
 * Категория ARTEFACTS сознательно даёт пустое дерево (не портирован
 * беспрецедентный текстовый формат `art_filter_table`, см. докстринг
 * auctionData.ts) — кнопка "Применить" в этом случае неактивна, как и
 * в оригинале при пустом дереве (`sendFilter()`'s `valid_key`).
 */
import { useMemo, useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import { resourceDisplayName, unitDisplayName, artefactDisplayName, unitIconUrl } from '../data/resourceIcons';
import { localData } from '../data/localDataStore';
import { resolveLocalName } from '../data/localNameResolver';
import {
  computeLotDisabled,
  serializeFilterTree,
  flagsToFilterTree,
  sortOptionsToFilterTree,
  buildCategoryTree,
  type AuctionLot,
  type AuctionData,
  type AuctionFilterCommand,
  type AuctionSortOption,
  type FilterTreeNode,
} from './auctionData';
import type { LocalUnit } from '../data/localData';

function lotLabel(lot: AuctionLot): string {
  if (lot.idCatOn === '1' && lot.libname) return resourceDisplayName(lot.libname);
  if (lot.idCatOn === '2' && lot.libname) return unitDisplayName(lot.libname);
  if (lot.idCatOn === '3' && lot.libname) return artefactDisplayName(lot.libname);
  return lot.libname ?? '';
}

function optionLabel(opt: AuctionSortOption): string {
  return opt.ln ?? resolveLocalName(localData.value, opt.id ?? '') ?? opt.id ?? '';
}

function allLeavesChecked(node: FilterTreeNode): boolean {
  if (!node.children || node.children.length === 0) return node.stat;
  return node.children.every(allLeavesChecked);
}

function cascadeStat(node: FilterTreeNode, stat: boolean): FilterTreeNode {
  return node.children ? { ...node, stat, children: node.children.map((c) => cascadeStat(c, stat)) } : { ...node, stat };
}

function toggleNode(nodes: FilterTreeNode[], target: FilterTreeNode, stat: boolean): FilterTreeNode[] {
  return nodes.map((n) => {
    if (n === target) return cascadeStat(n, stat);
    if (n.children) return { ...n, children: toggleNode(n.children, target, stat) };
    return n;
  });
}

interface FilterTreeGroupProps {
  title: string;
  nodes: FilterTreeNode[];
  onChange: (nodes: FilterTreeNode[]) => void;
  emptyHint?: string;
}

function FilterTreeGroup({ title, nodes, onChange, emptyHint }: FilterTreeGroupProps) {
  const allChecked = nodes.length > 0 && nodes.every(allLeavesChecked);

  function toggleAll(stat: boolean) {
    onChange(nodes.map((n) => cascadeStat(n, stat)));
  }

  function toggle(target: FilterTreeNode, stat: boolean) {
    onChange(toggleNode(nodes, target, stat));
  }

  return (
    <div class="ds-auction-filter__group">
      <div class="ds-auction-filter__group-title">{title}</div>
      {nodes.length === 0 ? (
        <div class="ds-auction-filter__empty">{emptyHint ?? 'Нет доступных вариантов'}</div>
      ) : (
        <>
          <label class="ds-auction-filter__row ds-auction-filter__row--all">
            <input type="checkbox" checked={allChecked} onChange={(e) => toggleAll((e.target as HTMLInputElement).checked)} />
            <span>Выбрать всё</span>
          </label>
          <ul class="ds-auction-filter__tree">
            {nodes.map((n) => (
              <FilterTreeNodeView key={n.id} node={n} onToggle={toggle} />
            ))}
          </ul>
        </>
      )}
    </div>
  );
}

function FilterTreeNodeView({ node, onToggle }: { node: FilterTreeNode; onToggle: (target: FilterTreeNode, stat: boolean) => void }) {
  const [expanded, setExpanded] = useState(true);
  const hasChildren = !!node.children && node.children.length > 0;
  const checked = hasChildren ? allLeavesChecked(node) : node.stat;

  return (
    <li class="ds-auction-filter__node">
      <label class="ds-auction-filter__row">
        {hasChildren && (
          <button
            type="button"
            class="ds-auction-filter__expand"
            onClick={(e) => {
              e.preventDefault();
              setExpanded(!expanded);
            }}
          >
            {expanded ? '▾' : '▸'}
          </button>
        )}
        <input type="checkbox" checked={checked} onChange={(e) => onToggle(node, (e.target as HTMLInputElement).checked)} />
        <span>{node.name ?? node.id}</span>
      </label>
      {hasChildren && expanded && (
        <ul class="ds-auction-filter__children">
          {node.children!.map((c) => (
            <FilterTreeNodeView key={c.id} node={c} onToggle={onToggle} />
          ))}
        </ul>
      )}
    </li>
  );
}

const SORT_DIRECTIONS: { id: 'UP' | 'DOWN'; label: string }[] = [
  { id: 'DOWN', label: '↓ по убыванию' },
  { id: 'UP', label: '↑ по возрастанию' },
];

export interface AuctionWindowProps {
  data: AuctionData;
  localUnits: Map<string, LocalUnit>;
  /** Текущий запас игрока по ресурсу lot.tradeBy (для клиентской проверки, см. computeLotDisabled). */
  playerResourceByName: Map<string, number>;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onBuy: (lotId: string, price: number) => void;
  onApplyFilter: (cmd: AuctionFilterCommand) => void;
}

export function AuctionWindow({ data, localUnits, playerResourceByName, tabs, onTabClick, onClose, onBuy, onApplyFilter }: AuctionWindowProps) {
  const { lots, categories, generalFilters, sortPlace, sortBuy, sortFieldOptions, activeSort } = data;

  const [filtersOpen, setFiltersOpen] = useState(false);

  const [selectedCategoryId, setSelectedCategoryId] = useState(
    () => categories.find((c) => c.cur)?.id ?? categories[0]?.id ?? '',
  );
  const resolveGroupName = (id: string) => resolveLocalName(localData.value, id);
  const [categoryTree, setCategoryTree] = useState<FilterTreeNode[]>(() =>
    buildCategoryTree(categories.find((c) => c.id === selectedCategoryId), localUnits, resolveGroupName),
  );
  const [generalTree, setGeneralTree] = useState<FilterTreeNode[]>(() => flagsToFilterTree(generalFilters));
  const [buyFromTree, setBuyFromTree] = useState<FilterTreeNode[]>(() => sortOptionsToFilterTree(sortBuy));

  const [scope, setScope] = useState(() => sortPlace.find((o) => o.cur)?.id ?? sortPlace[0]?.id ?? '');
  const [trader, setTrader] = useState(data.trader ?? '');

  const initialSlots = useMemo(() => {
    const slots: { field: string; direction: 'UP' | 'DOWN' }[] = [0, 1, 2].map((i) => ({
      field: activeSort[i]?.id ?? 'X',
      direction: 'DOWN' as const,
    }));
    return slots;
  }, [activeSort]);
  const [sortSlots, setSortSlots] = useState(initialSlots);

  function selectCategory(id: string) {
    setSelectedCategoryId(id);
    setCategoryTree(buildCategoryTree(categories.find((c) => c.id === id), localUnits, resolveGroupName));
  }

  const selectStr = serializeFilterTree(categoryTree);
  const filterStr = serializeFilterTree(generalTree);
  const buyByStr = serializeFilterTree(buyFromTree);
  const canApply = selectStr.length > 0 && filterStr.length > 0 && buyByStr.length > 0;

  function applyFilter() {
    if (!canApply) return;
    const sort = sortSlots.map((s) => `${s.field}|${s.direction}`).join(',');
    onApplyFilter({
      category: selectedCategoryId,
      page: Number(data.curPage) || 1,
      pageSize: Number(data.onPage) || 20,
      select: selectStr,
      filter: filterStr,
      buyBy: buyByStr,
      scope,
      sort,
      trader,
    });
  }

  return (
    <Window title="Аукцион" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <div class="ds-auction-filter">
        <button type="button" class="ds-popup__btn" onClick={() => setFiltersOpen(!filtersOpen)}>
          {filtersOpen ? 'Скрыть фильтр' : 'Фильтр'}
        </button>
        {filtersOpen && (
          <div class="ds-auction-filter__panel">
            {categories.length > 0 && (
              <div class="ds-auction-filter__tabs">
                {categories.map((c) => (
                  <button
                    key={c.id}
                    type="button"
                    class={`ds-auction-filter__tab${c.id === selectedCategoryId ? ' ds-auction-filter__tab--active' : ''}`}
                    onClick={() => selectCategory(c.id)}
                  >
                    {c.ln ?? resolveLocalName(localData.value, c.id) ?? c.id}
                  </button>
                ))}
              </div>
            )}

            <FilterTreeGroup
              title="Что купить"
              nodes={categoryTree}
              onChange={setCategoryTree}
              emptyHint="Для этой категории детальный фильтр пока не реализован — фильтрация по ней недоступна"
            />
            <FilterTreeGroup title="Общие фильтры" nodes={generalTree} onChange={setGeneralTree} />
            <FilterTreeGroup title="Чем платить" nodes={buyFromTree} onChange={setBuyFromTree} />

            <div class="ds-auction-filter__row-line">
              <label class="ds-auction-filter__field">
                <span>Область</span>
                <select class="ds-art-depot__select" value={scope ?? ''} onChange={(e) => setScope((e.target as HTMLSelectElement).value)}>
                  {sortPlace.map((o) => (
                    <option key={o.id} value={o.id ?? ''}>
                      {optionLabel(o)}
                    </option>
                  ))}
                </select>
              </label>
              <label class="ds-auction-filter__field">
                <span>Продавец</span>
                <input
                  type="text"
                  class="ds-art-depot__select"
                  value={trader}
                  onInput={(e) => setTrader((e.target as HTMLInputElement).value)}
                  placeholder="мин. 4 символа"
                />
              </label>
            </div>

            <div class="ds-auction-filter__sorts">
              {sortSlots.map((slot, i) => (
                <div key={i} class="ds-auction-filter__field">
                  <span>Сортировка {i + 1}</span>
                  <select
                    class="ds-art-depot__select"
                    value={slot.field}
                    onChange={(e) => {
                      const next = [...sortSlots];
                      next[i] = { ...next[i], field: (e.target as HTMLSelectElement).value };
                      setSortSlots(next);
                    }}
                  >
                    {sortFieldOptions.map((o) => (
                      <option key={o.id} value={o.id}>
                        {o.id === 'X' ? resolveLocalName(localData.value, 'SORT_CHOOSE') ?? 'Выбрать сортировку' : o.ln ?? o.id}
                      </option>
                    ))}
                  </select>
                  <select
                    class="ds-art-depot__select"
                    value={slot.direction}
                    onChange={(e) => {
                      const next = [...sortSlots];
                      next[i] = { ...next[i], direction: (e.target as HTMLSelectElement).value as 'UP' | 'DOWN' };
                      setSortSlots(next);
                    }}
                  >
                    {SORT_DIRECTIONS.map((d) => (
                      <option key={d.id} value={d.id}>
                        {d.label}
                      </option>
                    ))}
                  </select>
                </div>
              ))}
            </div>

            <button type="button" class="ds-popup__btn" disabled={!canApply} onClick={applyFilter}>
              Применить фильтр
            </button>
            {!canApply && <div class="ds-auction-filter__empty">Отметьте хотя бы один пункт в каждой группе выше</div>}
          </div>
        )}
      </div>

      <table>
        <thead>
          <tr>
            <th>Лот</th>
            <th>Кол-во</th>
            <th>Цена</th>
            <th>След. ставка</th>
            <th>Продавец</th>
            <th />
          </tr>
        </thead>
        <tbody>
          {lots.map((lot) => {
            const resourceCurrent = playerResourceByName.get(lot.tradeBy ?? '') ?? Number.MAX_SAFE_INTEGER;
            const disabled = computeLotDisabled(lot, resourceCurrent);
            const priceToBuy = lot.immediate === '1' ? lot.currentPrice : lot.nextPrice;
            return (
              <tr key={lot.id}>
                <td class="ds-artefact-row">
                  {lot.idCatOn === '2' && lot.libname && <img class="ds-unit-icon" src={unitIconUrl(lot.libname)} alt="" />}
                  <span>{lotLabel(lot)}</span>
                </td>
                <td>{lot.amount}</td>
                <td>{lot.currentPrice}</td>
                <td>{lot.nextPrice}</td>
                <td>{lot.seller}</td>
                <td>
                  <button
                    type="button"
                    class="ds-popup__btn"
                    disabled={disabled}
                    onClick={() => onBuy(lot.id!, priceToBuy)}
                  >
                    {lot.immediate === '1' ? 'Купить' : 'Сделать ставку'}
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
