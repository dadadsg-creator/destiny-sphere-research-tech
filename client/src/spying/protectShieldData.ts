/**
 * Разбор mark `protectedfield` — восстановление защитного поля
 * ЗАХВАЧЕННОЙ колонии (Фаза 3.2, `protectshieldclass.as`, источник:
 * docs/client-combat-ui.md §8), портировано 1:1 из
 * `xmlparsers.as:1225-1238`. Комментарий разработчика прямо над функцией
 * в оригинале — «НОВЫЙ ЗАЩИТНЫЙ ЩИТ ДЛЯ ЗАХВАЧЕННЫХ КОЛОНИЙ» — это
 * подтверждённо отдельный механизм от `powershieldclass.as` (силовой щит
 * своей военной базы), привязанный именно к судьбе захваченных колоний
 * (см. mechanics-combat.md §8, DE_CombProtectiveField).
 *
 * <protectedfield currentpercent="" price_res="K,N,S,F,P" price_silver=""/>
 *
 * price_res — 5 значений через запятую, цена восстановления ОДНОГО
 * процентного пункта по каждому из 5 базовых ресурсов (по порядку —
 * Kaderium/Nano/Stasis/Food/Prodium, как currentRes[0..4] в оригинале).
 */
import { parseXmlFragment } from '../protocol/response';

export interface ProtectShieldData {
  /** Текущий % защитного поля (0-100). */
  currentPercent: number;
  /** Цена восстановления одного процентного пункта — [kaderium, nano, stasis, food, prodium]. */
  priceRes: number[];
  /** Альтернативная цена одного пункта в SILVER_CREDIT. */
  priceSilver: number;
}

export function parseProtectShield(xml: string): ProtectShieldData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'protectedfield') {
    throw new Error(`Ожидался корневой тег <protectedfield>, получено: ${root?.nodeName}`);
  }

  return {
    currentPercent: Number(root.getAttribute('currentpercent')) || 0,
    priceRes: (root.getAttribute('price_res') ?? '').split(',').map(Number),
    priceSilver: Number(root.getAttribute('price_silver')) || 0,
  };
}
