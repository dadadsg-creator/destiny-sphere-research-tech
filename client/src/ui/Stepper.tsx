/**
 * Числовой степпер — упрощённый порт `initAmountScroll`/`initAmountScroll2`
 * (window.as:1872-2556, см. docs/client-window-framework.md §5). В
 * оригинале зажатие +/- ускоряет счёт (акселерация по кадрам) — здесь НЕ
 * реализовано, каждый клик — ровно ±1; акселерация отложена как отдельная
 * задача полировки, если понадобится на реальном экране.
 */
import type { JSX } from 'preact';

export interface StepperProps {
  value: number;
  min?: number;
  max?: number;
  onChange: (value: number) => void;
}

export function Stepper({ value, min = 0, max = Number.MAX_SAFE_INTEGER, onChange }: StepperProps) {
  const clamp = (v: number) => Math.min(max, Math.max(min, v));

  const onInput: JSX.GenericEventHandler<HTMLInputElement> = (e) => {
    const raw = Number(e.currentTarget.value);
    if (!Number.isNaN(raw)) onChange(clamp(raw));
  };

  return (
    <span class="ds-stepper">
      <button type="button" class="ds-stepper__btn" disabled={value <= min} onClick={() => onChange(clamp(value - 1))}>
        −
      </button>
      <input class="ds-stepper__input" type="number" value={value} min={min} max={max} onInput={onInput} />
      <button type="button" class="ds-stepper__btn" disabled={value >= max} onClick={() => onChange(clamp(value + 1))}>
        +
      </button>
    </span>
  );
}
