/**
 * Магазин артефактов кабинета — `artefact_shop_engine.as`, см.
 * docs/client-economy-social-ui.md §5.5. Разбор mark `artefact_shop`
 * (`xmlparsers.as:1600-1664`, диспетчер `parser.as:1491-1493`).
 *
 * **Найдено: сопоставление фильтров — ПОЗИЦИОННОЕ, не по id**
 * (`checkFilter`, `artefact_shop_engine.as:389-402`): у каждого артефакта
 * `filter_data[0]`/`filter_data[1]` сравниваются с выбором первого/второго
 * комбобокса напрямую по индексу массива, а не по `filter_data[].id`.
 * Порядок `filter_data[]` определяется порядком XML-атрибутов
 * `filter*_*` на `<artefact>` (`xmlparsers.as:1644-1656`, перебор `for kk
 * in attributes`) — то есть напрямую зависит от того, в каком порядке
 * сервер перечислил атрибуты. Воспроизведено здесь так же позиционно —
 * не более и не менее хрупко, чем в оригинале.
 *
 * **Найден и сознательно воспроизведён баг обновления списка по
 * фильтру** (`createList()`, `artefact_shop_engine.as:404-410`):
 * условие пропуска пересборки списка использует `||` там, где по смыслу
 * нужно `&&` — `if (f1_unchanged || f2_unchanged) return;`. Из-за этого
 * если поменять ТОЛЬКО ОДИН из двух фильтров (обычный случай), второй
 * фильтр «не изменился» → условие истинно → список не перерисовывается
 * вообще, кнопка «Фильтр» становится молчаливым no-op. Список
 * действительно обновляется, только если поменять ОБА фильтра сразу на
 * новые значения. Это чисто клиентский рендер-баг (не портит данные на
 * сервере), воспроизведён 1:1 через `shouldSkipFilterRefresh()` ниже —
 * см. рассуждение в CLIENT_PORTING_PLAN.md о выборе "сохранить, не чинить"
 * для этого класса багов.
 */
import { parseXmlFragment } from '../protocol/response';

export interface ShopFilterOption {
  id: string;
  name: string;
}

export interface ShopFilter {
  id: string;
  listTypes: ShopFilterOption[];
}

export interface ShopArtefact {
  id: string;
  name: string;
  exp: string;
  icon: string;
  priceSilver: number;
  priceGold: number;
  bonusText: string;
  life: string;
  /** Позиционно: filterData[0]/filterData[1] — см. докстринг файла. */
  filterData: { id: string; value: string }[];
}

export interface ArtefactShopData {
  artefacts: ShopArtefact[];
  filters: ShopFilter[];
}

export function parseArtefactShop(xml: string): ArtefactShopData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'artefact_shop') {
    throw new Error(`Ожидался корневой тег <artefact_shop>, получено: ${root?.nodeName}`);
  }

  const artefacts: ShopArtefact[] = [];
  const filters: ShopFilter[] = [];

  for (const section of Array.from(root.children)) {
    if (section.nodeName === 'filters') {
      for (const filterEl of Array.from(section.children)) {
        if (filterEl.nodeName !== 'filter') continue;
        const listTypes: ShopFilterOption[] = Array.from(filterEl.children)
          .filter((i) => i.nodeName === 'item')
          .map((i) => ({ id: i.getAttribute('id') ?? '', name: i.getAttribute('name') ?? '' }));
        filters.push({ id: filterEl.getAttribute('id') ?? '', listTypes });
      }
    } else if (section.nodeName === 'artefact_list') {
      for (const artEl of Array.from(section.children)) {
        if (artEl.nodeName !== 'artefact') continue;

        const filterData: { id: string; value: string }[] = [];
        for (const attr of Array.from(artEl.attributes)) {
          if (attr.name.indexOf('filter') === 0) {
            filterData.push({ id: attr.name.split('_')[0], value: attr.value });
          }
        }

        artefacts.push({
          id: artEl.getAttribute('id') ?? '',
          name: artEl.getAttribute('name') ?? '',
          exp: artEl.getAttribute('exp') ?? '',
          icon: artEl.getAttribute('icon') ?? '',
          priceSilver: Number(artEl.getAttribute('price_s')) || 0,
          priceGold: Number(artEl.getAttribute('price_g')) || 0,
          bonusText: (artEl.getAttribute('bonus_text') ?? '').split('#').join('%'),
          life: artEl.getAttribute('life') ?? '',
          filterData,
        });
      }
    }
  }

  return { artefacts, filters };
}

