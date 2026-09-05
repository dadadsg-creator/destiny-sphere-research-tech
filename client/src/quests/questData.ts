/**
 * Разбор mark `questmenu` — журнал заданий игрока (Фаза 3.12,
 * `quest_menu_class.as`), источник: docs/client-economy-social-ui.md §4.3.
 * Портировано 1:1 из `xmlparsers.as:120-194` (`questMenuParser`/
 * `parseSampleQuest`, диспетчер `parser.as:1548-1550`).
 *
 * **Предположительно другая система, чем в mechanics-quests-events.md**
 * (см. доклад §4.3) — тот серверный документ описывает DTF-квиз/
 * «квестовый турнир»/новогодние раздачи, а не обычный журнал заданий;
 * не установлено, куда делась основная система квестов на сервере.
 *
 * **Подтверждённый баг несоответствия подписи вкладки и реально
 * показываемых данных — сознательно НЕ исправлен, воспроизведён 1:1**
 * (`quest_menu_class.as:333-352` собирает подписи вкладок в порядке
 * OLD_QUEST(id 0)/CURRENT_QUEST(id 1)/NEW_QUEST(id 2), но
 * `showListQuest` (`:112-134`) рисует id 0→`current_quest`, id 1→
 * `new_quest`, id 2→`finished_quest` — подпись всегда на шаг «моложе»
 * реально показанных данных). См. `getQuestListForTab()`/`QUEST_TAB_LABELS`
 * ниже — это баг протокольного уровня (несовпадение маппинга сервер↔UI),
 * а не UI-ввода, поэтому по методологии этой сессии сохраняется, а не
 * тихо чинится.
 *
 * **Подтверждённая недописанная функциональность — детальное окно
 * квеста не показывает описание** (`quest_menu_class.as:281-303`
 * `createDetailText`/`textToArrayObject` парсят `data.desc` как
 * псевдо-XML, но результат парсинга нигде не используется для
 * отрисовки — ни одного текстового поля с описанием в активном коде
 * нет). См. QuestDetailPopup в QuestWindow.tsx — сознательно не рисует
 * описание, как и оригинал.
 */
import { parseXmlFragment } from '../protocol/response';

export interface QuestBonus {
  name: string;
  icon: string;
  value: string;
}

export interface QuestEntry {
  name: string;
  /** Как в оригинале — присутствует в данных, но нигде фактически не отображается (см. докстринг файла). */
  desc: string;
  bonus: QuestBonus[];
}

export interface QuestMenuData {
  currentQuest: QuestEntry[];
  newQuest: QuestEntry[];
  finishedQuest: QuestEntry[];
}

function parseSampleQuest(el: Element): QuestEntry {
  let desc = el.getAttribute('desc') ?? '';
  const bonus: QuestBonus[] = [];

  for (const child of Array.from(el.children)) {
    if (child.nodeName === 'bonuses') {
      for (const bonusEl of Array.from(child.children)) {
        if (bonusEl.nodeName === 'bonus') {
          bonus.push({
            name: bonusEl.getAttribute('name') ?? '',
            icon: bonusEl.getAttribute('icon') ?? '',
            value: bonusEl.getAttribute('value') ?? '',
          });
        }
      }
    } else if (child.nodeName === 'desc') {
      desc = child.textContent ?? '';
    }
  }

  return { name: el.getAttribute('name') ?? '', desc, bonus };
}

export function parseQuestMenu(xml: string): QuestMenuData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'questmenu') {
    throw new Error(`Ожидался корневой тег <questmenu>, получено: ${root?.nodeName}`);
  }

  const currentQuest: QuestEntry[] = [];
  const newQuest: QuestEntry[] = [];
  const finishedQuest: QuestEntry[] = [];

  for (const section of Array.from(root.children)) {
    const target =
      section.nodeName === 'current' ? currentQuest : section.nodeName === 'new' ? newQuest : section.nodeName === 'finished' ? finishedQuest : null;
    if (!target) continue;
    for (const questEl of Array.from(section.children)) {
      if (questEl.nodeName === 'quest') target.push(parseSampleQuest(questEl));
    }
  }

  return { currentQuest, newQuest, finishedQuest };
}

/** quest_menu_class.as:333-352 getArrayForTabs() — подписи вкладок (в порядке, как их видит игрок). */
export const QUEST_TAB_LABELS: { id: number; ln: string }[] = [
  { id: 0, ln: 'Старые' }, // OLD_QUEST
  { id: 1, ln: 'Текущие' }, // CURRENT_QUEST
  { id: 2, ln: 'Новые' }, // NEW_QUEST
];

/** quest_menu_class.as:112-134 showListQuest — реальный маппинг id вкладки → массив данных (сдвинут на 1 относительно подписи, см. докстринг файла). */
export function getQuestListForTab(data: QuestMenuData, tabId: number): QuestEntry[] {
  switch (tabId) {
    case 0:
      return data.currentQuest;
    case 1:
      return data.newQuest;
    case 2:
      return data.finishedQuest;
    default:
      return [];
  }
}

/** quest_menu_class.as:94-110 changeListQuest(undefined) — вкладка по умолчанию при открытии окна. */
export function getDefaultQuestTab(data: QuestMenuData): number {
  return data.newQuest.length > 0 ? 2 : 1;
}

/**
 * Не установлено достоверно: в `reqvestdata.as` нет отдельной функции для
 * открытия меню квестов, и строка "QuestMenu" нигде не встречается в
 * клиенте буквально — ни одной кнопки/триггера для этого окна найти не
 * удалось (в отличие от других доменов, где `tab=` строка подтверждена
 * дословно). Название таба здесь — предположение по аналогии с общим
 * паттерном `call_tabs(tab_call)` → `<getbuildmenu tab="<tab_call>"/>`
 * (`reqvestdata.as:399-416`), а не подтверждённый факт.
 */
export function buildOpenQuestMenuCommand(): { tab: 'QuestMenu' } {
  return { tab: 'QuestMenu' };
}
