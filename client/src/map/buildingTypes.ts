/**
 * id_building -> buildingname из живой БД мира (таблица ds_building,
 * ExecOnDB.php, world='eridan'/dbId=2, снято 2026-09-04). buildingname —
 * машинное имя, совпадает с ключом ассета (см. ASSET_CHECKLIST.md).
 * Значения вида NAME|N (13, 92-111) — это уровни апгрейда ARTEFACT_TOWER,
 * используют тот же ассет ARTEFACT_TOWER (buildingAssetKey() отрезает |N).
 * id=0 — NO_BUILDING, пустая ячейка без здания.
 */
export const BUILDING_NAMES: Record<number, string> = {
  0: "NO_BUILDING",
  1: "KADERIUM_FORGE",
  2: "NANO_CRYSTALLIZER",
  5: "CENTRAL_GATE_WRECK",
  6: "CENTRAL_GATE",
  7: "CONSTRUCTION_FACTORY",
  8: "SHELTER",
  9: "RESIDENTIAL_BUILDING",
  10: "PLANTATION",
  11: "SAIL_TRAP",
  12: "ARCHEOLOGICAL_INSTITUTE",
  13: "ARTEFACT_TOWER",
  14: "ALLIANCE_CONNECTOR",
  15: "CITY",
  16: "CRYSTAL_WEAVER",
  17: "FARM",
  18: "GONDOLA_TRANSPORTER",
  19: "HEAVY_WEAPON_MANUFACTURE",
  20: "INTERRING_TRADE_CONNECTOR",
  21: "KADERIUM_PRESS",
  22: "LIGHT_WEAPON_MANUFACTURE",
  23: "LOCAL_TRADE_CONNECTOR",
  24: "MESH_BOILER",
  25: "RING_GATE",
  26: "MILITARY_BASE",
  27: "MIND_FACTORY",
  28: "PRODIUM_COLLECTOR",
  29: "RESEARCH_CENTER",
  31: "RING_TRADE_CONNECTOR",
  32: "SAIL_FOREST",
  33: "SECRET_SERVICE_CENTER",
  34: "SOURCE",
  35: "SPYING_CENTER",
  36: "TRAINING_CAMP",
  37: "TUNNEL_RIVER_SCANNER",
  38: "VILLAGE",
  39: "WELL_LADLER",
  40: "HUGE_KADERIUM_FORGE",
  41: "LARGE_KADERIUM_FORGE",
  42: "LARGE_PRODIUM_COLLECTOR",
  43: "LARGE_NANO_CRYSTALLIZER",
  44: "HUGE_NANO_CRYSTALLIZER",
  45: "RUSHERS_TEMPLE",
  46: "CLOUD_ENDERS_TEMPLE",
  47: "ARCHEOLOGICAL_OUTPOST",
  48: "ENERGY_COLLECTOR",
  49: "CLONE_FARM",
  50: "HYDRO_FARM",
  51: "SMALL_STASIS_SILO",
  52: "MEDIUM_STASIS_SILO",
  53: "SMALL_FOOD_SILO",
  54: "MEDIUM_FOOD_SILO",
  55: "SMALL_KADERIUM_SILO",
  56: "MEDIUM_KADERIUM_SILO",
  57: "SMALL_NANO_SILO",
  58: "MEDIUM_NANO_SILO",
  59: "SMALL_PRODIUM_SILO",
  60: "MEDIUM_PRODIUM_SILO",
  61: "COLONIZATION_CENTER",
  64: "TERRAMORPH_TOWER",
  65: "VOCATION_BUILD",
  66: "SHIELD_BUILD",
  67: "TRAINING_CAMP2V",
  68: "LIGHT_WEAPON_MANUFACTURE2V",
  69: "HEAVY_WEAPON_MANUFACTURE2V",
  70: "TRAINING_CAMP2L",
  71: "LIGHT_WEAPON_MANUFACTURE2L",
  72: "HEAVY_WEAPON_MANUFACTURE2L",
  73: "TRAINING_CAMP2P",
  74: "LIGHT_WEAPON_MANUFACTURE2P",
  75: "HEAVY_WEAPON_MANUFACTURE2P",
  76: "SMALL_PRODIUM_DUMP",
  77: "SMALL_NANO_DUMP",
  78: "SMALL_KADERIUM_DUMP",
  79: "SMALL_FOOD_DUMP",
  80: "SMALL_STASIS_DUMP",
  81: "MEDIUM_PRODIUM_DUMP",
  82: "MEDIUM_NANO_DUMP",
  83: "MEDIUM_KADERIUM_DUMP",
  84: "MEDIUM_FOOD_DUMP",
  85: "MEDIUM_STASIS_DUMP",
  86: "HUGE_KADERIUM_FORGE_LIENSU",
  87: "HUGE_NANO_CRYSTALLIZER_LIENSU",
  90: "HUGE_PRODIUM_COLLECTOR",
  91: "PROTECTIVE_FIELD_GENERATOR",
  92: "ARTEFACT_TOWER|2",
  93: "ARTEFACT_TOWER|3",
  94: "ARTEFACT_TOWER|4",
  95: "ARTEFACT_TOWER|5",
  96: "ARTEFACT_TOWER|6",
  97: "ARTEFACT_TOWER|7",
  98: "ARTEFACT_TOWER|8",
  99: "ARTEFACT_TOWER|9",
  100: "ARTEFACT_TOWER|10",
  101: "ARTEFACT_TOWER|11",
  102: "ARTEFACT_TOWER|12",
  103: "ARTEFACT_TOWER|13",
  104: "ARTEFACT_TOWER|14",
  105: "ARTEFACT_TOWER|15",
  106: "ARTEFACT_TOWER|16",
  107: "ARTEFACT_TOWER|17",
  108: "ARTEFACT_TOWER|18",
  109: "ARTEFACT_TOWER|19",
  110: "ARTEFACT_TOWER|20",
  111: "ARTEFACT_TOWER|21",
};

