/**
 * Разбор XML mark `refresh` — <refresh location="HOME|NEUTRAL|CENTRAL">
 * <sota>217 симв.</sota><build>...</build></refresh>. См.
 * docs/client-map-and-grid.md §4.1-4.3 (основной репозиторий документации).
 */
import { parseXmlFragment } from '../protocol/response';

export interface RefreshData {
  idComb: string | null;
  gate: boolean;
  location: string | null;
  sota: string;
}

export function parseRefresh(xml: string): RefreshData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'refresh') {
    throw new Error(`Ожидался корневой тег <refresh>, получено: ${root?.nodeName}`);
  }
  const sotaEl = root.querySelector('sota');
  const sota = sotaEl?.textContent ?? '';

  return {
    idComb: root.getAttribute('id_comb'),
    gate: root.getAttribute('gate') === '1',
    location: root.getAttribute('location'),
    sota,
  };
}
