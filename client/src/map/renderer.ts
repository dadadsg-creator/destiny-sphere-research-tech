/**
 * Базовый PixiJS-рендерер карты/соты.
 *
 * Координаты — портированы формула-в-формулу из sota.as, см. hexGrid.ts
 * и docs/client-map-and-grid.md (основной репозиторий документации).
 */
import { Application, Container, Sprite, Assets } from 'pixi.js';
import { buildHexGrid, mapPixelSize } from './hexGrid';
import { parseGroundString, GROUND_LINKAGE } from './groundTypes';

export class MapRenderer {
  readonly app: Application;
  readonly worldLayer: Container;

  private constructor(app: Application) {
    this.app = app;
    this.worldLayer = new Container();
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
      this.worldLayer.addChild(sprite);
    }

    const size = mapPixelSize();
    this.worldLayer.x = (this.app.screen.width - size.width) / 2;
    this.worldLayer.y = (this.app.screen.height - size.height) / 2;
  }

  destroy(): void {
    this.app.destroy(true, { children: true });
  }
}