/** Отрезает суффикс уровня ("ARTEFACT_TOWER|5" -> "ARTEFACT_TOWER") — ключ для поиска текстуры. */
export function buildingAssetKey(buildingname: string): string {
  const sep = buildingname.indexOf('|');
  return sep === -1 ? buildingname : buildingname.slice(0, sep);
}

/**
 * Раса владельца соты — определяет, КАК выглядят постройки, не какие
 * именно можно строить (game.as:2342-2357, `getLibrary(1, race)`):
 * здания с ОДНИМ и тем же `id_building`/`buildingname` рисуются
 * СОВЕРШЕННО разной графикой в зависимости от расы игрока (подтверждено
 * визуально — `RESIDENTIAL_BUILDING` в `build_tech.lib`/`build_bio.lib`/
 * `build_psi.lib` — три никак не похожих друг на друга спрайта). 1
 * (Voraner) → "tech", 2 (Liensu) → "bio", 3 (Psoleon) → "psi", иначе —
 * "bio" (тот же дефолт, что и в оригинале, `default: lib = "buildlibb"`).
 */
export type Race = 'tech' | 'bio' | 'psi';

export function raceFromNumber(race: number | null | undefined): Race {
  if (race === 1) return 'tech';
  if (race === 3) return 'psi';
  return 'bio';
}

/**
 * Обнаружено при извлечении спрайтов (2026-09-10): 11 построек существуют
 * ТОЛЬКО в одной из трёх расовых библиотек — их id/имя в БД уже
 * расово-специфичны (суффикс "2V"/"2L"/"2P" — Voraner/Liensu/Psoleon, или
 * "_LIENSU"), либо здание вообще эксклюзивно для одной расы
 * (`CLONE_FARM`/`HUGE_PRODIUM_COLLECTOR` — только у Liensu). Для них
 * текущая раса игрока роли не играет — ассет только один, реальный.
 */
const RACE_EXCLUSIVE_BUILDING: Record<string, Race> = {
  TRAINING_CAMP2V: 'tech',
  LIGHT_WEAPON_MANUFACTURE2V: 'tech',
  HEAVY_WEAPON_MANUFACTURE2V: 'tech',
  TRAINING_CAMP2L: 'bio',
  LIGHT_WEAPON_MANUFACTURE2L: 'bio',
  HEAVY_WEAPON_MANUFACTURE2L: 'bio',
  HUGE_KADERIUM_FORGE_LIENSU: 'bio',
  HUGE_NANO_CRYSTALLIZER_LIENSU: 'bio',
  HUGE_PRODIUM_COLLECTOR: 'bio',
  CLONE_FARM: 'bio',
  TRAINING_CAMP2P: 'psi',
  LIGHT_WEAPON_MANUFACTURE2P: 'psi',
  HEAVY_WEAPON_MANUFACTURE2P: 'psi',
};

/** Реальная раса ассета — переопределяется для расово-эксклюзивных построек (см. RACE_EXCLUSIVE_BUILDING), иначе раса игрока как есть. */
export function effectiveBuildingRace(buildingname: string, race: Race): Race {
  return RACE_EXCLUSIVE_BUILDING[buildingAssetKey(buildingname)] ?? race;
}

/** Путь к текстуре постройки с учётом расы владельца — client/public/assets/buildings/<tech|bio|psi>/<NAME>.png. */
export function buildingAssetPath(buildingname: string, race: Race): string {
  const key = buildingAssetKey(buildingname);
  return `/assets/buildings/${effectiveBuildingRace(buildingname, race)}/${key}.png`;
}
