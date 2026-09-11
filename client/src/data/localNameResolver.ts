/**
 * Единая точка резолвинга машинного имени → локализованное название,
 * зеркало `debug.link.localNames` (`parser.as:701-834`, `local_names()`):
 * оригинал кладёт ЛЮБОЙ дочерний узел `loaddata` (`<att>`, `<interface>`,
 * `<unit>`, `<groundtype>` и т.д.) в ОДИН плоский словарь по `id`,
 * независимо от исходного тега — `game.getLocalNames(id)` просто читает
 * оттуда, не разбирая, откуда пришла запись. `localData.ts` парсит те же
 * данные в типизированные Map по категориям (ради типобезопасности) —
 * здесь они снова объединяются в один поиск, воспроизводя реальное
 * поведение `getLocalNames()`.
 *
 * **Важно**: `<att>`-имена (ресурсы и т.п.) в живых данных приходят
 * строчными ("кадериум", "нано-кристаллы" — подтверждено дампом
 * `loaddata` 2026-09-11) — заглавная буква здесь чисто косметическая
 * (`capitalizeFirst`), сам текст не меняется и не выдумывается.
 */
import type { LocalData } from './localData';

export function resolveLocalName(data: LocalData | null | undefined, id: string): string | null {
  if (!data) return null;
  return (
    data.attributeNames.get(id)?.ln ??
    data.interfaceStrings.get(id)?.ln ??
    data.units.get(id)?.ln ??
    data.groundTypes.get(id)?.ln ??
    data.cellTypes.get(id)?.ln ??
    data.messageScopes.get(id)?.ln ??
    data.rings.get(id)?.ln ??
    null
  );
}

export function capitalizeFirst(text: string): string {
  return text.length > 0 ? text.charAt(0).toUpperCase() + text.slice(1) : text;
}
