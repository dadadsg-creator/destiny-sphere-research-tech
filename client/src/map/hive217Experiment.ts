import {
  Application,
  Assets,
  Container,
  FederatedPointerEvent,
  Graphics,
  Rectangle,
  Sprite,
  Text,
  Texture,
} from 'pixi.js';
import { buildHexGrid, buildingAnchor, hexPolygonPoints, mapPixelSize, type CellPosition } from './hexGrid';
import { HOME_ART_GROUND, HOME_ART_GROUND_V14_AUDIT } from './locationGround';
import {
  BUILDING_LABELS,
  TERRAIN_BUILD_HINTS,
  allowedVariantsForTerrain,
  buildRestrictionReason,
  canBuildVariantOnTerrain,
  isHomeTerrainCode,
  type BuildingVariant,
  type HomeTerrainCode,
} from './buildingGroundRules';
import { publicAssetUrl } from '../assets/publicAssetUrl';
import './hive217Experiment.css';

type BuildingState = 'construction' | 'working' | 'disabled';
type FusionKind = 'three' | 'seven';
type RenderKind = 'single' | 'construction' | FusionKind | 'gate';

interface StoredBuilding {
  cell: number;
  state: BuildingState;
  variant: BuildingVariant;
}

interface StoredHiveBuildings {
  version: 1;
  buildings: StoredBuilding[];
  savedAt?: string;
}

// Шесть внутренних углов игровой соты на подложке V13, уже пересчитанные
// из пикселей исходника 1536×1024 в координаты мира Pixi. Верхняя и нижняя
// грани имеют разную ширину из-за перспективы, поэтому простой scale здесь
// принципиально не подходит.
const PLAYFIELD_FRAME = {
  topLeft: { x: 155.88, y: -89.2885 },
  topRight: { x: 634.38, y: -89.2885 },
  right: { x: 856.14, y: 115.765 },
  bottomRight: { x: 686.52, y: 341.943 },
  bottomLeft: { x: 87.9, y: 341.943 },
  left: { x: -61.26, y: 115.765 },
} as const;

// Одноклеточные исходники имеют разную исходную ширину. Ограничиваем их
// одинаковым полезным габаритом внутри клетки; широкая военная база и заводы
// уменьшаются сильнее, а компактные жилые дома почти сохраняют масштаб.
const SINGLE_BUILDING_MAX_WIDTH = 44;
const SINGLE_BUILDING_MAX_SCALE = 0.82;

interface Fusion {
  kind: FusionKind;
  cellNums: number[];
  anchor: { x: number; y: number };
  depthY: number;
}

interface RenderItem {
  kind: RenderKind;
  cellNums: number[];
  anchor: { x: number; y: number };
  depthY: number;
  variant: BuildingVariant;
  scaleX?: number;
  scaleY?: number;
}

interface BuildingAsset {
  alias: string;
  src: string;
  logicalWidth: number;
  anchor: readonly [number, number];
}

interface EffectItem {
  item: RenderItem;
  asset: BuildingAsset;
  sprite: Sprite;
  groundShadow: Sprite | null;
  shadow: Sprite | null;
  emissive: Sprite | null;
  rearShield: Sprite | null;
  width: number;
  height: number;
  visible: boolean;
}

interface GroundTileState {
  alias: string;
  src: string;
  column: number;
  row: number;
  pngBytes: number;
  loading: boolean;
  sprite: Sprite | null;
}

interface ShadowTuning {
  azimuth: number;
  elevation: number;
  compression: number;
  alphaThreshold: number;
  rayWidth: number;
  joinRadius: number;
  baselineOffset: number;
  groundOpacity: number;
  neighborOpacity: number;
  contactOpacity: number;
  blur: number;
}

interface SilhouetteProfile {
  width: number;
  height: number;
  columns: Array<{ x: number; top: number; bottom: number }>;
}

const DEFAULT_SHADOW_TUNING: ShadowTuning = {
  azimuth: 220,
  elevation: 33,
  compression: 0.84,
  alphaThreshold: 26,
  rayWidth: 2.25,
  joinRadius: 0.8,
  baselineOffset: 0.06,
  groundOpacity: 0.58,
  neighborOpacity: 0.16,
  contactOpacity: 0.72,
  blur: 1.2,
};

const MAP_ASSETS = {
  ground: { alias: 'hive-lab/ground-hd', src: publicAssetUrl('/assets/hive-lab/hive-ground-hd.png') },
  groundRingV04: { alias: 'hive-lab/ground-ring-v04', src: publicAssetUrl('/assets/hive-lab/home-hive-large-v04.png?v=20261018-1') },
  groundRingV05: { alias: 'hive-lab/ground-ring-v05', src: publicAssetUrl('/assets/hive-lab/home-hive-large-v05.png?v=20261019-1') },
  groundRingV07: { alias: 'hive-lab/ground-ring-v07', src: publicAssetUrl('/assets/hive-lab/home-hive-large-v07.png?v=20261019-1') },
  groundRingV08: { alias: 'hive-lab/ground-ring-v08', src: publicAssetUrl('/assets/hive-lab/home-hive-large-v08.png?v=20261019-1') },
  groundRingV09: { alias: 'hive-lab/ground-ring-v09', src: publicAssetUrl('/assets/hive-lab/home-hive-large-v09.png?v=20261019-1') },
  groundRingV10: { alias: 'hive-lab/ground-ring-v10', src: publicAssetUrl('/assets/hive-lab/home-hive-large-v10-sharp.png?v=20261019-1') },
  groundRingV11: { alias: 'hive-lab/ground-ring-v11', src: publicAssetUrl('/assets/hive-lab/home-hive-large-v11-real-relief.png?v=20261020-1') },
  groundRingV12: { alias: 'hive-lab/ground-ring-v12', src: publicAssetUrl('/assets/hive-lab/home-hive-large-v12-classic-layout.png?v=20261020-1') },
  groundRingV13: { alias: 'hive-lab/ground-ring-v13', src: publicAssetUrl('/assets/hive-lab/home-hive-large-v13-faq-proportions.png?v=20261020-1') },
  groundRingV14: { alias: 'hive-lab/ground-ring-v14', src: publicAssetUrl('/assets/hive-lab/home-hive-large-v14-max-detail-4608.png?v=20261020-1') },
  groundRingV14Preview: { alias: 'hive-lab/ground-ring-v14-preview', src: publicAssetUrl('/assets/hive-lab/home-hive-large-v14-preview.png?v=20261021-1') },
  groundRingV15: { alias: 'hive-lab/ground-ring-v15', src: publicAssetUrl('/assets/hive-lab/home-hive-large-v15-cell-locked-4608.png?v=20261020-1') },
  v02: { alias: 'hive-lab/v02', src: publicAssetUrl('/assets/hive-lab/house-v02.png'), logicalWidth: 56, anchor: [0.5, 0.954128] as const },
  v02Emissive: { alias: 'hive-lab/v02-emissive', src: publicAssetUrl('/assets/hive-lab/house-v02-emissive.png') },
  v03: { alias: 'hive-lab/v03', src: publicAssetUrl('/assets/hive-lab/house-v03.png'), logicalWidth: 54, anchor: [0.5, 0.839867] as const },
  v03Emissive: { alias: 'hive-lab/v03-emissive', src: publicAssetUrl('/assets/hive-lab/house-v03-emissive.png') },
  v05: { alias: 'hive-lab/v05', src: publicAssetUrl('/assets/hive-lab/house-v05.png'), logicalWidth: 52, anchor: [0.5, 0.840701] as const },
  v05Emissive: { alias: 'hive-lab/v05-emissive', src: publicAssetUrl('/assets/hive-lab/house-v05-emissive.png') },
  three: { alias: 'hive-lab/complex-3', src: publicAssetUrl('/assets/hive-lab/complex-3.png'), logicalWidth: 92, anchor: [0.5, 0.901585] as const },
  threeEmissive: { alias: 'hive-lab/complex-3-emissive', src: publicAssetUrl('/assets/hive-lab/complex-3-emissive.png') },
  seven: { alias: 'hive-lab/complex-7', src: publicAssetUrl('/assets/hive-lab/complex-7.png'), logicalWidth: 126, anchor: [0.5, 0.920767] as const },
  sevenEmissive: { alias: 'hive-lab/complex-7-emissive', src: publicAssetUrl('/assets/hive-lab/complex-7-emissive.png') },
  stasis: { alias: 'hive-lab/stasis-collector-v01', src: publicAssetUrl('/assets/hive-lab/stasis-collector-v01.png'), logicalWidth: 56, anchor: [0.5, 0.965] as const },
  forge: { alias: 'hive-lab/kaderium-forge-v03-dispatch', src: publicAssetUrl('/assets/hive-lab/kaderium-forge-v03-dispatch.png'), logicalWidth: 56, anchor: [0.5, 0.965] as const },
  nano: { alias: 'hive-lab/nano-crystallizer-v02-dispatch', src: publicAssetUrl('/assets/hive-lab/nano-crystallizer-v02-dispatch.png'), logicalWidth: 62, anchor: [0.5, 0.97] as const },
  food: { alias: 'hive-lab/greenhouse-farm-v02', src: publicAssetUrl('/assets/hive-lab/greenhouse-farm-v02.png'), logicalWidth: 60, anchor: [0.5, 0.97] as const },
  lightWeapons: { alias: 'hive-lab/light-weapons-factory-v02', src: publicAssetUrl('/assets/hive-lab/light-weapons-factory-v02.png'), logicalWidth: 62, anchor: [0.5, 0.97] as const },
  lightWeaponsEmissive: { alias: 'hive-lab/light-weapons-factory-v02-emissive', src: publicAssetUrl('/assets/hive-lab/light-weapons-factory-v02-emissive.png?v=20261021-1') },
  heavyWeapons: { alias: 'hive-lab/heavy-weapons-factory-v02', src: publicAssetUrl('/assets/hive-lab/heavy-weapons-factory-v02.png'), logicalWidth: 62, anchor: [0.5, 0.97] as const },
  heavyWeaponsEmissive: { alias: 'hive-lab/heavy-weapons-factory-v02-emissive', src: publicAssetUrl('/assets/hive-lab/heavy-weapons-factory-v02-emissive.png?v=20261021-1') },
  militaryBase: { alias: 'hive-lab/military-base-v03', src: publicAssetUrl('/assets/hive-lab/military-base-v03.png?v=20261018-3'), logicalWidth: 70, anchor: [0.5, 0.97] as const },
  militaryBaseShield: { alias: 'hive-lab/military-base-v03-rear-shield', src: publicAssetUrl('/assets/hive-lab/military-base-v03-rear-shield.png?v=20261021-2') },
  ringGate: { alias: 'hive-lab/ring-gate-v02', src: publicAssetUrl('/assets/hive-lab/ring-gate-v02.png?v=20261021-1'), logicalWidth: 70, anchor: [0.5, 0.96] as const },
  ringGateEmissive: { alias: 'hive-lab/ring-gate-v02-emissive', src: publicAssetUrl('/assets/hive-lab/ring-gate-v02-emissive.png?v=20261021-1') },
};

const EMISSIVE_ASSET_BY_KIND = {
  v02: MAP_ASSETS.v02Emissive,
  v03: MAP_ASSETS.v03Emissive,
  v05: MAP_ASSETS.v05Emissive,
  three: MAP_ASSETS.threeEmissive,
  seven: MAP_ASSETS.sevenEmissive,
  lightWeapons: MAP_ASSETS.lightWeaponsEmissive,
  heavyWeapons: MAP_ASSETS.heavyWeaponsEmissive,
  ringGate: MAP_ASSETS.ringGateEmissive,
} as const;

function isResidentialVariant(variant: BuildingVariant | undefined): variant is 0 | 1 | 2 {
  return variant === 0 || variant === 1 || variant === 2;
}

function assetForVariant(variant: BuildingVariant): BuildingAsset {
  return [MAP_ASSETS.v02, MAP_ASSETS.v03, MAP_ASSETS.v05, MAP_ASSETS.stasis, MAP_ASSETS.forge, MAP_ASSETS.nano, MAP_ASSETS.food, MAP_ASSETS.lightWeapons, MAP_ASSETS.heavyWeapons, MAP_ASSETS.militaryBase][variant];
}

function effectKeyForItem(item: RenderItem): keyof typeof EMISSIVE_ASSET_BY_KIND | null {
  if (item.kind === 'gate') return 'ringGate';
  if (item.kind === 'three' || item.kind === 'seven') return item.kind;
  if (item.variant === 0) return 'v02';
  if (item.variant === 1) return 'v03';
  if (item.variant === 2) return 'v05';
  if (item.variant === 7) return 'lightWeapons';
  if (item.variant === 8) return 'heavyWeapons';
  // Экран военной базы будет восстановлен отдельным задним слоем по концепту.
  // До этого база показывается чистым цветовым спрайтом без временных эффектов.
  if (item.variant === 9) return null;
  return null;
}

// Сумма загружаемых PNG из client/public/assets/hive-lab.
// В память попадает только одна из доступных подложек.
const RUNTIME_PNG_BYTES = {
  legacy: 18_011_792,
  ringV04: 19_630_976,
  ringV05: 14_869_209,
  ringV07: 11_381_648,
  ringV08: 11_208_102,
  ringV09: 11_024_975,
  ringV10: 24_780_516,
  ringV11: 3_927_352,
  ringV12: 3_778_749,
  ringV13: 3_934_751,
  ringV14Preview: 5_090_805,
  ringV15: 37_862_672,
} as const;

const V14_TILE_COLUMNS = 6;
const V14_TILE_ROWS = 4;
const V14_TILE_PNG_BYTES = [
  1_767_855, 1_789_072, 1_656_685, 1_650_465, 1_789_533, 1_735_811,
  1_829_468, 1_736_975, 1_583_407, 1_626_385, 1_687_468, 1_828_169,
  1_808_753, 1_746_620, 1_595_559, 1_606_780, 1_701_508, 1_779_722,
  1_795_475, 1_767_284, 1_764_207, 1_731_160, 1_773_209, 1_791_795,
] as const;

const V14_GROUND_TILES: GroundTileState[] = V14_TILE_PNG_BYTES.map((pngBytes, index) => {
  const row = Math.floor(index / V14_TILE_COLUMNS);
  const column = index % V14_TILE_COLUMNS;
  return {
    alias: `hive-lab/ground-ring-v14-r${row}-c${column}`,
    src: publicAssetUrl(`/assets/hive-lab/home-hive-large-v14-tiles/r${row}-c${column}.png?v=20261021-1`),
    column,
    row,
    pngBytes,
    loading: false,
    sprite: null,
  };
});

interface WaterSurface {
  x: number;
  y: number;
  rx: number;
  ry: number;
  jet?: number;
}

// Координаты сняты с принятого водного стенда и выражены в долях PNG.
const WATER_SURFACES: Partial<Record<keyof typeof EMISSIVE_ASSET_BY_KIND, WaterSurface[]>> = {
  v02: [{ x: 0.37, y: 0.885, rx: 0.078, ry: 0.018 }],
  v03: [{ x: 0.805, y: 0.69, rx: 0.082, ry: 0.023 }],
  three: [{ x: 0.555, y: 0.59, rx: 0.145, ry: 0.052, jet: 0.125 }],
  seven: [
    { x: 0.515, y: 0.35, rx: 0.07, ry: 0.021, jet: 0.11 },
    { x: 0.205, y: 0.625, rx: 0.06, ry: 0.021, jet: 0.075 },
    { x: 0.52, y: 0.625, rx: 0.055, ry: 0.019, jet: 0.08 },
    { x: 0.825, y: 0.625, rx: 0.06, ry: 0.021, jet: 0.075 },
    { x: 0.405, y: 0.825, rx: 0.075, ry: 0.022 },
    { x: 0.63, y: 0.825, rx: 0.075, ry: 0.022 },
  ],
};

