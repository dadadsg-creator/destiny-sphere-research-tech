/**
 * Живое хранилище марки `mode` (см. `premiumData.ts`) — по образцу
 * `combResStore.ts`/`localDataStore.ts`. `null` до первого ответа
 * сервера, содержащего эту марку (приходит не в каждом ответе).
 */
import { signal } from '@preact/signals';
import type { PremiumStatus } from './premiumData';

export const premiumStatus = signal<PremiumStatus | null>(null);

export function setPremiumStatus(status: PremiumStatus): void {
  premiumStatus.value = status;
}