/**
 * `icon` — полный libname артефакта (`prefix_scope_postfix`, тот же
 * формат, что и `BuildArtefact.libname` в `buildArtefactData.ts`), НЕ
 * готовый путь к файлу — подтверждено комментарием прямо в оригинале
 * (`artefact_shop_engine.as:665`: `//prefix="MINOR" scope="BUILDING"
 * postfix="FORGING">`, прямо над `getScope(icon)`/`getSize(icon)`/
 * `getName(icon)` — три геттера режут ОДНУ строку `icon` на три части).
 * Экранная иконка (`completeSampleArt()`, :662-680) — те же два слоя
 * (scope-маска + постфикс-картинка), что уже отрисовывает
 * `BuildArtefactWindow.tsx` через `artefactScopeMaskUrl`/
 * `artefactSizeColor`/`artefactIconUrl` из `resourceIcons.ts` — здесь
 * просто разбираем `icon` на те же три части и переиспользуем то же API.
 */
export function parseArtefactIcon(icon: string): { prefix: string | null; scope: string | null; postfix: string | null } {
  const parts = icon.split('_');
  if (parts.length < 3) return { prefix: null, scope: null, postfix: null };
  return { prefix: parts[0] ?? null, scope: parts[1] ?? null, postfix: parts.slice(2).join('_') };
}

/** artefact_shop_engine.as:389-402 checkFilter() — id=0 значит "Все" (без фильтра по этой оси). */
export function checkArtefactFilter(art: ShopArtefact, filter1Id: string, filter2Id: string): boolean {
  if (filter1Id !== '0' && art.filterData[0] !== undefined && art.filterData[0].value !== filter1Id) return false;
  if (filter2Id !== '0' && art.filterData[1] !== undefined && art.filterData[1].value !== filter2Id) return false;
  return true;
}

/**
 * artefact_shop_engine.as:404-410 createList() — БУКВАЛЬНО воспроизводит
 * найденный баг (|| вместо &&, см. докстринг файла). Возвращает true,
 * если пересборку списка нужно ПРОПУСТИТЬ (как в оригинале).
 */
export function shouldSkipFilterRefresh(current: { f1: string; f2: string }, newF1: string, newF2: string): boolean {
  return current.f1 === newF1 || current.f2 === newF2;
}

export interface CartEntry {
  artefactId: string;
  data: ShopArtefact;
  count: number;
}

/**
 * artefact_shop_engine.as:544-592 showInfoCache() — синхронизирует
 * корзину с текущими значениями степперов ВИДИМЫХ (отфильтрованных)
 * артефактов: существующие строки обновляют count, новые (count>0)
 * добавляются, count<=0 удаляются. Артефакты, скрытые текущим фильтром,
 * в корзине не трогаются (остаются как есть) — это осознанное поведение
 * "корзины", не баг.
 */
export function recomputeCart(cart: CartEntry[], visibleArtefacts: ShopArtefact[], amounts: Record<string, number>): CartEntry[] {
  const next = [...cart];

  for (const art of visibleArtefacts) {
    const count = amounts[art.id] ?? 0;
    const existingIndex = next.findIndex((e) => e.artefactId === art.id);

    if (existingIndex >= 0) {
      next[existingIndex] = { ...next[existingIndex], count };
    } else if (count > 0) {
      next.push({ artefactId: art.id, data: art, count });
    }
  }

  return next.filter((e) => e.count > 0);
}

export function computeCartTotal(cart: CartEntry[], concurrency: 'gold' | 'silver'): { count: number; totalPrice: number } {
  let count = 0;
  let totalPrice = 0;
  for (const entry of cart) {
    count += entry.count;
    totalPrice += entry.count * (concurrency === 'gold' ? entry.data.priceGold : entry.data.priceSilver);
  }
  return { count, totalPrice };
}

/**
 * requestDataCabinet.as:61-88 buyArtInShop() — **асимметрия протокола**:
 * это отдельный корневой тег `<buy_artefact_in_shop>`, НЕ обёрнутый в
 * `<cabinetRequest>`, в отличие от всех прочих команд кабинета.
 */
export function buildBuyArtefactsCommand(cart: CartEntry[], concurrency: 'gold' | 'silver') {
  return {
    rootTag: 'buy_artefact_in_shop' as const,
    artefacts: cart.map((e) => ({ id: e.data.id, count: e.count, life: e.data.life, concurency: concurrency })),
  };
}
