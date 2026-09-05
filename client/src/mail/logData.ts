/**
 * Разбор mark `log` (общий попап отчёта/лога, `logwindow.as`) и фрагмента
 * `sysmsg` (очередь системных/боевых/шпионских сообщений, `sys_mess_menu.as`)
 * — Фаза 3.11, источник: docs/client-economy-social-ui.md §4.5.
 *
 * `log` портирован 1:1 из `xmlparsers.as:9-32` (`datawindow`, диспетчер
 * `parser.as:1530-1532`): `<title>`→заголовок окна, `<data>`→блок
 * `DATA_FIELD` (доп. сводка), `<text>`→основной прокручиваемый отчёт
 * `REPORT_FIELD` (порядок полей подтверждён `menuLibCode.as:5-18`
 * `create_log_window(titletext,datatext,text_)` →
 * `logwindow.as:71-107` `completeWindow(mov,title,titleData,textData)`).
 *
 * **Находка (см. docs §4.5)**: кнопка «Написать письмо этому игроку»
 * (`MAIL_TO`) в `logwindow.as` — мёртвый код. `completeWindow()`
 * вызывается там РОВНО четырьмя аргументами (`logwindow.as:68`), пятый
 * параметр `mail_to` никогда не передаётся → ветка `if (mail_to !=
 * undefined)` всегда ложна → кнопка ВСЕГДА рисуется притушенной
 * (`_alpha=50`) и без обработчика клика (`logwindow.as:104-106`). В
 * HTML-версии это сознательно воспроизведено как есть (реальное поведение,
 * увиденное игроками), а не «исправлено» — см. LogWindow.tsx.
 *
 * `sysmsg` портирован из `parser.as:494-548` (`sysmsg_action`) — только
 * разбор ОДНОЙ порции новых сообщений (три тега `<m>`/`<w>`/`<s>` →
 * сис./военные/шпионские), БЕЗ накопительной логики через `debug.link.
 * sys_msg`/`war_msg`/`spy_msg` и разделительных полос между порциями
 * (`needSpliterInSystemMessages`) — это часть постоянного игрового цикла
 * (Фаза 2), а не конкретно этого окна; `sys_mess_menu.as` сам по себе
 * получает уже готовый массив строк (`text_arr`) и просто их отображает.
 * `encodeTagMessages()` — порт дословный (`parser.as:474-492`).
 */
import { parseXmlFragment } from '../protocol/response';

export interface LogWindowData {
  title: string;
  titleData: string;
  textData: string;
}

export function parseLogWindow(xml: string): LogWindowData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'log') {
    throw new Error(`Ожидался корневой тег <log>, получено: ${root?.nodeName}`);
  }

  let title = '';
  let titleData = '';
  let textData = '';
  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'title') title = el.textContent ?? '';
    else if (el.nodeName === 'data') titleData = el.textContent ?? '';
    else if (el.nodeName === 'text') textData = el.textContent ?? '';
  }

  return { title, titleData, textData };
}

/** parser.as:474-492 — {фигурные скобки}→<угловые>, экранированный \, &quot;→". */
export function encodeTagMessages(text: string): string {
  let result = '';
  for (const ch of text) {
    if (ch === '{') result += '<';
    else if (ch === '}') result += '>';
    else if (ch === '\\') continue;
    else result += ch;
  }
  return result.split('&quot;').join('"');
}

export interface SysMsgBatch {
  sys: string[];
  war: string[];
  spy: string[];
}

/** parser.as:494-548 — одна порция <sysmsg><m/><w/><s/></sysmsg>, без накопительного состояния. */
export function parseSysMsg(xml: string): SysMsgBatch {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'sysmsg') {
    throw new Error(`Ожидался корневой тег <sysmsg>, получено: ${root?.nodeName}`);
  }

  const sys: string[] = [];
  const war: string[] = [];
  const spy: string[] = [];

  for (const el of Array.from(root.children)) {
    const text = encodeTagMessages(el.textContent ?? '');
    if (el.nodeName === 'm') sys.push(text);
    else if (el.nodeName === 'w') war.push(text);
    else if (el.nodeName === 's') spy.push(text);
  }

  return { sys, war, spy };
}

/** reqvestdata.as:110-116 — <getSpyMenu/> (запрос лога шпионажа из sys_mess_menu.as, кнопка гейтится type_win=="SPY" && gate==1). */
export function buildGetSpyMenuCommand(): Record<string, never> {
  return {};
}
