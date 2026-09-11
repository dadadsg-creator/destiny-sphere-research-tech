/**
 * Отображение внутренних имён ресурсов/валют (см. combResData.ts) —
 * короткое русское название + путь к иконке. Иконки извлечены из
 * `client-assets/menu.lib/sprites/DefineSprite_*_<ИМЯ>/1.png` (FFDec-экспорт
 * `menu.lib.swf`, см. CLIENT_PORTING_PLAN.md «Экспорт графики») в
 * `public/assets/resources/`. Термины сверены с уже устоявшимися в порте
 * (ReincarnateWindow, RightBlock.tsx, main.tsx тестовые данные и т.д.).
 */
export const RESOURCE_NAME_RU: Record<string, string> = {
  KADERIUM: 'Кадериум',
  NANO_CRYSTAL: 'Нано-кристаллы',
  PRODIUM: 'Продиум',
  FOOD: 'Еда',
  STASIS_ENERGY: 'Энергия',
  WORKER: 'Рабочие',
  SCIENTIST: 'Учёные',
  ARCHEOLOGIST: 'Археологи',
  GOLD_CREDIT: 'Золото',
  SILVER_CREDIT: 'Серебро',
};

/**
 * Однобуквенные коды из `<b K="" N="" S="" F="" P="" W=""/>` и подобных
 * (buildMenuData.ts, buildInfoData.ts, LocalBuilding.attrs и т.д.) —
 * сверено с buildMenuData.ts:computeBuildAllowed() (S=STASIS_ENERGY,
 * P=PRODIUM, не "Silver"/"Population").
 */
export const RESOURCE_LETTER_TO_NAME: Record<string, string> = {
  K: 'KADERIUM',
  N: 'NANO_CRYSTAL',
  S: 'STASIS_ENERGY',
  F: 'FOOD',
  P: 'PRODIUM',
  W: 'WORKER',
};

export function resourceIconUrl(name: string): string {
  return `/assets/resources/${name}.png`;
}

/**
 * Иконка боевого юнита — `public/assets/units/<unitname>.png`, уже
 * извлечена в более ранней фазе из `war_units.lib.swf` (`game.getLibrary(
 * 2, race)` → "unitlib") и используется PixiJS battle-рендерером
 * (`battle/battleRenderer.ts`) — тот же путь, тот же набор ~95 машинных
 * имён, переиспользуем здесь как обычный `<img>` для `TrainUnitWindow`/
 * `ArmyCreationWindow` (раньше юниты показывались без картинки вовсе).
 */
export function unitIconUrl(unitname: string): string {
  return `/assets/units/${unitname}.png`;
}

/**
 * Иконка артефакта — извлечена из `client-assets/artefact.lib/sprites/
 * DefineSprite_*_<POSTFIX>/1.png`, ключ — ИМЕННО `postfix` артефакта
 * (`<artefact prefix="" scope="" postfix=""/>`), не полный libname:
 * иконка одна на "базовый вид" артефакта, префикс/scope — модификаторы
 * силы/редкости, картинку не меняют (проверено на STASIS_ENERGY_EGG —
 * иконка есть только по постфиксу, не по полному "MIGHTY_SPHERE_..."-имени).
 */
export function artefactIconUrl(postfix: string): string {
  return `/assets/artefacts/${postfix}.png`;
}

/**
 * Картинка артефакта — ТРИ слоя в оригинале (найдено декомпиляцией
 * `artefact.lib.swf`/`frame_1/DoAction.as:createArtefact`, 2026-09-09;
 * см. подробный докстринг `buildArtefactData.ts`): `art_clear` (фон
 * слота, круглая тёмная окантовка) снизу, `SCOPE_MOV` (форма-маска по
 * `scope`, красится СПЛОШНЫМ цветом по `prefix`/размеру через Flash
 * `Color.setRGB()`) посередине, `TYPE_MOV` (сама иконка по `postfix`,
 * `artefactIconUrl()`) сверху. `BuildArtefactWindow.tsx` использует
 * только 2 верхних слоя — `art_clear` сознательно убран (см. докстринг
 * того файла), поэтому здесь нет отдельной константы под него. Живьём
 * подтверждён топ-тир: SUPREME/"Колоссальная" → 0x9900FF (фиолетовый),
 * ровно как в реальном скриншоте владельца.
 */

