/**
 * Разбор ответа на `getNubHelp` (система обучающих подсказок «адвайзер»)
 * — Фаза 3.13, `adviceclass.as`, см. docs/client-economy-social-ui.md
 * §4.7. Портировано из `parser.as:323-362` (`advice_action`) — этот
 * разбор получает СЫРУЮ XML-строку `dat` из `obj.advice` (`parser.as:212`,
 * `if (obj.advice != undefined) { advice_action(obj.advice) }`) и парсит
 * её самостоятельно, в отличие от большинства других marks, которые уже
 * приходят раздиспетчеренным узлом через `case "тег":` в общем switch.
 *
 * **Имена дочерних тегов текста/подсказки-ссылки не установлены
 * достоверно** — оригинал ни разу не проверяет `nodeName`, а обращается
 * позиционно: `node.childNodes[0].firstChild.nodeValue` для текста
 * подсказки и `node.childNodes[z].childNodes[0].firstChild.nodeValue` для
 * имени связанной ссылки внутри `<art id="">`. Здесь эта позиционная
 * логика воспроизведена дословно (первый дочерний элемент → его
 * текстовое содержимое), а не заменена на поиск по названию тега — это
 * не менее (и не более) хрупко, чем в оригинале.
 *
 * **Реальный баг живого сервера (найден 2026-09-18 живым дампом)**:
 * ответ на `getNubHelp` иногда приходит БЕЗ закрывающего корневого тега
 * `</adv>` (сервер, по всей видимости, обрывает вывод сразу после
 * последнего `<art/>`, не дописывая закрывающий тег). AS2 `XML`-класс
 * оригинала терпим к этому; строгий браузерный `DOMParser('text/xml')`
 * вместо этого возвращает служебный документ-ошибку ("Unexpected end
 * of stream: still inside the root element") — сама подсказка (`txt`)
 * при этом заменяется текстом этой ошибки, хотя остальные поля
 * (`title`/`total`/`number`/список `<art>`) браузер по каким-то
 * причинам всё равно восстанавливает верно (что и маскировало баг —
 * заголовок и список подсказок в окне выглядели правильно, ломался
 * только сам текст). Правка на живом PHP не в рамках этого порта —
 * `ensureClosedRoot()` ниже дописывает недостающий закрывающий тег
 * перед парсингом, тот же класс обхода, что и у `arthint`
 * (`artefactStatData.ts:dedupeCellType`).
 */
import { parseXmlFragment } from '../protocol/response';
import { loadSetting, saveSetting } from './setting';

/** См. докстринг файла — обход бага сервера (недописанный закрывающий тег корня). */
function ensureClosedRoot(xml: string, rootTag: string): string {
  const trimmed = xml.trimEnd();
  const closeTag = `</${rootTag}>`;
  return trimmed.endsWith(closeTag) ? trimmed : `${trimmed}${closeTag}`;
}

export interface AdviceRelatedHint {
  id: string;
  name: string;
}

export interface AdviceHint {
  title: string;
  total: number;
  number: number;
  /** Спрайт здания «было», пусто если не задано (`obj.build != ""`). */
  build: string;
  /** Спрайт здания «станет», пусто если не задано. */
  tobuild: string;
  /** Позиция маркера на экране, 0-based (`surfMarker.gotoAndStop(surface+1)`). */
  surface: number;
  icon: string;
  /** HTML-текст подсказки. */
  txt: string;
  relatedHints: AdviceRelatedHint[];
}

export function parseAdviceHint(xml: string): AdviceHint {
  const doc = parseXmlFragment(ensureClosedRoot(xml, 'adv'));
  const root = doc.documentElement;
  if (!root) {
    throw new Error('Пустой ответ getNubHelp');
  }

  const firstChild = root.children[0];
  const txt = firstChild?.textContent ?? '';

  const relatedHints: AdviceRelatedHint[] = [];
  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'art') {
      const nameEl = el.children[0];
      relatedHints.push({ id: el.getAttribute('id') ?? '', name: nameEl?.textContent ?? '' });
    }
  }

  return {
    title: root.getAttribute('title') ?? '',
    total: Number(root.getAttribute('total')) || 0,
    number: Number(root.getAttribute('number')) || 0,
    build: root.getAttribute('build') ?? '',
    tobuild: root.getAttribute('tobuild') ?? '',
    surface: Number(root.getAttribute('surface')) || 0,
    icon: root.getAttribute('icon') ?? '',
    txt,
    relatedHints,
  };
}

/** adviceclass.as:227-234,239-244 switchAdvice()/substnick() — обе ветки шлют один и тот же запрос. */
export function buildGetNubHelpCommand(id: number): { id: number; stat: 'HINT' } {
  return { id, stat: 'HINT' };
}

/** adviceclass.as:229-232,242 — номер текущей подсказки сохраняется per-world (ключ "advhint", файл — имя текущего мира). */
export function saveAdviceHintNumber(worldName: string, hintId: number): void {
  saveSetting('advhint', hintId, worldName);
}

export function loadAdviceHintNumber(worldName: string): number | undefined {
  return loadSetting<number>('advhint', worldName);
}
