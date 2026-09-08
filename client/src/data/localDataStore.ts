/**
 * Живое хранилище статической базы (mark `loaddata`, см. `localData.ts`) —
 * приходит один раз за сессию (`parser.as:183`, `if (obj.loaddata !=
 * undefined) { local_names(obj.loaddata); }`), кладётся в общий сигнал,
 * экраны читают вместо `TEST_*`-констант. По образцу `ui/windowStore.ts`.
 */
import { signal } from '@preact/signals';
import type { LocalData } from './localData';

export const localData = signal<LocalData | null>(null);

export function setLocalData(data: LocalData): void {
  localData.value = data;
}
