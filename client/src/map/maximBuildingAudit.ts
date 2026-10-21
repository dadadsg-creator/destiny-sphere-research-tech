import './maximBuildingAudit.css';
import { Application, Assets, Container, Graphics, Sprite, Texture } from 'pixi.js';
import { buildHexGrid, buildingAnchor, hexPolygonPoints, mapPixelSize, CELL_WEIGHT_H, CELL_WEIGHT_V } from './hexGrid';
import {
  BUILDING_NAMES,
  buildingAssetKey,
  buildingMapAssetPath,
  effectiveBuildingRace,
  type Race,
} from './buildingTypes';
import buildingMapSizes from './buildingMapSizes.json';
import { publicAssetUrl } from '../assets/publicAssetUrl';

type AuditScope = 'core' | 'all';
type AuditScale = 'current' | 'cell';

interface AuditAsset {
  id: number;
  name: string;
  key: string;
  race: Race;
  alias: string;
  src: string;
  displayWidth: number;
  displayHeight: number;
}

interface AuditItem {
  cellNum: number;
  asset: AuditAsset;
  sprite: Sprite;
  shownWidth: number;
  shownHeight: number;
}

const GROUND_ASSET = {
  alias: 'maxim-audit/ground-hd',
  src: publicAssetUrl('/assets/hive-lab/hive-ground-hd.png'),
};

const CORE_BUILDING_IDS = [
  9, 17, 10, 32, 1, 2, 28, 48,
  29, 26, 7, 22, 19, 23, 25, 39,
];

const CELL_COUNTS = [19, 61, 92, 217] as const;
const DISPLAY_SIZES = buildingMapSizes as unknown as Record<string, [number, number]>;

function uniqueBuildingIds(): number[] {
  const firstIdByKey = new Map<string, number>();
  for (const [rawId, name] of Object.entries(BUILDING_NAMES)) {
    const id = Number(rawId);
    if (!name || name === 'NO_BUILDING' || id >= 92) continue;
    const key = buildingAssetKey(name);
    if (!firstIdByKey.has(key)) firstIdByKey.set(key, id);
  }
  return [...firstIdByKey.values()].sort((a, b) => a - b);
}

const ALL_BUILDING_IDS = uniqueBuildingIds();

function outerHivePolygon(inset = 0): number[] {
  const size = mapPixelSize();
  return [
    216, inset,
    size.width - 216, inset,
    size.width - inset, size.height / 2,
    size.width - 216, size.height - inset,
    216, size.height - inset,
    inset, size.height / 2,
  ];
}

