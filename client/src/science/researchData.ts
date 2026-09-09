/**
 * Разбор mark `research` — панель науки, 7 веток (Фаза 3.4,
 * `researchmenu.as`, источник: docs/client-economy-social-ui.md §4.1),
 * портировано 1:1 из `xmlparsers.as:3240-3315` (`research_menu`).
 *
 * <research turn="">
 *   <science branchid="" ln="" ir="" nextlev="" lev="" allow=""
 *            bonusall="" bonus="" C="" enddate=""/>   (7 штук, по одной
 *                                                       на ветку)
 *   <train work="" scientistmax="" scientistallow="" inprogress=""
 *          W="" N="" F="" C=""/>
 *   <MSG id="" titl="" head="" txt="" desc="" res="" avail="" price=""
 *        status="" rem=""/>   (повторяется)
 * </research>
 *
 * `<BRANCH>_<level>` — id для команд научного отдела, ПОДТВЕРЖДЁН кодом
 * (не только regex-эвристикой сервера): при СТАРТЕ исследования — целевой
 * уровень (`nextlev+1`), при ОТМЕНЕ — текущий уровень (`lev`), см.
 * `getStartResearchId`/`getCancelResearchId` ниже. `tree_scients/tree.as`
 * (493 строки) — чисто визуальный рендер дерева поверх этих же данных
 * (позиции/цвета movieclip'ов), без собственного сетевого формата;
 * `tree_scients/loader.as`/`setting.as` — общая инфраструктура (загрузка
 * ассетов, локальный persistence через тот же `setting`-класс, что и в
 * `window.as`), тоже не специфичны для науки. Все три сознательно не
 * портированы в этой версии (визуальное дерево — отдельная задача
 * полировки, как и 9-slice скин окна).
 */
import { parseXmlFragment } from '../protocol/response';
import { combResCurrent, type CombRes } from '../data/combResData';

export interface ScienceBranch {
  /** branchid — машинное имя ветки (KADERIUM/NANO_CRYSTAL/STASIS_ENERGY/PRODIUM/FOOD/TRADE/SPYING). */
  id: string;
  name: string | null;
  /** "ir" — роль не установлена по этому файлу (не описана в client-economy-social-ui.md §4.1). */
  ir: string | null;
  nextLevel: string | null;
  level: string | null;
  allow: string | null;
  /** Итоговый бонус (со знаком "%", "-" если не прислан). */
  bonusAll: string;
  /** Бонус от текущего исследуемого уровня (со знаком "%", "-" если не прислан). */
  bonus: string;
  /** Статичная оценка времени следующего уровня (в ходах), "-" если не прислано. */
  time: string;
  /** Если задано — исследование этой ветки уже идёт, показывать "Отменить", а не "Исследовать" (xmlparsers.as:3261). */
  inProgressUntil: string | null;
}

export interface ResearchTrainData {
  turn: string | null;
  work: number;
  scientistMax: number;
  /** Живой атрибут — до клиентского ужимания, см. computeScientistAllow(). */
  scientistAllowRaw: number;
  inProgress: number;
  /** Цена одного учёного: W (worker), N (nano), F (food), C (время). */
  priceWork: number;
  priceNano: number;
  priceFood: number;
  priceTime: number;
}

export interface ResearchMessage {
  id: string | null;
  titl: string | null;
  head: string | null;
  txt: string | null;
  desc: string | null;
  res: string | null;
  avail: string | null;
  price: string | null;
  status: string | null;
  rem: string | null;
}

export interface ResearchData {
  branches: ScienceBranch[];
  train: ResearchTrainData | null;
  messages: Record<string, ResearchMessage>;
}

export function parseResearch(xml: string): ResearchData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'research') {
    throw new Error(`Ожидался корневой тег <research>, получено: ${root?.nodeName}`);
  }

  const branches: ScienceBranch[] = [];
  let train: ResearchTrainData | null = null;
  const messages: Record<string, ResearchMessage> = {};

  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'science') {
      branches.push({
        id: el.getAttribute('branchid') ?? '',
        name: el.getAttribute('ln'),
        ir: el.getAttribute('ir'),
        nextLevel: el.getAttribute('nextlev'),
        level: el.getAttribute('lev'),
        allow: el.getAttribute('allow'),
        bonusAll: el.getAttribute('bonusall') !== null ? `${el.getAttribute('bonusall')}%` : '-',
        bonus: el.getAttribute('bonus') !== null ? `${el.getAttribute('bonus')}%` : '-',
        time: el.getAttribute('C') ?? '-',
        inProgressUntil: el.getAttribute('enddate'),
      });
    } else if (el.nodeName === 'train') {
      train = {
        turn: root.getAttribute('turn'),
        work: Number(el.getAttribute('work')) || 0,
        scientistMax: Number(el.getAttribute('scientistmax')) || 0,
        scientistAllowRaw: Number(el.getAttribute('scientistallow')) || 0,
        inProgress: Number(el.getAttribute('inprogress')) || 0,
        priceWork: Number(el.getAttribute('W')) || 0,
        priceNano: Number(el.getAttribute('N')) || 0,
        priceFood: Number(el.getAttribute('F')) || 0,
        priceTime: Number(el.getAttribute('C')) || 0,
      };
    } else if (el.nodeName === 'MSG') {
      const id = el.getAttribute('id') ?? '';
      messages[id] = {
        id: el.getAttribute('id'),
        titl: el.getAttribute('titl'),
        head: el.getAttribute('head'),
        txt: el.getAttribute('txt'),
        desc: el.getAttribute('desc'),
        res: el.getAttribute('res'),
        avail: el.getAttribute('avail'),
        price: el.getAttribute('price'),
        status: el.getAttribute('status'),
        rem: el.getAttribute('rem'),
      };
    }
  }

  return { branches, train, messages };
}

/** researchmenu.as:261 — id для старта исследования: "<ветка>_<целевой уровень>". */
export function getStartResearchId(branch: ScienceBranch): string {
  return `${branch.id}_${Number(branch.nextLevel) + 1}`;
}

/** researchmenu.as:354 — id для отмены: "<ветка>_<текущий уровень>" (БЕЗ +1, асимметрия с стартом — см. докстринг выше). */
export function getCancelResearchId(branch: ScienceBranch): string {
  return `${branch.id}_${branch.level}`;
}

export interface PlayerNanoFoodPool {
  nanoCrystal: number;
  food: number;
}

/** Марка combres (см. data/combResData.ts) → узкий срез, нужный computeScientistAllow()/ResearchWindow. */
export function combResToNanoFoodPool(res: CombRes): PlayerNanoFoodPool {
  return { nanoCrystal: combResCurrent(res, 'NANO_CRYSTAL'), food: combResCurrent(res, 'FOOD') };
}

/**
 * Категория «В» (`xmlparsers.as:3279-3291`) — сколько учёных реально
 * можно заказать: живой `scientistallow` ужимается по запасу нано/еды
 * игрока, а затем дополнительно не может превышать `work` (свободные
 * рабочие места).
 */
export function computeScientistAllow(train: ResearchTrainData, resources: PlayerNanoFoodPool): number {
  let allow = train.scientistAllowRaw;
  if (train.priceNano * allow > resources.nanoCrystal) {
    allow = Math.floor(resources.nanoCrystal / train.priceNano);
  }
  if (train.priceFood * allow > resources.food) {
    allow = Math.floor(resources.food / train.priceFood);
  }
  if (allow > train.work) {
    allow = train.work;
  }
  return allow;
}