const TERRAIN_NAMES: Record<string, string> = {
  D: 'песок',
  S: 'земля',
  G: 'трава',
  W: 'вода',
  X: 'системный узел',
};

const TERRAIN_COLORS: Record<string, number> = {
  D: 0xd6a45f,
  S: 0xb98d65,
  G: 0x72a567,
  W: 0x4faac8,
  X: 0x9b7cff,
};

const HIVE_BUILDINGS_STORAGE_KEY = 'ds:hive217:buildings:v1';
const HIVE_BUILDINGS_SNAPSHOT_STORAGE_KEY = 'ds:hive217:buildings:snapshot:v1';
const TERRAIN_AUDIT_CODES: readonly HomeTerrainCode[] = ['S', 'D', 'G', 'W', 'X'];
const TERRAIN_TARGET_COUNTS: Record<HomeTerrainCode, number> = {
  S: 92,
  D: 35,
  G: 45,
  W: 44,
  X: 1,
};

const NEIGHBOR_OFFSETS = [
  [24, -19.5],
  [48, 0],
  [24, 19.5],
  [-24, 19.5],
  [-48, 0],
  [-24, -19.5],
] as const;

function coordinateKey(x: number, y: number): string {
  return `${x}:${y}`;
}

function makeNeighborTable(cells: CellPosition[]): Map<number, number[]> {
  const byCoordinate = new Map(cells.map((cell) => [coordinateKey(cell.x, cell.y), cell.num]));
  return new Map(cells.map((cell) => [
    cell.num,
    NEIGHBOR_OFFSETS
      .map(([dx, dy]) => byCoordinate.get(coordinateKey(cell.x + dx, cell.y + dy)))
      .filter((num): num is number => num !== undefined),
  ]));
}

function groupAnchor(cellNums: number[], cells: CellPosition[]): { anchor: { x: number; y: number }; depthY: number } {
  const anchors = cellNums.map((cellNum) => buildingAnchor(cells[cellNum]));
  return {
    anchor: {
      x: anchors.reduce((sum, point) => sum + point.x, 0) / anchors.length,
      y: Math.max(...anchors.map((point) => point.y)) - 4.5,
    },
    depthY: anchors.reduce((sum, point) => sum + point.y, 0) / anchors.length,
  };
}

function resolveFusions(working: Set<number>, cells: CellPosition[], neighbors: Map<number, number[]>): Fusion[] {
  const used = new Set<number>();
  const fusions: Fusion[] = [];

  for (const center of [...working].sort((a, b) => a - b)) {
    const ring = neighbors.get(center) ?? [];
    if (ring.length !== 6 || ring.some((cellNum) => !working.has(cellNum) || used.has(cellNum)) || used.has(center)) continue;
    const cellNums = [center, ...ring].sort((a, b) => a - b);
    const geometry = groupAnchor(cellNums, cells);
    fusions.push({ kind: 'seven', cellNums, ...geometry });
    cellNums.forEach((cellNum) => used.add(cellNum));
  }

  for (const first of [...working].sort((a, b) => a - b)) {
    if (used.has(first)) continue;
    const candidates = (neighbors.get(first) ?? []).filter((cellNum) => working.has(cellNum) && !used.has(cellNum));
    let triangle: number[] | null = null;
    for (let left = 0; left < candidates.length - 1 && !triangle; left += 1) {
      for (let right = left + 1; right < candidates.length; right += 1) {
        if ((neighbors.get(candidates[left]) ?? []).includes(candidates[right])) {
          triangle = [first, candidates[left], candidates[right]].sort((a, b) => a - b);
          break;
        }
      }
    }
    if (!triangle) continue;
    const geometry = groupAnchor(triangle, cells);
    fusions.push({ kind: 'three', cellNums: triangle, ...geometry });
    triangle.forEach((cellNum) => used.add(cellNum));
  }

  return fusions;
}

function outerHivePolygon(inset = 0): number[] {
  const vertices = [
    PLAYFIELD_FRAME.topLeft,
    PLAYFIELD_FRAME.topRight,
    PLAYFIELD_FRAME.right,
    PLAYFIELD_FRAME.bottomRight,
    PLAYFIELD_FRAME.bottomLeft,
    PLAYFIELD_FRAME.left,
  ];
  if (inset <= 0) return vertices.flatMap(({ x, y }) => [x, y]);
  const center = vertices.reduce((sum, point) => ({ x: sum.x + point.x / 6, y: sum.y + point.y / 6 }), { x: 0, y: 0 });
  return vertices.flatMap(({ x, y }) => {
    const distance = Math.hypot(x - center.x, y - center.y);
    const factor = Math.max(0, (distance - inset) / distance);
    return [center.x + (x - center.x) * factor, center.y + (y - center.y) * factor];
  });
}

function projectPlayfieldPoint(x: number, y: number): { x: number; y: number } {
  const size = mapPixelSize();
  const halfHeight = size.height / 2;
  const topHalf = y <= halfHeight;
  const t = topHalf ? y / halfHeight : (y - halfHeight) / halfHeight;
  // Внешний контур исходной сетки не идеальная диагональ, а 12-вершинная
  // оболочка крайних гексов. Используем её реальную границу, иначе верхние
  // и нижние крайние клетки неизбежно вылезают из шестигранника.
  const logicalInset = y <= 6.5
    ? 216 - y * (24 / 6.5)
    : y <= 162.5
      ? 192 * (162.5 - y) / 156
      : y <= 175.5
        ? 0
        : y <= 331.5
          ? 192 * (y - 175.5) / 156
          : 192 + (y - 331.5) * (24 / 6.5);
  const logicalLeft = logicalInset;
  const logicalRight = size.width - logicalInset;
  const targetLeftA = topHalf ? PLAYFIELD_FRAME.topLeft : PLAYFIELD_FRAME.left;
  const targetLeftB = topHalf ? PLAYFIELD_FRAME.left : PLAYFIELD_FRAME.bottomLeft;
  const targetRightA = topHalf ? PLAYFIELD_FRAME.topRight : PLAYFIELD_FRAME.right;
  const targetRightB = topHalf ? PLAYFIELD_FRAME.right : PLAYFIELD_FRAME.bottomRight;
  const targetLeftX = targetLeftA.x + (targetLeftB.x - targetLeftA.x) * t;
  const targetRightX = targetRightA.x + (targetRightB.x - targetRightA.x) * t;
  const rowPosition = (x - logicalLeft) / Math.max(1, logicalRight - logicalLeft);
  const projectedY = targetLeftA.y + (targetLeftB.y - targetLeftA.y) * t;
  return {
    x: targetLeftX + (targetRightX - targetLeftX) * rowPosition,
    y: projectedY,
  };
}

function projectedScaleAt(y: number): { x: number; y: number } {
  const size = mapPixelSize();
  const halfHeight = size.height / 2;
  const clampedY = Math.max(0, Math.min(size.height, y));
  const topHalf = clampedY <= halfHeight;
  const t = topHalf ? clampedY / halfHeight : (clampedY - halfHeight) / halfHeight;
  const logicalInset = clampedY <= 6.5
    ? 216 - clampedY * (24 / 6.5)
    : clampedY <= 162.5
      ? 192 * (162.5 - clampedY) / 156
      : clampedY <= 175.5
        ? 0
        : clampedY <= 331.5
          ? 192 * (clampedY - 175.5) / 156
          : 192 + (clampedY - 331.5) * (24 / 6.5);
  const logicalWidth = size.width - logicalInset * 2;
  const leftA = topHalf ? PLAYFIELD_FRAME.topLeft : PLAYFIELD_FRAME.left;
  const leftB = topHalf ? PLAYFIELD_FRAME.left : PLAYFIELD_FRAME.bottomLeft;
  const rightA = topHalf ? PLAYFIELD_FRAME.topRight : PLAYFIELD_FRAME.right;
  const rightB = topHalf ? PLAYFIELD_FRAME.right : PLAYFIELD_FRAME.bottomRight;
  const targetLeft = leftA.x + (leftB.x - leftA.x) * t;
  const targetRight = rightA.x + (rightB.x - rightA.x) * t;
  return {
    x: (targetRight - targetLeft) / logicalWidth,
    y: (leftB.y - leftA.y) / halfHeight,
  };
}

function projectedCellPolygon(cell: CellPosition): number[] {
  const points = hexPolygonPoints();
  const projected: number[] = [];
  for (let index = 0; index < points.length; index += 2) {
    const point = projectPlayfieldPoint(cell.x + points[index], cell.y + points[index + 1]);
    projected.push(point.x, point.y);
  }
  return projected;
}

function pointInPolygon(x: number, y: number, polygon: number[]): boolean {
  let inside = false;
  for (let i = 0, j = polygon.length - 2; i < polygon.length; j = i, i += 2) {
    const xi = polygon[i];
    const yi = polygon[i + 1];
    const xj = polygon[j];
    const yj = polygon[j + 1];
    const crosses = (yi > y) !== (yj > y)
      && x < (xj - xi) * (y - yi) / (yj - yi) + xi;
    if (crosses) inside = !inside;
  }
  return inside;
}

function pointOnPolygonBoundary(x: number, y: number, polygon: number[], epsilon = 0.01): boolean {
  for (let i = 0, j = polygon.length - 2; i < polygon.length; j = i, i += 2) {
    const ax = polygon[j];
    const ay = polygon[j + 1];
    const bx = polygon[i];
    const by = polygon[i + 1];
    const dx = bx - ax;
    const dy = by - ay;
    const lengthSquared = dx * dx + dy * dy;
    if (lengthSquared === 0) continue;
    const projection = Math.max(0, Math.min(1, ((x - ax) * dx + (y - ay) * dy) / lengthSquared));
    const nearestX = ax + projection * dx;
    const nearestY = ay + projection * dy;
    if (Math.hypot(x - nearestX, y - nearestY) <= epsilon) return true;
  }
  return false;
}

function pointInOrOnPolygon(x: number, y: number, polygon: number[]): boolean {
  return pointInPolygon(x, y, polygon) || pointOnPolygonBoundary(x, y, polygon);
}

export class Hive217Experiment {
  readonly app: Application;
  readonly world = new Container();
  private readonly playfieldLayer = new Container();
  readonly cells = buildHexGrid();
  readonly terrain = HOME_ART_GROUND.split('');
  readonly actualTerrain: HomeTerrainCode[] = HOME_ART_GROUND.split('') as HomeTerrainCode[];
  readonly buildings = new Map<number, BuildingState>();
  readonly variants = new Map<number, BuildingVariant>();

  private readonly neighbors = makeNeighborTable(this.cells);
  private readonly buildingLayer = new Container();
  private readonly groundTileLayer = new Container();
  private readonly shadowLayer = new Container();
  private readonly contactShadowLayer = new Graphics();
  private readonly forgeGlowLayer = new Graphics();
  private readonly waterLayer = new Graphics();
  private readonly activityLayer = new Graphics();
  private readonly emissiveLayer = new Container();
  private readonly hoverLayer = new Graphics();
  private readonly selectionLayer = new Graphics();
  private readonly domeBaseLayer = new Graphics();
  private readonly terrainCalibrationLayer = new Graphics();
  private readonly terrainCalibrationLabelLayer = new Container();
  private readonly domeLayer = new Graphics();
  private readonly interactiveLayer = new Graphics();
  private readonly hud: HTMLDivElement;
  private effectItems: EffectItem[] = [];
  private readonly groundTiles = V14_GROUND_TILES.map((tile) => ({ ...tile }));
  private groundPreview: Sprite | null = null;
  private groundTileTextureBytes = 0;
  private loadedGroundTilePngBytes = 0;
  private selectedCell = 105;
  private hoveredCell: number | null = null;
  private fpsSamples: number[] = [];
  private lastHudUpdate = 0;
  private lastFusionCount = 0;
  private lastVisualCount = 0;
  private lastVisibleCount = 0;
  private runtimeTextureBytes = 0;
  private animationEnabled = true;
  private animationIntensity = 1.6;
  private demoMode = false;
  private lastEffectUpdate = 0;
  private shadowsEnabled = true;
  private contactShadowsEnabled = true;
  private fusionsEnabled = true;
  private ringGroundEnabled = false;
  private ringGroundVersion: 'v04' | 'v05' | 'v07' | 'v08' | 'v09' | 'v10' | 'v11' | 'v12' | 'v13' | 'v14' | 'v15' = 'v15';
  private terrainCalibrationEnabled = false;
  private terrainAuditEnabled = false;
  private buildNotice = '';
  private shadowTuning: ShadowTuning = { ...DEFAULT_SHADOW_TUNING };
  private readonly shadowTextureCache = new Map<string, Texture>();
  private readonly silhouetteCache = new Map<string, SilhouetteProfile>();
  private viewScale = 1;
  private drawerOpen = false;
  private cameraInitialized = false;
  private overviewMode = false;
  private cameraDragging = false;
  private cameraDragMoved = false;
  private cameraDragStart = { x: 0, y: 0 };
  private cameraWorldStart = { x: 0, y: 0 };
  private readonly resizeHandler = () => this.handleCameraResize();
  private readonly wheelHandler = (event: WheelEvent) => {
    event.preventDefault();
    const rect = this.app.canvas.getBoundingClientRect();
    const screenPoint = {
      x: (event.clientX - rect.left) * this.app.screen.width / Math.max(1, rect.width),
      y: (event.clientY - rect.top) * this.app.screen.height / Math.max(1, rect.height),
    };
    const factor = Math.exp(-event.deltaY * 0.0012);
    this.zoomCameraAt(screenPoint, this.world.scale.x * factor);
  };

  private constructor(app: Application, parent: HTMLElement) {
    this.app = app;
    this.forgeGlowLayer.blendMode = 'add';
    this.hud = this.createHud(parent);
  }

  static async create(parent: HTMLElement): Promise<Hive217Experiment> {
    const app = new Application();
    await app.init({
      resizeTo: parent,
      backgroundColor: 0x020915,
      antialias: true,
      autoDensity: true,
      // 1.5x остаётся резче исходного клиента, но уменьшает площадь
      // framebuffer на HiDPI-экране на 44% относительно 2x.
      resolution: Math.min(window.devicePixelRatio || 1, 1.5),
      roundPixels: true,
      preference: 'webgl',
    });
    parent.appendChild(app.canvas);
    const experiment = new Hive217Experiment(app, parent);
    await experiment.initialize();
    return experiment;
  }

