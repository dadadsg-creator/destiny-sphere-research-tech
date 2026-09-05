/**
 * Генератор формы личного кабинета по данным submenuData.ts —
 * subMenuEngine.as:137-243,395-619 (виджеты) + :245-284 (сборка и отправка
 * значений при нажатии кнопки). См. docs/client-economy-social-ui.md §5.1.
 * Раскладка — обычный поток блоков (flex-wrap), а не порт байт-в-байт
 * позиционирования оригинала (которое реально не использовало x/y, см.
 * submenuData.ts докстринг).
 */
import { useState } from 'preact/hooks';
import { ConfirmPopup } from '../ui/Popup';
import { Stepper } from '../ui/Stepper';
import { getDefaultRadioSelection, type SubmenuElement, type SubmenuFormData } from './submenuData';

export interface SubmenuFormProps {
  form: SubmenuFormData;
  onClose: () => void;
  onSubmit: (buttonId: string, values: Record<string, string>) => void;
}

type FieldState = Record<string, string | number | { ring: number; comb: number; sota: number }>;

function initialState(elements: SubmenuElement[]): FieldState {
  const state: FieldState = {};
  for (const el of elements) {
    if (el.type === 'combobox') state[el.id] = el.options[el.defIndex]?.id ?? el.options[0]?.id ?? '';
    else if (el.type === 'amountf') state[el.id] = el.def;
    else if (el.type === 'inputtext') state[el.id] = el.defValue;
    else if (el.type === 'radio') state[el.id] = getDefaultRadioSelection(el);
    else if (el.type === 'addrcomb') state[el.id] = { ring: 1, comb: 1, sota: 1 };
    else if (el.type === 'hidden') state[el.id] = el.data;
  }
  return state;
}

/** subMenuEngine.as:245-284 sendDataButton — только textfield/button не участвуют в отправке. */
function resolveFieldValue(el: SubmenuElement, state: FieldState): string | null {
  switch (el.type) {
    case 'addrcomb': {
      const v = state[el.id] as { ring: number; comb: number; sota: number };
      return `${v.ring}.${v.comb}.${v.sota}`;
    }
    case 'combobox':
    case 'radio':
      return String(state[el.id] ?? '');
    case 'amountf':
      return String(state[el.id] ?? 0);
    case 'inputtext':
      return String(state[el.id] ?? '');
    case 'hidden':
      return String(state[el.id] ?? '');
    default:
      return null;
  }
}

export function SubmenuForm({ form, onClose, onSubmit }: SubmenuFormProps) {
  const [state, setState] = useState<FieldState>(() => initialState(form.elements));
  const [confirm, setConfirm] = useState<{ text: string; buttonId: string; fieldIds: string[] } | null>(null);

  const set = (id: string, value: FieldState[string]) => setState((s) => ({ ...s, [id]: value }));

  const collectValues = (fieldIds: string[]): Record<string, string> => {
    const byId = new Map(form.elements.filter((e): e is Extract<SubmenuElement, { id: string }> => 'id' in e).map((e) => [e.id, e]));
    const values: Record<string, string> = {};
    for (const id of fieldIds) {
      const el = byId.get(id);
      if (!el) continue;
      const value = resolveFieldValue(el, state);
      if (value !== null) values[id] = value;
    }
    return values;
  };

  const handleButton = (btn: Extract<SubmenuElement, { type: 'button' }>) => {
    if (btn.confirmText) {
      setConfirm({ text: btn.confirmText, buttonId: btn.id, fieldIds: btn.fieldIdsForSend });
    } else {
      onSubmit(btn.id, collectValues(btn.fieldIdsForSend));
    }
  };

  return (
    <div class="ds-window">
      <div class="ds-window__titlebar">
        <span class="ds-window__title">{form.titleWindow}</span>
        {form.closeButtonEnabled && (
          <button type="button" class="ds-window__close" onClick={onClose} aria-label="Закрыть">
            ×
          </button>
        )}
      </div>
      <div class="ds-window__content" style={{ display: 'flex', flexWrap: 'wrap', gap: '8px', alignItems: 'center' }}>
        {form.elements.map((el, i) => {
          if (el.type === 'br') return <div key={i} style={{ flexBasis: '100%' }} />;
          if (el.type === 'textfield') return <span key={i} dangerouslySetInnerHTML={{ __html: el.text }} />;
          if (el.type === 'hidden') return null;
          if (el.type === 'inputtext')
            return (
              <input
                key={el.id}
                type="text"
                value={String(state[el.id] ?? '')}
                maxLength={el.maxChars > 0 ? el.maxChars : undefined}
                onInput={(e) => set(el.id, (e.target as HTMLInputElement).value)}
              />
            );
          if (el.type === 'combobox')
            return (
              <select key={el.id} value={String(state[el.id] ?? '')} onChange={(e) => set(el.id, (e.target as HTMLSelectElement).value)}>
                {el.options.map((o) => (
                  <option key={o.id} value={o.id}>
                    {o.ln}
                  </option>
                ))}
              </select>
            );
          if (el.type === 'radio')
            return (
              <span key={el.id}>
                {el.points.map((p) => (
                  <label key={p.id} style={{ marginRight: '6px' }}>
                    <input
                      type="radio"
                      name={el.id}
                      checked={state[el.id] === p.id}
                      onChange={() => set(el.id, p.id)}
                    />
                    {p.label}
                  </label>
                ))}
              </span>
            );
          if (el.type === 'amountf')
            return <Stepper key={el.id} value={Number(state[el.id] ?? el.def)} min={el.min} max={el.max} onChange={(v) => set(el.id, v)} />;
          if (el.type === 'addrcomb') {
            const v = state[el.id] as { ring: number; comb: number; sota: number };
            return (
              <span key={el.id}>
                <Stepper value={v.ring} min={1} max={4} onChange={(ring) => set(el.id, { ...v, ring })} />.
                <Stepper value={v.comb} min={1} max={2000} onChange={(comb) => set(el.id, { ...v, comb })} />.
                <Stepper value={v.sota} min={1} max={6} onChange={(sota) => set(el.id, { ...v, sota })} />
              </span>
            );
          }
          if (el.type === 'button')
            return (
              <button key={el.id} onClick={() => handleButton(el)}>
                {el.label}
              </button>
            );
          return null;
        })}
      </div>

      {confirm && (
        <ConfirmPopup
          message={confirm.text}
          onYes={() => {
            onSubmit(confirm.buttonId, collectValues(confirm.fieldIds));
            setConfirm(null);
          }}
          onNo={() => setConfirm(null)}
        />
      )}
    </div>
  );
}
