import {
  Application,
  Assets,
  Container,
  FederatedPointerEvent,
  Graphics,
  Rectangle,
  Sprite,
  Texture,
} from 'pixi.js';
import { buildHexGrid, buildingAnchor, hexPolygonPoints, mapPixelSize, type CellPosition } from './hexGrid';
import { HOME_LAB_GROUND } from './locationGround';
import './hive217Experiment.css';

type BuildingState = 'construction' | 'working' | 'disabled';
type FusionKind = 'three' | 'seven';
type BuildingVariant = 0 | 1 | 2;

interface Fusion {
  kind: FusionKind;
  cellNums: number[];
  anchor: { x: number; y: number };
  depthY: number;
}

interface RenderItem {
  kind: 'single' | 'construction' | FusionKind;
  cellNums: number[];
  anchor: { x: number; y: number };
  depthY: number;
  variant: number;
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
  width: number;
  height: number;
  visible: boolean;
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
  ground: { alias: 'hive-lab/ground-hd', src: '/assets/hive-lab/hive-ground-hd.png' },
  v02: { alias: 'hive-lab/v02', src: '/assets/hive-lab/house-v02.png', logicalWidth: 56, anchor: [0.5, 0.954128] as const },
  v02Emissive: { alias: 'hive-lab/v02-emissive', src: '/assets/hive-lab/house-v02-emissive.png' },
  v03: { alias: 'hive-lab/v03', src: '/assets/hive-lab/house-v03.png', logicalWidth: 54, anchor: [0.5, 0.839867] as const },
  v03Emissive: { alias: 'hive-lab/v03-emissive', src: '/assets/hive-lab/house-v03-emissive.png' },
  v05: { alias: 'hive-lab/v05', src: '/assets/hive-lab/house-v05.png', logicalWidth: 52, anchor: [0.5, 0.840701] as const },
  v05Emissive: { alias: 'hive-lab/v05-emissive', src: '/assets/hive-lab/house-v05-emissive.png' },
  three: { alias: 'hive-lab/complex-3', src: '/assets/hive-lab/complex-3.png', logicalWidth: 92, anchor: [0.5, 0.901585] as const },
  threeEmissive: { alias: 'hive-lab/complex-3-emissive', src: '/assets/hive-lab/complex-3-emissive.png' },
  seven: { alias: 'hive-lab/complex-7', src: '/assets/hive-lab/complex-7.png', logicalWidth: 126, anchor: [0.5, 0.920767] as const },
  sevenEmissive: { alias: 'hive-lab/complex-7-emissive', src: '/assets/hive-lab/complex-7-emissive.png' },
};

const EMISSIVE_ASSET_BY_KIND = {
  v02: MAP_ASSETS.v02Emissive,
  v03: MAP_ASSETS.v03Emissive,
  v05: MAP_ASSETS.v05Emissive,
  three: MAP_ASSETS.threeEmissive,
  seven: MAP_ASSETS.sevenEmissive,
} as const;

// Сумма одиннадцати подготовленных PNG из client/public/assets/hive-lab.
// Обновляется вместе с prepare-hive-lab-assets.ps1 после смены экспорта.
const RUNTIME_PNG_BYTES = 12_226_510;

interface WaterSurface {
  x: number;
  y: number;
  rx: number;
  ry: number;
  jet?: number;
}

// Координаты сняты с принятого водного стенда и выражены в долях PNG.
const WATER_SURFACES: Partial<Record<'v02' | 'v03' | 'v05' | 'three' | 'seven', WaterSurface[]>> = {
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
};

