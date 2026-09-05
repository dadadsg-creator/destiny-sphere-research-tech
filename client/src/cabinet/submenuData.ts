/**
 * Универсальный «протокол форм» личного кабинета — Фаза 3.14, движок
 * `MN_Cabinet`, см. docs/client-economy-social-ui.md §5.1-5.2. Сервер
 * присылает XML-описание произвольной формы (типизированные поля +
 * кнопки, каждая кнопка знает СВОЙ поднабор полей для отправки), клиент
 * строит UI без какой-либо специфичной для конкретной формы логики.
 * Портировано 1:1 из `parserCabinetData.as:104-354` (`parseSubMenu`/
 * `parseForm`/`parse*`) и `subMenuEngine.as` (виджеты/отправка).
 *
 * <cabinetRequest command="callsubmenu" menu="<id>"/>
 * → <cabinet action="submenuopen"><form idmenu=".." title=".." closebut="0|1">
 *      <textfield id=".." .../>текст</textfield>
 *      <addrcomb id=".." .../>
 *      <combobox id=".." defIndex=".."><list id=".." val=".."/>...</combobox>
 *      <button id=".." label=".." data="id1,id2" [confirm=".."]/>
 *      <amount id=".." min=".." max=".." def=".."/>
 *      <inputtext id=".." [maxchars=".."] [allowletters=".."]>текст</inputtext>
 *      <radio id=".."><point id=".." label=".." cur="0|1"/><br/>...</radio>
 *      <br/>
 *      <hidden id=".." data=".."/>
 *    </form></cabinet>
 * → <cabinetRequest command="confirmsubmenu" button="<id>" menu="<idmenu>"
 *                   <fieldId1>="<value1>" .../>
 *
 * **Найдено и подтверждено (см. доклад §5.2): атрибуты `x`/`y`/`width`/
 * `height` парсятся для всех 8 типов, но `subMenuEngine.positionElement()`
 * их не читает вообще** — реальное позиционирование всегда через
 * автоматический flow-layout (перенос строки по ширине окна, либо явный
 * `<br/>`). Здесь `x`/`y`/`height` НЕ портированы вовсе (не нужны для
 * HTML-раскладки — flexbox/wrap делает то же самое естественно), только
 * `width` сохранён (изредка переиспользуется для inputtext/combobox).
 */
export type SubmenuElementType = 'addrcomb' | 'textfield' | 'combobox' | 'button' | 'amountf' | 'inputtext' | 'radio' | 'br' | 'hidden';

export interface TextFieldElement {
  type: 'textfield';
  id: string;
  text: string;
}

export interface AddrCombElement {
  type: 'addrcomb';
  id: string;
}

export interface ComboBoxOption {
  id: string;
  ln: string;
}

export interface ComboBoxElement {
  type: 'combobox';
  id: string;
  defIndex: number;
  options: ComboBoxOption[];
}

export interface ButtonElement {
  type: 'button';
  id: string;
  label: string;
  /** id полей формы, чьи значения отправляются именно при нажатии ЭТОЙ кнопки. */
  fieldIdsForSend: string[];
  confirmText: string | null;
}

export interface AmountElement {
  type: 'amountf';
  id: string;
  min: number;
  max: number;
  def: number;
}

export interface InputTextElement {
  type: 'inputtext';
  id: string;
  defValue: string;
  maxChars: number;
  /** Regex-подобный набор разрешённых символов (Flash `restrict`), пусто — без ограничений. */
  restrict: string;
}

export interface RadioPoint {
  id: string;
  label: string;
  cur: boolean;
}

export interface RadioElement {
  type: 'radio';
  id: string;
  points: RadioPoint[];
  /** true — хотя бы одна точка пришла с cur="1" (см. getDefaultRadioSelection). */
  curDetected: boolean;
}

export interface BrElement {
  type: 'br';
}

export interface HiddenElement {
  type: 'hidden';
  id: string;
  data: string;
}

export type SubmenuElement =
  | TextFieldElement
  | AddrCombElement
  | ComboBoxElement
  | ButtonElement
  | AmountElement
  | InputTextElement
  | RadioElement
  | BrElement
  | HiddenElement;

export interface SubmenuFormData {
  idmenu: string;
  titleWindow: string;
  closeButtonEnabled: boolean;
  elements: SubmenuElement[];
}

