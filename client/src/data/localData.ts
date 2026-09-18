/**
 * Разбор mark `loaddata` — статическая база локализации/характеристик
 * (здания, юниты, грунт, интерфейс и т.д.), присылается ОДИН раз через
 * `<load locations="0|1|3"/>` (та же команда, что и `<refresh>`/`<sota>`,
 * см. map/refreshData.ts). Источник формата — сервер
 * `ds/classes/UA/UA_Load.class.php:22-786` (`UA_Load::performAction`),
 * клиентский разбор — `parser.as:647-834` (`local_names`). Живьём
 * проверено 2026-09-04 (`client/scripts/dump-loaddata.ts`) — 317 КБ,
 * root `<load v="118">`, ~97 `<unit>`, здания (`<b>`), грунт
 * (`<groundtype>`), интерфейс (`<interface>`), артефакты и т.д.
 *
 * ВАЖНО про кодировку: PHP оборачивает большинство текстовых значений в
 * `urlencode()`, но живой ответ содержит их уже РАСКОДИРОВАННЫМИ (читаемая
 * кириллица без %XX) — механизм декодирования на сервере/в транспорте не
 * прослежен до конца, эмпирически декодировать самим не нужно. ЕДИНСТВЕННОЕ
 * исключение — `ATTACK`/`DEFENCE` у `<unit>`: сервер строит их как
 * `(value*100)."%25"` (`UA_Load.class.php:606,611,613` — буквальная
 * конкатенация строки "%25", а не urlencode числа), то есть это
 * закодированный знак процента, который реально приходит как литеральные
 * символы `%25` и нуждается в точечной замене на `%` (см. `fixPercent()`).
 * Другие `%` в тексте (например, "Перейти на %0D премиум" — шаблонный
 * плейсхолдер) — это НЕ url-кодирование, decodeURIComponent() на них
 * сломается или даст неверный результат, поэтому здесь НЕ используется
 * общий decode прохода по всей строке.
 */
import { parseXmlFragment } from '../protocol/response';

/** "50%25" -> "50%" (см. комментарий выше про ATTACK/DEFENCE). Прочее — без изменений. */
function fixPercent(value: string | null): string | null {
  if (value === null) return null;
  return value.endsWith('%25') ? value.slice(0, -2) : value;
}

export interface LocalBuilding {
  id: number;
  /** buildingname — машинное имя (= BUILDING_NAMES из map/buildingTypes.ts). */
  name: string | null;
  type: string | null;
  ln: string | null;
  unique: boolean;
  description: string;
  /** Однобуквенные коды стоимости постройки (K/N/S/F/P/W/C/A и т.п.) — сырые, не расшифрованы поресурсно в этой версии. */
  attrs: Record<string, string>;
}

export interface LocalUnit {
  /** id — это unitname (машинное имя), НЕ числовой id_unit. */
  id: string;
  ln: string | null;
  level: string | null;
  race: string | null;
  attack: string | null;
  defence: string | null;
  damageMin: string | null;
  damageMax: string | null;
  hitpoints: string | null;
  description: string;
  /** Прочие атрибуты (однобуквенные коды стоимости содержания и т.п.). */
  attrs: Record<string, string>;
}

export interface LocalGroundType {
  id: string;
  ln: string | null;
  description: string;
}

export interface LocalSimpleEntry {
  id: string;
  ln: string | null;
}

/**
 * Каталог артефактов (`<artefact id="" idn="" ln="" bn="" bv="">description</artefact>`,
 * `UA_Load.class.php:378-444`) — `id` это ПОЛНОЕ имя (`artefactname`,
 * тот же формат `prefix_scope_postfix`, что и `libname` в
 * buildArtefactData.ts и т.д.). `bv` — displaystring бонуса СВОЕЙ
 * кодировкой, ОТЛИЧНОЙ от рантайм-марок (`buildingartefact` и т.п.
 * используют литеральные "%2B"/"%25" — см. buildArtefactData.ts): здесь,
 * по буквальному комментарию сервера, "# = +, $ = %" — см. `fixBonusHash()`.
 */
export interface LocalArtefact {
  id: string;
  idn: string | null;
  ln: string | null;
  description: string;
  /** Машинное имя бонусного атрибута (совпадает с именем ресурса, например STASIS_ENERGY) — пусто, если у артефакта нет бонуса. */
  bonusName: string;
  /** Готовая для показа строка бонуса (уже "+800"/"+50%", не сырое "#800"/"#50$"). */
  bonusValue: string;
}