  private async initialize(): Promise<void> {
    const urlParams = new URLSearchParams(window.location.search);
    this.demoMode = urlParams.get('demo') === 'maxim' || import.meta.env.VITE_SHOWCASE_SCREEN === 'hive-217';
    // Полигон всегда открывается обзором всей игровой соты. Масштаб
    // рассчитывается по доступной области окна, после чего игрок может
    // приблизить нужный участок колесом и перемещать карту перетаскиванием.
    this.overviewMode = true;
    if (urlParams.get('tools') === 'open') this.setDrawerOpen(true);
    const selectedCellValue = urlParams.get('selectedCell');
    const selectedCellParam = selectedCellValue === null ? Number.NaN : Number(selectedCellValue);
    if (Number.isInteger(selectedCellParam) && selectedCellParam >= 0 && selectedCellParam < this.cells.length) {
      this.selectedCell = selectedCellParam;
    }
    this.contactShadowsEnabled = urlParams.get('contact-shadow') !== 'off';
    this.fusionsEnabled = urlParams.get('fusions') !== 'off';
    this.terrainAuditEnabled = urlParams.get('terrain-audit') === 'on';
    this.terrainCalibrationEnabled = urlParams.get('terrain-map') === 'on' || this.terrainAuditEnabled;
    const groundParam = urlParams.get('ground') ?? (this.demoMode ? 'ring-v14' : null);
    this.ringGroundEnabled = groundParam !== 'legacy';
    this.ringGroundVersion = groundParam === 'ring-v04'
      ? 'v04'
      : groundParam === 'ring-v05'
        ? 'v05'
        : groundParam === 'ring-v07'
          ? 'v07'
          : groundParam === 'ring-v08'
            ? 'v08'
            : groundParam === 'ring-v09'
              ? 'v09'
              : groundParam === 'ring-v10'
                ? 'v10'
                : groundParam === 'ring-v11'
                  ? 'v11'
                    : groundParam === 'ring-v12'
                      ? 'v12'
                      : groundParam === 'ring-v13'
                        ? 'v13'
                        : groundParam === 'ring-v14' ? 'v14' : 'v15';
    this.restoreTerrainAudit();
    const contactShadowInput = this.hud.querySelector<HTMLInputElement>('[data-action="contact-shadows"]');
    const fusionsInput = this.hud.querySelector<HTMLInputElement>('[data-action="fusions"]');
    const terrainMapInput = this.hud.querySelector<HTMLInputElement>('[data-action="terrain-map"]');
    const terrainAuditInput = this.hud.querySelector<HTMLInputElement>('[data-action="terrain-audit"]');
    if (contactShadowInput) contactShadowInput.checked = this.contactShadowsEnabled;
    if (fusionsInput) fusionsInput.checked = this.fusionsEnabled;
    if (terrainMapInput) terrainMapInput.checked = this.terrainCalibrationEnabled;
    if (terrainAuditInput) terrainAuditInput.checked = this.terrainAuditEnabled;
    const activeGround = !this.ringGroundEnabled
      ? MAP_ASSETS.ground
      : this.ringGroundVersion === 'v04'
        ? MAP_ASSETS.groundRingV04
        : this.ringGroundVersion === 'v05'
          ? MAP_ASSETS.groundRingV05
          : this.ringGroundVersion === 'v07'
            ? MAP_ASSETS.groundRingV07
            : this.ringGroundVersion === 'v08'
              ? MAP_ASSETS.groundRingV08
              : this.ringGroundVersion === 'v09'
                ? MAP_ASSETS.groundRingV09
                : this.ringGroundVersion === 'v10'
                  ? MAP_ASSETS.groundRingV10
                  : this.ringGroundVersion === 'v11'
                    ? MAP_ASSETS.groundRingV11
                    : this.ringGroundVersion === 'v12'
                      ? MAP_ASSETS.groundRingV12
                      : this.ringGroundVersion === 'v13'
                        ? MAP_ASSETS.groundRingV13
                        : this.ringGroundVersion === 'v14' ? MAP_ASSETS.groundRingV14Preview : MAP_ASSETS.groundRingV15;
    const groundAliases = new Set([
      MAP_ASSETS.ground.alias,
      MAP_ASSETS.groundRingV04.alias,
      MAP_ASSETS.groundRingV05.alias,
      MAP_ASSETS.groundRingV07.alias,
      MAP_ASSETS.groundRingV08.alias,
      MAP_ASSETS.groundRingV09.alias,
      MAP_ASSETS.groundRingV10.alias,
      MAP_ASSETS.groundRingV11.alias,
      MAP_ASSETS.groundRingV12.alias,
      MAP_ASSETS.groundRingV13.alias,
      MAP_ASSETS.groundRingV14.alias,
      MAP_ASSETS.groundRingV14Preview.alias,
      MAP_ASSETS.groundRingV15.alias,
    ]);
    const activeAssets = Object.values(MAP_ASSETS).filter(({ alias }) => !groundAliases.has(alias) || alias === activeGround.alias);
    await Assets.load(activeAssets.map(({ alias, src }) => ({ alias, src })));
    this.runtimeTextureBytes = [...new Set(activeAssets.map(({ alias }) => alias))]
      .reduce((sum, alias) => {
        const texture = Assets.get(alias) as Texture;
        return sum + Math.round(texture.width) * Math.round(texture.height) * 4;
      }, 0);
    this.verifyPlayfieldGeometry();
    this.app.stage.addChild(this.world);
    this.drawPlatform();
    this.drawSharedGround();
    this.drawDome();
    this.drawTerrainCalibration();
    this.playfieldLayer.addChild(
      this.terrainCalibrationLayer,
      this.shadowLayer,
      this.contactShadowLayer,
      this.buildingLayer,
      this.forgeGlowLayer,
      this.waterLayer,
      this.emissiveLayer,
      this.activityLayer,
      this.terrainCalibrationLabelLayer,
      this.hoverLayer,
      this.selectionLayer,
      this.interactiveLayer,
    );
    this.world.addChild(
      this.domeBaseLayer,
      this.playfieldLayer,
      this.domeLayer,
    );
    const buildingsRestored = !this.demoMode && this.restoreBuildingState();
    if (!buildingsRestored) {
      if (urlParams.get('density') === 'full') this.seedAllBuildings();
      else this.seedGameBuildings();
      this.saveBuildingState();
    }
    if (this.demoMode) {
      const subtitle = this.hud.querySelector<HTMLElement>('[data-role="subtitle"]');
      const help = this.hud.querySelector<HTMLElement>('[data-role="help"]');
      const saveSnapshotButton = this.hud.querySelector<HTMLButtonElement>('[data-action="save-building-snapshot"]');
      const restoreSnapshotButton = this.hud.querySelector<HTMLButtonElement>('[data-action="restore-building-snapshot"]');
      if (subtitle) subtitle.textContent = 'Демонстрация для Максима · 217 клеток · состояние не сохраняется';
      if (help) help.textContent = 'Демонстрационный режим не меняет сохранённую застройку. Колесо меняет масштаб, перетаскивание двигает карту.';
      if (saveSnapshotButton) saveSnapshotButton.disabled = true;
      if (restoreSnapshotButton) restoreSnapshotButton.disabled = true;
      this.setBuildingSaveStatus('Демонстрационный режим: сохранения отключены.');
    }
    this.bindInteraction();
    this.rebuildBuildings();
    this.applyTerrainAuditVisibility();
    this.drawCellMarker(this.selectionLayer, this.selectedCell, 0xffd777, 0.92, 2);
    this.showOverview(!this.terrainAuditEnabled);
    this.app.canvas.addEventListener('wheel', this.wheelHandler, { passive: false });
    this.app.renderer.on('resize', this.resizeHandler);
    this.app.ticker.add((ticker) => {
      this.fpsSamples.push(ticker.FPS);
      if (this.fpsSamples.length > 45) this.fpsSamples.shift();
      const now = performance.now();
      this.domeBaseLayer.alpha = 0.82 + Math.sin(now / 4200) * 0.06;
      this.domeLayer.alpha = 0.94 + Math.sin(now / 3500) * 0.04;
      if (this.animationEnabled && now - this.lastEffectUpdate >= 50) {
        this.animateEffects(now);
        this.lastEffectUpdate = now;
      }
      if (now - this.lastHudUpdate > 500) {
        this.updateHud();
        this.lastHudUpdate = now;
      }
    });
  }

  private drawPlatform(): void {
    const size = mapPixelSize();
    if (this.ringGroundEnabled) {
      return;
    }
    const platform = new Graphics()
      .poly([-26, size.height / 2, 198, -22, size.width - 198, -22, size.width + 26, size.height / 2, size.width - 198, size.height + 22, 198, size.height + 22])
      .fill({ color: 0x101a28, alpha: 1 })
      .stroke({ width: 8, color: 0x5b6d78, alpha: 0.9 });
    platform
      .poly([-12, size.height / 2, 204, -9, size.width - 204, -9, size.width + 12, size.height / 2, size.width - 204, size.height + 9, 204, size.height + 9])
      .stroke({ width: 2.5, color: 0x53c9ff, alpha: 0.72 });
    this.world.addChild(platform);

    const ring = new Graphics();
    ring.arc(size.width / 2, size.height / 2 - 84, 560, Math.PI * 1.12, Math.PI * 1.88).stroke({ width: 16, color: 0x243444, alpha: 0.7 });
    ring.arc(size.width / 2, size.height / 2 - 84, 560, Math.PI * 1.12, Math.PI * 1.88).stroke({ width: 2, color: 0x65cfff, alpha: 0.42 });
    this.world.addChildAt(ring, 0);
  }

  private drawSharedGround(): void {
    const size = mapPixelSize();
    const groundAsset = !this.ringGroundEnabled
      ? MAP_ASSETS.ground
      : this.ringGroundVersion === 'v04'
        ? MAP_ASSETS.groundRingV04
        : this.ringGroundVersion === 'v05'
          ? MAP_ASSETS.groundRingV05
          : this.ringGroundVersion === 'v07'
            ? MAP_ASSETS.groundRingV07
            : this.ringGroundVersion === 'v08'
              ? MAP_ASSETS.groundRingV08
              : this.ringGroundVersion === 'v09'
                ? MAP_ASSETS.groundRingV09
                : this.ringGroundVersion === 'v10'
                  ? MAP_ASSETS.groundRingV10
                  : this.ringGroundVersion === 'v11'
                    ? MAP_ASSETS.groundRingV11
                    : this.ringGroundVersion === 'v12'
                      ? MAP_ASSETS.groundRingV12
                      : this.ringGroundVersion === 'v13'
                        ? MAP_ASSETS.groundRingV13
                        : this.ringGroundVersion === 'v14' ? MAP_ASSETS.groundRingV14Preview : MAP_ASSETS.groundRingV15;
    const texture = Assets.get(groundAsset.alias);
    const ground = new Sprite(texture);
    const aspect = texture.width / texture.height;
    if (this.ringGroundEnabled) {
      if (this.ringGroundVersion === 'v07' || this.ringGroundVersion === 'v08' || this.ringGroundVersion === 'v09' || this.ringGroundVersion === 'v10' || this.ringGroundVersion === 'v11' || this.ringGroundVersion === 'v12' || this.ringGroundVersion === 'v13' || this.ringGroundVersion === 'v14' || this.ringGroundVersion === 'v15') {
        // В исходниках V07–V09 центральный шестиугольник занимает только часть
        // кадра. Масштабируем его шесть граней к точной игровой границе,
        // оставляя фрагменты соседних сот за пределами логической сетки.
        ground.width = size.width * 1.242352941;
        ground.height = size.height * 1.706666667;
        ground.x = -size.width * 0.121176471;
        ground.y = -size.height / 3;
      } else {
        ground.width = size.width;
        ground.height = size.height;
        ground.x = 0;
        ground.y = 0;
      }
      this.world.addChildAt(ground, 0);
      if (this.ringGroundVersion === 'v14') {
        this.groundPreview = ground;
        this.world.addChildAt(this.groundTileLayer, 1);
      }
      return;
    }
    ground.width = size.width + 30;
    ground.height = ground.width / aspect;
    if (ground.height < size.height + 36) {
      ground.height = size.height + 36;
      ground.width = ground.height * aspect;
    }
    ground.x = (size.width - ground.width) / 2;
    ground.y = (size.height - ground.height) / 2 + 1;
    const mask = new Graphics().poly(outerHivePolygon()).fill(0xffffff);
    ground.mask = mask;
    this.world.addChild(ground, mask);

    const innerRim = new Graphics().poly(outerHivePolygon(1)).stroke({ width: 2, color: 0xc8e9e5, alpha: 0.42 });
    this.world.addChild(innerRim);
  }

  private drawDome(): void {
    const size = mapPixelSize();
    const centerX = size.width / 2;
    const centerY = size.height / 2 - 22;
    // Мягкое основание силовой сферы: несколько полупрозрачных полос
    // создают ореол без дорогого размытия на каждом кадре.
    for (const band of [
      { inset: 3, width: 24, alpha: 0.025 },
      { inset: 7, width: 13, alpha: 0.045 },
      { inset: 11, width: 6, alpha: 0.09 },
      { inset: 14, width: 1.4, alpha: 0.28 },
    ]) {
      this.domeBaseLayer
        .poly(outerHivePolygon(band.inset))
        .stroke({ width: band.width, color: 0x79dcff, alpha: band.alpha });
    }
    this.domeLayer
      .ellipse(centerX, centerY, size.width * 0.58, size.height * 0.96)
      .fill({ color: 0x80d8ff, alpha: 0.035 })
      .stroke({ width: 2.2, color: 0xaee8ff, alpha: 0.62 });
    this.domeLayer
      .arc(centerX, centerY, size.width * 0.57, Math.PI * 1.08, Math.PI * 1.92)
      .stroke({ width: 8, color: 0x78cfff, alpha: 0.08 });
    this.domeLayer
      .arc(centerX - 10, centerY - 8, size.width * 0.48, Math.PI * 1.12, Math.PI * 1.54)
      .stroke({ width: 2.5, color: 0xffffff, alpha: 0.24 });
  }

  private drawTerrainCalibration(): void {
    this.terrainCalibrationLayer.clear();
    this.terrainCalibrationLabelLayer.removeChildren().forEach((child) => child.destroy());
    for (const cell of this.cells) {
      const plannedCode = this.terrain[cell.num];
      const code = this.terrainAuditEnabled ? this.actualTerrain[cell.num] : this.homeTerrainAt(cell.num);
      const mismatch = this.terrainAuditEnabled && code !== plannedCode;
      const color = TERRAIN_COLORS[code] ?? 0xffffff;
      const points = projectedCellPolygon(cell);
      this.terrainCalibrationLayer
        .poly(points)
        .fill({ color, alpha: mismatch ? 0.52 : code === 'X' ? 0.66 : 0.3 })
        .stroke({
          width: mismatch ? 2.4 : code === 'X' ? 1.5 : 0.8,
          color: mismatch ? 0xff6d5f : code === 'X' ? 0xe0d7ff : 0xe7f7ff,
          alpha: mismatch || code === 'X' ? 0.96 : 0.72,
        });

      const label = new Text({
        text: `#${String(cell.num).padStart(3, '0')} ${mismatch ? `${plannedCode}→${code}` : code}\nr${String(cell.row).padStart(2, '0')} c${String(cell.col).padStart(2, '0')}`,
        style: {
          fontFamily: 'Consolas, "Courier New", monospace',
          fontSize: 5.8,
          fontWeight: '700',
          lineHeight: 6.5,
          align: 'center',
          fill: 0xffffff,
          stroke: { color: mismatch ? 0x4d120d : 0x07111c, width: mismatch ? 2 : 1.6 },
        },
        resolution: 2,
      });
      label.anchor.set(0.5);
      const center = projectPlayfieldPoint(cell.x + 24, cell.y + 13);
      label.position.set(center.x, center.y);
      label.eventMode = 'none';
      this.terrainCalibrationLabelLayer.addChild(label);
    }
    this.terrainCalibrationLayer.visible = this.terrainCalibrationEnabled;
    this.terrainCalibrationLabelLayer.visible = this.terrainCalibrationEnabled;
  }

