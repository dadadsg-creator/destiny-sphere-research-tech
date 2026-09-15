/**
 * Разбор mark `combcomplex` — экран «обзор комплекса»
 * (`fillComplex()`, `sota.as:1013-1300+`), портирован из
 * `TL_ComplexView.class.php:9-46`. Живая команда — НЕ через
 * `getbuildmenu`-семейство (нет `c=`), отдельный верхнеуровневый тег,
 * см. `reqvestdata.as:791-806` (`NavigateComplex`).
 *
 * <combcomplex number="" ring="" ringname="" gate="0|1">
 *   <comb num="1-6" cscore="" home="0|1" league="" cname="" captured="0|1"
 *         uname="" avatar="" race="1-3" newbee="0|1" gate="0|1" cban="1"?
 *         godpro="ms"? premic="1"? freez="1"? npc="1"? alliance="[TAG]"
 *         urlpic="" samealliance="1"?/>  (× 6, могут быть частично пустые —
 *         свободная ячейка комплекса даёт только num/cscore/home/league)
 *   <rings><ring id="RING_NAME" ln="Локализованное"/> (×N)</rings>
 * </combcomplex>
 *
 * VK-специфика оригинала (VKUID, ComplexProfiles, кнопка "Профиль
 * VK") сознательно не портирована — см. project memory
 * remaster_vk_integration_rebuild: VK встраивается заново отдельной
 * задачей, не 1:1.
 */
import { parseXmlFragment } from '../protocol/response';

export interface ComplexViewComb {
  num: number;
  cscore: number;
  home: boolean;
  league: string;
  combName: string | null;
  captured: boolean;
  userName: string | null;
  avatar: string | null;
  race: string | null;
  newbee: boolean;
  gateOpen: boolean;
  banned: boolean;
  /** Оставшееся время премиум-неуязвимости новичка, мс (godpro). */
  godModeMs: number | null;
  premium: boolean;
  frozen: boolean;
  npc: boolean;
  allianceTag: string | null;
  allianceIconUrl: string | null;
  sameAlliance: boolean;
}

export interface ComplexViewRing {
  id: string;
  ln: string | null;
}

export interface ComplexViewData {
  complexNumber: number;
  ringNumber: number;
  ringName: string;
  gateOpen: boolean;
  combs: ComplexViewComb[];
  rings: ComplexViewRing[];
}

function bool01(v: string | null): boolean {
  return v === '1';
}

export function parseComplexView(xml: string): ComplexViewData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'combcomplex') {
    throw new Error(`Ожидался корневой тег <combcomplex>, получено: ${root?.nodeName}`);
  }

  const combs: ComplexViewComb[] = [];
  const rings: ComplexViewRing[] = [];

  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'comb') {
      combs.push({
        num: Number(el.getAttribute('num')) || 0,
        cscore: Number(el.getAttribute('cscore')) || 0,
        home: bool01(el.getAttribute('home')),
        league: el.getAttribute('league') ?? '',
        combName: el.getAttribute('cname'),
        captured: bool01(el.getAttribute('captured')),
        userName: el.getAttribute('uname'),
        avatar: el.getAttribute('avatar'),
        race: el.getAttribute('race'),
        newbee: bool01(el.getAttribute('newbee')),
        gateOpen: bool01(el.getAttribute('gate')),
        banned: bool01(el.getAttribute('cban')),
        godModeMs: el.getAttribute('godpro') !== null ? Number(el.getAttribute('godpro')) : null,
        premium: bool01(el.getAttribute('premic')),
        frozen: bool01(el.getAttribute('freez')),
        npc: bool01(el.getAttribute('npc')),
        allianceTag: el.getAttribute('alliance'),
        allianceIconUrl: el.getAttribute('urlpic'),
        sameAlliance: bool01(el.getAttribute('samealliance')),
      });
    } else if (el.nodeName === 'rings') {
      for (const ringEl of Array.from(el.children)) {
        if (ringEl.nodeName !== 'ring') continue;
        const id = ringEl.getAttribute('id');
        if (!id) continue;
        rings.push({ id, ln: ringEl.getAttribute('ln') });
      }
    }
  }

  combs.sort((a, b) => a.num - b.num);

  return {
    complexNumber: Number(root.getAttribute('number')) || 0,
    ringNumber: Number(root.getAttribute('ring')) || 0,
    ringName: root.getAttribute('ringname') ?? '',
    gateOpen: bool01(root.getAttribute('gate')),
    combs,
    rings,
  };
}

/**
 * reqvestdata.as:791-806 (`NavigateComplex`) / :267-289 (первое открытие,
 * `case "COMPLEX"`) — `<complexview direction="LEFT|RIGHT|HOME"
 * ring="RING_XXX" complexnum="N"/>`. Все три параметра необязательны и
 * взаимоисключающи по смыслу оригинала: либо `direction` (перелистывание
 * от текущей позиции сессии), либо явные `ring`+`complexnum` (переход по
 * выбору в форме). Явные координаты имеют приоритет на сервере
 * (`UA_ComplexView::checkLocalParams`, `ring` проверяется первым).
 */
export interface ComplexViewNavigateCommand {
  direction?: 'LEFT' | 'RIGHT' | 'HOME';
  ring?: string;
  complexnum?: number;
}

export function buildComplexViewCommand(opts: ComplexViewNavigateCommand): Record<string, string | number | undefined> {
  return {
    direction: opts.direction,
    ring: opts.ring,
    complexnum: opts.complexnum,
  };
}
