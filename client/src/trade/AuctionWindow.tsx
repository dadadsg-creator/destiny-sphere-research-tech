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
 *
 * **Правка после живого показа владельцу (2026-09-18)**: три находки —
 * (1) кнопка раскрытия ветки рисовалась символами `▾`/`▸`, которые в
 * тестовом окружении показывались как обычный дефис (нет глифа в
 * используемом шрифте) — заменена на CSS-треугольник (`border`-приём,
 * не зависит от шрифта); (2) список юнитов/ресурсов был совсем без
 * графики ("это игра, нужна графика") — добавлены иконки
 * (`FilterTreeIcon`, см. `auctionData.ts`) для ресурсов/юнитов/размеров
 * артефактов/бонусов-ресурсов везде в дереве, ветка дерева
 * ограничена по высоте со скроллом (не разрастается на десятки юнитов);
 * (3) категория ARTEFACTS была сознательно пустой — найден и портирован
 * формат `art_filter_table` (`auctionData.ts:buildArtefactCategoryTree`),
 * дерево теперь полноценное (scope→размер/бонус).
 */
import { useMemo, useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import {
  resourceDisplayName,
  unitDisplayName,
  artefactDisplayName,
  unitIconUrl,
  resourceIconUrl,
  artefactIconUrl,
  artefactScopeMaskUrl,
  artefactSizeColor,
  splitArtefactLibname,
} from '../data/resourceIcons';
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
import type { LocalUnit, LocalArtefactScope, LocalSimpleEntry } from '../data/localData';

function lotLabel(lot: AuctionLot): string {
  if (lot.idCatOn === '1' && lot.libname) return resourceDisplayName(lot.libname);
  if (lot.idCatOn === '2' && lot.libname) return unitDisplayName(lot.libname);
  if (lot.idCatOn === '3' && lot.libname) return artefactDisplayName(lot.libname);
  return lot.libname ?? '';
}

/** Картинка лота — ресурс/юнит уже были, артефакт (3) добавлен по прямому запросу владельца ("нужна графика"), разбор prefix/scope/postfix — см. splitArtefactLibname(). */
function LotIcon({ lot }: { lot: AuctionLot }) {
  if (!lot.libname) return null;
  if (lot.idCatOn === '1') return <img class="ds-unit-icon" src={resourceIconUrl(lot.libname)} alt="" />;
  if (lot.idCatOn === '2') return <img class="ds-unit-icon" src={unitIconUrl(lot.libname)} alt="" />;
  if (lot.idCatOn === '3') {
    const { prefix, scope, postfix } = splitArtefactLibname(lot.libname);
    const maskUrl = artefactScopeMaskUrl(scope);
    return (
      <span class="ds-artefact-row__icon-wrap">
        {maskUrl && (
          <span
            class="ds-artefact-row__scope"
            style={{ '--scope-mask': `url(${maskUrl})`, '--size-color': artefactSizeColor(prefix) ?? undefined }}
          />
        )}
        <img class="ds-artefact-row__icon" src={artefactIconUrl(postfix)} alt="" />
      </span>
    );
  }
  return null;
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

/**
 * Плоский список ресурсов (RESOURCES-категория, "чем платить") — владелец
 * (2026-09-18): "нафига так место неоптимально тратить [вертикально]... не
 * галочками, а просто кликать по ресурсу". Горизонтальный ряд "фишек" с
 * картинкой, без чекбокса — сам клик по фишке переключает `stat`,
 * подсвеченная/яркая = выбрана, тусклая = нет. Применяется ТОЛЬКО когда
 * ВСЕ узлы группы — листья с иконкой (ресурсы); дерево юнитов/артефактов
 * (с вложенностью) и текстовые общие фильтры (без иконок) остаются
 * обычным вертикальным списком ниже.
 */
function FilterChipRow({ nodes, onToggle }: { nodes: FilterTreeNode[]; onToggle: (target: FilterTreeNode, stat: boolean) => void }) {
  return (
    <div class="ds-auction-filter__chips">
      {nodes.map((n) => (
        <button
          key={n.id}
          type="button"
          class={`ds-auction-filter__chip${n.stat ? ' ds-auction-filter__chip--selected' : ''}`}
          title={n.name ?? n.id}
          onClick={() => onToggle(n, !n.stat)}
        >
          <NodeIcon icon={n.icon} />
          <span>{n.name ?? n.id}</span>
        </button>
      ))}
    </div>
  );
}

function FilterTreeGroup({ title, nodes, onChange, emptyHint }: FilterTreeGroupProps) {
  const allChecked = nodes.length > 0 && nodes.every(allLeavesChecked);
  const isChipRow = nodes.length > 0 && nodes.every((n) => (!n.children || n.children.length === 0) && n.icon);

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
          {isChipRow ? (
            <FilterChipRow nodes={nodes} onToggle={toggle} />
          ) : (
            <div class="ds-auction-filter__scroll">
              <ul class="ds-auction-filter__tree">
                {nodes.map((n) => (
                  <FilterTreeNodeView key={n.id} node={n} onToggle={toggle} />
                ))}
              </ul>
            </div>
          )}
        </>
      )}
    </div>
  );
}