  /**
   * Первый не лабораторный, а игровой пресет домашней соты.
   *
   * Здесь намеренно нет случайной россыпи: жилые группы образуют два
   * читаемых района, добыча и еда тяготеют к своей поверхности, а
   * военный узел вынесен в нижний сектор. Это пока художественная композиция,
   * а не подмена серверного `AllowBuildings`: в живом клиенте допустимый
   * тип грунта всё равно сообщает сервер. Центральная позиция X остаётся
   * системной и никогда не получает обычное здание.
   */
  private seedGameBuildings(): void {
    const place = (cellNums: number[], variant: BuildingVariant, state: BuildingState = 'working') => {
      for (const cellNum of cellNums) {
        const preferred = this.cells[cellNum];
        const target = !this.buildings.has(cellNum) && canBuildVariantOnTerrain(variant, this.homeTerrainAt(cellNum))
          ? cellNum
          : this.cells
            .filter((cell) => !this.buildings.has(cell.num) && canBuildVariantOnTerrain(variant, this.homeTerrainAt(cell.num)))
            .sort((left, right) => {
              const leftDistance = (left.x - preferred.x) ** 2 + (left.y - preferred.y) ** 2;
              const rightDistance = (right.x - preferred.x) ** 2 + (right.y - preferred.y) ** 2;
              return leftDistance - rightDistance || left.num - right.num;
            })[0]?.num;
        if (target === undefined) throw new Error(`Нет свободной совместимой клетки для «${BUILDING_LABELS[variant]}»`);
        this.buildings.set(target, state);
        this.variants.set(target, variant);
      }
    };

    // Два полноценных жилых квартала 7 → 1 на земле. Третий крупный
    // комплекс рядом с центром оказался слишком плотным в рабочем масштабе.
    place([23, 13, 24, 35, 34, 22, 12], 0);
    place([146, 130, 131, 160, 161, 114, 174], 1);

    // Отдельные жилые дома сохраняют воздушность районов и показывают
    // все три одноклеточных силуэта без превращения поля в ковёр зданий.
    place([5, 8, 42, 55], 0);
    place([69, 85, 100, 118], 1);
    place([132, 148, 175, 216], 2);

    // Энергетика — на северной пустынной зоне.
    place([1, 18, 30, 38], 3);

    // Два компактных производственных пояса на земле.
    place([47, 75, 204], 4);
    place([61, 82, 154], 5);

    // Парники читаются на зелёной полосе, не занимая водную чашу.
    place([63, 77, 135, 144, 156, 169], 6);

    // Военный узел вынесен в самостоятельный нижний сектор: высокие
    // силуэты не перекрывают центральный жилой квартал и позицию X.
    place([167, 196], 7);
    place([185, 186], 8);
    place([197], 9);

    // Две стройки оставляют на первом кадре видимым игровой цикл.
    place([43], 4, 'construction');
    place([172], 6, 'construction');
  }

  private seedAllBuildings(): void {
    for (const cell of this.cells) {
      const terrain = this.homeTerrainAt(cell.num);
      const allowed = allowedVariantsForTerrain(terrain);
      if (!allowed.length) continue;
      this.buildings.set(cell.num, 'working');
      this.variants.set(cell.num, allowed[cell.num % allowed.length]);
    }
  }

  private homeTerrainAt(cellNum: number): HomeTerrainCode {
    // Пока V14 остаётся рабочей подложкой, её подтверждённая визуальная
    // разметка является единым источником для подписей, меню строительства
    // и восстановления сохранённых домов. Иначе игрок видит один грунт,
    // а после перезагрузки снова получает тип из старого проектного плана.
    const terrain = this.ringGroundVersion === 'v14'
      ? this.actualTerrain[cellNum]
      : this.terrain[cellNum];
    if (!isHomeTerrainCode(terrain)) throw new Error(`Неизвестный тип поверхности ${terrain} в клетке ${cellNum}`);
    return terrain;
  }

  private bindInteraction(): void {
    const boundary = outerHivePolygon();
    this.interactiveLayer.poly(boundary).fill({ color: 0xffffff, alpha: 0.001 });
    this.interactiveLayer.eventMode = 'static';
    this.interactiveLayer.cursor = 'grab';
    this.interactiveLayer.hitArea = new Rectangle(
      PLAYFIELD_FRAME.left.x,
      PLAYFIELD_FRAME.topLeft.y,
      PLAYFIELD_FRAME.right.x - PLAYFIELD_FRAME.left.x,
      PLAYFIELD_FRAME.bottomLeft.y - PLAYFIELD_FRAME.topLeft.y,
    );
    this.interactiveLayer.on('pointermove', (event: FederatedPointerEvent) => {
      if (this.cameraDragging) {
        const dx = event.global.x - this.cameraDragStart.x;
        const dy = event.global.y - this.cameraDragStart.y;
        if (!this.cameraDragMoved && Math.hypot(dx, dy) >= 5) this.cameraDragMoved = true;
        if (this.cameraDragMoved) {
          this.world.x = this.cameraWorldStart.x + dx;
          this.world.y = this.cameraWorldStart.y + dy;
          this.clampCamera();
          this.updateVisibleItems();
          this.interactiveLayer.cursor = 'grabbing';
          this.hoverLayer.clear();
          this.hoveredCell = null;
          return;
        }
      }
      const point = this.playfieldLayer.toLocal(event.global);
      const hit = this.hitCell(point.x, point.y);
      if (hit === this.hoveredCell) return;
      this.hoveredCell = hit;
      this.hoverLayer.clear();
      if (hit !== null) {
        const code = this.terrainAuditEnabled ? this.actualTerrain[hit] : this.homeTerrainAt(hit);
        this.drawCellMarker(this.hoverLayer, hit, TERRAIN_COLORS[code] ?? 0x7fdcff, 0.34, 1.25);
      }
      this.updateHud();
    });
    this.interactiveLayer.on('pointerout', () => {
      if (this.cameraDragging) return;
      this.hoveredCell = null;
      this.hoverLayer.clear();
      this.updateHud();
    });
    this.interactiveLayer.on('pointerdown', (event: FederatedPointerEvent) => {
      this.cameraDragging = true;
      this.cameraDragMoved = false;
      this.cameraDragStart = { x: event.global.x, y: event.global.y };
      this.cameraWorldStart = { x: this.world.x, y: this.world.y };
      this.interactiveLayer.cursor = 'grabbing';
    });
    const endCameraDrag = () => {
      this.cameraDragging = false;
      this.interactiveLayer.cursor = 'grab';
    };
    this.interactiveLayer.on('pointerup', endCameraDrag);
    this.interactiveLayer.on('pointerupoutside', () => {
      endCameraDrag();
      this.cameraDragMoved = false;
    });
    this.interactiveLayer.on('pointertap', (event: FederatedPointerEvent) => {
      if (this.cameraDragMoved) {
        this.cameraDragMoved = false;
        return;
      }
      const point = this.playfieldLayer.toLocal(event.global);
      const cellNum = this.hitCell(point.x, point.y);
      if (cellNum === null) return;
      this.selectedCell = cellNum;
      this.buildNotice = '';
      this.setSelectedCellQuery(cellNum);
      this.selectionLayer.clear();
      this.drawCellMarker(this.selectionLayer, cellNum, 0xffd777, 0.92, 2);
      this.updateHud();
    });
  }

  private hitCell(x: number, y: number): number | null {
    for (const cell of this.cells) if (pointInOrOnPolygon(x, y, projectedCellPolygon(cell))) return cell.num;
    return null;
  }

  private drawCellMarker(target: Graphics, cellNum: number, color: number, alpha: number, width: number): void {
    const cell = this.cells[cellNum];
    if (!cell) return;
    target.poly(projectedCellPolygon(cell))
      .fill({ color, alpha: alpha * 0.14 })
      .stroke({ width, color, alpha });
  }

  private silhouetteFor(asset: BuildingAsset): SilhouetteProfile {
    const cached = this.silhouetteCache.get(asset.alias);
    if (cached) return cached;
    const texture = Assets.get(asset.alias) as Texture;
    const width = Math.max(1, Math.round(texture.width));
    const height = Math.max(1, Math.round(texture.height));
    const source = document.createElement('canvas');
    source.width = width;
    source.height = height;
    const context = source.getContext('2d', { willReadFrequently: true });
    if (!context) throw new Error(`Не удалось прочитать альфа-силуэт ${asset.alias}`);
    context.drawImage(texture.source.resource as CanvasImageSource, 0, 0, width, height);
    const rgba = context.getImageData(0, 0, width, height).data;
    const columns: SilhouetteProfile['columns'] = [];
    for (let x = 0; x < width; x += 1) {
      let top = -1;
      let bottom = -1;
      for (let y = 0; y < height; y += 1) {
        if (rgba[(y * width + x) * 4 + 3] < this.shadowTuning.alphaThreshold) continue;
        if (top < 0) top = y;
        bottom = y;
      }
      if (top >= 0 && bottom >= 0) columns.push({ x, top, bottom });
    }
    const profile = { width, height, columns };
    this.silhouetteCache.set(asset.alias, profile);
    return profile;
  }

  private shadowTextureFor(asset: BuildingAsset): Texture {
    const tuning = this.shadowTuning;
    const cacheKey = [
      asset.alias,
      tuning.azimuth,
      tuning.elevation,
      tuning.compression,
      tuning.alphaThreshold,
      tuning.rayWidth,
      tuning.joinRadius,
      tuning.baselineOffset,
      tuning.blur,
    ].join(':');
    const cached = this.shadowTextureCache.get(cacheKey);
    if (cached) return cached;

    const profile = this.silhouetteFor(asset);
    const raw = document.createElement('canvas');
    raw.width = profile.width;
    raw.height = profile.height;
    const rawContext = raw.getContext('2d');
    if (!rawContext) throw new Error(`Не удалось построить тень ${asset.alias}`);
    const azimuth = tuning.azimuth * Math.PI / 180;
    const elevation = tuning.elevation * Math.PI / 180;
    const directionX = Math.cos(azimuth);
    const directionY = Math.sin(azimuth);
    const lengthPerHeight = tuning.compression / Math.tan(elevation);
    const baseline = Math.max(0, Math.min(profile.height - 1, (asset.anchor[1] + tuning.baselineOffset) * profile.height));
    const sourceToLogical = profile.width / asset.logicalWidth;
    rawContext.strokeStyle = 'rgba(2, 7, 12, 0.9)';
    rawContext.lineWidth = Math.max(1, tuning.rayWidth + tuning.joinRadius * 2 * sourceToLogical);
    rawContext.lineCap = tuning.joinRadius > 0 ? 'round' : 'butt';
    rawContext.lineJoin = 'round';
    rawContext.beginPath();
    for (const column of profile.columns) {
      const screenHeight = Math.max(0, baseline - column.top);
      if (screenHeight <= 0) continue;
      const startY = Math.min(column.bottom, baseline);
      const length = screenHeight * lengthPerHeight;
      rawContext.moveTo(column.x, startY);
      rawContext.lineTo(column.x + directionX * length, startY + directionY * length);
    }
    rawContext.stroke();

    const output = document.createElement('canvas');
    output.width = profile.width;
    output.height = profile.height;
    const outputContext = output.getContext('2d');
    if (!outputContext) throw new Error(`Не удалось сгладить тень ${asset.alias}`);
    const sourceBlur = Math.max(0, tuning.blur * sourceToLogical);
    outputContext.filter = sourceBlur > 0 ? `blur(${sourceBlur}px)` : 'none';
    outputContext.drawImage(raw, 0, 0);
    const texture = Texture.from(output);
    this.shadowTextureCache.set(cacheKey, texture);
    return texture;
  }

  private drawBuildingShadows(item: RenderItem, asset: BuildingAsset, castSilhouette = true): { ground: Sprite | null; receiver: Sprite | null } {
    const width = asset.logicalWidth * (item.scaleX ?? 1);
    const logicalHeight = asset.logicalWidth * (Assets.get(asset.alias) as Texture).height / (Assets.get(asset.alias) as Texture).width;
    const height = logicalHeight * (item.scaleY ?? 1);
    if (this.contactShadowsEnabled) {
      this.contactShadowLayer
        .ellipse(item.anchor.x, item.anchor.y - 0.6, width * 0.46, Math.max(2, width * 0.065))
        .fill({ color: 0x071017, alpha: this.shadowTuning.contactOpacity * 0.22 })
        .ellipse(item.anchor.x, item.anchor.y - 0.25, width * 0.31, Math.max(1.35, width * 0.04))
        .fill({ color: 0x02070b, alpha: this.shadowTuning.contactOpacity * 0.56 });
    }

    if (!this.shadowsEnabled || !castSilhouette) return { ground: null, receiver: null };

    const texture = this.shadowTextureFor(asset);
    const stateFactor = item.kind === 'construction' ? 0.42 : 1;
    const configure = (sprite: Sprite) => {
      sprite.anchor.set(asset.anchor[0], asset.anchor[1]);
      sprite.width = width;
      sprite.height = height;
      sprite.x = item.anchor.x;
      sprite.y = item.anchor.y;
    };
    const ground = new Sprite(texture);
    configure(ground);
    ground.alpha = this.shadowTuning.groundOpacity * stateFactor;
    this.shadowLayer.addChild(ground);

    const receiver = new Sprite(texture);
    configure(receiver);
    receiver.alpha = this.shadowTuning.neighborOpacity * stateFactor;
    return { ground, receiver };
  }

  private projectRenderItem(item: RenderItem): RenderItem {
    const scale = projectedScaleAt(item.anchor.y);
    const anchor = projectPlayfieldPoint(item.anchor.x, item.anchor.y);
    const singleAsset = item.kind === 'single' || item.kind === 'construction' ? assetForVariant(item.variant) : null;
    const visualScale = singleAsset
      ? Math.min(SINGLE_BUILDING_MAX_SCALE, SINGLE_BUILDING_MAX_WIDTH / singleAsset.logicalWidth)
      : 1;
    return {
      ...item,
      anchor,
      depthY: anchor.y,
      scaleX: scale.x * visualScale,
      scaleY: scale.y * visualScale,
    };
  }

  private itemFitsInsideHive(item: RenderItem, asset: BuildingAsset): boolean {
    const projectedItem = this.projectRenderItem(item);
    const texture = Assets.get(asset.alias) as Texture;
    const logicalHeight = asset.logicalWidth * texture.height / texture.width;
    const width = asset.logicalWidth * (projectedItem.scaleX ?? 1);
    const height = logicalHeight * (projectedItem.scaleY ?? 1);
    const left = projectedItem.anchor.x - asset.anchor[0] * width;
    const top = projectedItem.anchor.y - asset.anchor[1] * height;
    const right = left + width;
    const bottom = top + height;
    const boundary = outerHivePolygon();
    return [
      [left, top],
      [right, top],
      [right, bottom],
      [left, bottom],
    ].every(([x, y]) => pointInOrOnPolygon(x, y, boundary));
  }

  private verifyPlayfieldGeometry(): void {
    const boundary = outerHivePolygon();
    for (const cell of this.cells) {
      const points = projectedCellPolygon(cell);
      for (let index = 0; index < points.length; index += 2) {
        const x = points[index];
        const y = points[index + 1];
        if (!pointInOrOnPolygon(x, y, boundary)) {
          throw new Error(`Клетка ${cell.num} выходит за внутреннюю границу соты`);
        }
      }
    }
  }

