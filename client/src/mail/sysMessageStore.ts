/**
 * Накопительный лог системных/боевых/шпионских сообщений — аналог
 * `debug.link.sys_msg`/`war_msg`/`spy_msg` оригинала (`parser.as:494-548`,
 * см. `logData.ts` докстринг). Марка `sysmsg` приходит ПОЧТИ с каждым
 * ответом сервера и несёт только ОДНУ новую порцию — сама марка её не
 * накапливает, поэтому раньше содержимое просто терялось (`console.log`
 * и всё). `SysMessageWindow.tsx` — уже готовый попап-просмотрщик ОДНОГО
 * из трёх логов по требованию игрока (кнопка статус-бара, ещё не
 * воссоздана — см. план, «Точки входа»), здесь только правильно копим
 * историю, чтобы было что показать, когда эта точка входа появится.
 */
import { signal } from '@preact/signals';
import type { SysMsgBatch } from './logData';

export const sysMessages = signal<string[]>([]);
export const warMessages = signal<string[]>([]);
export const spyMessages = signal<string[]>([]);

export function appendSysMsgBatch(batch: SysMsgBatch): void {
  if (batch.sys.length > 0) sysMessages.value = [...sysMessages.value, ...batch.sys];
  if (batch.war.length > 0) warMessages.value = [...warMessages.value, ...batch.war];
  if (batch.spy.length > 0) spyMessages.value = [...spyMessages.value, ...batch.spy];
}
