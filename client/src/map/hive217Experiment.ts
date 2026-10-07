import {
  Application,
  Assets,
  Container,
  FederatedPointerEvent,
  Graphics,
  Rectangle,
  Sprite,
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
  emissive: Sprite | null;
  width: number;
  height: number;
}

const MAP_ASSETS = {
  ground: { alias: 'hive-lab/ground', src: '/assets/hive-lab/hive-ground.png' },
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
  private animationEnabled = true;
  private animationIntensity = 1.6;
  private lastEffectUpdate = 0;
  private readonly resizeHandler = () => this.fitToViewport();

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
    await Assets.load(Object.values(MAP_ASSETS).map(({ alias, src }) => ({ alias, src })));
    this.app.stage.addChild(this.world);
    this.drawPlatform();
    this.drawSharedGround();
    this.drawDome();
    this.world.addChild(
      this.shadowLayer,
      this.buildingLayer,
      this.waterLayer,
      this.emissiveLayer,
      this.domeLayer,
      this.hoverLayer,
      this.selectionLayer,
      this.interactiveLayer,
    );
    this.seedBuildings();
    this.bindInteraction();
    this.rebuildBuildings();
    this.drawCellMarker(this.selectionLayer, this.selectedCell, 0xffd777, 0.92, 2);
    this.fitToViewport();
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

  private bindInteraction(): void {
    const size = mapPixelSize();
    this.interactiveLayer.rect(0, 0, size.width, size.height).fill({ color: 0xffffff, alpha: 0.001 });
    this.interactiveLayer.eventMode = 'static';
    this.interactiveLayer.cursor = 'pointer';
    this.interactiveLayer.hitArea = new Rectangle(0, 0, size.width, size.height);
    this.interactiveLayer.on('pointermove', (event: FederatedPointerEvent) => {
      const point = this.world.toLocal(event.global);
      const hit = this.hitCell(point.x, point.y);
      if (hit === this.hoveredCell) return;
      this.hoveredCell = hit;
      this.hoverLayer.clear();
      if (hit !== null) this.drawCellMarker(this.hoverLayer, hit, TERRAIN_COLORS[this.terrain[hit]] ?? 0x7fdcff, 0.34, 1.25);
      this.updateHud();
    });
    this.interactiveLayer.on('pointerout', () => {
      this.hoveredCell = null;
      this.hoverLayer.clear();
      this.updateHud();
    });
    this.interactiveLayer.on('pointertap', (event: FederatedPointerEvent) => {
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

  private rebuildBuildings(): void {
    this.shadowLayer.removeChildren().forEach((child) => child.destroy());
    this.buildingLayer.removeChildren().forEach((child) => child.destroy());
    this.emissiveLayer.removeChildren().forEach((child) => child.destroy());
    this.waterLayer.clear();
    this.effectItems = [];
    const working = new Set([...this.buildings].filter(([, state]) => state === 'working').map(([cellNum]) => cellNum));
    const fusions = resolveFusions(working, this.cells, this.neighbors);
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
      const shadowWidth = asset.logicalWidth * (item.kind === 'single' || item.kind === 'construction' ? 0.78 : 0.9);
      const shadow = new Graphics()
        .ellipse(item.anchor.x - shadowWidth * 0.13, item.anchor.y - 2.5, shadowWidth * 0.52, Math.max(3.6, shadowWidth * 0.075))
        .fill({ color: 0x07101a, alpha: item.kind === 'construction' ? 0.22 : 0.42 });
      this.shadowLayer.addChild(shadow);

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
        emissive,
        width: sprite.width,
        height: sprite.height,
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
    if (this.animationEnabled) this.animateEffects(performance.now());
    this.updateHud(fusions.length, items.length);
  }

  private animateEffects(timeMs: number): void {
    this.waterLayer.clear();
    const seconds = timeMs / 1000;
    for (const [index, effect] of this.effectItems.entries()) {
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
    this.rebuildBuildings();
  }

  clear(): void {
    this.buildings.clear();
    this.variants.clear();
    this.rebuildBuildings();
  }

  private createHud(parent: HTMLElement): HTMLDivElement {
    const hud = document.createElement('div');
    hud.className = 'ds-hive-lab-hud';
    hud.innerHTML = `
      <div class="ds-hive-lab-hud__title">Жилая сота · 217 клеток</div>
      <div class="ds-hive-lab-hud__metrics" data-role="metrics"></div>
      <div class="ds-hive-lab-hud__animation">
        <label><input type="checkbox" data-action="animation" checked> Вода и свечение</label>
        <label class="ds-hive-lab-hud__range">Интенсивность
          <input type="range" min="0.5" max="2.5" step="0.1" value="1.6" data-action="intensity">
          <output data-role="intensity">1,6×</output>
        </label>
      </div>
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
      <div class="ds-hive-lab-hud__help">Выбери клетку. Контур появляется только при наведении.</div>
      <div class="ds-hive-lab-hud__actions">
        <button type="button" data-action="reset">Исходная застройка</button>
        <button type="button" data-action="clear">Очистить соту</button>
      </div>`;
    parent.appendChild(hud);
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

  private fitToViewport(): void {
    const size = mapPixelSize();
    const bounds = { x: -36, y: -175, width: size.width + 72, height: size.height + 220 };
    const scale = Math.min(this.app.screen.width / bounds.width, this.app.screen.height / bounds.height) * 0.96;
    this.world.scale.set(scale);
    this.world.x = (this.app.screen.width - size.width * scale) / 2;
    this.world.y = (this.app.screen.height - bounds.height * scale) / 2 - bounds.y * scale;
  }

  destroy(): void {
    this.app.renderer.off('resize', this.resizeHandler);
    this.hud.remove();
    this.app.destroy(true, { children: true });
  }
}