  private rebuildBuildings(): void {
    this.shadowLayer.removeChildren().forEach((child) => child.destroy());
    this.contactShadowLayer.clear();
    this.buildingLayer.removeChildren().forEach((child) => child.destroy());
    this.forgeGlowLayer.clear();
    this.emissiveLayer.removeChildren().forEach((child) => child.destroy());
    this.waterLayer.clear();
    this.effectItems = [];
    const working = new Set([...this.buildings]
      .filter(([cellNum, state]) => state === 'working' && isResidentialVariant(this.variants.get(cellNum)))
      .map(([cellNum]) => cellNum));
    const candidateFusions = this.fusionsEnabled ? resolveFusions(working, this.cells, this.neighbors) : [];
    const fusions = candidateFusions.filter((fusion) => {
      const item: RenderItem = { ...fusion, variant: 0 };
      const asset = fusion.kind === 'seven' ? MAP_ASSETS.seven : MAP_ASSETS.three;
      return this.itemFitsInsideHive(item, asset);
    });
    const fusedCells = new Set(fusions.flatMap((fusion) => fusion.cellNums));
    const logicalItems: RenderItem[] = fusions.map((fusion) => ({
      kind: fusion.kind,
      cellNums: fusion.cellNums,
      anchor: fusion.anchor,
      depthY: fusion.depthY,
      variant: 0,
    }));
    const gateAnchor = buildingAnchor(this.cells[108]);
    logicalItems.push({
      kind: 'gate',
      cellNums: [108],
      anchor: { x: gateAnchor.x, y: gateAnchor.y - 4.5 },
      depthY: gateAnchor.y,
      variant: 9,
    });
    for (const [cellNum, state] of this.buildings) {
      if (state === 'working' && fusedCells.has(cellNum)) continue;
      const anchor = buildingAnchor(this.cells[cellNum]);
      logicalItems.push({
        kind: state === 'construction' ? 'construction' : 'single',
        cellNums: [cellNum],
        anchor: { x: anchor.x, y: anchor.y - 4.5 },
        depthY: anchor.y,
        variant: this.variants.get(cellNum) ?? (cellNum % 3) as BuildingVariant,
      });
    }
    const items = logicalItems.map((item) => this.projectRenderItem(item));
    items.sort((left, right) => left.depthY - right.depthY || left.anchor.x - right.anchor.x);
    this.lastFusionCount = fusions.length;
    this.lastVisualCount = items.length;

    for (const item of items) {
      const asset = item.kind === 'gate'
        ? MAP_ASSETS.ringGate
        : item.kind === 'seven'
        ? MAP_ASSETS.seven
        : item.kind === 'three'
          ? MAP_ASSETS.three
          : assetForVariant(item.variant);
      const militaryWithoutLongShadow = (item.kind === 'single' || item.kind === 'construction') && item.variant >= 7;
      const shadows = this.drawBuildingShadows(item, asset, !militaryWithoutLongShadow);
      if (shadows.receiver) this.buildingLayer.addChild(shadows.receiver);

      const sprite = Sprite.from(asset.alias);
      sprite.anchor.set(asset.anchor[0], asset.anchor[1]);
      sprite.width = asset.logicalWidth * (item.scaleX ?? 1);
      sprite.height = asset.logicalWidth * sprite.texture.height / sprite.texture.width * (item.scaleY ?? 1);
      sprite.x = item.anchor.x;
      sprite.y = item.anchor.y;
      const itemState = item.kind === 'three' || item.kind === 'seven' || item.kind === 'gate'
        ? 'working'
        : this.buildings.get(item.cellNums[0]);
      let rearShield: Sprite | null = null;
      if (item.kind === 'single' && item.variant === 9 && itemState === 'working') {
        rearShield = Sprite.from(MAP_ASSETS.militaryBaseShield.alias);
        rearShield.anchor.set(asset.anchor[0], asset.anchor[1]);
        rearShield.width = sprite.width * 1.16;
        rearShield.height = sprite.height * 1.12;
        rearShield.x = item.anchor.x;
        rearShield.y = item.anchor.y;
        rearShield.alpha = 0.76;
        rearShield.blendMode = 'normal';
        // В глубинном порядке щит стоит непосредственно перед своей базой:
        // задняя полусфера видна вокруг корпуса, но никогда не лежит на фасаде.
        this.buildingLayer.addChild(rearShield);
      }
      if (item.kind === 'construction') {
        sprite.alpha = 0.35;
        sprite.tint = 0x9fdfff;
      } else if (this.buildings.get(item.cellNums[0]) === 'disabled') {
        sprite.alpha = 0.62;
        sprite.tint = 0x9eb8c4;
      }
      this.buildingLayer.addChild(sprite);

      const effectKey = effectKeyForItem(item);
      const emissiveAsset = effectKey ? EMISSIVE_ASSET_BY_KIND[effectKey] : null;
      let emissive: Sprite | null = null;
      if (itemState === 'working' && emissiveAsset) {
        emissive = Sprite.from(emissiveAsset.alias);
        emissive.anchor.set(asset.anchor[0], asset.anchor[1]);
        emissive.width = asset.logicalWidth * (item.scaleX ?? 1);
        emissive.height = asset.logicalWidth * emissive.texture.height / emissive.texture.width * (item.scaleY ?? 1);
        emissive.x = item.anchor.x;
        emissive.y = item.anchor.y;
        emissive.alpha = item.kind === 'gate' ? 0.55 : 0.12;
        emissive.blendMode = 'add';
        this.emissiveLayer.addChild(emissive);
      }
      this.effectItems.push({
        item,
        asset,
        sprite,
        groundShadow: shadows.ground,
        shadow: shadows.receiver,
        emissive,
        rearShield,
        width: sprite.width,
        height: sprite.height,
        visible: true,
      });

      if (item.kind === 'construction') {
        const scaffold = new Graphics()
          .circle(item.anchor.x, item.anchor.y - 11, 7)
          .stroke({ width: 1.4, color: 0x79dcff, alpha: 0.9 })
          .moveTo(item.anchor.x - 7, item.anchor.y - 11)
          .lineTo(item.anchor.x + 7, item.anchor.y - 11)
          .stroke({ width: 1, color: 0xc7f3ff, alpha: 0.75 });
        this.buildingLayer.addChild(scaffold);
      }
    }
    this.updateVisibleItems();
    if (this.animationEnabled) this.animateEffects(performance.now());
    this.updateHud(fusions.length, items.length);
  }

  private updateVisibleItems(): void {
    this.updateGroundTiles();
    if (!this.cameraInitialized) {
      this.lastVisibleCount = this.effectItems.length;
      return;
    }
    const safe = this.safeViewport();
    const margin = 42;
    let visibleCount = 0;
    for (const effect of this.effectItems) {
      const topLeft = this.playfieldLayer.toGlobal({
        x: effect.item.anchor.x - effect.asset.anchor[0] * effect.width,
        y: effect.item.anchor.y - effect.asset.anchor[1] * effect.height,
      });
      const bottomRight = this.playfieldLayer.toGlobal({
        x: effect.item.anchor.x + (1 - effect.asset.anchor[0]) * effect.width,
        y: effect.item.anchor.y + (1 - effect.asset.anchor[1]) * effect.height,
      });
      const left = topLeft.x;
      const top = topLeft.y;
      const right = bottomRight.x;
      const bottom = bottomRight.y;
      const visible = right >= safe.left - margin
        && left <= safe.right + margin
        && bottom >= safe.top - margin
        && top <= safe.bottom + margin;
      effect.visible = visible;
      effect.sprite.visible = visible;
      if (effect.groundShadow) effect.groundShadow.visible = visible;
      if (effect.shadow) effect.shadow.visible = visible;
      if (effect.emissive) effect.emissive.visible = visible;
      if (effect.rearShield) effect.rearShield.visible = visible;
      if (visible) visibleCount += 1;
    }
    this.lastVisibleCount = visibleCount;
  }

  private updateGroundTiles(): void {
    if (this.ringGroundVersion !== 'v14' || !this.cameraInitialized) return;
    if (this.groundPreview) this.groundPreview.visible = true;
    const useHighDetail = !this.overviewMode && this.world.scale.x >= 0.72;
    const size = mapPixelSize();
    const groundX = -size.width * 0.121176471;
    const groundY = -size.height / 3;
    const groundWidth = size.width * 1.242352941;
    const groundHeight = size.height * 1.706666667;
    const tileWidth = groundWidth / V14_TILE_COLUMNS;
    const tileHeight = groundHeight / V14_TILE_ROWS;
    const safe = this.safeViewport();
    const margin = 96;

    for (const tile of this.groundTiles) {
      const x = groundX + tile.column * tileWidth;
      const y = groundY + tile.row * tileHeight;
      const topLeft = this.world.toGlobal({ x, y });
      const bottomRight = this.world.toGlobal({ x: x + tileWidth, y: y + tileHeight });
      const visible = useHighDetail
        && bottomRight.x >= safe.left - margin
        && topLeft.x <= safe.right + margin
        && bottomRight.y >= safe.top - margin
        && topLeft.y <= safe.bottom + margin;
      if (tile.sprite) tile.sprite.visible = visible;
      if (visible && !tile.sprite && !tile.loading) void this.loadGroundTile(tile, x, y, tileWidth, tileHeight);
    }
  }

  private async loadGroundTile(tile: GroundTileState, x: number, y: number, width: number, height: number): Promise<void> {
    tile.loading = true;
    try {
      const texture = await Assets.load<Texture>({ alias: tile.alias, src: tile.src });
      const sprite = new Sprite(texture);
      sprite.x = x;
      sprite.y = y;
      // Небольшое перекрытие скрывает субпиксельные швы при дробном масштабе.
      sprite.width = width + 0.35;
      sprite.height = height + 0.35;
      tile.sprite = sprite;
      this.groundTileLayer.addChild(sprite);
      this.groundTileTextureBytes += Math.round(texture.width) * Math.round(texture.height) * 4;
      this.loadedGroundTilePngBytes += tile.pngBytes;
      this.updateGroundTiles();
      this.updateHud();
    } finally {
      tile.loading = false;
    }
  }

  private animateEffects(timeMs: number): void {
    this.waterLayer.clear();
    this.forgeGlowLayer.clear();
    this.activityLayer.clear();
    const seconds = timeMs / 1000;
    for (const [index, effect] of this.effectItems.entries()) {
      if (!effect.visible) continue;
      const kind = effectKeyForItem(effect.item);
      if (effect.emissive) {
        const military = kind === 'ringGate' || kind === 'lightWeapons' || kind === 'heavyWeapons';
        const pulse = military
          ? 0.24 + (Math.sin(seconds * 1.35 + index * 0.43) + 1) * 0.13
          : 0.09 + (Math.sin(seconds * 0.72 + index * 0.43) + 1) * 0.055;
        effect.emissive.alpha = Math.min(military ? 0.72 : 0.28, pulse * this.animationIntensity);
      }
      const left = effect.item.anchor.x - effect.asset.anchor[0] * effect.width;
      const top = effect.item.anchor.y - effect.asset.anchor[1] * effect.height;
      const phase = seconds + index * 0.31;
      if (effect.rearShield) {
        const shieldPulse = (Math.sin(phase * 0.72) + 1) * 0.5;
        effect.rearShield.alpha = Math.min(0.9, (0.58 + shieldPulse * 0.11) * this.animationIntensity);
      }
      const isWorkingForge = effect.item.kind === 'single'
        && effect.item.variant === 4
        && this.buildings.get(effect.item.cellNums[0]) === 'working';
      if (isWorkingForge) {
        const forgePulse = (Math.sin(phase * 2.2) + 1) * 0.5;
        const centerX = left + effect.width * 0.19;
        const centerY = top + effect.height * 0.655;
        this.forgeGlowLayer
          .ellipse(centerX, centerY, effect.width * 0.115, effect.height * 0.105)
          .fill({ color: 0xd30b00, alpha: Math.min(0.28, (0.09 + forgePulse * 0.055) * this.animationIntensity) })
          .ellipse(centerX, centerY, effect.width * 0.075, effect.height * 0.073)
          .fill({ color: 0xff2600, alpha: Math.min(0.46, (0.16 + forgePulse * 0.08) * this.animationIntensity) })
          .ellipse(centerX, centerY + effect.height * 0.008, effect.width * 0.038, effect.height * 0.045)
          .fill({ color: 0xff6a28, alpha: Math.min(0.64, (0.24 + forgePulse * 0.1) * this.animationIntensity) });
      }
      if (!kind) continue;
      if (kind === 'ringGate') {
        const centerX = left + effect.width * 0.603;
        const centerY = top + effect.height * 0.478;
        for (let ring = 0; ring < 3; ring += 1) {
          const progress = ((phase * 0.34 + ring / 3) % 1 + 1) % 1;
          this.activityLayer
            .ellipse(centerX, centerY, effect.width * (0.045 + progress * 0.105), effect.height * (0.09 + progress * 0.19))
            .stroke({ width: 0.8, color: 0x8eeeff, alpha: (1 - progress) * 0.46 * this.animationIntensity });
        }
      } else if (kind === 'heavyWeapons') {
        const progress = ((phase * 0.48) % 1 + 1) % 1;
        this.activityLayer
          .circle(left + effect.width * (0.18 + progress * 0.22), top + effect.height * (0.78 + progress * 0.07), 0.75)
          .fill({ color: 0xffbc65, alpha: 0.62 * this.animationIntensity });
      }
      const surfaces = WATER_SURFACES[kind];
      if (!surfaces || !effect.emissive) continue;
      const seed = effect.item.cellNums.reduce((sum, cellNum) => sum + cellNum, 0) * 0.17;
      for (const [surfaceIndex, surface] of surfaces.entries()) {
        const centerX = left + surface.x * effect.width;
        const centerY = top + surface.y * effect.height;
        const radiusX = Math.max(1.15, surface.rx * effect.width);
        const radiusY = Math.max(0.45, surface.ry * effect.height);
        const phase = seconds * 0.36 + seed + surfaceIndex * 0.31;

        // Светлая движущаяся полоса и тёмная впадина остаются внутри чаши.
        const drift = Math.sin(phase * Math.PI * 2) * radiusY * 0.46;
        this.waterLayer
          .ellipse(centerX, centerY + drift, radiusX * 0.82, Math.max(0.28, radiusY * 0.38))
          .stroke({ width: 0.48, color: 0xbaf7ff, alpha: Math.min(0.62, 0.22 * this.animationIntensity) });
        this.waterLayer
          .ellipse(centerX, centerY - drift * 0.65, radiusX * 0.68, Math.max(0.24, radiusY * 0.3))
          .stroke({ width: 0.55, color: 0x07527a, alpha: Math.min(0.42, 0.13 * this.animationIntensity) });

        for (let ringIndex = 0; ringIndex < 2; ringIndex += 1) {
          const progress = ((phase * 0.58 + ringIndex / 2) % 1 + 1) % 1;
          this.waterLayer
            .ellipse(
              centerX,
              centerY,
              Math.max(0.35, radiusX * progress),
              Math.max(0.18, radiusY * progress),
            )
            .stroke({
              width: Math.max(0.35, 0.7 - progress * 0.28),
              color: 0xd3fbff,
              alpha: Math.min(0.75, (1 - progress) * 0.42 * this.animationIntensity),
            });
        }

        if (surface.jet) {
          const jetHeight = surface.jet * effect.height;
          const sway = Math.sin(seconds * 0.9 + seed) * radiusX * 0.06;
          this.waterLayer
            .moveTo(centerX, centerY)
            .quadraticCurveTo(centerX - sway * 0.4, centerY - jetHeight * 0.5, centerX + sway, centerY - jetHeight)
            .stroke({ width: 0.68, color: 0xa7edff, alpha: Math.min(0.66, 0.28 * this.animationIntensity) });
        }
      }
    }
  }

  private setAnimationEnabled(enabled: boolean): void {
    this.animationEnabled = enabled;
    this.emissiveLayer.visible = enabled;
    this.forgeGlowLayer.visible = enabled;
    this.waterLayer.visible = enabled;
    this.activityLayer.visible = enabled;
    if (enabled) this.animateEffects(performance.now());
    else {
      this.forgeGlowLayer.clear();
      this.waterLayer.clear();
      this.activityLayer.clear();
    }
    this.updateHud();
  }

  private buildSelected(variant: BuildingVariant): void {
    if (this.buildings.has(this.selectedCell)) return;
    const terrain = this.homeTerrainAt(this.selectedCell);
    if (!canBuildVariantOnTerrain(variant, terrain)) {
      this.buildNotice = buildRestrictionReason(variant, terrain);
      this.updateHud();
      return;
    }
    this.buildNotice = '';
    this.variants.set(this.selectedCell, variant);
    this.buildings.set(this.selectedCell, 'construction');
    this.setDensityQuery(null);
    this.saveBuildingState();
    this.rebuildBuildings();
  }

