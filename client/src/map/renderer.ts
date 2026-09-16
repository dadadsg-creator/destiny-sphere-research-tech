/**
 * Базовый PixiJS-рендерер карты/соты.
 *
 * Координаты — портированы формула-в-формулу из sota.as, см. hexGrid.ts
 * и docs/client-map-and-grid.md (основной репозиторий документации).
 */
import { Application, Container, Sprite, Assets, Graphics } from 'pixi.js';
import { buildHexGrid, buildingAnchor, mapPixelSize, hexPolygonPoints, CELL_WEIGHT_H, CELL_WEIGHT_V } from './hexGrid';
import { parseGroundString, GROUND_LINKAGE } from './groundTypes';
import { BUILDING_NAMES, buildingAssetKey, buildingAssetPath, effectiveBuildingRace, type Race } from './buildingTypes';
import type { BuildRecord } from './buildData';

/**
 * Декоративное окружение вокруг домашней соты (владелец, 2026-09-16:
 * "убери синее пустое пространство вокруг соты, продолжи шестиугольную
 * сетку за её границы"). ЧЕТВЁРТАЯ версия — первые три (см. git-историю
 * файла) изобретали геометрию/покрытие сами (то 6 копий с придуманной
 * пёстрой заливкой, то одно математически бесшовное кольцо с
 * придуманными "зонами" central/side/desert) — владелец в итоге прямо
 * спросил: "где у тебя ячейки-разделители, и где они на Флеше?" —
 * справедливо, ни то ни другое не было взято из реального клиента.
 *
 * Реальный источник — `sota.as:286-405` (`createsotasos`), ветка
 * `obj.location == "HOME"` (главный экран): 6 соседей, каждый —
 * ГОТОВАЯ 217-символьная строка грунта (`sota.as:19-21`) на
 * ЗАФИКСИРОВАННОМ пиксельном офсете (`sota.as:379-403`), без какой-либо
 * "бесшовной" геометрии — это в оригинале и есть 6 независимых копий
 * `buildHexGrid()`-формы:
 * - `icount=0` (offset -24,-333, "сверху") → `center_loopback` — смесь
 *   FROG/E_FIELD/SOIL/ROCK (буквы E/F/S/R).
 * - `icount=1,5` (offset -625,-157 / 601,-176, "сверху слева/справа") →
 *   `home_loopback` — ручной узор DESERT/SOIL/GRASS/WATER (D/S/G/W).
 * - `icount=2,3,4` (offset -601,176 / 24,333 / 625,157, "снизу-слева/
 *   снизу/снизу-справа") → `neutral_loopback` — "D"×217, сплошная
 *   пустыня.
 * Раньше здесь пробовали "исправить" геометрию математическим кольцом —
 * это и было ошибкой: (x,y) кольца физически не совпадали с (x,y) этих
 * 6 офсетов (две независимые системы координат), поэтому НИ ОДНА
 * настоящая буква грунта не находила пару, и весь рендер незаметно
 * скатывался в запасной цвет. Теперь ячейки строятся НАПРЯМУЮ из
 * `buildHexGrid()` + офсета — те самые позиции и та самая буква, что и
 * в оригинале, без посредников.
 *
 * Полные 6×217=1302 ячейки текстурами рендерить нельзя (роняло WebGL,
 * см. более раннюю запись плана) — `ENV_MARGIN` обрезает каждую копию
 * до полосы вблизи главной соты (типичный экран всё равно показывает
 * только тонкий срез у самого шва, см. `docs/main_screen.md`).
 */
const ENV_MARGIN = 150;

