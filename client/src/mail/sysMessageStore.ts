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

/**
 * Последнее пришедшее сообщение (любого из трёх типов) — показывается
 * прямо в нижней панели (`BottomBar.tsx`), в том же ряду, что часы и
 * кнопки меч/бинокль/шеврон, как одна общая строка статуса в
 * оригинале (владелец прислал скриншот реального статус-бара: между
 * часами и иконками — одна общая пустая/текстовая полоса, НЕ отдельная
 * панель поверх). Реального разделения "какое поле в оригинале носитель
 * этого текста" в декомпиле нет (`button.texts` в `game.as:858-863`
 * пишется на каждую кнопку по отдельности, назначение не установлено —
 * похоже на подсказку по наведению, не на общую строку), здесь —
 * практичное приближение: одна строка, самое свежее сообщение любого
 * типа.
 */
export const latestLogMessage = signal<string | null>(null);

export function appendSysMsgBatch(batch: SysMsgBatch): void {
  if (batch.sys.length > 0) sysMessages.value = [...sysMessages.value, ...batch.sys];
  if (batch.war.length > 0) warMessages.value = [...warMessages.value, ...batch.war];
  if (batch.spy.length > 0) spyMessages.value = [...spyMessages.value, ...batch.spy];
  const all = [...batch.sys, ...batch.war, ...batch.spy];
  if (all.length > 0) latestLogMessage.value = all[all.length - 1];
}
