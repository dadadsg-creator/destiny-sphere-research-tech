/**
 * Окно «Колонизация» — colonyzationclass.as, см.
 * docs/client-economy-social-ui.md §3.1. Данные — colonizationData.ts.
 * Три состояния по `action`: "1" готов к настройке, "2" в процессе
 * (кнопка отмены), "3"/"4" завершено (всё скрыто).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import { buildColonyDestination, isValidColonyName, type ColonizationData } from './colonizationData';

export interface ColonizationWindowProps {
  data: ColonizationData;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onSend: (destination: string, name: string) => void;
  onCancel: () => void;
}

export function ColonizationWindow({ data, tabs, onTabClick, onClose, onSend, onCancel }: ColonizationWindowProps) {
  const [ring, setRing] = useState(data.rings[0]?.id ?? '');
  const [random, setRandom] = useState(true);
  const [complex, setComplex] = useState(1);
  const [sota, setSota] = useState(1);
  const [name, setName] = useState('');

  if (data.action === '3' || data.action === '4') {
    return (
      <Window title="Колонизация" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
        <p class="ds-train-unit__idle">Колонизация завершена.</p>
      </Window>
    );
  }

  if (data.action === '2') {
    return (
      <Window title="Колонизация" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
        <p>{data.textStatus || 'Колонизация уже в процессе.'}</p>
        <button type="button" class="ds-popup__btn" onClick={onCancel}>
          Отменить
        </button>
      </Window>
    );
  }

  const nameValid = isValidColonyName(name);
  const canSend = !!ring && nameValid;

  return (
    <Window title="Колонизация" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <div class="ds-spying__row">
        <span class="ds-spying__label">Кольцо:</span>
        <select value={ring ?? ''} onChange={(e) => setRing((e.target as HTMLSelectElement).value)}>
          {data.rings.map((r) => (
            <option key={r.id} value={r.id ?? ''}>
              {r.ln}
            </option>
          ))}
        </select>
      </div>

      <label class="ds-spying__choice">
        <input type="checkbox" checked={random} onChange={() => setRandom(!random)} />
        Случайная сота в кольце
      </label>

      {!random && (
        <div class="ds-spying__row">
          <span class="ds-spying__label">Комплекс.сота:</span>
          <input type="number" class="ds-stepper__input" value={complex} onInput={(e) => setComplex(Number((e.target as HTMLInputElement).value))} />
          <input type="number" class="ds-stepper__input" value={sota} onInput={(e) => setSota(Number((e.target as HTMLInputElement).value))} />
        </div>
      )}

      <div class="ds-spying__row">
        <span class="ds-spying__label">Название соты (3-25):</span>
        <input type="text" class="ds-text-input" value={name} onInput={(e) => setName((e.target as HTMLInputElement).value)} />
      </div>

      <button
        type="button"
        class="ds-popup__btn"
        disabled={!canSend}
        onClick={() => onSend(buildColonyDestination(ring, random ? { random: true } : { complex, sota }), name)}
      >
        Отправить модуль колонизации
      </button>
    </Window>
  );
}
