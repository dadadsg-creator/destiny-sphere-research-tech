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
    this.groundLayer = new Container();
    this.buildLayer = new Container();
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