  private completeSelected(): void {
    if (this.buildings.get(this.selectedCell) !== 'construction') return;
    this.buildings.set(this.selectedCell, 'working');
    this.saveBuildingState();
    this.rebuildBuildings();
  }

  private toggleSelectedPower(): void {
    const state = this.buildings.get(this.selectedCell);
    if (state !== 'working' && state !== 'disabled') return;
    this.buildings.set(this.selectedCell, state === 'working' ? 'disabled' : 'working');
    this.saveBuildingState();
    this.rebuildBuildings();
  }

  private demolishSelected(): void {
    this.buildings.delete(this.selectedCell);
    this.variants.delete(this.selectedCell);
    this.buildNotice = '';
    this.setDensityQuery(null);
    this.saveBuildingState();
    this.rebuildBuildings();
  }

  reset(): void {
    this.buildings.clear();
    this.variants.clear();
    this.seedGameBuildings();
    this.setDensityQuery(null);
    this.saveBuildingState();
    this.rebuildBuildings();
  }

  fillAll(): void {
    this.buildings.clear();
    this.variants.clear();
    this.seedAllBuildings();
    const animationInput = this.hud.querySelector<HTMLInputElement>('[data-action="animation"]');
    if (animationInput) animationInput.checked = true;
    this.setAnimationEnabled(true);
    this.setDensityQuery('full');
    this.saveBuildingState();
    this.rebuildBuildings();
  }

  clear(): void {
    this.buildings.clear();
    this.variants.clear();
    this.setDensityQuery(null);
    this.saveBuildingState();
    this.rebuildBuildings();
  }

  private restoreBuildingState(): boolean {
    return this.restoreBuildingStateFrom(HIVE_BUILDINGS_STORAGE_KEY, false);
  }

  private restoreBuildingStateFrom(storageKey: string, rebuild: boolean): boolean {
    try {
      const stored = window.localStorage.getItem(storageKey);
      if (!stored) return false;
      const parsed = JSON.parse(stored) as Partial<StoredHiveBuildings>;
      if (parsed.version !== 1 || !Array.isArray(parsed.buildings)) return false;

      const restoredBuildings = new Map<number, BuildingState>();
      const restoredVariants = new Map<number, BuildingVariant>();
      for (const item of parsed.buildings) {
        if (!item || !Number.isInteger(item.cell) || item.cell < 0 || item.cell >= this.cells.length) continue;
        if (item.state !== 'construction' && item.state !== 'working' && item.state !== 'disabled') continue;
        if (!Number.isInteger(item.variant) || !(item.variant in BUILDING_LABELS)) continue;
        const variant = item.variant as BuildingVariant;
        if (!canBuildVariantOnTerrain(variant, this.homeTerrainAt(item.cell))) continue;
        restoredBuildings.set(item.cell, item.state);
        restoredVariants.set(item.cell, variant);
      }

      this.buildings.clear();
      this.variants.clear();
      restoredBuildings.forEach((state, cell) => this.buildings.set(cell, state));
      restoredVariants.forEach((variant, cell) => this.variants.set(cell, variant));
      if (rebuild) {
        this.setDensityQuery(null);
        this.saveBuildingState();
        this.rebuildBuildings();
      }
      const savedLabel = parsed.savedAt
        ? new Date(parsed.savedAt).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit', second: '2-digit', timeZone: 'Europe/Berlin' })
        : 'без отметки времени';
      this.setBuildingSaveStatus(`${storageKey === HIVE_BUILDINGS_SNAPSHOT_STORAGE_KEY ? 'Снимок восстановлен' : 'Автосохранение восстановлено'}: ${restoredBuildings.size} зданий · ${savedLabel}.`);
      return true;
    } catch {
      this.setBuildingSaveStatus('Не удалось прочитать сохранённую застройку.', true);
      return false;
    }
  }

  private storedBuildingState(): StoredHiveBuildings {
    return {
      version: 1,
      savedAt: new Date().toISOString(),
      buildings: [...this.buildings.entries()]
        .map(([cell, buildingState]) => {
          const variant = this.variants.get(cell);
          return variant === undefined ? null : { cell, state: buildingState, variant };
        })
        .filter((building): building is StoredBuilding => building !== null)
        .sort((left, right) => left.cell - right.cell),
    };
  }

  private saveBuildingState(): void {
    if (this.demoMode) return;
    try {
      const state = this.storedBuildingState();
      window.localStorage.setItem(HIVE_BUILDINGS_STORAGE_KEY, JSON.stringify(state));
      const savedLabel = new Date(state.savedAt ?? Date.now()).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit', second: '2-digit', timeZone: 'Europe/Berlin' });
      this.setBuildingSaveStatus(`Автосохранено: ${state.buildings.length} зданий · ${savedLabel}.`);
    } catch {
      this.setBuildingSaveStatus('Браузер запретил автосохранение.', true);
    }
  }

  private saveBuildingSnapshot(): void {
    if (this.demoMode) return;
    try {
      const state = this.storedBuildingState();
      window.localStorage.setItem(HIVE_BUILDINGS_SNAPSHOT_STORAGE_KEY, JSON.stringify(state));
      const savedLabel = new Date(state.savedAt ?? Date.now()).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit', second: '2-digit', timeZone: 'Europe/Berlin' });
      this.setBuildingSaveStatus(`Контрольный снимок: ${state.buildings.length} зданий · ${savedLabel}.`);
    } catch {
      this.setBuildingSaveStatus('Браузер не разрешил сохранить контрольный снимок.', true);
    }
  }

  private restoreBuildingSnapshot(): void {
    if (this.demoMode) return;
    if (!this.restoreBuildingStateFrom(HIVE_BUILDINGS_SNAPSHOT_STORAGE_KEY, true)) {
      this.setBuildingSaveStatus('Контрольный снимок ещё не создан.', true);
    }
  }

  private setBuildingSaveStatus(message: string, isError = false): void {
    const status = this.hud.querySelector<HTMLElement>('[data-role="building-save-status"]');
    if (!status) return;
    status.textContent = message;
    status.classList.toggle('is-error', isError);
  }

  private setDensityQuery(value: 'full' | null): void {
    const url = new URL(window.location.href);
    if (value) url.searchParams.set('density', value);
    else url.searchParams.delete('density');
    window.history.replaceState(null, '', url);
  }

  private setSelectedCellQuery(cellNum: number): void {
    const url = new URL(window.location.href);
    url.searchParams.set('selectedCell', String(cellNum));
    window.history.replaceState(null, '', url);
  }

  private setBooleanQuery(name: 'contact-shadow' | 'fusions', enabled: boolean): void {
    const url = new URL(window.location.href);
    if (enabled) url.searchParams.delete(name);
    else url.searchParams.set(name, 'off');
    window.history.replaceState(null, '', url);
  }

  private setTerrainMapQuery(enabled: boolean): void {
    const url = new URL(window.location.href);
    if (enabled) url.searchParams.set('terrain-map', 'on');
    else url.searchParams.delete('terrain-map');
    window.history.replaceState(null, '', url);
  }

  private restoreTerrainAudit(): void {
    const baseline = this.ringGroundVersion === 'v14' ? HOME_ART_GROUND_V14_AUDIT : HOME_ART_GROUND;
    this.actualTerrain.splice(0, this.actualTerrain.length, ...(baseline.split('') as HomeTerrainCode[]));
    try {
      const stored = window.localStorage.getItem(this.terrainAuditStorageKey());
      if (!stored || stored.length !== this.cells.length) return;
      const values = stored.split('');
      if (!values.every(isHomeTerrainCode)) return;
      this.actualTerrain.splice(0, this.actualTerrain.length, ...(values as HomeTerrainCode[]));
    } catch {
      // Стенд остаётся работоспособным и в режиме браузера без localStorage.
    }
  }

  private saveTerrainAudit(): void {
    try {
      window.localStorage.setItem(this.terrainAuditStorageKey(), this.actualTerrain.join(''));
    } catch {
      // Сама разметка продолжает жить до перезагрузки страницы.
    }
  }

  private terrainAuditStorageKey(): string {
    return `ds:hive217:ring-${this.ringGroundVersion}:terrain-audit:v2`;
  }

  private setTerrainAuditQuery(enabled: boolean): void {
    const url = new URL(window.location.href);
    if (enabled) url.searchParams.set('terrain-audit', 'on');
    else url.searchParams.delete('terrain-audit');
    window.history.replaceState(null, '', url);
  }

  private applyTerrainAuditVisibility(): void {
    const showScene = !this.terrainAuditEnabled;
    this.buildingLayer.visible = showScene;
    this.shadowLayer.visible = showScene && this.shadowsEnabled;
    this.contactShadowLayer.visible = showScene && this.contactShadowsEnabled;
    this.forgeGlowLayer.visible = showScene && this.animationEnabled;
    this.waterLayer.visible = showScene;
    this.emissiveLayer.visible = showScene;
    this.activityLayer.visible = showScene;
    this.terrainCalibrationLayer.visible = this.terrainCalibrationEnabled;
    this.terrainCalibrationLabelLayer.visible = this.terrainCalibrationEnabled;
  }

  private setTerrainAuditEnabled(enabled: boolean): void {
    this.terrainAuditEnabled = enabled;
    if (enabled) {
      this.terrainCalibrationEnabled = true;
      const terrainMapInput = this.hud.querySelector<HTMLInputElement>('[data-action="terrain-map"]');
      if (terrainMapInput) terrainMapInput.checked = true;
      this.setTerrainMapQuery(true);
      this.showOverview(false);
    }
    this.setTerrainAuditQuery(enabled);
    this.drawTerrainCalibration();
    this.applyTerrainAuditVisibility();
    this.updateHud();
  }

  private setActualTerrain(code: HomeTerrainCode): void {
    this.actualTerrain[this.selectedCell] = code;
    this.saveTerrainAudit();
    this.drawTerrainCalibration();
    this.selectionLayer.clear();
    this.drawCellMarker(this.selectionLayer, this.selectedCell, 0xffd777, 0.92, 2);
    this.updateHud();
  }

  private resetTerrainAudit(): void {
    const baseline = this.ringGroundVersion === 'v14' ? HOME_ART_GROUND_V14_AUDIT : HOME_ART_GROUND;
    this.actualTerrain.splice(0, this.actualTerrain.length, ...(baseline.split('') as HomeTerrainCode[]));
    this.saveTerrainAudit();
    this.drawTerrainCalibration();
    const status = this.hud.querySelector<HTMLElement>('[data-role="terrain-audit-status"]');
    if (status) status.textContent = this.ringGroundVersion === 'v14'
      ? 'Разметка возвращена к принятой классификации V14 и сохранена локально.'
      : 'Разметка возвращена к исходному плану и сохранена локально.';
    this.updateHud();
  }

  private clearGeneratedShadows(clearSilhouettes = false): void {
    for (const texture of this.shadowTextureCache.values()) texture.destroy(true);
    this.shadowTextureCache.clear();
    if (clearSilhouettes) this.silhouetteCache.clear();
  }

  private shadowValueLabel(key: keyof ShadowTuning, value: number): string {
    if (key === 'azimuth' || key === 'elevation') return `${Math.round(value)}°`;
    if (key === 'groundOpacity' || key === 'neighborOpacity' || key === 'contactOpacity') return `${Math.round(value * 100)}%`;
    if (key === 'alphaThreshold') return String(Math.round(value));
    if (key === 'baselineOffset') return value.toFixed(3).replace('.', ',');
    if (key === 'compression') return value.toFixed(2).replace('.', ',');
    return `${value.toFixed(2).replace(/0$/, '').replace('.', ',')} px`;
  }

  private syncShadowControls(root: ParentNode = this.hud): void {
    for (const input of root.querySelectorAll<HTMLInputElement>('[data-shadow]')) {
      const key = input.dataset.shadow as keyof ShadowTuning;
      input.value = String(this.shadowTuning[key]);
      const output = root.querySelector<HTMLOutputElement>(`[data-shadow-output="${key}"]`);
      if (output) output.value = this.shadowValueLabel(key, this.shadowTuning[key]);
    }
    const lengthOutput = root.querySelector<HTMLOutputElement>('[data-role="shadow-length"]');
    if (lengthOutput) {
      const length = this.shadowTuning.compression / Math.tan(this.shadowTuning.elevation * Math.PI / 180);
      lengthOutput.value = `${length.toFixed(2).replace('.', ',')} px`;
    }
  }

  private resetShadowTuning(): void {
    this.shadowTuning = { ...DEFAULT_SHADOW_TUNING };
    this.clearGeneratedShadows(true);
    this.syncShadowControls();
    this.rebuildBuildings();
  }

