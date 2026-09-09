/**
 * Живое хранилище мира между альянсами — список (`alliancepeace`) и
 * открытая детальная карточка одного альянса (`alliancePeaceData`)
 * приходят ДВУМЯ разными марками (см. `protocol/dispatch.tsx`,
 * `renderAlliancePeaceList`/`renderAlliancePeaceDetail`) — тот же
 * паттерн, что и `mail/mailStore.ts`. Детальная карточка полностью
 * заменяет список в окне (кнопка «Назад» просто перерисовывает уже
 * сохранённый список, без нового запроса к серверу).
 */
import { signal } from '@preact/signals';
import type { TabDef } from '../ui/tabs';
import type { AlliancePeaceDetailData, AlliancePeaceListData } from './alliancePeaceData';

export const alliancePeaceList = signal<{ data: AlliancePeaceListData; tabs: TabDef[] } | null>(null);
export const alliancePeaceDetail = signal<AlliancePeaceDetailData | null>(null);

export function setAlliancePeaceList(data: AlliancePeaceListData, tabs: TabDef[]): void {
  alliancePeaceList.value = { data, tabs };
  alliancePeaceDetail.value = null;
}

export function setAlliancePeaceDetail(data: AlliancePeaceDetailData): void {
  alliancePeaceDetail.value = data;
}

export function clearAlliancePeaceDetail(): void {
  alliancePeaceDetail.value = null;
}
