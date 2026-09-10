/**
 * Базовый PixiJS-рендерер карты/соты.
 *
 * Координаты — портированы формула-в-формулу из sota.as, см. hexGrid.ts
 * и docs/client-map-and-grid.md (основной репозиторий документации).
 */
import { Application, Container, Sprite, Assets, Graphics } from 'pixi.js';
import { buildHexGrid, buildingAnchor, mapPixelSize, hexPolygonPoints } from './hexGrid';
import { parseGroundString, GROUND_LINKAGE } from './groundTypes';
import { BUILDING_NAMES, buildingAssetKey, buildingAssetPath, effectiveBuildingRace, type Race } from './buildingTypes';
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
   * Bounding box тайла — 48×26 (2h×2v при h=24,v=13), тайлы НЕ
   * перекрываются (это было неверное предположение, см. hexGrid.ts) —
   * каждый тайл обрезается гекс-маской `hexPolygonPoints()`, буквально
   * как в оригинале (`sota.as:417-422`, `setMask()`), иначе соседние
   * прямоугольные текстуры сливаются в сплошной фон без видимой границы
   * ячейки (найдено 2026-09-10 по сравнению с реальным Flash-клиентом).
   */
  async renderGround(sotaString: string): Promise<void> {
    // `refresh` (и поэтому renderGround()) приходит почти на каждый ответ
    // сервера, не только один раз при входе — без явной очистки старые
    // спрайты+маски копились бы бесконечно на повторных вызовах (сама сота
    // ведь редко меняет форму, но перерисовывается заново каждый раз).
    this.groundLayer.removeChildren();
    const names = parseGroundString(sotaString);
    const uniqueNames = [...new Set(Object.values(GROUND_LINKAGE))];
    await Assets.load(uniqueNames.map((n) => ({ alias: n, src: `/assets/ground/${n}.png` })));

    const hexPoints = hexPolygonPoints();
    const cells = buildHexGrid();
    for (const cell of cells) {
      const groundName = names[cell.num];
      const sprite = Sprite.from(groundName);
      sprite.x = cell.x;
      sprite.y = cell.y;
      sprite.width = 48;
      sprite.height = 26;
      sprite.eventMode = 'static';
      sprite.cursor = 'pointer';
      const num = cell.num;
      sprite.on('pointertap', () => this.onCellClick?.(num, this.occupiedCells.has(num)));

      const mask = new Graphics().poly(hexPoints).fill(0xffffff);
      mask.x = cell.x;
      mask.y = cell.y;
      this.groundLayer.addChild(mask);
      sprite.mask = mask;

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
   *
   * `race` — раса ВЛАДЕЛЬЦА соты (не обязательно текущего игрока — на
   * чужой соте это была бы раса того игрока, здесь используется
   * `ctx.race` текущего пользователя, потому что живьём проверялась
   * только своя сота, см. buildingTypes.ts). Один и тот же
   * `buildingname` рисуется СОВЕРШЕННО разной графикой на разных расах
   * (`buildingAssetPath()`) — алиас текстуры тоже расо-специфичный,
   * иначе кэш PixiJS перепутает "RESIDENTIAL_BUILDING" разных рас между
   * сотами/перезагрузками.
   *
   * `status==="4"` (деактивировано, `sota.as:864-868`) — поверх обычного
   * спрайта здания добавляется бейдж "UNACTIVE" (та же иконка во всех
   * трёх расовых библиотеках — байт-в-байт идентична, поэтому взята одна
   * общая копия, `public/assets/buildings/UNACTIVE.png`, а не по расе).
   * Остальные статусы ("1" стройка/"2" активация/"3" деактивация/"5"
   * апгрейд — свои спрайты STROIKA/PROGRESS/ACTIVATION/BUSY +
   * прогресс-бар, `sota.as:830-884`) — НЕ реализованы в этом заходе,
   * это отдельный, более крупный кусок работы (нужны ещё 4-5 ассетов и
   * логика процента строительства), не путать с уже подключённым
   * `record.percent`, который используется только UI-окнами (`BuildInfoWindow`
   * и т.п.), не самой картой.
   */
  async renderBuildings(records: BuildRecord[], race: Race): Promise<void> {
    this.buildLayer.removeChildren();
    this.occupiedCells = new Set(
      records.filter((r) => (BUILDING_NAMES[r.id] ?? 'NO_BUILDING') !== 'NO_BUILDING').map((r) => r.num),
    );

    const cells = buildHexGrid();
    const toLoad = new Map<string, string>();
    let needsUnactiveIcon = false;
    for (const record of records) {
      const buildingname = BUILDING_NAMES[record.id];
      if (!buildingname || buildingname === 'NO_BUILDING') continue;
      const key = buildingAssetKey(buildingname);
      const alias = `${effectiveBuildingRace(buildingname, race)}/${key}`;
      toLoad.set(alias, buildingAssetPath(buildingname, race));
      if (record.status === '4') needsUnactiveIcon = true;
    }
    if (needsUnactiveIcon) toLoad.set('UNACTIVE', '/assets/buildings/UNACTIVE.png');
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
      const alias = `${effectiveBuildingRace(buildingname, race)}/${key}`;
      const sprite = Sprite.from(alias);
      sprite.anchor.set(0.5, 1);
      const anchor = buildingAnchor(cell);
      sprite.x = anchor.x;
      sprite.y = anchor.y;
      this.buildLayer.addChild(sprite);

      if (record.status === '4') {
        const badge = Sprite.from('UNACTIVE');
        badge.anchor.set(0.5, 1);
        badge.x = anchor.x;
        badge.y = anchor.y;
        this.buildLayer.addChild(badge);
      }
    }
  }

  destroy(): void {
    this.app.destroy(true, { children: true });
  }
}