  private createHud(parent: HTMLElement): HTMLDivElement {
    const hud = document.createElement('div');
    hud.className = 'ds-hive-lab-hud is-collapsed';
    hud.innerHTML = `
      <button class="ds-hive-lab-hud__toggle" type="button" data-action="drawer" aria-expanded="false" title="Развернуть инженерную панель">‹</button>
      <div class="ds-hive-lab-hud__content">
        <div class="ds-hive-lab-hud__title">Наша инженерная панель</div>
        <div class="ds-hive-lab-hud__subtitle" data-role="subtitle">Домашняя игровая сота · 217 клеток</div>
        <div class="ds-hive-lab-hud__metrics" data-role="metrics"></div>

        <details open>
          <summary>Нагрузка сцены</summary>
          <div class="ds-hive-lab-hud__load-metrics" data-role="load-metrics"></div>
        </details>

        <details open>
          <summary>Вид соты</summary>
          <div class="ds-hive-lab-hud__group">
            <label class="ds-hive-lab-hud__range">Масштаб камеры
              <input type="range" min="20" max="250" step="5" value="100" data-action="zoom">
              <output data-role="zoom">100%</output>
            </label>
            <div class="ds-hive-lab-hud__camera-actions">
              <button type="button" data-action="overview">Вся сота</button>
              <button type="button" data-action="focus-selected">К выбранной клетке</button>
            </div>
            <label><input type="checkbox" data-action="dome" checked> Защитная сфера</label>
            <label><input type="checkbox" data-action="terrain-map"> Сетка: ID, координаты и код грунта</label>
            <small data-role="terrain-contract">92 земли · 35 песка · 45 травы · 44 воды · центр X</small>
            <small><b>#num</b> — позиция в серверной строке · <b>r/c</b> — ряд/колонка · S земля · D пустыня · G трава · W вода · X система</small>
            <label><input type="checkbox" data-action="shadows" checked> Падающие тени по силуэту</label>
            <label><input type="checkbox" data-action="contact-shadows" checked> Контактные тени под домами</label>
            <label><input type="checkbox" data-action="fusions" checked> Объединять дома 3 → 1 и 7 → 1</label>
          </div>
        </details>

        <details open>
          <summary>Сверка грунта с рисунком</summary>
          <div class="ds-hive-lab-hud__group">
            <label><input type="checkbox" data-action="terrain-audit"> Режим ручной сверки</label>
            <small>Включи режим, выбери клетку на чистой подложке и укажи, какой грунт виден на рисунке. Неизменённые клетки считаются совпавшими с планом.</small>
            <div class="ds-hive-lab-hud__audit-counts" data-role="terrain-audit-counts"></div>
            <div class="ds-hive-lab-hud__terrain-buttons" data-role="terrain-audit-buttons">
              <button type="button" data-art-terrain="S">S · земля</button>
              <button type="button" data-art-terrain="D">D · пустыня</button>
              <button type="button" data-art-terrain="G">G · трава</button>
              <button type="button" data-art-terrain="W">W · вода</button>
              <button type="button" data-art-terrain="X">X · узел</button>
            </div>
            <div class="ds-hive-lab-hud__camera-actions">
              <button type="button" data-action="copy-terrain-audit">Скопировать строку</button>
              <button type="button" data-action="reset-terrain-audit">Вернуть исходную разметку</button>
            </div>
            <small data-role="terrain-audit-status">Разметка сохраняется локально в этом браузере.</small>
          </div>
        </details>

        <details open>
          <summary>Свет и тени</summary>
          <div class="ds-hive-lab-hud__group">
            <label class="ds-hive-lab-hud__range">Направление тени
              <input type="range" min="180" max="270" step="1" value="220" data-shadow="azimuth">
              <output data-shadow-output="azimuth">220°</output>
            </label>
            <label class="ds-hive-lab-hud__range">Высота Силуса
              <input type="range" min="10" max="80" step="1" value="33" data-shadow="elevation">
              <output data-shadow-output="elevation">33°</output>
            </label>
            <label class="ds-hive-lab-hud__range">Сжатие изометрии
              <input type="range" min="0.15" max="0.9" step="0.01" value="0.84" data-shadow="compression">
              <output data-shadow-output="compression">0,84</output>
            </label>
            <label class="ds-hive-lab-hud__range">Плотность тени
              <input type="range" min="0" max="0.9" step="0.02" value="0.58" data-shadow="groundOpacity">
              <output data-shadow-output="groundOpacity">58%</output>
            </label>
            <label class="ds-hive-lab-hud__range">Тень на соседних домах
              <input type="range" min="0" max="0.45" step="0.01" value="0.16" data-shadow="neighborOpacity">
              <output data-shadow-output="neighborOpacity">16%</output>
            </label>
            <label class="ds-hive-lab-hud__range">Контакт с землёй
              <input type="range" min="0" max="1" step="0.02" value="0.72" data-shadow="contactOpacity">
              <output data-shadow-output="contactOpacity">72%</output>
            </label>
            <label class="ds-hive-lab-hud__range">Мягкость края
              <input type="range" min="0" max="4" step="0.2" value="1.2" data-shadow="blur">
              <output data-shadow-output="blur">1,2 px</output>
            </label>
            <p class="ds-hive-lab-hud__derived">Длина на 1 px высоты: <output data-role="shadow-length">1,29 px</output></p>
            <button type="button" data-action="reset-shadow">Вернуть принятый свет</button>
          </div>
        </details>

        <details>
          <summary>Точная математика силуэта</summary>
          <div class="ds-hive-lab-hud__group">
            <label class="ds-hive-lab-hud__range">Порог прозрачности
              <input type="range" min="1" max="180" step="1" value="26" data-shadow="alphaThreshold">
              <output data-shadow-output="alphaThreshold">26</output>
            </label>
            <label class="ds-hive-lab-hud__range">Толщина луча
              <input type="range" min="0.5" max="8" step="0.25" value="2.25" data-shadow="rayWidth">
              <output data-shadow-output="rayWidth">2,25 px</output>
            </label>
            <label class="ds-hive-lab-hud__range">Склейка силуэта
              <input type="range" min="0" max="3" step="0.1" value="0.8" data-shadow="joinRadius">
              <output data-shadow-output="joinRadius">0,8 px</output>
            </label>
            <label class="ds-hive-lab-hud__range">Поправка основания
              <input type="range" min="-0.1" max="0.1" step="0.005" value="0.06" data-shadow="baselineOffset">
              <output data-shadow-output="baselineOffset">0,060</output>
            </label>
          </div>
        </details>

        <details open>
          <summary>Оживление</summary>
          <div class="ds-hive-lab-hud__group">
            <label><input type="checkbox" data-action="animation" checked> Вода и свечение</label>
            <label class="ds-hive-lab-hud__range">Интенсивность
              <input type="range" min="0.5" max="2.5" step="0.1" value="1.6" data-action="intensity">
              <output data-role="intensity">1,6×</output>
            </label>
          </div>
        </details>

        <details open>
          <summary>Выбранная клетка</summary>
          <section class="ds-hive-lab-hud__cell">
            <h3 data-role="cell-title">Клетка</h3>
            <p data-role="cell-state"></p>
            <p class="ds-hive-lab-hud__ground-rule" data-role="ground-rule"></p>
            <div class="ds-hive-lab-hud__build" data-role="build-menu">
              <button type="button" data-build="0">Башенный дом</button>
              <button type="button" data-build="1">Купольный дом</button>
              <button type="button" data-build="2">Овальный дом</button>
              <button type="button" data-build="3">Стази-сборщик</button>
              <button type="button" data-build="4">Кадериумная кузница</button>
              <button type="button" data-build="5">Нанокристаллизатор</button>
              <button type="button" data-build="6">Фермерский парник</button>
              <button type="button" data-build="7">Завод лёгкого вооружения</button>
              <button type="button" data-build="8">Фабрика тяжёлого вооружения</button>
              <button type="button" data-build="9">Военная база</button>
            </div>
            <div class="ds-hive-lab-hud__build" data-role="construction-menu" hidden>
              <button type="button" data-action="complete">Завершить строительство</button>
              <button type="button" data-action="demolish">Отменить</button>
            </div>
            <div class="ds-hive-lab-hud__build" data-role="building-menu" hidden>
              <button type="button" data-action="power">Отключить здание</button>
              <button type="button" class="danger" data-action="demolish">Снести здание</button>
            </div>
          </section>
        </details>

        <div class="ds-hive-lab-hud__help" data-role="help">Колесо меняет масштаб относительно курсора. Потяни пустую поверхность, чтобы переместить камеру. Застройка и сверка грунта сохраняются в этом браузере автоматически.</div>
        <div class="ds-hive-lab-hud__save-status" data-role="building-save-status">Автосохранение включено.</div>
        <div class="ds-hive-lab-hud__save-actions">
          <button type="button" data-action="save-building-snapshot">Запомнить постройки</button>
          <button type="button" data-action="restore-building-snapshot">Вернуть снимок</button>
        </div>
        <div class="ds-hive-lab-hud__actions">
          <button type="button" data-action="fill-all">Заполнить доступные</button>
          <button type="button" data-action="reset">Игровая застройка</button>
          <button type="button" data-action="clear">Очистить соту</button>
        </div>
      </div>`;
    parent.appendChild(hud);
    hud.querySelector<HTMLButtonElement>('[data-action="drawer"]')?.addEventListener('click', (event) => {
      this.setDrawerOpen(!this.drawerOpen, event.currentTarget as HTMLButtonElement);
    });
    hud.querySelector<HTMLInputElement>('[data-action="zoom"]')?.addEventListener('input', (event) => {
      const input = event.currentTarget as HTMLInputElement;
      this.viewScale = Number(input.value) / 100;
      const output = hud.querySelector<HTMLOutputElement>('[data-role="zoom"]');
      if (output) output.value = `${input.value}%`;
      this.zoomCameraAt(this.safeViewportCenter(), this.detailCameraScale() * this.viewScale);
    });
    hud.querySelector('[data-action="overview"]')?.addEventListener('click', () => this.showOverview(!this.terrainAuditEnabled));
    hud.querySelector('[data-action="focus-selected"]')?.addEventListener('click', () => this.focusSelectedCell(true));
    hud.querySelector<HTMLInputElement>('[data-action="dome"]')?.addEventListener('change', (event) => {
      const visible = (event.currentTarget as HTMLInputElement).checked;
      this.domeBaseLayer.visible = visible;
      this.domeLayer.visible = visible;
    });
    hud.querySelector<HTMLInputElement>('[data-action="terrain-map"]')?.addEventListener('change', (event) => {
      const input = event.currentTarget as HTMLInputElement;
      this.terrainCalibrationEnabled = input.checked || this.terrainAuditEnabled;
      input.checked = this.terrainCalibrationEnabled;
      this.terrainCalibrationLayer.visible = this.terrainCalibrationEnabled;
      this.terrainCalibrationLabelLayer.visible = this.terrainCalibrationEnabled;
      this.setTerrainMapQuery(this.terrainCalibrationEnabled);
    });
    hud.querySelector<HTMLInputElement>('[data-action="terrain-audit"]')?.addEventListener('change', (event) => {
      this.setTerrainAuditEnabled((event.currentTarget as HTMLInputElement).checked);
    });
    hud.querySelectorAll<HTMLButtonElement>('[data-art-terrain]').forEach((button) => button.addEventListener('click', () => {
      const code = button.dataset.artTerrain;
      if (code && isHomeTerrainCode(code)) this.setActualTerrain(code);
    }));
    hud.querySelector('[data-action="reset-terrain-audit"]')?.addEventListener('click', () => this.resetTerrainAudit());
    hud.querySelector('[data-action="copy-terrain-audit"]')?.addEventListener('click', async () => {
      const status = hud.querySelector<HTMLElement>('[data-role="terrain-audit-status"]');
      try {
        await navigator.clipboard.writeText(this.actualTerrain.join(''));
        if (status) status.textContent = 'Фактическая строка из 217 кодов скопирована.';
      } catch {
        if (status) status.textContent = this.actualTerrain.join('');
      }
    });
    hud.querySelector<HTMLInputElement>('[data-action="shadows"]')?.addEventListener('change', (event) => {
      this.shadowsEnabled = (event.currentTarget as HTMLInputElement).checked;
      this.rebuildBuildings();
    });
    hud.querySelector<HTMLInputElement>('[data-action="contact-shadows"]')?.addEventListener('change', (event) => {
      this.contactShadowsEnabled = (event.currentTarget as HTMLInputElement).checked;
      this.setBooleanQuery('contact-shadow', this.contactShadowsEnabled);
      this.rebuildBuildings();
    });
    hud.querySelector<HTMLInputElement>('[data-action="fusions"]')?.addEventListener('change', (event) => {
      this.fusionsEnabled = (event.currentTarget as HTMLInputElement).checked;
      this.setBooleanQuery('fusions', this.fusionsEnabled);
      this.rebuildBuildings();
    });
    hud.querySelectorAll<HTMLInputElement>('[data-shadow]').forEach((input) => input.addEventListener('input', (event) => {
      const target = event.currentTarget as HTMLInputElement;
      const key = target.dataset.shadow as keyof ShadowTuning;
      const value = Number(target.value);
      this.shadowTuning[key] = value;
      const output = hud.querySelector<HTMLOutputElement>(`[data-shadow-output="${key}"]`);
      if (output) output.value = this.shadowValueLabel(key, value);
      const lengthOutput = hud.querySelector<HTMLOutputElement>('[data-role="shadow-length"]');
      if (lengthOutput) {
        const length = this.shadowTuning.compression / Math.tan(this.shadowTuning.elevation * Math.PI / 180);
        lengthOutput.value = `${length.toFixed(2).replace('.', ',')} px`;
      }
      this.clearGeneratedShadows(key === 'alphaThreshold');
      this.rebuildBuildings();
    }));
    hud.querySelector('[data-action="reset-shadow"]')?.addEventListener('click', () => this.resetShadowTuning());
    hud.querySelector('[data-action="fill-all"]')?.addEventListener('click', () => this.fillAll());
    hud.querySelector('[data-action="reset"]')?.addEventListener('click', () => this.reset());
    hud.querySelector('[data-action="clear"]')?.addEventListener('click', () => this.clear());
    hud.querySelector('[data-action="save-building-snapshot"]')?.addEventListener('click', () => this.saveBuildingSnapshot());
    hud.querySelector('[data-action="restore-building-snapshot"]')?.addEventListener('click', () => this.restoreBuildingSnapshot());
    hud.querySelector('[data-action="complete"]')?.addEventListener('click', () => this.completeSelected());
    hud.querySelector('[data-action="power"]')?.addEventListener('click', () => this.toggleSelectedPower());
    hud.querySelectorAll('[data-action="demolish"]').forEach((button) => button.addEventListener('click', () => this.demolishSelected()));
    hud.querySelectorAll<HTMLElement>('[data-build]').forEach((button) => button.addEventListener('click', () => {
      this.buildSelected(Number(button.dataset.build) as BuildingVariant);
    }));
    hud.querySelector<HTMLInputElement>('[data-action="animation"]')?.addEventListener('change', (event) => {
      this.setAnimationEnabled((event.currentTarget as HTMLInputElement).checked);
    });
    hud.querySelector<HTMLInputElement>('[data-action="intensity"]')?.addEventListener('input', (event) => {
      this.animationIntensity = Number((event.currentTarget as HTMLInputElement).value);
      const output = this.hud.querySelector<HTMLOutputElement>('[data-role="intensity"]');
      if (output) output.value = `${this.animationIntensity.toFixed(1).replace('.', ',')}×`;
      if (this.animationEnabled) this.animateEffects(performance.now());
    });
    return hud;
  }