/**
 * `arr_size` из `artefact.lib.swf` (`ds/flash/`-копия, новее и полнее
 * версии в `SPHERE_client/` — там нет GIANT). `null` = "NO" в оригинале
 * (SILVER/NORMAL — без перекраски, натуральный цвет формы). Прочерк
 * (значение не задано в оригинале вообще — AVERAGE/UNIQUE/COMMON и т.д.
 * отсутствуют в обеих версиях `arr_size`) — не выдумываем цвет, просто
 * не красим (см. `artefactSizeColor()`).
 */
export const ARTEFACT_SIZE_COLOR: Record<string, string | null> = {
  REDS: '#FF2080',
  PAID: '#49FF39',
  SMALL: '#9FFF40',
  MINOR: '#CCCCCC',
  AVERAGE: '#FFE700',
  MAJOR: '#FF9900',
  GREAT: '#FF0000',
  SUPERIOR: '#9900FF',
  MIGHTY: '#FF9900',
  OUTSTANDING: '#FF0000',
  SUPREME: '#9900FF',
  WHITE: '#FFFFFF',
  GOLD: '#FFCC00',
  GIANT: '#22CCFF',
  SILVER: null,
  NORMAL: null,
};

export function artefactSizeColor(prefix: string | null): string | null {
  if (!prefix) return null;
  return ARTEFACT_SIZE_COLOR[prefix] ?? null;
}

/**
 * Маска формы scope-рамки — извлечена из `client-assets/ArtColors/
 * sprites/DefineSprite_*_<SCOPE>/1.png` (та же `ArtColors.swf`, что и
 * `art_clear`). Известны машинные имена 11 scope, реально используемых
 * на сервере (`ds_artefactscope.scopename`, живая БД, 2026-09-09):
 * BUILDING, COMB, COMBCOMPLEX, WAR, ALLIANCE, SPHERE, ICE, HOLIDAY,
 * HOLIDAY2, QUEST, LOVE — для ICE/HOLIDAY/HOLIDAY2 подходящей формы в
 * `ArtColors.swf` не нашлось (возможно другая библиотека/добавлены
 * позже) — для них, как и для неизвестных scope, рамка не рисуется
 * (`null`), а не подставляется случайная форма.
 */
const KNOWN_ARTEFACT_SCOPES = new Set([
  'BUILDING',
  'COMB',
  'COMBCOMPLEX',
  'WAR',
  'ALLIANCE',
  'SPHERE',
  'QUEST',
  'LOVE',
  'MEGA',
  'RING',
  'RELIGION',
  'RACE',
  'IMMEDIATE',
]);

export function artefactScopeMaskUrl(scope: string | null): string | null {
  if (!scope || !KNOWN_ARTEFACT_SCOPES.has(scope)) return null;
  return `/assets/artefact-scopes/${scope}.png`;
}

/**
 * Иконки веток науки — извлечены из `client-assets/tree_scients_tree/
 * sprites/DefineSprite_*_<ИМЯ>/1.png` (та самая библиотека
 * `tree_scients/tree.as` — визуальное дерево науки, упомянутая, но не
 * портированная в `researchData.ts`; сам набор иконок 23×23,
 * консистентный, годится и без дерева). 5 из 7 веток совпадают с
 * обычными именами ресурсов (KADERIUM/NANO_CRYSTAL/STASIS_ENERGY/
 * PRODIUM/FOOD) — сознательно НЕ переиспользуем `resourceIconUrl()` для
 * них: у `tree_scients_tree` свой визуальный стиль, смешивать с общим
 * набором `menu.lib` было бы непоследовательно. Ветки TRADE/SPYING
 * (`branchid`) отдельных иконок в `menu.lib` не имеют вовсе — только
 * здесь.
 */
const RESEARCH_BRANCH_NAMES: Record<string, string> = {
  KADERIUM: 'Кадериум',
  NANO_CRYSTAL: 'Нанокристаллы',
  STASIS_ENERGY: 'Энергия',
  PRODIUM: 'Продиум',
  FOOD: 'Еда',
  TRADE: 'Торговля',
  SPYING: 'Шпионаж',
};

export function researchBranchName(branchId: string): string {
  return RESEARCH_BRANCH_NAMES[branchId] ?? branchId;
}

export function researchBranchIconUrl(branchId: string): string | null {
  if (!(branchId in RESEARCH_BRANCH_NAMES)) return null;
  return `/assets/research/${branchId}.png`;
}
