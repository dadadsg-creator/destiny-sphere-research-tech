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
  NANO_CRYSTAL: 'Нанокристалл',
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
 * Рамка слота артефакта — в оригинале иконка это ДВА отдельных слоя
 * (`buildartefactclass.as:157-176`): `art_clear` (фон/рамка слота,
 * `attachMovie`, круглая тёмная окантовка) под низом, и сам артефакт
 * (`art_lib.createArtefact()`) поверх, отцентрован по рамке
 * (`pic_for._x = ... + obj._width/2`). У `art_clear` есть цветные
 * scope-варианты окантовки (`ArtColors/sprites/<SCOPE>`, например
 * "SPHERE"/"WAR"/"BUILDING") — они полупрозрачные цветовые оверлеи,
 * рассчитанные на blend-режим Flash, плоский PNG-экспорт даёт из них
 * почти белый пустой кадр — не воспроизводится в этой версии, используется
 * один универсальный `art_clear` для всех scope (сознательное упрощение).
 * Извлечена из `client-assets/ArtColors/sprites/DefineSprite_31_art_clear/1.png`.
 */
export const ARTEFACT_SLOT_FRAME_URL = '/assets/ui/artefact-slot-frame.png';