const HOME_LOOPBACK_NEIGHBORS: { dx: number; dy: number; ground: string[] }[] = [
  {
    dx: -24,
    dy: -333,
    ground: parseGroundString(
      'EFFFFFFFEFEFFFFFFEFFFEFFFFFEFFFFFEFFFFEFFFFFFFEFFFEFFFFFFFFFEFFEFFFFFFFFFFFEFEFFFFFFFFFFFFFEEFFFFFFFEEEEEEEEEEEEEEEEEFFFFFFFEEFFFFFFFFFFFFFESEFFFFFFFFFFFESREFFFFFFFFFERSSEFFFFFFFESSSREFFFFFESRRSSEFFFESRRRSSEFESSRRSRRE',
    ),
  },
  {
    dx: -625,
    dy: -157,
    ground: parseGroundString(
      'DDDDDSSSSDDDSSSDDDDDDDSSSDDDDDDDDDSSDDDGGGSDDDDSSDGGGGGSDDDGGSDGGGGGGSSSSGGSGGGWWWSGSSSSGGSGGGWWWSSSSSSGGGGSGWWWWSSSSSSGGGGSSWWWWSSSSSSGWWWWWWWWGSSSSSDWWWSSGGWGSSSSWWWSSGGWGGSSSSSWSSWWWSSSSSSWWWWWSSGSWWWWSSSSSSSSSSSSS',
    ),
  },
  { dx: -601, dy: 176, ground: parseGroundString('D'.repeat(217)) },
  { dx: 24, dy: 333, ground: parseGroundString('D'.repeat(217)) },
  { dx: 625, dy: 157, ground: parseGroundString('D'.repeat(217)) },
  {
    dx: 601,
    dy: -176,
    ground: parseGroundString(
      'DDDDDSSSSDDDSSSDDDDDDDSSSDDDDDDDDDSSDDDGGGSDDDDSSDGGGGGSDDDGGSDGGGGGGSSSSGGSGGGWWWSGSSSSGGSGGGWWWSSSSSSGGGGSGWWWWSSSSSSGGGGSSWWWWSSSSSSGWWWWWWWWGSSSSSDWWWSSGGWGSSSSWWWSSGGWGGSSSSSWSSWWWSSSSSSWWWWWSSGSWWWWSSSSSSSSSSSSS',
    ),
  },
];

interface EnvCell {
  x: number;
  y: number;
  ground: string;
  /** Индекс соседа 0-5 (порядок `HOME_LOOPBACK_NEIGHBORS`/`icount` из sota.as) — для границ, см. renderEnvironment(). */
  neighbor: number;
}

/** Настоящие ячейки 6 соседей (sota.as), обрезанные полосой ENV_MARGIN вокруг главной соты — см. докстринг выше. */
function buildEnvironmentCells(): EnvCell[] {
  const width = mapPixelSize().width;
  const height = mapPixelSize().height;
  const mainCells = buildHexGrid();
  const cells: EnvCell[] = [];
  HOME_LOOPBACK_NEIGHBORS.forEach((n, neighbor) => {
    for (const cell of mainCells) {
      const x = cell.x + n.dx;
      const y = cell.y + n.dy;
      if (x < -ENV_MARGIN || x > width + ENV_MARGIN || y < -ENV_MARGIN || y > height + ENV_MARGIN) continue;
      cells.push({ x, y, ground: n.ground[cell.num], neighbor });
    }
  });
  return cells;
}

function cellKey(x: number, y: number): string {
  return `${Math.round(x)},${Math.round(y)}`;
}

/**
 * Смещения 6 соседей ячейки, в том же порядке, что рёбра
 * `hexPolygonPoints()` (ребро i — между вершинами i и i+1, направлено
 * ровно к соседу с тем же индексом ниже) — владелец, 2026-09-16: "между
 * ячейками вообще не должно быть границ, только между сотами". Поэтому
 * граница рисуется НЕ по всему контуру ячейки, а только по тем рёбрам,
 * где сосед принадлежит ДРУГОЙ сотe (или это главная сота, или там
 * вообще ничего нет) — см. `renderEnvironment()`.
 */
