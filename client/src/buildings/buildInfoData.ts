/**
 * Разбор mark `getbuildinfo` — состояние конкретного здания и доступные
 * действия (Фаза 3.3, `buildinfo.as`, источник:
 * docs/client-economy-social-ui.md §1.8), портировано 1:1 из
 * `xmlparsers.as:2629-2732` (`ParseInfoBuild`).
 *
 * <getbuildinfo un="" destr="" up="" activate="" ia="" ac="" deact=""
 *               upgrade="" ...прочие поля состояния здания...>
 *   <upgradedata id_next_build="" allow="0|1" kad="" nano="" stat=""
 *                food="" prod="" time="" work="" sctarg="" sccur=""/>
 *   <tu K="" N="" S="" F="" P=""/>   (добавляемые ресурсы — не установлено точно "tu"/"tp" как аббревиатуры)
 *   <tp K="" N="" S="" F="" P=""/>   (снимаемые ресурсы)
 *   <bo ctype="" gtype=""/>          (разрешённые сочетания тип-ячейки/тип-грунта, повторяется)
 *   <MSG id="" titl="" head="" txt="" desc="" avail="" status=""/>  (повторяется, ключ — id)
 * </getbuildinfo>
 *
 * Кнопки-действия по полям корня (буквальная таблица из доклада,
 * подтверждена реальными вызовами buildinfo.as:162-305):
 *   un (не undefined)   -> "Отменить постройку" -> <cancelbuild c=""/>
 *   destr               -> "Снести"             -> <delbuild c=""/>
 *   up                  -> "Отменить апгрейд"   -> <cancelupgrade c=""/>
 *   activate            -> "Активировать"       -> <activatebuild c=""/>
 *   ia && ac            -> "Отменить активацию" -> <cancelactivate c=""/>  (НЕ cancelactivatebuild!)
 *   ia && !ac           -> статус-текст DEACTIVATION, кнопки нет
 *   deact               -> "Деактивировать"     -> <deactivatebuild c=""/>
 *   upgrade (gate dataupgrade.allow) -> "Апгрейд" -> <upgradebuild c=""/>
 * Поле `ia` — общий флаг и для активации, и для деактивации (одно и то
 * же поле, различается косвенно наличием `ac`) — серверная модель
 * (mechanics-buildings.md §2.2) подтверждена клиентом буквально 1:1.
 *
 * **Запрос на открытие этого окна — не был найден статическим анализом
 * AS2 (ни в `reqvestdata.as`, ни литералом в `call_tabs()`), подтверждён
 * живым запросом к реальному серверу (2026-09-08, Фаза 4) и окончательно
 * закрыт дампом `ds_useractiontype` в тот же день (protocol-client-server.md
 * §12.1) — там нет отдельного тега `getbuildinfo`, только общий
 * `getbuildmenu`/`UA_GetBuildMenu`**: `<getbuildmenu c="<num>"
 * tab="BuildingInfo"/>` — по аналогии с другими PN_/TI_/TL_-обработчиками,
 * диспетчеризуемыми через тот же `getbuildmenu` (см.
 * protocol-client-server.md §5.4). Реальный ответ живого сервера
 * (мир «Сфера Ориона», обработчик `PN_BuildingInfo`) содержит ещё и
 * `<alltabs><tab id="PN_BuildingInfo" .../><tab id="PN_BuildingArtefact"
 * .../></alltabs>` — список вкладок этого окна (в т.ч. подтверждает,
 * что артефактная вкладка здания диспетчеризуется через
 * `PN_BuildingArtefact`); здесь не разбирается отдельно, т.к. это
 * тот же кросс-доменный `tabs_array`/`TabDef`, что и везде (см.
 * `ui/tabs.ts`), не специфичные для этого домена данные. Разобран
 * парсером `parseInfoBuild()` без ошибок на реальных данных — все
 * прочие поля (`info`/`tp`/`bo`) совпали с ожиданиями.
 */
import { parseXmlFragment } from '../protocol/response';

