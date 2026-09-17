/**
 * Разбор mark `transferresource` — передача ресурсов/юнитов/артефактов
 * между СВОИМИ сотами игрока в одном мире (Фаза 3.5, `transfer_class.as`,
 * источник: docs/client-economy-social-ui.md §4.4). **НЕ межмировой
 * перенос** — несмотря на название класса, это внутримировая логистика
 * (см. докстринг в докладе — явное предупреждение владельцу не путать с
 * `mechanics-world-transfer.md`). Портировано из
 * `xmlparsers.as:1730-1800` (`transfer_menu_parser`).
 *
 * <transferresource>
 *   <article id_category="1|2|3" id="" libname="" amount=""
 *            [bonusvalue="" bonusname="" death_message=""]/>  (категория
 *            3 = артефакт, доп. поля бонуса)
 *   <to id_comb="" combname="" coords=""/>
 * </transferresource>
 *
 * Категория 3 (артефакт) — `libname` очищается через `parser.artrp()`
 * (`:` → `_`, `xmlparsers.as:1751`) и локализуется через
 * `transferArtefactDisplayName()` (`resourceIcons.ts`), см. докстринг
 * той функции. `bonusValue` декодируется здесь же (`$`/`#` → `%`/`+`,
 * первое вхождение, `xmlparsers.as:1763-1767` — дословно та же логика,
 * не анкорная `fixBonusHash` из `localData.ts`, там другой формат поля).
 */
import { parseXmlFragment } from '../protocol/response';

/** xmlparsers.as:1763-1767 — первое вхождение "#"->"+", первое "$"->"%". */
function fixTransferBonusValue(raw: string): string {
  return raw.replace('#', '+').replace('$', '%');
}

export interface TransferArticle {
  category: number;
  id: string | null;
  libname: string | null;
  amount: string | null;
  /** Только категория 3 (артефакт) — bonusValue уже декодирован ($/# -> %/+). */
  bonusValue?: string;
  bonusName?: string;
  expiredMessage?: string;
}

export interface TransferTarget {
  idComb: string | null;
  combName: string | null;
  coords: string | null;
}

export interface TransferMenuData {
  /** По категориям 1(ресурсы)/2(юниты)/3(артефакты). */
  articlesByCategory: Record<number, TransferArticle[]>;
  targets: TransferTarget[];
}

export function parseTransferMenu(xml: string): TransferMenuData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'transferresource') {
    throw new Error(`Ожидался корневой тег <transferresource>, получено: ${root?.nodeName}`);
  }

  const articlesByCategory: Record<number, TransferArticle[]> = {};
  const targets: TransferTarget[] = [];

  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'article') {
      const category = Number(el.getAttribute('id_category'));
      const article: TransferArticle = {
        category,
        id: el.getAttribute('id'),
        libname: el.getAttribute('libname'),
        amount: el.getAttribute('amount'),
      };
      if (category === 3) {
        const rawBonusValue = el.getAttribute('bonusvalue');
        article.bonusValue = rawBonusValue ? fixTransferBonusValue(rawBonusValue) : undefined;
        article.bonusName = el.getAttribute('bonusname') ?? undefined;
        article.expiredMessage = el.getAttribute('death_message') ?? undefined;
      }
      (articlesByCategory[category] ??= []).push(article);
    } else if (el.nodeName === 'to') {
      targets.push({
        idComb: el.getAttribute('id_comb'),
        combName: el.getAttribute('combname'),
        coords: el.getAttribute('coords'),
      });
    }
  }

  return { articlesByCategory, targets };
}

/**
 * reqvestdata.as:633-637 (буквально подтверждено) —
 * <transferresource combto="" id_article="" id_category="" amount=""
 * death=""/>.
 */
export interface TransferCommand {
  comto: string;
  idArticle: string;
  idCategory: number;
  amount: number;
  death: number;
}

export function buildTransferCommand(
  id: string,
  category: number,
  amount: number,
  targetCombId: string,
  deathKey: number = 0,
): TransferCommand {
  return { comto: targetCombId, idArticle: id, idCategory: category, amount, death: deathKey };
}