const NEIGHBOR_OFFSETS: { dx: number; dy: number }[] = [
  { dx: CELL_WEIGHT_H, dy: -1.5 * CELL_WEIGHT_V },
  { dx: 2 * CELL_WEIGHT_H, dy: 0 },
  { dx: CELL_WEIGHT_H, dy: 1.5 * CELL_WEIGHT_V },
  { dx: -CELL_WEIGHT_H, dy: 1.5 * CELL_WEIGHT_V },
  { dx: -2 * CELL_WEIGHT_H, dy: 0 },
  { dx: -CELL_WEIGHT_H, dy: -1.5 * CELL_WEIGHT_V },
];

export class MapRenderer {
  readonly app: Application;
  readonly worldLayer: Container;
  /** Декоративное окружение (см. ENV_NEIGHBORS) — под groundLayer/buildLayer, не кликабельно. */
  readonly envLayer: Container;
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
  /**
   * Спрайт грунта по `num` — начиная с добавления гекс-маски (2026-09-10,
   * см. `hexPolygonPoints()`) `groundLayer.children[num]` больше НЕ
   * указывает на нужный спрайт (на каждую ячейку теперь 2 узла — маска +
   * спрайт, оба сидят в `groundLayer`, `children[num]` съезжает на
   * произвольный узел другой ячейки). Найдено живым тестом — скрипты
   * тестирования (`scripts/live-map-click.ts` и т.п.) читали `children[num]`
   * напрямую и молча кликали не туда. Этот массив — единственный надёжный
   * способ найти "экранную позицию ячейки num" снаружи рендерера.
   */
  readonly groundSprites: Sprite[] = [];
  /**
   * Последний известный `BuildRecord` по `num` ячейки — снапшот на
   * момент последнего `renderBuildings()`. Нужен панели выбранного
   * здания (`ui/hud/BuildingPanel.tsx`, docs/main_screen.md §9): имя,
   * картинка, состояние и процент готовности уже есть в этих данных
   * ДО ответа `getbuildinfo` — не нужно ждать сервер, чтобы показать
   * базовые поля панели сразу по клику.
   */
  private buildRecords = new Map<number, BuildRecord>();

  getBuildRecord(num: number): BuildRecord | undefined {
    return this.buildRecords.get(num);
  }

  /**
   * Отступы "безопасной зоны" карты — вокруг неё стоит непрозрачный HUD
   * (docs/main_screen.md §8: "нужно задать безопасную область карты с
   * учётом верхней, нижней и правой панели"). Раньше `fitToViewport()`
   * вписывал соту в ВЕСЬ канвас — верхушка центральных ворот и нижний
   * край карты оказывались ПОД непрозрачными верхней/нижней панелями
   * (сам канвас на весь экран, но не весь экран реально виден игроку).
   *
   * ПЕРЕСМОТРЕНО (2026-09-16, владелец: "сота стала слишком маленькой,
   * справа/снизу много пустого места"): правая панель здания БОЛЬШЕ НЕ
   * резервируется в этих отступах — по прямому указанию владельца
   * ("правый край может частично заходить под область правой панели,
   * пока панель закрыта… при открытии панели масштаб менять не нужно").
   * Раз панель — уже непрозрачная плавающая накладка поверх канваса
   * (не часть безопасной зоны рендера), а не часть слоя, который сам
   * канвас обязан обходить, открытие/закрытие панели просто накладывает
   * её поверх карты, ничего не пересчитывая (`fitToViewport()` не
   * вызывается ни при открытии, ни при закрытии панели — только на
   * `renderGround`/`renderBuildings`/resize/`setSafeAreaInsets`).
   * Нижний отступ подобран под конкретное требование "нижний край соты
   * примерно на 20px выше нижней панели" (60px высота панели + 20px
   * зазор). Значения синхронны с `ui/hud/hud.css` вручную (как и
   * везде в этом порте, единый источник совпадения — комментарий, не
   * импорт).
   */
  private safeInsets = { top: 0, bottom: 0, left: 0, right: 0 };

