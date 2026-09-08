/**
 * Базовый PixiJS-рендерер карты/соты.
 *
 * Координаты — портированы формула-в-формулу из sota.as, см. hexGrid.ts
 * и docs/client-map-and-grid.md (основной репозиторий документации).
 */
import { Application, Container, Sprite, Assets } from 'pixi.js';
import { buildHexGrid, buildingAnchor, mapPixelSize } from './hexGrid';
import { parseGroundString, GROUND_LINKAGE } from './groundTypes';
import { BUILDING_NAMES, buildingAssetKey } from './buildingTypes';
import type { BuildRecord } from './buildData';

export class MapRenderer {
  readonly app: Application;
  readonly worldLayer: Container;
  readonly groundLayer: Container;
  readonly buildLayer: Container;

  /**
   * Клик по ячейке соты (аналог onRelease на building/ground-спрайте в
   * sota.as) — вешается на грунтовый слой (он есть всегда и на каждую
   * ячейку целиком, в отличие от спрайта постройки, который не
   * перекрывает всю ячейку) — см. план «Сшивание», 2026-09-08.
   * `hasBuilding` вычисляется по последнему `renderBuildings()`.
   */
  onCellClick: ((num: number, hasBuilding: boolean) => void) | null = null;
  private occupiedCells = new Set<number>();

  private constructor(app: Application) {
    this.app = app;
    this.worldLayer = new Container();
    this.groundLayer = new Container();
    this.buildLayer = new Container();
    this.worldLayer.addChild(this.groundLayer);
    this.worldLayer.addChild(this.buildLayer);
    this.app.stage.addChild(this.worldLayer);
  }

  static async create(parent: HTMLElement): Promise<MapRenderer> {
    const app = new Application();
    await app.init({
      resizeTo: parent,
      backgroundColor: 0x040d23, // тот же фон, что в ds/index.php (см. Фаза 1, живой тест)
      antialias: true,
    });
    parent.appendChild(app.canvas);
    return new MapRenderer(app);
  }

  /**
   * Отрисовать грунт соты по 217-символьной строке <sota> (см.
   * docs/client-map-and-grid.md §4.2). Текстуры — public/assets/ground/*.png
   * (скопированы из client-assets/ds3/, авторитетного источника — grounds.swf
   * грузится тем же именем, но реально не используется, см. Фазу 2 плана).
   */
  async renderGround(sotaString: string): Promise<void> {
    const names = parseGroundString(sotaString);
    const uniqueNames = [...new Set(Object.values(GROUND_LINKAGE))];
    await Assets.load(uniqueNames.map((n) => ({ alias: n, src: `/assets/ground/${n}.png` })));

    const cells = buildHexGrid();
    for (const cell of cells) {
      const groundName = names[cell.num];
      const sprite = Sprite.from(groundName);
      sprite.x = cell.x;
      sprite.y = cell.y;
      // Тайл шире ячейки (48×26 при h=24,v=13) — тайлы перекрываются по
      // краям намеренно, как в оригинале (полосы засветки на стыках).
      sprite.width = 48;
      sprite.height = 26;
      sprite.eventMode = 'static';
      sprite.cursor = 'pointer';
      const num = cell.num;
      sprite.on('pointertap', () => this.onCellClick?.(num, this.occupiedCells.has(num)));
      this.groundLayer.addChild(sprite);
    }

    const size = mapPixelSize();
    this.worldLayer.x = (this.app.screen.width - size.width) / 2;
    this.worldLayer.y = (this.app.screen.height - size.height) / 2;
  }

  /**
   * Отрисовать постройки по данным <build> (см. buildData.ts,
   * docs/client-map-and-grid.md §4.3). Якорь — нижняя вершина ячейки
   * (buildingAnchor из hexGrid.ts), спрайт центрирован по низу
   * (anchor 0.5/1) — **предположение**: извлечённые FFDec PNG-кадры не
   * хранят исходную registration point movie clip'а из .fla, точное
   * визуальное совпадение с Flash-клиентом не подтверждено покадрово.
   * id_building=0 (NO_BUILDING) и нераспознанные id пропускаются.
   */
  async renderBuildings(records: BuildRecord[]): Promise<void> {
    this.buildLayer.removeChildren();
    this.occupiedCells = new Set(
      records.filter((r) => (BUILDING_NAMES[r.id] ?? 'NO_BUILDING') !== 'NO_BUILDING').map((r) => r.num),
    );

    const cells = buildHexGrid();
    const toLoad = new Map<string, string>();
    for (const record of records) {
      const buildingname = BUILDING_NAMES[record.id];
      if (!buildingname || buildingname === 'NO_BUILDING') continue;
      const key = buildingAssetKey(buildingname);
      toLoad.set(key, `/assets/buildings/${key}.png`);
    }
    await Assets.load([...toLoad.entries()].map(([alias, src]) => ({ alias, src })));

    for (const record of records) {
      const buildingname = BUILDING_NAMES[record.id];
      if (!buildingname || buildingname === 'NO_BUILDING') continue;
      const cell = cells[record.num];
      if (!cell) {
        console.warn('[renderBuildings] неизвестный num ячейки:', record.num);
        continue;
      }

      const key = buildingAssetKey(buildingname);
      const sprite = Sprite.from(key);
      sprite.anchor.set(0.5, 1);
      const anchor = buildingAnchor(cell);
      sprite.x = anchor.x;
      sprite.y = anchor.y;
      this.buildLayer.addChild(sprite);
    }
  }

  destroy(): void {
    this.app.destroy(true, { children: true });
  }
}
