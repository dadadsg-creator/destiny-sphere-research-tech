/**
 * Разбор mark `colonynavigate` — список своих колоний/сот для быстрого
 * перехода (`PN_ColonyNavigatePanel.class.php:11-53`). Открывается
 * `<getbuildmenu tab="ColonyNavigatePanel"/>` (БЕЗ `c=`, как и
 * `combcomplex`/`otpusk`), см. `reqvestdata.as:282-284`
 * (`case "COLONY"`).
 *
 * <colonynavigate>
 *   <colony combname="" coords="RING.COMPLEX.COMB" id_comb=""
 *           allow="0|1" capture="0|1" base="0|N" disable="0|1"?/> (×N)
 * </colonynavigate>
 *
 * `allow=0` — это текущая сота (переход не нужен); `capture=1` —
 * захваченная колония (доступна кнопка "покинуть", `disable=1` если
 * защитное поле ниже `MIN_PROTECTIVE_FIELD_TO_LEAVE_COLONY`); `base`
 * — есть ли военная база (`id_building=26`) на соте.
 */
import { parseXmlFragment } from '../protocol/response';

export interface ColonyNavigateEntry {
  combName: string;
  coords: string;
  idComb: string;
  canNavigate: boolean;
  captured: boolean;
  hasBase: boolean;
  leaveDisabled: boolean;
}

export function parseColonyNavigate(xml: string): ColonyNavigateEntry[] {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'colonynavigate') {
    throw new Error(`Ожидался корневой тег <colonynavigate>, получено: ${root?.nodeName}`);
  }

  const entries: ColonyNavigateEntry[] = [];
  for (const el of Array.from(root.children)) {
    if (el.nodeName !== 'colony') continue;
    entries.push({
      combName: decodeURIComponent(el.getAttribute('combname') ?? ''),
      coords: decodeURIComponent(el.getAttribute('coords') ?? ''),
      idComb: el.getAttribute('id_comb') ?? '',
      canNavigate: el.getAttribute('allow') === '1',
      captured: el.getAttribute('capture') === '1',
      hasBase: Number(el.getAttribute('base')) > 0,
      leaveDisabled: el.getAttribute('disable') === '1',
    });
  }
  return entries;
}

/**
 * reqvestdata.as:267-292 (`navigate()`) — три случая, объединённые в
 * этой функции по её же контракту: `dest` либо "HOME"/"CENTRAL"/
 * "NEUTRAL" (переключение слоя ТЕКУЩЕЙ соты), либо
 * "RING.COMPLEX.COMB" (переход на ДРУГУЮ свою соту/колонию, строка —
 * готовое поле `coords` из `ColonyNavigateEntry`). `cell="1"` —
 * константа-флаг ("наличие соты в кешах"), не координата ячейки, см.
 * докстринг `reqvestdata.as:270`.
 */
export function buildNavigateCommand(dest: string): Record<string, string> {
  return { cell: '1', dest };
}

/** reqvestdata.as:296-301 (`leaveColony`) — покинуть захваченную колонию, необратимо. */
export function buildLeaveColonyCommand(idComb: string): Record<string, string> {
  return { id: idComb };
}