  setSafeAreaInsets(insets: Partial<{ top: number; bottom: number; left: number; right: number }>): void {
    this.safeInsets = { ...this.safeInsets, ...insets };
    this.fitToViewport();
  }

  private selectionGraphics: Graphics | null = null;

  /**
   * Подсветка выбранной ЯЧЕЙКИ (docs/main_screen.md §4/§9). Владелец
   * уточнил (2026-09-16): "сейчас подсвечивается только изображение
   * здания, лучше добавить тонкую голубую рамку вокруг клетки" — рамка
   * по факту уже рисовалась и раньше, но толстая (4px) заливка+тинт
   * спрайта визуально сливались в одно "пятно вокруг картинки", а не
   * читались как отдельная рамка именно ГЕКСА. Сделано тоньше (2px) и
   * тинт спрайта убран — рамка сама по себе однозначно читается как
   * контур ячейки, без конкурирующего эффекта на здании поверх нее.
   * Вызывается из liveApp.ts реактивным эффектом на `selectedBuilding`-
   * сигнал — этот метод сам ничего не знает о HUD/сигналах, чистая
   * функция рендерера.
   */
  setSelectedCell(num: number | null): void {
    this.selectionGraphics?.destroy();
    this.selectionGraphics = null;
    if (num === null) return;

    const cell = buildHexGrid()[num];
    if (!cell) return;
    const g = new Graphics().poly(hexPolygonPoints()).stroke({ width: 2, color: 0x28b8ff, alpha: 1 });
    g.x = cell.x;
    g.y = cell.y;
    this.worldLayer.addChild(g);
    this.selectionGraphics = g;
  }

  private constructor(app: Application) {
    this.app = app;
    this.worldLayer = new Container();
    this.envLayer = new Container();
    this.groundLayer = new Container();
    this.buildLayer = new Container();
    // Владелец: "окружающие клетки не должны быть кликабельными, все
    // события мыши должны проходить только к основной соте и интерфейсу"
    // — отключает hit-testing для всего поддерева разом, а не по спрайту.
    this.envLayer.eventMode = 'none';
    // envLayer — первым, чтобы быть визуально ПОД groundLayer/buildLayer
    // ("окружение... не должно закрывать её здания").
    this.worldLayer.addChild(this.envLayer);
    this.worldLayer.addChild(this.groundLayer);
    this.worldLayer.addChild(this.buildLayer);
    this.app.stage.addChild(this.worldLayer);
    // Пересчёт масштаба при изменении размера окна, не только на
    // следующий ответ сервера (`resizeTo: parent` в create() уже меняет
    // сам канвас — этот колбэк держит содержимое отцентрированным под
    // новый размер сразу, см. fitToViewport()).
    this.app.renderer.on('resize', () => this.fitToViewport());
  }

  static async create(parent: HTMLElement): Promise<MapRenderer> {
    const app = new Application();
    await app.init({
      resizeTo: parent,
      backgroundColor: 0x040d23, // тот же фон, что в ds/index.php (см. Фаза 1, живой тест)
      antialias: true,
      // Плотность пикселей экрана (docs/main_screen.md §16, "избегать
      // дробного масштаба... учитывать плотность пикселей") — без этого
      // на Retina/HiDPI-экранах канвас рендерился в 1 CSS-пиксель = 1
      // физический пиксель, и весь слой (уже отдельно смасштабированный
      // fitToViewport()) дополнительно "размазывался" браузерным
      // апскейлом канваса. `autoDensity` держит CSS-размер канваса
      // прежним, реальное разрешение буфера — умноженное на
      // devicePixelRatio (максимум 2 — выше нет смысла, только память).
      resolution: Math.min(window.devicePixelRatio || 1, 2),
      autoDensity: true,
      // Округление позиций спрайтов до целого пикселя — вторая часть
      // той же жалобы ("некоторые здания выглядят размытыми"):
      // fitToViewport() задаёт произвольный дробный scale, из-за чего
      // экранные координаты спрайтов почти никогда не целые — линейная
      // фильтрация текстур размывает такие полупиксельные позиции.
      roundPixels: true,
    });
    parent.appendChild(app.canvas);
    const renderer = new MapRenderer(app);
    await renderer.renderEnvironment();
    return renderer;
  }