/** "#800" -> "+800", "#50$" -> "+50%" — см. докстринг LocalArtefact про "# = +, $ = %". */
function fixBonusHash(raw: string | null): string {
  if (!raw) return '';
  return raw.replace(/^#/, '+').replace(/\$$/, '%');
}

/**
 * Каталог "область артефакта" для дерева фильтров аукциона
 * (`<artscope>`, `UA_Load.class.php:195-208` + клиентский разбор
 * `parser.as:715-737`, `art_filter_arr.artscope`). В ОТЛИЧИЕ от всех
 * остальных узлов `loaddata`, это НЕ атрибуты — весь список одним
 * текстовым узлом: `"name|id|sizes|bonuses;name|id|sizes|bonuses;..."`,
 * где `sizes`/`bonuses` — сами через запятую списки id из
 * `artefactSizes`/`artefactBonuses` ниже (могут быть пустыми).
 */
export interface LocalArtefactScope {
  id: string;
  name: string | null;
  sizeIds: string[];
  bonusIds: string[];
}

export interface LocalData {
  version: string | null;
  buildings: Map<number, LocalBuilding>;
  units: Map<string, LocalUnit>;
  groundTypes: Map<string, LocalGroundType>;
  cellTypes: Map<string, LocalSimpleEntry>;
  interfaceStrings: Map<string, LocalSimpleEntry>;
  attributeNames: Map<string, LocalSimpleEntry>;
  messageScopes: Map<string, LocalSimpleEntry>;
  rings: Map<string, LocalSimpleEntry & { ringNumber: string | null }>;
  artefacts: Map<string, LocalArtefact>;
  /** `<artscope>`/`<artsize>`/`<artbonus>` — только для дерева фильтров аукциона (категория ARTEFACTS), см. LocalArtefactScope. */
  artefactScopes: Map<string, LocalArtefactScope>;
  artefactSizes: Map<string, LocalSimpleEntry>;
  artefactBonuses: Map<string, LocalSimpleEntry>;
  /** "OK", если сервер подтвердил целостность передачи (dataintegrity). */
  dataIntegrityOk: boolean;
}

/** `"name|id|extra..."` — общий вид одной записи `artscope`/`artsize`/`artbonus`. */
function parsePipeName(entry: string): { name: string | null; id: string; rest: string[] } {
  const [name, id, ...rest] = entry.split('|');
  return { name: name || null, id: id ?? '', rest };
}

/** `<artsize>`/`<artbonus>` — `"name|id;name|id;..."`. */
function parseSimplePipeList(text: string): LocalSimpleEntry[] {
  if (!text) return [];
  return text
    .split(';')
    .filter((entry) => entry.length > 0)
    .map((entry) => {
      const { name, id } = parsePipeName(entry);
      return { id, ln: name };
    });
}

/** `<artscope>` — `"name|id|sizeIds(,)|bonusIds(,);..."`, см. LocalArtefactScope. */
function parseArtScopeList(text: string): LocalArtefactScope[] {
  if (!text) return [];
  return text
    .split(';')
    .filter((entry) => entry.length > 0)
    .map((entry) => {
      const { name, id, rest } = parsePipeName(entry);
      const [sizes, bonuses] = rest;
      return {
        id,
        name,
        sizeIds: sizes ? sizes.split(',').filter((s) => s.length > 0) : [],
        bonusIds: bonuses ? bonuses.split(',').filter((b) => b.length > 0) : [],
      };
    });
}

const KNOWN_UNIT_FIELDS = new Set([
  'id',
  'ln',
  'level',
  'race',
  'ATTACK',
  'DEFENCE',
  'DAMAGE_MIN',
  'DAMAGE_MAX',
  'HITPOINTS',
]);

const KNOWN_BUILDING_FIELDS = new Set(['id', 'name', 'type', 'ln', 'unique']);

export function parseLocalData(xml: string): LocalData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'load') {
    throw new Error(`Ожидался корневой тег <load>, получено: ${root?.nodeName}`);
  }

  const result: LocalData = {
    version: root.getAttribute('v'),
    buildings: new Map(),
    units: new Map(),
    groundTypes: new Map(),
    cellTypes: new Map(),
    interfaceStrings: new Map(),
    attributeNames: new Map(),
    messageScopes: new Map(),
    rings: new Map(),
    artefacts: new Map(),
    artefactScopes: new Map(),
    artefactSizes: new Map(),
    artefactBonuses: new Map(),
    dataIntegrityOk: false,
  };

  for (const el of Array.from(root.children)) {
    switch (el.nodeName) {
      case 'b': {
        const attrs: Record<string, string> = {};
        for (const attr of Array.from(el.attributes)) {
          if (!KNOWN_BUILDING_FIELDS.has(attr.name)) attrs[attr.name] = attr.value;
        }
        const id = Number(el.getAttribute('id'));
        result.buildings.set(id, {
          id,
          name: el.getAttribute('name'),
          type: el.getAttribute('type'),
          ln: el.getAttribute('ln'),
          unique: el.getAttribute('unique') === '1',
          description: el.textContent ?? '',
          attrs,
        });
        break;
      }
      case 'unit': {
        const attrs: Record<string, string> = {};
        for (const attr of Array.from(el.attributes)) {
          if (!KNOWN_UNIT_FIELDS.has(attr.name)) attrs[attr.name] = attr.value;
        }
        const id = el.getAttribute('id') ?? '';
        result.units.set(id, {
          id,
          ln: el.getAttribute('ln'),
          level: el.getAttribute('level'),
          race: el.getAttribute('race'),
          attack: fixPercent(el.getAttribute('ATTACK')),
          defence: fixPercent(el.getAttribute('DEFENCE')),
          damageMin: el.getAttribute('DAMAGE_MIN'),
          damageMax: el.getAttribute('DAMAGE_MAX'),
          hitpoints: el.getAttribute('HITPOINTS'),
          description: el.textContent ?? '',
          attrs,
        });
        break;
      }
      case 'groundtype':
        result.groundTypes.set(el.getAttribute('id') ?? '', {
          id: el.getAttribute('id') ?? '',
          ln: el.getAttribute('ln'),
          description: el.textContent ?? '',
        });
        break;
      case 'celltype':
        result.cellTypes.set(el.getAttribute('id') ?? '', {
          id: el.getAttribute('id') ?? '',
          ln: el.getAttribute('ln'),
        });
        break;
      case 'interface':
        result.interfaceStrings.set(el.getAttribute('id') ?? '', {
          id: el.getAttribute('id') ?? '',
          ln: el.getAttribute('ln'),
        });
        break;
      case 'att':
        result.attributeNames.set(el.getAttribute('id') ?? '', {
          id: el.getAttribute('id') ?? '',
          ln: el.getAttribute('ln'),
        });
        break;
      case 'messagescope':
        result.messageScopes.set(el.getAttribute('id') ?? '', {
          id: el.getAttribute('id') ?? '',
          ln: el.getAttribute('ln'),
        });
        break;
      case 'ring':
        result.rings.set(el.getAttribute('id') ?? '', {
          id: el.getAttribute('id') ?? '',
          ln: el.getAttribute('ln'),
          ringNumber: el.getAttribute('ringnumber'),
        });
        break;
      case 'dataintegrity':
        result.dataIntegrityOk = el.getAttribute('ln') === 'OK';
        break;
      case 'artefact': {
        const id = el.getAttribute('id') ?? '';
        result.artefacts.set(id, {
          id,
          idn: el.getAttribute('idn'),
          ln: el.getAttribute('ln'),
          description: el.textContent ?? '',
          bonusName: el.getAttribute('bn') ?? '',
          bonusValue: fixBonusHash(el.getAttribute('bv')),
        });
        break;
      }
      case 'artscope':
        for (const s of parseArtScopeList(el.textContent ?? '')) result.artefactScopes.set(s.id, s);
        break;
      case 'artsize':
        for (const s of parseSimplePipeList(el.textContent ?? '')) result.artefactSizes.set(s.id, s);
        break;
      case 'artbonus':
        for (const s of parseSimplePipeList(el.textContent ?? '')) result.artefactBonuses.set(s.id, s);
        break;
      // 'comb' — не разобран в этой версии (не нужен для текущих
      // экранов); формат см. UA_Load.class.php:36-55, при необходимости
      // добавить.
      default:
        break;
    }
  }

  return result;
}