  private updateHud(fusionCount?: number, visualCount?: number): void {
    const metrics = this.hud.querySelector<HTMLElement>('[data-role="metrics"]');
    if (!metrics) return;
    const working = [...this.buildings.values()].filter((state) => state === 'working').length;
    const construction = [...this.buildings.values()].filter((state) => state === 'construction').length;
    const disabled = [...this.buildings.values()].filter((state) => state === 'disabled').length;
    const fps = this.fpsSamples.length
      ? Math.round(this.fpsSamples.reduce((sum, value) => sum + value, 0) / this.fpsSamples.length)
      : 0;
    const hovered = this.hoveredCell ?? this.selectedCell;
    const plannedTerrainCode = this.terrain[hovered];
    const terrainCode = this.terrainAuditEnabled ? this.actualTerrain[hovered] : this.homeTerrainAt(hovered);
    const existingFusionCount = fusionCount ?? this.lastFusionCount;
    metrics.textContent = [
      `FPS ${fps}`,
      `клеток 217`,
      `домов ${working}`,
      `строится ${construction}`,
      `отключено ${disabled}`,
      `комплексов ${existingFusionCount}`,
      `визуальных объектов ${visualCount ?? this.lastVisualCount}`,
      this.terrainAuditEnabled && terrainCode !== plannedTerrainCode
        ? `клетка ${hovered}: план ${plannedTerrainCode} → рисунок ${terrainCode}`
        : `клетка ${hovered}: ${TERRAIN_NAMES[terrainCode] ?? terrainCode}`,
    ].join('  ·  ');

    const loadMetrics = this.hud.querySelector<HTMLElement>('[data-role="load-metrics"]');
    if (loadMetrics) {
      const shadowTextureBytes = [...this.shadowTextureCache.values()]
        .reduce((sum, texture) => sum + Math.round(texture.width) * Math.round(texture.height) * 4, 0);
      const waterSurfaceCount = this.effectItems.reduce((sum, effect) => {
        if (!effect.emissive) return sum;
        const kind = effectKeyForItem(effect.item);
        if (!kind) return sum;
        return sum + (WATER_SURFACES[kind]?.length ?? 0);
      }, 0);
      const visibleWaterSurfaceCount = this.effectItems.reduce((sum, effect) => {
        if (!effect.emissive || !effect.visible) return sum;
        const kind = effectKeyForItem(effect.item);
        if (!kind) return sum;
        return sum + (WATER_SURFACES[kind]?.length ?? 0);
      }, 0);
      const frameMs = fps > 0 ? 1000 / fps : 0;
      const toMiB = (bytes: number) => (bytes / 1024 / 1024).toFixed(2).replace('.', ',');
      const loadedTileCount = this.groundTiles.filter((tile) => tile.sprite).length;
      const groundPngBytes = !this.ringGroundEnabled
        ? RUNTIME_PNG_BYTES.legacy
        : this.ringGroundVersion === 'v04' ? RUNTIME_PNG_BYTES.ringV04
          : this.ringGroundVersion === 'v05' ? RUNTIME_PNG_BYTES.ringV05
            : this.ringGroundVersion === 'v07' ? RUNTIME_PNG_BYTES.ringV07
              : this.ringGroundVersion === 'v08' ? RUNTIME_PNG_BYTES.ringV08
                : this.ringGroundVersion === 'v09' ? RUNTIME_PNG_BYTES.ringV09
                  : this.ringGroundVersion === 'v10' ? RUNTIME_PNG_BYTES.ringV10
                    : this.ringGroundVersion === 'v11' ? RUNTIME_PNG_BYTES.ringV11
                      : this.ringGroundVersion === 'v12' ? RUNTIME_PNG_BYTES.ringV12
                        : this.ringGroundVersion === 'v13' ? RUNTIME_PNG_BYTES.ringV13
                          : this.ringGroundVersion === 'v14'
                            ? RUNTIME_PNG_BYTES.ringV14Preview + this.loadedGroundTilePngBytes
                            : RUNTIME_PNG_BYTES.ringV15;
      loadMetrics.innerHTML = `
        <div><b>${working + construction + disabled}</b> занятых клеток · <b>${visualCount ?? this.lastVisualCount}</b> визуальных зданий · <b>${this.lastVisibleCount}</b> в кадре</div>
        <div>Объединение <b>${this.fusionsEnabled ? 'включено' : 'выключено'}</b> · контактная тень <b>${this.contactShadowsEnabled ? 'включена' : 'выключена'}</b></div>
        <div><b>${visibleWaterSurfaceCount}</b> из ${waterSurfaceCount} водных поверхностей в кадре · анимация ${this.animationEnabled ? 'включена' : 'выключена'}</div>
        <div><b>${fps || '—'} FPS</b> · ${frameMs ? `${frameMs.toFixed(1).replace('.', ',')} мс/кадр` : 'сбор данных'}</div>
        ${this.ringGroundVersion === 'v14' ? `<div>HD-тайлы V14: <b>${loadedTileCount} / ${this.groundTiles.length}</b> · ${this.overviewMode ? 'сейчас только preview' : 'видимая область + preview'}</div>` : ''}
        <div>PNG <b>${toMiB(groundPngBytes)} МиБ</b> · текстуры сцены ≈ <b>${toMiB(this.runtimeTextureBytes + this.groundTileTextureBytes + shadowTextureBytes)} МиБ</b></div>
        <small>Оценка RGBA в памяти, без кадровых буферов браузера.</small>`;
    }

    const auditCounts = this.hud.querySelector<HTMLElement>('[data-role="terrain-audit-counts"]');
    const terrainContract = this.hud.querySelector<HTMLElement>('[data-role="terrain-contract"]');
    if (terrainContract) {
      const source = this.ringGroundVersion === 'v14' ? this.actualTerrain : this.terrain;
      const count = (code: HomeTerrainCode) => source.filter((value) => value === code).length;
      terrainContract.textContent = `${count('S')} земли · ${count('D')} песка · ${count('G')} травы · ${count('W')} воды · центр X`;
    }
    const mismatchCount = this.actualTerrain.reduce((sum, code, index) => sum + (code === this.terrain[index] ? 0 : 1), 0);
    if (auditCounts) {
      const rows = TERRAIN_AUDIT_CODES.map((code) => {
        const actual = this.actualTerrain.filter((value) => value === code).length;
        const target = TERRAIN_TARGET_COUNTS[code];
        const delta = actual - target;
        const deltaLabel = delta === 0 ? '0' : delta > 0 ? `+${delta}` : String(delta);
        const deltaClass = delta === 0 ? 'is-even' : delta > 0 ? 'is-extra' : 'is-missing';
        return `<div><b>${code}</b><span>${TERRAIN_NAMES[code]}</span><span>${actual} / ${target}</span><em class="${deltaClass}">${deltaLabel}</em></div>`;
      }).join('');
      auditCounts.innerHTML = `<strong>${mismatchCount} отличий от плана</strong>${rows}`;
    }
    const selectedActualTerrain = this.actualTerrain[this.selectedCell];
    this.hud.querySelectorAll<HTMLButtonElement>('[data-art-terrain]').forEach((button) => {
      button.classList.toggle('is-active', button.dataset.artTerrain === selectedActualTerrain);
      button.disabled = !this.terrainAuditEnabled;
    });

    const selectedTerrainCode = this.terrain[this.selectedCell];
    const selectedGameplayTerrainCode = this.homeTerrainAt(this.selectedCell);
    const selectedTerrain = TERRAIN_NAMES[selectedGameplayTerrainCode] ?? selectedGameplayTerrainCode;
    const selectedCellMeta = this.cells[this.selectedCell];
    const state = this.buildings.get(this.selectedCell);
    const selectedVariant = this.variants.get(this.selectedCell);
    const selectedBuildingName = selectedVariant === undefined ? 'Здание' : BUILDING_LABELS[selectedVariant];
    const title = this.hud.querySelector<HTMLElement>('[data-role="cell-title"]');
    const stateText = this.hud.querySelector<HTMLElement>('[data-role="cell-state"]');
    const groundRule = this.hud.querySelector<HTMLElement>('[data-role="ground-rule"]');
    const buildMenu = this.hud.querySelector<HTMLElement>('[data-role="build-menu"]');
    const constructionMenu = this.hud.querySelector<HTMLElement>('[data-role="construction-menu"]');
    const buildingMenu = this.hud.querySelector<HTMLElement>('[data-role="building-menu"]');
    const powerButton = this.hud.querySelector<HTMLButtonElement>('[data-action="power"]');
    const selectedHomeTerrain = this.homeTerrainAt(this.selectedCell);
    const allowedVariants = allowedVariantsForTerrain(selectedHomeTerrain);
    if (title) {
      title.textContent = this.terrainAuditEnabled
        ? `#${String(this.selectedCell).padStart(3, '0')} · план ${selectedTerrainCode} → рисунок ${selectedActualTerrain} · r${String(selectedCellMeta.row).padStart(2, '0')} c${String(selectedCellMeta.col).padStart(2, '0')}`
        : `#${String(this.selectedCell).padStart(3, '0')} ${selectedGameplayTerrainCode} · r${String(selectedCellMeta.row).padStart(2, '0')} c${String(selectedCellMeta.col).padStart(2, '0')} · ${selectedTerrain}`;
    }
    if (stateText) {
      stateText.textContent = this.terrainAuditEnabled
        ? selectedTerrainCode === selectedActualTerrain
          ? 'Пока считается совпавшей с планом. Если рисунок показывает другой грунт, выбери его выше.'
          : `Отмечено расхождение: по плану ${TERRAIN_NAMES[selectedTerrainCode]}, на рисунке ${TERRAIN_NAMES[selectedActualTerrain]}.`
        : selectedGameplayTerrainCode === 'X'
          ? 'Системный узел Конструкторов: здесь установлены кольцевые ворота домашней соты.'
        : !state
          ? 'Свободная клетка. Выбери одноклеточное здание.'
        : state === 'construction'
          ? `${selectedBuildingName}: строительство начато. Следующая команда завершит его.`
          : state === 'disabled'
            ? `${selectedBuildingName}: заморожено и не участвует в объединении.`
            : isResidentialVariant(selectedVariant)
              ? `${selectedBuildingName}: работает. Соседние жилые дома объединяются автоматически.`
              : `${selectedBuildingName}: работает как самостоятельное одноклеточное здание.`;
    }
    if (groundRule) {
      groundRule.textContent = this.terrainAuditEnabled
        ? `Фактическая разметка сохранена локально. Сейчас: ${selectedActualTerrain} · ${TERRAIN_NAMES[selectedActualTerrain]}.`
        : this.buildNotice || TERRAIN_BUILD_HINTS[selectedHomeTerrain];
      groundRule.classList.toggle('is-error', this.terrainAuditEnabled ? selectedTerrainCode !== selectedActualTerrain : Boolean(this.buildNotice));
    }
    this.hud.querySelectorAll<HTMLButtonElement>('[data-build]').forEach((button) => {
      const variant = Number(button.dataset.build) as BuildingVariant;
      const allowed = canBuildVariantOnTerrain(variant, selectedHomeTerrain);
      button.disabled = this.terrainAuditEnabled || !allowed;
      button.title = allowed
        ? `${BUILDING_LABELS[variant]} можно строить на поверхности «${selectedTerrain.toLowerCase()}»`
        : buildRestrictionReason(variant, selectedHomeTerrain);
    });
    if (buildMenu) buildMenu.hidden = this.terrainAuditEnabled || state !== undefined || allowedVariants.length === 0;
    if (constructionMenu) constructionMenu.hidden = this.terrainAuditEnabled || state !== 'construction';
    if (buildingMenu) buildingMenu.hidden = this.terrainAuditEnabled || state !== 'working' && state !== 'disabled';
    if (powerButton) powerButton.textContent = state === 'disabled' ? 'Включить здание' : 'Отключить здание';
  }

  private cameraBounds(): { x: number; y: number; width: number; height: number } {
    const size = mapPixelSize();
    return {
      x: -size.width * 0.121176471,
      y: -175,
      width: size.width * 1.242352941,
      height: size.height * 1.706666667,
    };
  }

  private safeViewport(): { left: number; top: number; right: number; bottom: number; width: number; height: number } {
    const screenWidth = this.app.screen.width;
    const screenHeight = this.app.screen.height;
    // Экономическая колонка прозрачная и лежит поверх карты. Не вычитаем её
    // ширину из камеры: иначе на широких экранах сота сдвигается вправо и
    // вокруг подложки появляются большие чёрные поля.
    const left = 0;
    const requestedRight = this.drawerOpen ? (screenWidth <= 900 ? 316 : 350) : 0;
    const rightInset = Math.min(requestedRight, Math.max(0, screenWidth - left - 150));
    const top = Math.min(54, screenHeight * 0.14);
    const bottomInset = Math.min(48, screenHeight * 0.13);
    const right = screenWidth - rightInset;
    const bottom = screenHeight - bottomInset;
    return {
      left,
      top,
      right,
      bottom,
      width: Math.max(150, right - left),
      height: Math.max(150, bottom - top),
    };
  }

  private safeViewportCenter(): { x: number; y: number } {
    const safe = this.safeViewport();
    return { x: safe.left + safe.width / 2, y: safe.top + safe.height / 2 };
  }

  private detailCameraScale(): number {
    return 1.6;
  }

  private overviewCameraScale(): number {
    const safe = this.safeViewport();
    const bounds = this.cameraBounds();
    // Стартовый обзор работает как background-size: cover. На узком окне
    // края подложки симметрично уходят за экран, на широком она растёт до
    // его границ. Небольшой 9% технологический отступ повторяет композицию
    // эталонного кадра 1920×1080 и не оставляет широких чёрных полос.
    return Math.max(safe.width / bounds.width, safe.height / bounds.height) * 0.91;
  }

  private syncCameraZoomControl(): void {
    const percent = Math.round(this.world.scale.x / this.detailCameraScale() * 100);
    const input = this.hud.querySelector<HTMLInputElement>('[data-action="zoom"]');
    const output = this.hud.querySelector<HTMLOutputElement>('[data-role="zoom"]');
    if (input) input.value = String(Math.max(Number(input.min), Math.min(Number(input.max), percent)));
    if (output) output.value = `${percent}%`;
  }

  private clampCamera(): void {
    if (!this.cameraInitialized) return;
    const safe = this.safeViewport();
    const bounds = this.cameraBounds();
    const scale = this.world.scale.x;
    const projectedWidth = bounds.width * scale;
    const projectedHeight = bounds.height * scale;
    if (projectedWidth <= safe.width) {
      this.world.x = safe.left + (safe.width - projectedWidth) / 2 - bounds.x * scale;
    } else {
      const visibleX = Math.min(120, safe.width * 0.42);
      const minimumX = safe.left + visibleX - (bounds.x + bounds.width) * scale;
      const maximumX = safe.right - visibleX - bounds.x * scale;
      this.world.x = Math.max(minimumX, Math.min(maximumX, this.world.x));
    }
    if (projectedHeight <= safe.height) {
      this.world.y = safe.top + (safe.height - projectedHeight) / 2 - bounds.y * scale;
    } else {
      const visibleY = Math.min(90, safe.height * 0.38);
      const minimumY = safe.top + visibleY - (bounds.y + bounds.height) * scale;
      const maximumY = safe.bottom - visibleY - bounds.y * scale;
      this.world.y = Math.max(minimumY, Math.min(maximumY, this.world.y));
    }
  }

  private zoomCameraAt(screenPoint: { x: number; y: number }, requestedScale: number): void {
    if (!this.cameraInitialized) return;
    const minimumScale = Math.max(0.2, this.overviewCameraScale() * 0.85);
    const maximumScale = this.detailCameraScale() * 2.5;
    const nextScale = Math.max(minimumScale, Math.min(maximumScale, requestedScale));
    const worldPoint = this.world.toLocal(screenPoint);
    this.world.scale.set(nextScale);
    this.world.x = screenPoint.x - worldPoint.x * nextScale;
    this.world.y = screenPoint.y - worldPoint.y * nextScale;
    this.viewScale = nextScale / this.detailCameraScale();
    this.clampCamera();
    this.updateVisibleItems();
    this.syncCameraZoomControl();
  }

  private focusSelectedCell(restoreDetailScale = false): void {
    this.overviewMode = false;
    const rawAnchor = buildingAnchor(this.cells[this.selectedCell]);
    const anchor = projectPlayfieldPoint(rawAnchor.x, rawAnchor.y);
    const scale = restoreDetailScale || !this.cameraInitialized
      ? this.detailCameraScale()
      : this.world.scale.x;
    if (restoreDetailScale) this.viewScale = 1;
    this.cameraInitialized = true;
    this.world.scale.set(scale);
    const center = this.safeViewportCenter();
    this.world.x = center.x - anchor.x * scale;
    this.world.y = center.y - (anchor.y - 12) * scale;
    this.clampCamera();
    this.updateVisibleItems();
    this.syncCameraZoomControl();
  }

  private showOverview(collapseDrawer = true): void {
    this.overviewMode = true;
    if (collapseDrawer && this.drawerOpen) this.setDrawerOpen(false);
    const bounds = this.cameraBounds();
    const safe = this.safeViewport();
    const scale = this.overviewCameraScale();
    // Оптический центр соты должен находиться выше геометрического центра
    // окна: так в кадр попадает ближний фрагмент центральной соты комплекса,
    // а основная игровая сота не выглядит опущенной к нижнему статус-бару.
    const verticalBias = Math.min(100, safe.height * 0.12);
    this.cameraInitialized = true;
    this.world.scale.set(scale);
    this.world.x = safe.left + (safe.width - bounds.width * scale) / 2 - bounds.x * scale;
    this.world.y = safe.top + (safe.height - bounds.height * scale) / 2 - bounds.y * scale - verticalBias;
    this.viewScale = scale / this.detailCameraScale();
    this.updateVisibleItems();
    this.syncCameraZoomControl();
  }

  private setDrawerOpen(open: boolean, providedButton?: HTMLButtonElement): void {
    this.drawerOpen = open;
    this.hud.classList.toggle('is-collapsed', !open);
    const button = providedButton ?? this.hud.querySelector<HTMLButtonElement>('[data-action="drawer"]');
    if (button) {
      button.textContent = open ? '›' : '‹';
      button.title = open ? 'Свернуть инженерную панель' : 'Развернуть инженерную панель';
      button.setAttribute('aria-expanded', String(open));
    }
    if (this.cameraInitialized) {
      if (open) this.focusSelectedCell();
      else {
        this.clampCamera();
        this.updateVisibleItems();
      }
    }
  }

  private handleCameraResize(): void {
    if (!this.cameraInitialized) return;
    if (this.overviewMode) this.showOverview(false);
    else this.focusSelectedCell();
  }

  destroy(): void {
    this.app.renderer.off('resize', this.resizeHandler);
    this.app.canvas.removeEventListener('wheel', this.wheelHandler);
    this.clearGeneratedShadows(true);
    this.hud.remove();
    this.app.destroy(true, { children: true });
  }
}