  /**
   * Рисует декоративное окружение (buildEnvironmentCells, настоящие
   * данные `sota.as`) один раз при создании рендерера — статичный фон,
   * не зависит от ответов сервера, поэтому не пересоздаётся на каждый
   * `renderGround()` (в отличие от groundLayer).
   */
  private async renderEnvironment(): Promise<void> {
    const hexPoints = hexPolygonPoints();
    const cells = buildEnvironmentCells();

    // Настоящие текстуры грунта — тот же приём, что и у основной сетки
    // (`renderGround()`): Sprite + гекс-маска на ячейку. БЕЗ обводки
    // здесь — контур ячейки сам по себе не граница, см. ниже.
    await Assets.load([
      { alias: 'ROCK', src: '/assets/ground/ROCK.png' },
      { alias: 'SOIL', src: '/assets/ground/SOIL.png' },
      { alias: 'DESERT', src: '/assets/ground/DESERT.png' },
      { alias: 'GRASS', src: '/assets/ground/GRASS.png' },
      { alias: 'WATER', src: '/assets/ground/WATER.png' },
      { alias: 'FROG', src: '/assets/ground/FROG.png' },
      { alias: 'E_FIELD', src: '/assets/ground/E_FIELD.png' },
    ]);
    for (const cell of cells) {
      const mask = new Graphics().poly(hexPoints).fill(0xffffff);
      mask.x = cell.x;
      mask.y = cell.y;
      this.envLayer.addChild(mask);

      const sprite = Sprite.from(cell.ground);
      sprite.x = cell.x;
      sprite.y = cell.y;
      sprite.width = 48;
      sprite.height = 26;
      sprite.mask = mask;
      this.envLayer.addChild(sprite);
    }

    // Границы — ТОЛЬКО между разными сотами (владелец, дословно: "между
    // ячейками вообще не должно быть границ, только между сотами"), не
    // по контуру каждой ячейки и не по типу грунта внутри одной соты
    // (`home_loopback` сам по себе содержит D/S/G/W вперемешку — это не
    // повод рисовать линию). Регион ячейки — индекс её настоящего соседа
    // (0-5, `icount` из sota.as) или главная сота; для каждой ячейки
    // проверяем все 6 соседних позиций: ребро обводится, только если
    // сосед — другая сота (другой индекс/главная сота) или вне охвата
    // вообще.
    const mainKeys = new Set(buildHexGrid().map((c) => cellKey(c.x, c.y)));
    const regionByKey = new Map(cells.map((c) => [cellKey(c.x, c.y), c.neighbor]));
    const regionOf = (x: number, y: number): number | 'main' | null => {
      const key = cellKey(x, y);
      if (mainKeys.has(key)) return 'main';
      return regionByKey.get(key) ?? null;
    };

    const border = new Graphics();
    for (const cell of cells) {
      for (let e = 0; e < 6; e++) {
        const nb = NEIGHBOR_OFFSETS[e];
        const neighborRegion = regionOf(cell.x + nb.dx, cell.y + nb.dy);
        if (neighborRegion === null || neighborRegion === cell.neighbor) continue;
        const p1 = e * 2;
        const p2 = ((e + 1) % 6) * 2;
        border
          .moveTo(hexPoints[p1] + cell.x, hexPoints[p1 + 1] + cell.y)
          .lineTo(hexPoints[p2] + cell.x, hexPoints[p2 + 1] + cell.y);
      }
    }
    border.stroke({ width: 1, color: 0x0b1220, alpha: 0.85 });
    this.envLayer.addChild(border);
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
    this.groundSprites.length = 0;
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
      this.groundSprites[num] = sprite;
    }