const TERRAIN_COLORS: Record<string, number> = {
  D: 0xd6a45f,
  S: 0xb98d65,
  G: 0x72a567,
  W: 0x4faac8,
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

function pointInHex(localX: number, localY: number, cell: CellPosition): boolean {
  const x = localX - cell.x;
  const y = localY - cell.y;
  if (x < 0 || x > 48 || y < 0 || y > 26) return false;
  if (y < 6.5) return x >= 24 - y * (24 / 6.5) && x <= 24 + y * (24 / 6.5);
  if (y > 19.5) {
    const bottom = 26 - y;
    return x >= 24 - bottom * (24 / 6.5) && x <= 24 + bottom * (24 / 6.5);
  }
  return true;
}

function outerHivePolygon(inset = 0): number[] {
  const width = mapPixelSize().width;
  const height = mapPixelSize().height;
  return [
    216, inset,
    width - 216, inset,
    width - inset, height / 2,
    width - 216, height - inset,
    216, height - inset,
    inset, height / 2,
  ];
}

export class Hive217Experiment {
  readonly app: Application;
  readonly world = new Container();
  readonly cells = buildHexGrid();
  readonly terrain = HOME_LAB_GROUND.split('');
  readonly buildings = new Map<number, BuildingState>();
  readonly variants = new Map<number, BuildingVariant>();

  private readonly neighbors = makeNeighborTable(this.cells);
  private readonly buildingLayer = new Container();
  private readonly shadowLayer = new Container();
  private readonly contactShadowLayer = new Graphics();
  private readonly waterLayer = new Graphics();
  private readonly emissiveLayer = new Container();
  private readonly hoverLayer = new Graphics();
  private readonly selectionLayer = new Graphics();
  private readonly domeLayer = new Graphics();
  private readonly interactiveLayer = new Graphics();
  private readonly hud: HTMLDivElement;
  private effectItems: EffectItem[] = [];
  private selectedCell = 108;
  private hoveredCell: number | null = null;
  private fpsSamples: number[] = [];
  private lastHudUpdate = 0;
  private lastFusionCount = 0;
  private lastVisualCount = 0;
  private lastVisibleCount = 0;
  private runtimeTextureBytes = 0;
  private animationEnabled = true;
  private animationIntensity = 1.6;
  private lastEffectUpdate = 0;
  private shadowsEnabled = true;
  private contactShadowsEnabled = true;
  private fusionsEnabled = true;
  private shadowTuning: ShadowTuning = { ...DEFAULT_SHADOW_TUNING };
  private readonly shadowTextureCache = new Map<string, Texture>();
  private readonly silhouetteCache = new Map<string, SilhouetteProfile>();
  private viewScale = 1;
  private drawerOpen = true;
  private cameraInitialized = false;
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
    this.contactShadowsEnabled = urlParams.get('contact-shadow') !== 'off';
    this.fusionsEnabled = urlParams.get('fusions') !== 'off';
    const contactShadowInput = this.hud.querySelector<HTMLInputElement>('[data-action="contact-shadows"]');
    const fusionsInput = this.hud.querySelector<HTMLInputElement>('[data-action="fusions"]');
    if (contactShadowInput) contactShadowInput.checked = this.contactShadowsEnabled;
    if (fusionsInput) fusionsInput.checked = this.fusionsEnabled;
    await Assets.load(Object.values(MAP_ASSETS).map(({ alias, src }) => ({ alias, src })));
    this.runtimeTextureBytes = [...new Set(Object.values(MAP_ASSETS).map(({ alias }) => alias))]
      .reduce((sum, alias) => {
        const texture = Assets.get(alias) as Texture;
        return sum + Math.round(texture.width) * Math.round(texture.height) * 4;
      }, 0);
    this.app.stage.addChild(this.world);
    this.drawPlatform();
    this.drawSharedGround();
    this.drawDome();
    this.world.addChild(
      this.shadowLayer,
      this.contactShadowLayer,
      this.buildingLayer,
      this.waterLayer,
      this.emissiveLayer,
      this.domeLayer,
      this.hoverLayer,
      this.selectionLayer,
      this.interactiveLayer,
    );
    if (urlParams.get('density') === 'full') this.seedAllBuildings();
    else this.seedBuildings();
    this.bindInteraction();
    this.rebuildBuildings();
    this.drawCellMarker(this.selectionLayer, this.selectedCell, 0xffd777, 0.92, 2);
    this.focusSelectedCell(true);
    this.app.canvas.addEventListener('wheel', this.wheelHandler, { passive: false });
    this.app.renderer.on('resize', this.resizeHandler);
    this.app.ticker.add((ticker) => {
      this.fpsSamples.push(ticker.FPS);
      if (this.fpsSamples.length > 45) this.fpsSamples.shift();
      const now = performance.now();
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
    const texture = Assets.get(MAP_ASSETS.ground.alias);
    const ground = new Sprite(texture);
    const aspect = texture.width / texture.height;
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
    this.domeLayer
      .ellipse(centerX, centerY + 18, size.width * 0.49, size.height * 0.23)
      .stroke({ width: 1, color: 0x8ad8ff, alpha: 0.12 });
    this.domeLayer
      .moveTo(centerX, centerY - size.height * 0.96)
      .bezierCurveTo(centerX - 150, centerY - 190, centerX - 170, centerY + 170, centerX - 82, centerY + size.height * 0.78)
      .stroke({ width: 1, color: 0x9ee3ff, alpha: 0.11 });
    this.domeLayer
      .moveTo(centerX, centerY - size.height * 0.96)
      .bezierCurveTo(centerX + 150, centerY - 190, centerX + 170, centerY + 170, centerX + 82, centerY + size.height * 0.78)
      .stroke({ width: 1, color: 0x9ee3ff, alpha: 0.11 });
  }

  private seedBuildings(): void {
    const byTerrain = new Map<string, number[]>();
    this.terrain.forEach((code, cellNum) => {
      const list = byTerrain.get(code) ?? [];
      list.push(cellNum);
      byTerrain.set(code, list);
    });
    for (const code of ['D', 'S', 'G', 'W']) {
      const candidates = byTerrain.get(code) ?? [];
      for (let index = 2; index < candidates.length; index += Math.max(5, Math.floor(candidates.length / 8))) {
        this.buildings.set(candidates[index], 'working');
        this.variants.set(candidates[index], (candidates[index] % 3) as BuildingVariant);
      }
    }
    const center = 108;
    this.buildings.set(center, 'working');
    this.variants.set(center, 1);
    for (const neighbor of this.neighbors.get(center) ?? []) {
      this.buildings.set(neighbor, 'working');
      this.variants.set(neighbor, (neighbor % 3) as BuildingVariant);
    }
    for (const cellNum of [65, 66, 78, 139, 151, 152]) {
      this.buildings.set(cellNum, 'working');
      this.variants.set(cellNum, (cellNum % 3) as BuildingVariant);
    }
    for (const cellNum of [34, 173]) {
      this.buildings.set(cellNum, 'construction');
      this.variants.set(cellNum, (cellNum % 3) as BuildingVariant);
    }
  }

  private seedAllBuildings(): void {
    for (const cell of this.cells) {
      this.buildings.set(cell.num, 'working');
      this.variants.set(cell.num, (cell.num % 3) as BuildingVariant);
    }
  }

  private bindInteraction(): void {
    const size = mapPixelSize();
    this.interactiveLayer.rect(0, 0, size.width, size.height).fill({ color: 0xffffff, alpha: 0.001 });
    this.interactiveLayer.eventMode = 'static';
    this.interactiveLayer.cursor = 'grab';
    this.interactiveLayer.hitArea = new Rectangle(0, 0, size.width, size.height);
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
      const point = this.world.toLocal(event.global);
      const hit = this.hitCell(point.x, point.y);
      if (hit === this.hoveredCell) return;
      this.hoveredCell = hit;
      this.hoverLayer.clear();
      if (hit !== null) this.drawCellMarker(this.hoverLayer, hit, TERRAIN_COLORS[this.terrain[hit]] ?? 0x7fdcff, 0.34, 1.25);
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
      const point = this.world.toLocal(event.global);
      const cellNum = this.hitCell(point.x, point.y);
      if (cellNum === null) return;
      this.selectedCell = cellNum;
      this.selectionLayer.clear();
      this.drawCellMarker(this.selectionLayer, cellNum, 0xffd777, 0.92, 2);
      this.updateHud();
    });
  }

  private hitCell(x: number, y: number): number | null {
    for (const cell of this.cells) if (pointInHex(x, y, cell)) return cell.num;
    return null;
  }

  private drawCellMarker(target: Graphics, cellNum: number, color: number, alpha: number, width: number): void {
    const cell = this.cells[cellNum];
    if (!cell) return;
    target.poly(hexPolygonPoints().map((value, index) => value + (index % 2 === 0 ? cell.x : cell.y)))
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

  private drawBuildingShadows(item: RenderItem, asset: BuildingAsset): { ground: Sprite | null; receiver: Sprite | null } {
    const width = asset.logicalWidth;
    const height = width * (Assets.get(asset.alias) as Texture).height / (Assets.get(asset.alias) as Texture).width;
    if (this.contactShadowsEnabled) {
      this.contactShadowLayer
        .ellipse(item.anchor.x, item.anchor.y - 0.6, width * 0.46, Math.max(2, width * 0.065))
        .fill({ color: 0x071017, alpha: this.shadowTuning.contactOpacity * 0.22 })
        .ellipse(item.anchor.x, item.anchor.y - 0.25, width * 0.31, Math.max(1.35, width * 0.04))
        .fill({ color: 0x02070b, alpha: this.shadowTuning.contactOpacity * 0.56 });
    }

    if (!this.shadowsEnabled) return { ground: null, receiver: null };

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

  private rebuildBuildings(): void {
    this.shadowLayer.removeChildren().forEach((child) => child.destroy());
    this.contactShadowLayer.clear();
    this.buildingLayer.removeChildren().forEach((child) => child.destroy());
    this.emissiveLayer.removeChildren().forEach((child) => child.destroy());
    this.waterLayer.clear();
    this.effectItems = [];
    const working = new Set([...this.buildings].filter(([, state]) => state === 'working').map(([cellNum]) => cellNum));
    const fusions = this.fusionsEnabled ? resolveFusions(working, this.cells, this.neighbors) : [];
    const fusedCells = new Set(fusions.flatMap((fusion) => fusion.cellNums));
    const items: RenderItem[] = fusions.map((fusion) => ({
      kind: fusion.kind,
      cellNums: fusion.cellNums,
      anchor: fusion.anchor,
      depthY: fusion.depthY,
      variant: 0,
    }));
    for (const [cellNum, state] of this.buildings) {
      if (state === 'working' && fusedCells.has(cellNum)) continue;
      const anchor = buildingAnchor(this.cells[cellNum]);
      items.push({
        kind: state === 'construction' ? 'construction' : 'single',
        cellNums: [cellNum],
        anchor: { x: anchor.x, y: anchor.y - 4.5 },
        depthY: anchor.y,
        variant: this.variants.get(cellNum) ?? cellNum % 3,
      });
    }
    items.sort((left, right) => left.depthY - right.depthY || left.anchor.x - right.anchor.x);
    this.lastFusionCount = fusions.length;
    this.lastVisualCount = items.length;

    for (const item of items) {
      const asset = item.kind === 'seven'
        ? MAP_ASSETS.seven
        : item.kind === 'three'
          ? MAP_ASSETS.three
          : [MAP_ASSETS.v02, MAP_ASSETS.v03, MAP_ASSETS.v05][item.variant];
      const shadows = this.drawBuildingShadows(item, asset);
      if (shadows.receiver) this.buildingLayer.addChild(shadows.receiver);

      const sprite = Sprite.from(asset.alias);
      sprite.anchor.set(asset.anchor[0], asset.anchor[1]);
      sprite.width = asset.logicalWidth;
      sprite.height = asset.logicalWidth * sprite.texture.height / sprite.texture.width;
      sprite.x = item.anchor.x;
      sprite.y = item.anchor.y;
      if (item.kind === 'construction') {
        sprite.alpha = 0.35;
        sprite.tint = 0x9fdfff;
      } else if (this.buildings.get(item.cellNums[0]) === 'disabled') {
        sprite.alpha = 0.62;
        sprite.tint = 0x9eb8c4;
      }
      this.buildingLayer.addChild(sprite);

      const itemState = item.kind === 'three' || item.kind === 'seven'
        ? 'working'
        : this.buildings.get(item.cellNums[0]);
      const effectKey = item.kind === 'three' || item.kind === 'seven'
        ? item.kind
        : item.variant === 0 ? 'v02' : item.variant === 1 ? 'v03' : 'v05';
      let emissive: Sprite | null = null;
      if (itemState === 'working') {
        emissive = Sprite.from(EMISSIVE_ASSET_BY_KIND[effectKey].alias);
        emissive.anchor.set(asset.anchor[0], asset.anchor[1]);
        emissive.width = asset.logicalWidth;
        emissive.height = asset.logicalWidth * emissive.texture.height / emissive.texture.width;
        emissive.x = item.anchor.x;
        emissive.y = item.anchor.y;
        emissive.alpha = 0.1;
        this.emissiveLayer.addChild(emissive);
      }
      this.effectItems.push({
        item,
        asset,
        sprite,
        groundShadow: shadows.ground,
        shadow: shadows.receiver,
        emissive,
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
    if (!this.cameraInitialized) {
      this.lastVisibleCount = this.effectItems.length;
      return;
    }
    const scale = this.world.scale.x;
    const safe = this.safeViewport();
    const margin = 42;
    let visibleCount = 0;
    for (const effect of this.effectItems) {
      const left = this.world.x + (effect.item.anchor.x - effect.asset.anchor[0] * effect.width) * scale;
      const top = this.world.y + (effect.item.anchor.y - effect.asset.anchor[1] * effect.height) * scale;
      const right = left + effect.width * scale;
      const bottom = top + effect.height * scale;
      const visible = right >= safe.left - margin
        && left <= safe.right + margin
        && bottom >= safe.top - margin
        && top <= safe.bottom + margin;
      effect.visible = visible;
      effect.sprite.visible = visible;
      if (effect.groundShadow) effect.groundShadow.visible = visible;
      if (effect.shadow) effect.shadow.visible = visible;
      if (effect.emissive) effect.emissive.visible = visible;
      if (visible) visibleCount += 1;
    }
    this.lastVisibleCount = visibleCount;
  }

  private animateEffects(timeMs: number): void {
    this.waterLayer.clear();
    const seconds = timeMs / 1000;
    for (const [index, effect] of this.effectItems.entries()) {
      if (!effect.visible) continue;
      if (effect.emissive) {
        const pulse = 0.09 + (Math.sin(seconds * 0.72 + index * 0.43) + 1) * 0.055;
        effect.emissive.alpha = Math.min(0.28, pulse * this.animationIntensity);
      }
      const kind = effect.item.kind === 'three' || effect.item.kind === 'seven'
        ? effect.item.kind
        : effect.item.variant === 0 ? 'v02' : effect.item.variant === 1 ? 'v03' : 'v05';
      const surfaces = WATER_SURFACES[kind];
      if (!surfaces || !effect.emissive) continue;
      const left = effect.item.anchor.x - effect.asset.anchor[0] * effect.width;
      const top = effect.item.anchor.y - effect.asset.anchor[1] * effect.height;
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
    this.waterLayer.visible = enabled;
    if (enabled) this.animateEffects(performance.now());
    else this.waterLayer.clear();
    this.updateHud();
  }

  private buildSelected(variant: BuildingVariant): void {
    if (this.buildings.has(this.selectedCell)) return;
    this.variants.set(this.selectedCell, variant);
    this.buildings.set(this.selectedCell, 'construction');
    this.rebuildBuildings();
  }

  private completeSelected(): void {
    if (this.buildings.get(this.selectedCell) !== 'construction') return;
    this.buildings.set(this.selectedCell, 'working');
    this.rebuildBuildings();
  }

  private toggleSelectedPower(): void {
    const state = this.buildings.get(this.selectedCell);
    if (state !== 'working' && state !== 'disabled') return;
    this.buildings.set(this.selectedCell, state === 'working' ? 'disabled' : 'working');
    this.rebuildBuildings();
  }

  private demolishSelected(): void {
    this.buildings.delete(this.selectedCell);
    this.variants.delete(this.selectedCell);
    this.rebuildBuildings();
  }

  reset(): void {
    this.buildings.clear();
    this.variants.clear();
    this.seedBuildings();
    this.setDensityQuery(null);
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
    this.rebuildBuildings();
  }

  clear(): void {
    this.buildings.clear();
    this.variants.clear();
    this.setDensityQuery(null);
    this.rebuildBuildings();
  }

  private setDensityQuery(value: 'full' | null): void {
    const url = new URL(window.location.href);
    if (value) url.searchParams.set('density', value);
    else url.searchParams.delete('density');
    window.history.replaceState(null, '', url);
  }

  private setBooleanQuery(name: 'contact-shadow' | 'fusions', enabled: boolean): void {
    const url = new URL(window.location.href);
    if (enabled) url.searchParams.delete(name);
    else url.searchParams.set(name, 'off');
    window.history.replaceState(null, '', url);
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
    hud.className = 'ds-hive-lab-hud';
    hud.innerHTML = `
      <button class="ds-hive-lab-hud__toggle" type="button" data-action="drawer" aria-expanded="true" title="Свернуть инженерную панель">›</button>
      <div class="ds-hive-lab-hud__content">
        <div class="ds-hive-lab-hud__title">Наша инженерная панель</div>
        <div class="ds-hive-lab-hud__subtitle">Жилая сота · 217 клеток</div>
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
            <label><input type="checkbox" data-action="shadows" checked> Падающие тени по силуэту</label>
            <label><input type="checkbox" data-action="contact-shadows" checked> Контактные тени под домами</label>
            <label><input type="checkbox" data-action="fusions" checked> Объединять дома 3 → 1 и 7 → 1</label>
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
            <div class="ds-hive-lab-hud__build" data-role="build-menu">
              <button type="button" data-build="0">Башенный дом</button>
              <button type="button" data-build="1">Купольный дом</button>
              <button type="button" data-build="2">Овальный дом</button>
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

        <div class="ds-hive-lab-hud__help">Колесо меняет масштаб относительно курсора. Потяни пустую поверхность, чтобы переместить камеру.</div>
        <div class="ds-hive-lab-hud__actions">
          <button type="button" data-action="fill-all">Заполнить 217</button>
          <button type="button" data-action="reset">Исходная застройка</button>
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
    hud.querySelector('[data-action="overview"]')?.addEventListener('click', () => this.showOverview());
    hud.querySelector('[data-action="focus-selected"]')?.addEventListener('click', () => this.focusSelectedCell(true));
    hud.querySelector<HTMLInputElement>('[data-action="dome"]')?.addEventListener('change', (event) => {
      this.domeLayer.visible = (event.currentTarget as HTMLInputElement).checked;
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
    const terrainCode = this.terrain[hovered];
    const existingFusionCount = fusionCount ?? this.lastFusionCount;
    metrics.textContent = [
      `FPS ${fps}`,
      `клеток 217`,
      `домов ${working}`,
      `строится ${construction}`,
      `отключено ${disabled}`,
      `комплексов ${existingFusionCount}`,
      `визуальных объектов ${visualCount ?? this.lastVisualCount}`,
      `клетка ${hovered}: ${TERRAIN_NAMES[terrainCode] ?? terrainCode}`,
    ].join('  ·  ');

    const loadMetrics = this.hud.querySelector<HTMLElement>('[data-role="load-metrics"]');
    if (loadMetrics) {
      const shadowTextureBytes = [...this.shadowTextureCache.values()]
        .reduce((sum, texture) => sum + Math.round(texture.width) * Math.round(texture.height) * 4, 0);
      const waterSurfaceCount = this.effectItems.reduce((sum, effect) => {
        if (!effect.emissive) return sum;
        const kind = effect.item.kind === 'three' || effect.item.kind === 'seven'
          ? effect.item.kind
          : effect.item.variant === 0 ? 'v02' : effect.item.variant === 1 ? 'v03' : 'v05';
        return sum + (WATER_SURFACES[kind]?.length ?? 0);
      }, 0);
      const visibleWaterSurfaceCount = this.effectItems.reduce((sum, effect) => {
        if (!effect.emissive || !effect.visible) return sum;
        const kind = effect.item.kind === 'three' || effect.item.kind === 'seven'
          ? effect.item.kind
          : effect.item.variant === 0 ? 'v02' : effect.item.variant === 1 ? 'v03' : 'v05';
        return sum + (WATER_SURFACES[kind]?.length ?? 0);
      }, 0);
      const frameMs = fps > 0 ? 1000 / fps : 0;
      const toMiB = (bytes: number) => (bytes / 1024 / 1024).toFixed(2).replace('.', ',');
      loadMetrics.innerHTML = `
        <div><b>${working + construction + disabled}</b> занятых клеток · <b>${visualCount ?? this.lastVisualCount}</b> визуальных зданий · <b>${this.lastVisibleCount}</b> в кадре</div>
        <div>Объединение <b>${this.fusionsEnabled ? 'включено' : 'выключено'}</b> · контактная тень <b>${this.contactShadowsEnabled ? 'включена' : 'выключена'}</b></div>
        <div><b>${visibleWaterSurfaceCount}</b> из ${waterSurfaceCount} водных поверхностей в кадре · анимация ${this.animationEnabled ? 'включена' : 'выключена'}</div>
        <div><b>${fps || '—'} FPS</b> · ${frameMs ? `${frameMs.toFixed(1).replace('.', ',')} мс/кадр` : 'сбор данных'}</div>
        <div>PNG <b>${toMiB(RUNTIME_PNG_BYTES)} МиБ</b> · текстуры сцены ≈ <b>${toMiB(this.runtimeTextureBytes + shadowTextureBytes)} МиБ</b></div>
        <small>Оценка RGBA в памяти, без кадровых буферов браузера.</small>`;
    }

    const selectedTerrain = TERRAIN_NAMES[this.terrain[this.selectedCell]] ?? this.terrain[this.selectedCell];
    const state = this.buildings.get(this.selectedCell);
    const title = this.hud.querySelector<HTMLElement>('[data-role="cell-title"]');
    const stateText = this.hud.querySelector<HTMLElement>('[data-role="cell-state"]');
    const buildMenu = this.hud.querySelector<HTMLElement>('[data-role="build-menu"]');
    const constructionMenu = this.hud.querySelector<HTMLElement>('[data-role="construction-menu"]');
    const buildingMenu = this.hud.querySelector<HTMLElement>('[data-role="building-menu"]');
    const powerButton = this.hud.querySelector<HTMLButtonElement>('[data-action="power"]');
    if (title) title.textContent = `Клетка ${this.selectedCell} · ${selectedTerrain}`;
    if (stateText) {
      stateText.textContent = !state
        ? 'Свободная клетка. Выбери одноклеточный дом.'
        : state === 'construction'
          ? 'Строительство начато. Следующая команда завершит его.'
          : state === 'disabled'
            ? 'Здание заморожено и не участвует в объединении.'
            : 'Здание работает. Соседние одинаковые уровни объединяются автоматически.';
    }
    if (buildMenu) buildMenu.hidden = state !== undefined;
    if (constructionMenu) constructionMenu.hidden = state !== 'construction';
    if (buildingMenu) buildingMenu.hidden = state !== 'working' && state !== 'disabled';
    if (powerButton) powerButton.textContent = state === 'disabled' ? 'Включить здание' : 'Отключить здание';
  }

  private cameraBounds(): { x: number; y: number; width: number; height: number } {
    const size = mapPixelSize();
    return { x: -36, y: -175, width: size.width + 72, height: size.height + 220 };
  }

  private safeViewport(): { left: number; top: number; right: number; bottom: number; width: number; height: number } {
    const screenWidth = this.app.screen.width;
    const screenHeight = this.app.screen.height;
    const left = screenWidth >= 560 ? 128 : 0;
    const requestedRight = this.drawerOpen ? (screenWidth <= 900 ? 316 : 350) : 0;
    const rightInset = Math.min(requestedRight, Math.max(0, screenWidth - left - 150));
    const top = Math.min(94, screenHeight * 0.2);
    const bottomInset = Math.min(60, screenHeight * 0.16);
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
    return Math.min(safe.width / bounds.width, safe.height / bounds.height) * 0.94;
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
    const anchor = buildingAnchor(this.cells[this.selectedCell]);
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

  private showOverview(): void {
    if (this.drawerOpen) this.setDrawerOpen(false);
    const bounds = this.cameraBounds();
    const safe = this.safeViewport();
    const scale = this.overviewCameraScale();
    this.cameraInitialized = true;
    this.world.scale.set(scale);
    this.world.x = safe.left + (safe.width - bounds.width * scale) / 2 - bounds.x * scale;
    this.world.y = safe.top + (safe.height - bounds.height * scale) / 2 - bounds.y * scale;
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
    this.focusSelectedCell();
  }

  destroy(): void {
    this.app.renderer.off('resize', this.resizeHandler);
    this.app.canvas.removeEventListener('wheel', this.wheelHandler);
    this.clearGeneratedShadows(true);
    this.hud.remove();
    this.app.destroy(true, { children: true });
  }
}
