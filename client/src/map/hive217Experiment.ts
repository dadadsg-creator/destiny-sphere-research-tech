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

type BuildingState = 'construction' | 'working';
type FusionKind = 'three' | 'seven';

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

const MAP_ASSETS = {
  ground: { alias: 'hive-lab/ground', src: '/assets/hive-lab/hive-ground.png' },
  v02: { alias: 'hive-lab/v02', src: '/assets/hive-lab/house-v02.png', logicalWidth: 56, anchor: [0.5, 0.954128] as const },
  v03: { alias: 'hive-lab/v03', src: '/assets/hive-lab/house-v03.png', logicalWidth: 54, anchor: [0.5, 0.839867] as const },
  v05: { alias: 'hive-lab/v05', src: '/assets/hive-lab/house-v05.png', logicalWidth: 52, anchor: [0.5, 0.840701] as const },
  three: { alias: 'hive-lab/complex-3', src: '/assets/hive-lab/complex-3.png', logicalWidth: 92, anchor: [0.5, 0.901585] as const },
  seven: { alias: 'hive-lab/complex-7', src: '/assets/hive-lab/complex-7.png', logicalWidth: 126, anchor: [0.5, 0.920767] as const },
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

  private readonly neighbors = makeNeighborTable(this.cells);
  private readonly buildingLayer = new Container();
  private readonly shadowLayer = new Container();
  private readonly hoverLayer = new Graphics();
  private readonly selectionLayer = new Graphics();
  private readonly domeLayer = new Graphics();
  private readonly interactiveLayer = new Graphics();
  private readonly hud: HTMLDivElement;
  private selectedCell = 108;
  private hoveredCell: number | null = null;
  private fpsSamples: number[] = [];
  private lastHudUpdate = 0;
  private lastFusionCount = 0;
  private lastVisualCount = 0;
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
    this.world.addChild(this.shadowLayer, this.buildingLayer, this.domeLayer, this.hoverLayer, this.selectionLayer, this.interactiveLayer);
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
      }
    }
    const center = 108;
    this.buildings.set(center, 'working');
    for (const neighbor of this.neighbors.get(center) ?? []) this.buildings.set(neighbor, 'working');
    for (const cellNum of [65, 66, 78, 139, 151, 152]) this.buildings.set(cellNum, 'working');
    for (const cellNum of [34, 173]) this.buildings.set(cellNum, 'construction');
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
      const state = this.buildings.get(cellNum);
      if (!state) this.buildings.set(cellNum, 'construction');
      else if (state === 'construction') this.buildings.set(cellNum, 'working');
      else this.buildings.delete(cellNum);
      this.selectionLayer.clear();
      this.drawCellMarker(this.selectionLayer, cellNum, 0xffd777, 0.92, 2);
      this.rebuildBuildings();
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
        variant: cellNum % 3,
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
      }
      this.buildingLayer.addChild(sprite);

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
    this.updateHud(fusions.length, items.length);
  }

  private createHud(parent: HTMLElement): HTMLDivElement {
    const hud = document.createElement('div');
    hud.className = 'ds-hive-lab-hud';
    hud.innerHTML = `
      <div class="ds-hive-lab-hud__title">Сота 217 · технологический эксперимент</div>
      <div class="ds-hive-lab-hud__metrics" data-role="metrics"></div>
      <div class="ds-hive-lab-hud__help">Клик: стройка → готово → снести. Сетка видна только при наведении.</div>
      <div class="ds-hive-lab-hud__actions">
        <button type="button" data-action="reset">Исходная застройка</button>
        <button type="button" data-action="clear">Очистить</button>
      </div>`;
    parent.appendChild(hud);
    hud.querySelector('[data-action="reset"]')?.addEventListener('click', () => {
      this.buildings.clear();
      this.seedBuildings();
      this.rebuildBuildings();
    });
    hud.querySelector('[data-action="clear"]')?.addEventListener('click', () => {
      this.buildings.clear();
      this.rebuildBuildings();
    });
    return hud;
  }

  private updateHud(fusionCount?: number, visualCount?: number): void {
    const metrics = this.hud.querySelector<HTMLElement>('[data-role="metrics"]');
    if (!metrics) return;
    const working = [...this.buildings.values()].filter((state) => state === 'working').length;
    const construction = this.buildings.size - working;
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
      `комплексов ${existingFusionCount}`,
      `визуальных объектов ${visualCount ?? this.lastVisualCount}`,
      `клетка ${hovered}: ${TERRAIN_NAMES[terrainCode] ?? terrainCode}`,
    ].join('  ·  ');
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
