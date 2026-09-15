/**
 * Разбор марки верхнего уровня `mode` — премиум-статус игрока.
 * `parser.as:127-139` (`prem_data`): значение — просто число (мс,
 * абсолютный timestamp окончания премиума, не флаг и не остаток
 * времени), `debug.link.premiumTime = Number(datax25)`.
 * `game.as:356` (`getPremium()`) сводит это к простому булеву
 * флагу — ровно то же самое делаем здесь: `> 0` значит премиум
 * активен. Сравнение с `game.getServerTime()` (для отображения
 * остатка времени, `game.as:1172-1174`) не портировано — на данный
 * момент нужен только сам факт "премиум да/нет" для 3 мест,
 * захардкоженных как `isPremium={false}` (см. `dispatch.tsx`).
 */
export interface PremiumStatus {
  isPremium: boolean;
  /** debug.link.premiumTime оригинала — epoch ms окончания премиума, null если не пришло. */
  premiumUntilMs: number | null;
}

export function parsePremiumStatus(raw: string): PremiumStatus {
  const value = Number(raw);
  return {
    isPremium: value > 0,
    premiumUntilMs: Number.isFinite(value) && value > 0 ? value : null,
  };
}