function parseRadio(el: Element): RadioElement {
  const points: RadioPoint[] = [];
  let curDetected = false;
  for (const child of Array.from(el.children)) {
    if (child.nodeName === 'point') {
      const isCur = Number(child.getAttribute('cur')) === 1;
      /** DataRadio.as:16-31 — только ПЕРВАЯ точка с cur="1" реально отмечается, остальные последующие cur="1" игнорируются. */
      points.push({ id: child.getAttribute('id') ?? '', label: child.getAttribute('label') ?? '', cur: isCur && !curDetected });
      if (isCur && !curDetected) curDetected = true;
    }
  }
  return { type: 'radio', id: el.getAttribute('id') ?? '', points, curDetected };
}

/** subMenuEngine.as:362-371 — если ни одна точка не пришла с cur=1, выбирается первая точка списка. */
export function getDefaultRadioSelection(radio: RadioElement): string {
  const marked = radio.points.find((p) => p.cur);
  if (marked) return marked.id;
  return radio.points[0]?.id ?? '';
}

function parseElement(el: Element): SubmenuElement | null {
  switch (el.nodeName) {
    case 'textfield':
      return { type: 'textfield', id: el.getAttribute('id') ?? '', text: el.textContent ?? '' };
    case 'addrcomb':
      return { type: 'addrcomb', id: el.getAttribute('id') ?? '' };
    case 'combobox': {
      const options: ComboBoxOption[] = Array.from(el.children)
        .filter((c) => c.nodeName === 'list')
        .map((c) => ({ id: c.getAttribute('id') ?? '', ln: c.getAttribute('val') ?? '' }));
      return { type: 'combobox', id: el.getAttribute('id') ?? '', defIndex: Number(el.getAttribute('defIndex')) || 0, options };
    }
    case 'button':
      return {
        type: 'button',
        id: el.getAttribute('id') ?? '',
        label: el.getAttribute('label') ?? '',
        fieldIdsForSend: (el.getAttribute('data') ?? '').split(',').filter(Boolean),
        confirmText: el.getAttribute('confirm'),
      };
    case 'amount':
      return {
        type: 'amountf',
        id: el.getAttribute('id') ?? '',
        min: Number(el.getAttribute('min')) || 0,
        max: Number(el.getAttribute('max')) || 0,
        def: Number(el.getAttribute('def')) || 0,
      };
    case 'inputtext': {
      const maxcharsAttr = el.getAttribute('maxchars');
      return {
        type: 'inputtext',
        id: el.getAttribute('id') ?? '',
        defValue: el.textContent ?? '',
        maxChars: maxcharsAttr !== null ? Number(maxcharsAttr) : -1,
        restrict: el.getAttribute('allowletters') ?? '',
      };
    }
    case 'radio':
      return parseRadio(el);
    case 'br':
      return { type: 'br' };
    case 'hidden':
      return { type: 'hidden', id: el.getAttribute('id') ?? '', data: el.getAttribute('data') ?? '' };
    default:
      return null;
  }
}

export function parseSubmenuForm(formEl: Element): SubmenuFormData {
  const elements: SubmenuElement[] = [];
  for (const child of Array.from(formEl.children)) {
    const parsed = parseElement(child);
    if (parsed) elements.push(parsed);
  }

  return {
    idmenu: formEl.getAttribute('idmenu') ?? '',
    titleWindow: formEl.getAttribute('title') ?? '',
    closeButtonEnabled: formEl.getAttribute('closebut') === '1' || formEl.getAttribute('closebut') === 'true',
    elements,
  };
}

/** parserCabinetData.as:104-118 — вложенный <form> внутри <cabinet action="submenuopen">, см. cabinetMainData.ts. */
export function parseSubmenuFormFromCabinet(root: Element): SubmenuFormData | null {
  for (const child of Array.from(root.children)) {
    if (child.nodeName === 'form') return parseSubmenuForm(child);
  }
  return null;
}

/** requestDataCabinet.as:14-17 — <cabinetRequest command="callsubmenu" menu=""/>. */
export function buildOpenSubmenuCommand(menuId: string): { command: 'callsubmenu'; menu: string } {
  return { command: 'callsubmenu', menu: menuId };
}

/** requestDataCabinet.as:39-49 subMenuAction() — <cabinetRequest command="confirmsubmenu" button="" menu=""  id1="v1" .../>. */
export function buildSubmenuActionCommand(
  idmenu: string,
  buttonId: string,
  values: Record<string, string>,
): { command: 'confirmsubmenu'; menu: string; button: string; values: Record<string, string> } {
  return { command: 'confirmsubmenu', menu: idmenu, button: buttonId, values };
}