    this.fitToViewport();
  }

  /**
   * Масштабирует и центрирует `worldLayer` под текущий размер канваса
   * (docs/main_screen.md §8: "сота должна занимать максимальную площадь
   * экрана... карта пересчитывает масштаб, но сохраняет исходную
   * шестиугольную геометрию"). Раньше сота рисовалась в нативном
   * пиксельном размере (scale=1) без учёта размера канваса — при полном
   * `#app` на весь вьюпорт (главный экран) сота оставалась маленькой в
   * центре большого тёмного поля. Масштаб — единый коэффициент по обеим
   * осям (не растягивает пропорции), с небольшим отступом (0.92), чтобы
   * гекс-цветок не упирался краями точно в край канваса. Не трогает САМУ
   * отрисовку (грунт/постройки/гекс-математика) — только позицию/масштаб
   * готового слоя, поэтому не пересекается с "не входит в задачу:
   * отрисовка соты" (§3 ТЗ).
   */
  fitToViewport(): void {
    const size = mapPixelSize();
    const { top, bottom, left, right } = this.safeInsets;
    const availW = Math.max(50, this.app.screen.width - left - right);
    const availH = Math.max(50, this.app.screen.height - top - bottom);
    // 0.97, не 0.92 — небольшой отступ "для воздуха" вместо ощутимого
    // (владелец: "увеличить примерно на 12-15%"), точный зазор снизу
    // (20px) уже заложен прямо в safeInsets.bottom, не в этом множителе.
    const scale = Math.min((availW * 0.97) / size.width, (availH * 0.97) / size.height);
    this.worldLayer.scale.set(scale);
    this.worldLayer.x = left + (availW - size.width * scale) / 2;
    this.worldLayer.y = top + (availH - size.height * scale) / 2;
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
    // Подсветка выбранной ячейки (setSelectedCell) сама по себе живёт
    // отдельным graphics-оверлеем в worldLayer, не в buildLayer —
    // переживает removeChildren() выше без доп. кода, ничего здесь не
    // трогает.
    // ВАЖНО (найдено 2026-09-10 живым кликом — сервер открыл getbuildinfo
    // для ячейки, которую мы считали пустой): "занятость" ячейки для
    // клика НЕ должна зависеть от того, умеем ли мы НАРИСОВАТЬ постройку
    // (BUILDING_NAMES — снятый один раз живой манифест, может не знать
    // id с других миров/новых построек) — иначе клик уходит с неверным
    // табом ("AllowBuildings" вместо "BuildingInfo"). Единственный
    // надёжный признак "пусто" — буквальный id_building=0 (NO_BUILDING).
    this.occupiedCells = new Set(records.filter((r) => r.id !== 0).map((r) => r.num));
    this.buildRecords = new Map(records.map((r) => [r.num, r]));

    const cells = buildHexGrid();
    const toLoad = new Map<string, string>();
    let needsUnactiveIcon = false;
    for (const record of records) {
      const buildingname = BUILDING_NAMES[record.id];
      if (buildingname === undefined) {
        // id_building отсутствует в BUILDING_NAMES (снят живым SQL-запросом
        // к одному конкретному миру, 2026-09-04) — на ДРУГИХ мирах могут
        // быть свои id, которых там не было. Не путать с id=0 (NO_BUILDING,
        // осознанно пустая ячейка) — тут сервер действительно шлёт постройку,
        // мы её просто не умеем нарисовать (не в списке, не на карте).
        console.warn('[renderBuildings] неизвестный id_building (нет в BUILDING_NAMES):', record.id, 'ячейка', record.num);
        continue;
      }
      if (buildingname === 'NO_BUILDING') continue;
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
