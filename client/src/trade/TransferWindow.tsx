/**
 * Окно «Передача между сотами» — transfer_class.as, см.
 * docs/client-economy-social-ui.md §4.4. **НЕ межмировой перенос** —
 * логистика между собственными сотами игрока в одном мире (см. докстринг
 * transferData.ts). Данные — transferData.ts (parseTransferMenu,
 * buildTransferCommand).
 *
 * **Исправление (2026-09-11)**: категории 1/2 показывали сырое машинное
 * имя вместо названия — исправлено через `resourceDisplayName()`/
 * `unitDisplayName()` (живая локализация из `loaddata`, не выдуманные
 * строки, см. `resourceIcons.ts`).
 *
 * **Исправление (2026-09-18)**: категория 3 (артефакты) тоже теперь
 * локализована — `transferArtefactDisplayName()` (`resourceIcons.ts`,
 * очистка id через `:`→`_` + имя+бонус, дословно `xmlparsers.as:
 * 1751-1779`) — раньше считалось отдельной неподключённой таблицей
 * (см. `transferData.ts`), по факту та же каталожная локализация,
 * что и везде (`LocalData.artefacts`).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import { ResourceValue } from '../ui/ResourceValue';
import { resourceDisplayName, unitDisplayName, unitIconUrl, transferArtefactDisplayName } from '../data/resourceIcons';
import type { TabDef } from '../ui/tabs';
import { buildTransferCommand, type TransferArticle, type TransferCommand, type TransferMenuData } from './transferData';

export interface TransferWindowProps {
  data: TransferMenuData;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onTransfer: (command: TransferCommand) => void;
  onCalculate: (id: string, category: number, amount: number, targetCombId: string) => void;
}

const CATEGORY_LABELS: Record<number, string> = { 1: 'Ресурсы', 2: 'Юниты', 3: 'Артефакты' };

function articleLabel(a: TransferArticle, category: number): string {
  if (category === 1 && a.libname) return resourceDisplayName(a.libname);
  if (category === 2 && a.libname) return unitDisplayName(a.libname);
  if (category === 3 && a.libname) {
    const name = transferArtefactDisplayName(a.libname, a.bonusName, a.bonusValue);
    return a.expiredMessage ? `${name} (${a.expiredMessage})` : name;
  }
  return a.libname ?? a.id ?? '';
}

export function TransferWindow({ data, tabs, onTabClick, onClose, onTransfer, onCalculate }: TransferWindowProps) {
  const [category, setCategory] = useState(1);
  const articles = data.articlesByCategory[category] ?? [];
  const [articleId, setArticleId] = useState(articles[0]?.id ?? '');
  const [amount, setAmount] = useState(0);
  const [targetId, setTargetId] = useState(data.targets[0]?.idComb ?? '');

  const article = articles.find((a) => a.id === articleId);
  const canTransfer = !!article && amount > 0 && !!targetId;

  return (
    <Window title="Передача между сотами" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <div class="ds-spying__row">
        <span class="ds-spying__label">Категория:</span>
        {[1, 2, 3].map((cat) => (
          <label key={cat} class="ds-spying__choice">
            <input
              type="radio"
              name="transfer-category"
              checked={category === cat}
              onChange={() => {
                setCategory(cat);
                setArticleId(data.articlesByCategory[cat]?.[0]?.id ?? '');
              }}
            />
            {CATEGORY_LABELS[cat]}
          </label>
        ))}
      </div>

      <div class="ds-spying__row">
        <span class="ds-spying__label">Предмет:</span>
        <select value={articleId ?? ''} onChange={(e) => setArticleId((e.target as HTMLSelectElement).value)}>
          {articles.map((a) => (
            <option key={a.id} value={a.id ?? ''}>
              {articleLabel(a, category)}
            </option>
          ))}
        </select>
      </div>

      {article && (
        <div class="ds-spying__row">
          <span class="ds-spying__label">Доступно у вас:</span>
          {category === 1 && article.libname ? (
            <ResourceValue name={article.libname} value={article.amount ?? '0'} />
          ) : (
            <span class="ds-artefact-row">
              {category === 2 && article.libname && <img class="ds-unit-icon" src={unitIconUrl(article.libname)} alt="" />}
              {article.amount ?? '0'} шт. ({articleLabel(article, category)})
            </span>
          )}
        </div>
      )}

      <div class="ds-spying__row">
        <span class="ds-spying__label">Количество:</span>
        <Stepper value={amount} min={0} onChange={setAmount} />
      </div>

      <div class="ds-spying__row">
        <span class="ds-spying__label">Куда:</span>
        <select value={targetId ?? ''} onChange={(e) => setTargetId((e.target as HTMLSelectElement).value)}>
          {data.targets.map((t) => (
            <option key={t.idComb} value={t.idComb ?? ''}>
              {t.combName} ({t.coords})
            </option>
          ))}
        </select>
      </div>

      <div class="ds-spying__row">
        <button
          type="button"
          class="ds-popup__btn"
          disabled={!canTransfer}
          onClick={() => article && onCalculate(article.id!, category, amount, targetId)}
        >
          Рассчитать
        </button>
        <button
          type="button"
          class="ds-popup__btn"
          disabled={!canTransfer}
          onClick={() => article && onTransfer(buildTransferCommand(article.id!, category, amount, targetId))}
        >
          Отправить
        </button>
      </div>
    </Window>
  );
}
