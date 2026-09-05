/**
 * Разбор marks `alliancepeace`/`alliancePeaceData` — мир между альянсами
 * (Фаза 3.6, `peace_aliance.as`, источник:
 * docs/client-economy-social-ui.md §2.4), портировано 1:1 из
 * `xmlparsers.as:658-849` (`alliancepeace_menu_parser`,
 * `alliancepeacedata_menu_parser`, `parseAllianceDataForPeace`).
 *
 * <alliancepeace name="" viewdata="" allowaction="0|1" maxdays="">
 *   <allow><alliance .../>...</allow>     (можно предложить мир)
 *   <offer><alliance .../>...</offer>     (ждём подтверждения от нас)
 *   <wait><alliance .../>...</wait>       (оффер вам, ждёт вашего ответа)
 *   <dismiss><alliance .../>...</dismiss> (заявки на расторжение)
 *   <ready><alliance .../>...</ready>     (уже действующий мир)
 * </alliancepeace>
 * где <alliance name="" short="" leader="" id="" peace_duration=""
 *               peace_end_date="" you="0|1"
 *               my_k="" my_n="" my_f="" my_p="" my_s="" my_e=""
 *               he_k="" he_n="" he_f="" he_p="" he_s="" he_e=""/>
 *
 * **ПОДТВЕРЖДЁННЫЙ БАГ СЕРВЕРА (не наш, зафиксирован для полноты)**:
 * `allowaction` — ЕДИНСТВЕННОЕ, что скрывает кнопки Да/Нет на клиенте;
 * `UA_AlliancePeace::checkAction()` на сервере всегда возвращает true —
 * то есть это только UI-подсказка, не защита (см. доклад §2.4).
 *
 * <alliancePeaceData name="" short="" id="" edit="0|1">
 *   <actions allow="" dimiss="" offer="" confirm="" confirmdimiss=""/>
 *   <you name="" k="" n="" f="" e="" p="" s=""/>
 *   <notyou name="" k="" n="" f="" e="" p="" s=""/>
 *   <desc>текст</desc>
 * </alliancePeaceData>
 */
import { parseXmlFragment } from '../protocol/response';

export interface AlliancePeaceEntry {
  name: string | null;
  short: string | null;
  leader: string | null;
  id: string | null;
  peaceDuration: string | null;
  peaceEndDate: string | null;
  you: boolean;
  myPrice: { k: string | null; n: string | null; f: string | null; p: string | null; s: string | null; e: string | null };
  hisPrice: { k: string | null; n: string | null; f: string | null; p: string | null; s: string | null; e: string | null };
}

export interface AlliancePeaceListData {
  alliName: string | null;
  /** Единственное, что делает строки списка кликабельными — не защита, см. докстринг. */
  viewData: string;
  /** Единственное, что скрывает кнопки Да/Нет — не защита, см. докстринг (подтверждённый баг сервера). */
  allowAction: string;
  blinkDismiss: boolean;
  maxPeaceLength: string | null;
  allow: AlliancePeaceEntry[];
  ready: AlliancePeaceEntry[];
  offer: AlliancePeaceEntry[];
  wait: AlliancePeaceEntry[];
  dismiss: AlliancePeaceEntry[];
}

function parseAllianceEntry(el: Element): AlliancePeaceEntry {
  return {
    name: el.getAttribute('name'),
    short: el.getAttribute('short'),
    leader: el.getAttribute('leader'),
    id: el.getAttribute('id'),
    peaceDuration: el.getAttribute('peace_duration'),
    peaceEndDate: el.getAttribute('peace_end_date'),
    you: el.getAttribute('you') === '1',
    myPrice: {
      k: el.getAttribute('my_k'),
      n: el.getAttribute('my_n'),
      f: el.getAttribute('my_f'),
      p: el.getAttribute('my_p'),
      s: el.getAttribute('my_s'),
      e: el.getAttribute('my_e'),
    },
    hisPrice: {
      k: el.getAttribute('he_k'),
      n: el.getAttribute('he_n'),
      f: el.getAttribute('he_f'),
      p: el.getAttribute('he_p'),
      s: el.getAttribute('he_s'),
      e: el.getAttribute('he_e'),
    },
  };
}

function parseGroup(root: Element, tag: string): AlliancePeaceEntry[] {
  const groupEl = Array.from(root.children).find((el) => el.nodeName === tag);
  if (!groupEl) return [];
  return Array.from(groupEl.children)
    .filter((el) => el.nodeName === 'alliance')
    .map(parseAllianceEntry);
}

