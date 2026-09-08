/**
 * Живое хранилище марки `combres` (см. `combResData.ts`) — приходит почти
 * при каждом ответе сервера, каждый раз ЦЕЛИКОМ заменяет предыдущее
 * значение (сервер не шлёт дельты). По образцу `localDataStore.ts`.
 */
import { signal } from '@preact/signals';
import type { CombRes } from './combResData';

export const combRes = signal<CombRes>({});

export function setCombRes(res: CombRes): void {
  combRes.value = res;
}