function bytesLabel(bytes: number): string {
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1).replace('.', ',')} КиБ`;
  return `${(bytes / 1024 / 1024).toFixed(2).replace('.', ',')} МиБ`;
}

function raceLabel(race: Race): string {
  if (race === 'tech') return 'Воранеры';
  if (race === 'bio') return 'Лиенсу';
  return 'Псолао';
}

function parseRace(value: string | null): Race {
  return value === 'bio' || value === 'psi' ? value : 'tech';
}

function parseCellCount(value: string | null): number {
  const parsed = Number(value);
  return CELL_COUNTS.includes(parsed as (typeof CELL_COUNTS)[number]) ? parsed : 61;
}

function sortedCentralCells(count: number) {
  const size = mapPixelSize();
  const centerX = size.width / 2;
  const centerY = size.height / 2;
  return [...buildHexGrid()]
    .sort((a, b) => {
      const acx = a.x + CELL_WEIGHT_H - centerX;
      const acy = a.y + CELL_WEIGHT_V - centerY;
      const bcx = b.x + CELL_WEIGHT_H - centerX;
      const bcy = b.y + CELL_WEIGHT_V - centerY;
      const ad = acx * acx + acy * acy * 2.4;
      const bd = bcx * bcx + bcy * bcy * 2.4;
      return ad - bd || a.y - b.y || a.x - b.x;
    })
    .slice(0, count);
}

export class MaximBuildingAudit {
  readonly app: Application;
  readonly world = new Container();
  readonly platformLayer = new Container();
  readonly buildingLayer = new Container();
  readonly markerLayer = new Container();
  readonly interactionLayer = new Container();

  private readonly parent: HTMLElement;
  private readonly hud: HTMLElement;
  private race: Race = 'tech';
  private cellCount = 61;
  private scope: AuditScope = 'core';
  private scaleMode: AuditScale = 'current';
  private zoomPercent = 115;
  private items: AuditItem[] = [];
  private selectedCell = 108;
  private pngBytes = 0;
  private decodedBytes = 0;
  private loadMs = 0;
  private fpsSamples: number[] = [];
  private updateToken = 0;
  private lastMetricsUpdate = 0;

  private constructor(app: Application, parent: HTMLElement) {
    this.app = app;
    this.parent = parent;
    this.hud = this.createHud();
  }

  static async create(parent: HTMLElement): Promise<MaximBuildingAudit> {
    const app = new Application();
    await app.init({
      resizeTo: parent,
      backgroundColor: 0x020915,
      antialias: true,
      autoDensity: true,
      resolution: Math.min(window.devicePixelRatio || 1, 1.5),
      roundPixels: true,
      preference: 'webgl',
    });
    parent.appendChild(app.canvas);
    const audit = new MaximBuildingAudit(app, parent);
    await audit.initialize();
    return audit;
  }

  private async initialize(): Promise<void> {
    const params = new URLSearchParams(location.search);
    this.race = parseRace(params.get('race'));
    this.cellCount = parseCellCount(params.get('cells'));
    this.scope = params.get('scope') === 'all' ? 'all' : 'core';
    this.scaleMode = params.get('scale') === 'cell' ? 'cell' : 'current';
    this.zoomPercent = Math.min(220, Math.max(55, Number(params.get('zoom')) || 115));
    this.syncControls();

    await Assets.load(GROUND_ASSET);
    this.app.stage.addChild(this.world);
    this.world.addChild(this.platformLayer, this.buildingLayer, this.markerLayer, this.interactionLayer);
    this.drawPlatform();
    this.bindControls();
    await this.rebuild();
    this.fitWorld();

    this.app.renderer.on('resize', this.fitWorld);
    this.app.ticker.add((ticker) => {
      this.fpsSamples.push(ticker.FPS);
      if (this.fpsSamples.length > 50) this.fpsSamples.shift();
      if (performance.now() - this.lastMetricsUpdate > 500) {
        this.updateMetrics();
        this.lastMetricsUpdate = performance.now();
      }
    });
  }

  private createHud(): HTMLElement {
    const hud = document.createElement('aside');
    hud.className = 'ds-maxim-audit';
    hud.innerHTML = `
      <a class="ds-maxim-audit__back" href="http://127.0.0.1:4174/prototype/labs.html">← Все полигоны</a>
      <p class="ds-maxim-audit__eyebrow">СРАВНЕНИЕ ГРАФИКИ</p>
      <h1>Домики Максима на нашей соте</h1>
      <p class="ds-maxim-audit__intro">Исходные PNG не меняются. Стенд сравнивает текущую посадку клиента и нормализацию до ширины одной клетки.</p>
      <p class="ds-maxim-audit__verdict"><strong>Решение:</strong> набор отклонён как художественная основа. Экран сохранён для проверки совместимости, размеров и производительности.</p>
      <label>Раса
        <select data-control="race">
          <option value="tech">Воранеры</option>
          <option value="bio">Лиенсу</option>
          <option value="psi">Псолао</option>
        </select>
      </label>
      <label>Занято клеток
        <select data-control="cells">
          <option value="19">19 · крупный квартал</option>
          <option value="61">61 · наш старый полигон</option>
          <option value="92">92 · экономическая ёмкость</option>
          <option value="217">217 · предельная нагрузка</option>
        </select>
      </label>
      <label>Набор зданий
        <select data-control="scope">
          <option value="core">16 основных типов</option>
          <option value="all">Все доступные типы</option>
        </select>
      </label>
      <label>Посадка
        <select data-control="scale">
          <option value="current">Точные размеры текущего клиента</option>
          <option value="cell">Нормализация до ширины клетки</option>
        </select>
      </label>
      <label class="ds-maxim-audit__range">Масштаб сцены <output data-output="zoom">115%</output>
        <input data-control="zoom" type="range" min="55" max="220" step="5" value="115" />
      </label>
      <section class="ds-maxim-audit__metrics" data-section="metrics"></section>
      <section class="ds-maxim-audit__selection" data-section="selection">
        <h2>Выбранное здание</h2>
        <p>Нажми на занятую клетку.</p>
      </section>
      <details>
        <summary>Что проверяем</summary>
        <ul>
          <li>масштаб и заполнение клетки;</li>
          <li>нижний anchor после обрезки прозрачных полей;</li>
          <li>единство перспективы и направления света;</li>
          <li>число уникальных текстур и реальную память;</li>
          <li>стоимость повторения спрайтов на 19–217 клетках.</li>
        </ul>
      </details>
    `;
    this.parent.appendChild(hud);
    return hud;
  }

  private syncControls(): void {
    this.hud.querySelector<HTMLSelectElement>('[data-control="race"]')!.value = this.race;
    this.hud.querySelector<HTMLSelectElement>('[data-control="cells"]')!.value = String(this.cellCount);
    this.hud.querySelector<HTMLSelectElement>('[data-control="scope"]')!.value = this.scope;
    this.hud.querySelector<HTMLSelectElement>('[data-control="scale"]')!.value = this.scaleMode;
    this.hud.querySelector<HTMLInputElement>('[data-control="zoom"]')!.value = String(this.zoomPercent);
    this.hud.querySelector<HTMLOutputElement>('[data-output="zoom"]')!.value = `${this.zoomPercent}%`;
  }

  private bindControls(): void {
    const changeAndRebuild = async () => {
      this.race = parseRace(this.hud.querySelector<HTMLSelectElement>('[data-control="race"]')!.value);
      this.cellCount = parseCellCount(this.hud.querySelector<HTMLSelectElement>('[data-control="cells"]')!.value);
      this.scope = this.hud.querySelector<HTMLSelectElement>('[data-control="scope"]')!.value === 'all' ? 'all' : 'core';
      this.scaleMode = this.hud.querySelector<HTMLSelectElement>('[data-control="scale"]')!.value === 'cell' ? 'cell' : 'current';
      this.writeUrl();
      await this.rebuild();
    };
    for (const name of ['race', 'cells', 'scope', 'scale']) {
      this.hud.querySelector(`[data-control="${name}"]`)?.addEventListener('change', changeAndRebuild);
    }
    this.hud.querySelector<HTMLInputElement>('[data-control="zoom"]')!.addEventListener('input', (event) => {
      this.zoomPercent = Number((event.currentTarget as HTMLInputElement).value);
      this.hud.querySelector<HTMLOutputElement>('[data-output="zoom"]')!.value = `${this.zoomPercent}%`;
      this.writeUrl();
      this.fitWorld();
    });
  }

  private writeUrl(): void {
    const params = new URLSearchParams(location.search);
    params.set('screen', 'maxim-buildings');
    params.set('race', this.race);
    params.set('cells', String(this.cellCount));
    params.set('scope', this.scope);
    params.set('scale', this.scaleMode);
    params.set('zoom', String(this.zoomPercent));
    history.replaceState(null, '', `${location.pathname}?${params.toString()}`);
  }

  private drawPlatform(): void {
    const size = mapPixelSize();
    const base = new Graphics()
      .poly([-26, size.height / 2, 198, -22, size.width - 198, -22, size.width + 26, size.height / 2, size.width - 198, size.height + 22, 198, size.height + 22])
      .fill({ color: 0x101a28, alpha: 1 })
      .stroke({ width: 8, color: 0x5b6d78, alpha: 0.9 });
    this.platformLayer.addChild(base);

    const texture = Assets.get(GROUND_ASSET.alias) as Texture;
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
    this.platformLayer.addChild(ground, mask);
    this.platformLayer.addChild(new Graphics().poly(outerHivePolygon(1)).stroke({ width: 2, color: 0xc8e9e5, alpha: 0.45 }));
  }

  private assetForId(id: number): AuditAsset {
    const name = BUILDING_NAMES[id];
    const key = buildingAssetKey(name);
    const assetRace = effectiveBuildingRace(name, this.race);
    const display = DISPLAY_SIZES[`${assetRace}/${key}`] ?? [48, 48];
    return {
      id,
      name,
      key,
      race: assetRace,
      alias: `maxim-audit/${assetRace}/${key}`,
      src: buildingMapAssetPath(name, this.race),
      displayWidth: display[0],
      displayHeight: display[1],
    };
  }

  private async rebuild(): Promise<void> {
    const token = ++this.updateToken;
    const ids = this.scope === 'all' ? ALL_BUILDING_IDS : CORE_BUILDING_IDS;
    const cells = sortedCentralCells(this.cellCount).sort((a, b) => a.y - b.y || a.x - b.x);
    const assets = new Map<string, AuditAsset>();
    const assignments = cells.map((cell, index) => {
      const asset = this.assetForId(ids[index % ids.length]);
      assets.set(asset.alias, asset);
      return { cell, asset };
    });

    const started = performance.now();
    await Assets.load([...assets.values()].map(({ alias, src }) => ({ alias, src })));
    const sizes = await Promise.all([...assets.values()].map(async (asset) => {
      const response = await fetch(asset.src);
      const blob = await response.blob();
      return blob.size;
    }));
    if (token !== this.updateToken) return;
    this.loadMs = performance.now() - started;
    this.pngBytes = sizes.reduce((sum, size) => sum + size, 0);
    this.decodedBytes = [...assets.keys()].reduce((sum, alias) => {
      const texture = Assets.get(alias) as Texture;
      return sum + Math.round(texture.width) * Math.round(texture.height) * 4;
    }, 0);

    this.buildingLayer.removeChildren();
    this.markerLayer.removeChildren();
    this.interactionLayer.removeChildren();
    this.items = assignments.map(({ cell, asset }) => {
      const sprite = Sprite.from(asset.alias);
      sprite.anchor.set(0.5, 1);
      const aspect = sprite.texture.height / Math.max(1, sprite.texture.width);
      const shownWidth = this.scaleMode === 'cell' ? 48 : asset.displayWidth;
      const shownHeight = this.scaleMode === 'cell' ? shownWidth * aspect : asset.displayHeight;
      sprite.width = shownWidth;
      sprite.height = shownHeight;
      const anchor = buildingAnchor(cell);
      sprite.position.set(anchor.x, anchor.y);
      this.buildingLayer.addChild(sprite);
      return { cellNum: cell.num, asset, sprite, shownWidth, shownHeight };
    });

    for (const item of this.items) {
      const cell = buildHexGrid()[item.cellNum];
      const hit = new Graphics().poly(hexPolygonPoints()).fill({ color: 0xffffff, alpha: 0.001 });
      hit.position.set(cell.x, cell.y);
      hit.eventMode = 'static';
      hit.cursor = 'pointer';
      hit.on('pointertap', () => this.selectCell(item.cellNum));
      this.interactionLayer.addChild(hit);
    }
    if (!this.items.some((item) => item.cellNum === this.selectedCell)) this.selectedCell = this.items[0]?.cellNum ?? 108;
    this.selectCell(this.selectedCell);
    this.updateMetrics();
  }

  private selectCell(cellNum: number): void {
    this.selectedCell = cellNum;
    this.markerLayer.removeChildren();
    const cell = buildHexGrid()[cellNum];
    if (!cell) return;
    const marker = new Graphics().poly(hexPolygonPoints()).stroke({ width: 2, color: 0xffd777, alpha: 0.95 });
    marker.position.set(cell.x, cell.y);
    this.markerLayer.addChild(marker);

    const item = this.items.find((candidate) => candidate.cellNum === cellNum);
    const section = this.hud.querySelector<HTMLElement>('[data-section="selection"]')!;
    if (!item) {
      section.innerHTML = '<h2>Выбранное здание</h2><p>Свободная клетка.</p>';
      return;
    }
    const texture = Assets.get(item.asset.alias) as Texture;
    const widthRatio = item.shownWidth / 48;
    const notes: string[] = [];
    if (item.asset.displayWidth < 32) notes.push('текущая посадка заметно уже клетки');
    if (item.asset.displayHeight > 65) notes.push('высокий силуэт требует проверки перекрытий');
    if (item.asset.displayWidth > 70) notes.push('специальный многоклеточный масштаб');
    if (notes.length === 0) notes.push('габарит близок к одноклеточному диапазону');
    section.innerHTML = `
      <h2>${item.asset.key}</h2>
      <p>${raceLabel(item.asset.race)} · клетка ${cellNum}</p>
      <dl>
        <dt>PNG</dt><dd>${Math.round(texture.width)}×${Math.round(texture.height)} px</dd>
        <dt>Размер клиента</dt><dd>${item.asset.displayWidth.toFixed(1)}×${item.asset.displayHeight.toFixed(1)}</dd>
        <dt>На стенде</dt><dd>${item.shownWidth.toFixed(1)}×${item.shownHeight.toFixed(1)} · ${(widthRatio * 100).toFixed(0)}% клетки</dd>
        <dt>Предварительно</dt><dd>${notes.join('; ')}</dd>
      </dl>
    `;
  }

  private updateMetrics(): void {
    const fps = this.fpsSamples.length
      ? this.fpsSamples.reduce((sum, value) => sum + value, 0) / this.fpsSamples.length
      : 0;
    const unique = new Set(this.items.map((item) => item.asset.alias)).size;
    const widths = this.items.map((item) => item.asset.displayWidth);
    const heights = this.items.map((item) => item.asset.displayHeight);
    const section = this.hud.querySelector<HTMLElement>('[data-section="metrics"]')!;
    section.innerHTML = `
      <h2>Нагрузка сцены</h2>
      <dl>
        <dt>Спрайтов</dt><dd>${this.items.length}</dd>
        <dt>Уникальных текстур</dt><dd>${unique}</dd>
        <dt>PNG уникального набора</dt><dd>${bytesLabel(this.pngBytes)}</dd>
        <dt>Распакованные RGBA</dt><dd>${bytesLabel(this.decodedBytes)}</dd>
        <dt>Загрузка и декодирование</dt><dd>${this.loadMs.toFixed(0)} мс</dd>
        <dt>Кадр</dt><dd>${fps.toFixed(0)} FPS · ${fps > 0 ? (1000 / fps).toFixed(1) : '—'} мс</dd>
        <dt>Исходный игровой габарит</dt><dd>${Math.min(...widths).toFixed(0)}–${Math.max(...widths).toFixed(0)} × ${Math.min(...heights).toFixed(0)}–${Math.max(...heights).toFixed(0)}</dd>
      </dl>
      <p>Повторение одной текстуры на многих клетках не умножает её память.</p>
    `;
  }

  private readonly fitWorld = (): void => {
    const size = mapPixelSize();
    const panelWidth = Math.min(370, Math.max(310, this.hud.getBoundingClientRect().width));
    const availableWidth = Math.max(320, this.app.screen.width - panelWidth - 28);
    const availableHeight = Math.max(260, this.app.screen.height - 36);
    const fit = Math.min(availableWidth / (size.width + 70), availableHeight / (size.height + 90));
    const scale = fit * this.zoomPercent / 100;
    this.world.scale.set(scale);
    this.world.x = 14 + (availableWidth - size.width * scale) / 2;
    this.world.y = 18 + (availableHeight - size.height * scale) / 2;
  };

  destroy(): void {
    this.app.renderer.off('resize', this.fitWorld);
    this.hud.remove();
    this.app.destroy(true, { children: true });
  }
}
