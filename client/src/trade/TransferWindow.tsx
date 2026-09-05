/**
 * Окно «Передача между сотами» — transfer_class.as, см.
 * docs/client-economy-social-ui.md §4.4. **НЕ межмировой перенос** —
 * логистика между собственными сотами игрока в одном мире (см. докстринг
 * transferData.ts). Данные — transferData.ts (parseTransferMenu,
 * buildTransferCommand).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { Stepper } from '../ui/Stepper';
import type { TabDef } from '../ui/tabs';
import { buildTransferCommand, type TransferCommand, type TransferMenuData } from './transferData';

export interface TransferWindowProps {
  data: TransferMenuData;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onTransfer: (command: TransferCommand) => void;
  onCalculate: (id: string, category: number, amount: number, targetCombId: string) => void;
}

const CATEGORY_LABELS: Record<number, string> = { 1: 'Ресурсы', 2: 'Юниты', 3: 'Артефакты' };

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
              {a.libname ?? a.id} (есть: {a.amount})
            </option>
          ))}
        </select>
      </div>

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