/** Графика узла — см. FilterTreeIcon в auctionData.ts. */
function NodeIcon({ icon }: { icon: FilterTreeNode['icon'] }) {
  if (!icon) return null;
  if (icon.kind === 'img') return <img class="ds-auction-filter__icon" src={icon.url} alt="" />;
  if (icon.kind === 'swatch') return <span class="ds-auction-filter__swatch" style={{ background: icon.color }} />;
  if (icon.kind === 'scope' && icon.maskUrl) {
    return <span class="ds-auction-filter__scope-icon" style={{ '--scope-mask': `url(${icon.maskUrl})` }} />;
  }
  return null;
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
            aria-label={expanded ? 'Свернуть' : 'Развернуть'}
            class={`ds-auction-filter__expand${expanded ? ' ds-auction-filter__expand--open' : ''}`}
            onClick={(e) => {
              e.preventDefault();
              setExpanded(!expanded);
            }}
          />
        )}
        <input type="checkbox" checked={checked} onChange={(e) => onToggle(node, (e.target as HTMLInputElement).checked)} />
        <NodeIcon icon={node.icon} />
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
  localArtefactScopes: Map<string, LocalArtefactScope>;
  localArtefactSizes: Map<string, LocalSimpleEntry>;
  localArtefactBonuses: Map<string, LocalSimpleEntry>;
  /** Текущий запас игрока по ресурсу lot.tradeBy (для клиентской проверки, см. computeLotDisabled). */
  playerResourceByName: Map<string, number>;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onBuy: (lotId: string, price: number) => void;
  onApplyFilter: (cmd: AuctionFilterCommand) => void;
}

export function AuctionWindow({
  data,
  localUnits,
  localArtefactScopes,
  localArtefactSizes,
  localArtefactBonuses,
  playerResourceByName,
  tabs,
  onTabClick,
  onClose,
  onBuy,
  onApplyFilter,
}: AuctionWindowProps) {
  const { lots, categories, generalFilters, sortPlace, sortBuy, sortFieldOptions, activeSort } = data;

  const [filtersOpen, setFiltersOpen] = useState(false);

  const [selectedCategoryId, setSelectedCategoryId] = useState(
    () => categories.find((c) => c.cur)?.id ?? categories[0]?.id ?? '',
  );
  const resolveGroupName = (id: string) => resolveLocalName(localData.value, id);
  const buildTreeForCategory = (categoryId: string) =>
    buildCategoryTree(
      categories.find((c) => c.id === categoryId),
      localUnits,
      localArtefactScopes,
      localArtefactSizes,
      localArtefactBonuses,
      resolveGroupName,
    );
  const [categoryTree, setCategoryTree] = useState<FilterTreeNode[]>(() => buildTreeForCategory(selectedCategoryId));
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
    setCategoryTree(buildTreeForCategory(id));
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
                  <LotIcon lot={lot} />
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