export interface UpgradeData {
  idNextBuild: string | null;
  allow: boolean;
  kad: string;
  nano: string;
  stat: string;
  food: string;
  prod: string;
  time: string;
  work: string;
  sctarg: string | null;
  sccur: string | null;
}

export interface AllowGround {
  ctype: string | null;
  gtype: string | null;
}

export interface BuildInfoMessage {
  id: string | null;
  titl: string | null;
  head: string | null;
  txt: string | null;
  desc: string | null;
  avail: string | null;
  status: string | null;
}

export interface BuildInfoData {
  /** Сырые атрибуты корня — un/destr/up/activate/ia/ac/deact/upgrade и прочие поля состояния, см. таблицу выше. */
  info: Record<string, string>;
  upgrade: UpgradeData | null;
  /** <tu> — добавляемые при постройке ресурсы (K/N/S/F/P, "0" если сервер не прислал). */
  added: Record<'K' | 'N' | 'S' | 'F' | 'P', string>;
  /** <tp> — снимаемые (например при сносе) ресурсы. */
  removed: Record<'K' | 'N' | 'S' | 'F' | 'P', string>;
  allowGround: AllowGround[];
  messages: Record<string, BuildInfoMessage>;
}

const RES_KEYS = ['K', 'N', 'S', 'F', 'P'] as const;

export function parseInfoBuild(xml: string): BuildInfoData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'getbuildinfo') {
    throw new Error(`Ожидался корневой тег <getbuildinfo>, получено: ${root?.nodeName}`);
  }

  const info: Record<string, string> = {};
  for (const attr of Array.from(root.attributes)) info[attr.name] = attr.value;

  let upgrade: UpgradeData | null = null;
  const added: Record<string, string> = {};
  const removed: Record<string, string> = {};
  const allowGround: AllowGround[] = [];
  const messages: Record<string, BuildInfoMessage> = {};

  for (const el of Array.from(root.children)) {
    if (el.nodeName === 'upgradedata') {
      upgrade = {
        idNextBuild: el.getAttribute('id_next_build'),
        allow: el.getAttribute('allow') !== '0',
        kad: el.getAttribute('kad') ?? '--',
        nano: el.getAttribute('nano') ?? '--',
        stat: el.getAttribute('stat') ?? '--',
        food: el.getAttribute('food') ?? '--',
        prod: el.getAttribute('prod') ?? '--',
        time: el.getAttribute('time') ?? '--',
        work: el.getAttribute('work') ?? '--',
        sctarg: el.getAttribute('sctarg'),
        sccur: el.getAttribute('sccur'),
      };
    } else if (el.nodeName === 'tu') {
      for (const key of RES_KEYS) added[key] = el.getAttribute(key) ?? '0';
    } else if (el.nodeName === 'tp') {
      for (const key of RES_KEYS) removed[key] = el.getAttribute(key) ?? '0';
    } else if (el.nodeName === 'bo') {
      allowGround.push({ ctype: el.getAttribute('ctype'), gtype: el.getAttribute('gtype') });
    } else if (el.nodeName === 'MSG') {
      const id = el.getAttribute('id') ?? '';
      messages[id] = {
        id: el.getAttribute('id'),
        titl: el.getAttribute('titl'),
        head: el.getAttribute('head'),
        txt: el.getAttribute('txt'),
        desc: el.getAttribute('desc'),
        avail: el.getAttribute('avail'),
        status: el.getAttribute('status'),
      };
    }
  }

  for (const key of RES_KEYS) {
    if (added[key] === undefined) added[key] = '0';
    if (removed[key] === undefined) removed[key] = '0';
  }

  return {
    info,
    upgrade,
    added: added as BuildInfoData['added'],
    removed: removed as BuildInfoData['removed'],
    allowGround,
    messages,
  };
}

/** Подтверждено живым запросом (Фаза 4, 2026-09-08) — см. докстринг файла. */
export function buildGetBuildInfoCommand(cellNum: string): { c: string; tab: 'BuildingInfo' } {
  return { c: cellNum, tab: 'BuildingInfo' };
}