export function parseAlliancePeaceList(xml: string): AlliancePeaceListData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'alliancepeace') {
    throw new Error(`Ожидался корневой тег <alliancepeace>, получено: ${root?.nodeName}`);
  }

  const dismiss = parseGroup(root, 'dismiss');
  const blinkDismiss = dismiss.some((entry) => !entry.you);

  return {
    alliName: root.getAttribute('name'),
    viewData: root.getAttribute('viewdata') ?? '',
    allowAction: root.getAttribute('allowaction') ?? '',
    blinkDismiss,
    maxPeaceLength: root.getAttribute('maxdays'),
    allow: parseGroup(root, 'allow'),
    ready: parseGroup(root, 'ready'),
    offer: parseGroup(root, 'offer'),
    wait: parseGroup(root, 'wait'),
    dismiss,
  };
}

export interface AlliancePeaceDetailData {
  id: string | null;
  edit: string | null;
  buttons: { allow: string | null; dismiss: string | null; offer: string | null; confirm: string | null; confirmDismiss: string | null };
  youSide: { name: string | null; k: string | null; n: string | null; f: string | null; e: string | null; p: string | null; s: string | null };
  opponentSide: { name: string | null; k: string | null; n: string | null; f: string | null; e: string | null; p: string | null; s: string | null };
  desc: string;
}

export function parseAlliancePeaceDetail(xml: string): AlliancePeaceDetailData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'alliancePeaceData') {
    throw new Error(`Ожидался корневой тег <alliancePeaceData>, получено: ${root?.nodeName}`);
  }

  const data: AlliancePeaceDetailData = {
    id: root.getAttribute('id'),
    edit: root.getAttribute('edit'),
    buttons: { allow: null, dismiss: null, offer: null, confirm: null, confirmDismiss: null },
    youSide: { name: null, k: null, n: null, f: null, e: null, p: null, s: null },
    opponentSide: { name: null, k: null, n: null, f: null, e: null, p: null, s: null },
    desc: '',
  };

  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'actions') {
      data.buttons = {
        allow: el.getAttribute('allow'),
        dismiss: el.getAttribute('dimiss'),
        offer: el.getAttribute('offer'),
        confirm: el.getAttribute('confirm'),
        confirmDismiss: el.getAttribute('confirmdimiss'),
      };
    } else if (el.nodeName === 'you') {
      data.youSide = {
        name: el.getAttribute('name'),
        k: el.getAttribute('k'),
        n: el.getAttribute('n'),
        f: el.getAttribute('f'),
        e: el.getAttribute('e'),
        p: el.getAttribute('p'),
        s: el.getAttribute('s'),
      };
    } else if (el.nodeName === 'notyou') {
      data.opponentSide = {
        name: el.getAttribute('name'),
        k: el.getAttribute('k'),
        n: el.getAttribute('n'),
        f: el.getAttribute('f'),
        e: el.getAttribute('e'),
        p: el.getAttribute('p'),
        s: el.getAttribute('s'),
      };
    } else if (el.nodeName === 'desc') {
      data.desc = el.textContent ?? '';
    }
  }

  return data;
}

/**
 * Единая команда `<getAlliancePeace .../>` (RequestDataAlli.as, см.
 * §2.4) — статус определяет действие, дословно совпадает с таблицей
 * `UA_AlliancePeace` из mechanics-alliances-diplomacy.md §5.2.
 */
export type AlliancePeaceStatus =
  | 'setPeace'
  | 'confirmPeace'
  | 'declinePeace'
  | 'terminatePeace'
  | 'confirmTerminatePeace'
  | 'declineTerminatePeace'
  | 'cancelPeace'
  | 'cancelTerminate';

export interface AlliancePeaceOfferPrice {
  k: number;
  n: number;
  f: number;
  p: number;
  s: number;
  e: number;
}

export interface AlliancePeaceCommand {
  alliId: string;
  status: AlliancePeaceStatus;
  time?: number;
  my?: AlliancePeaceOfferPrice;
  he?: AlliancePeaceOfferPrice;
}

export function buildSendOfferPeaceCommand(
  idAlli: string,
  myPrice: AlliancePeaceOfferPrice,
  hisPrice: AlliancePeaceOfferPrice,
  time: number,
): AlliancePeaceCommand {
  return { alliId: idAlli, status: 'setPeace', time, my: myPrice, he: hisPrice };
}

export function buildSimplePeaceCommand(idAlli: string, status: Exclude<AlliancePeaceStatus, 'setPeace'>): AlliancePeaceCommand {
  return { alliId: idAlli, status };
}
