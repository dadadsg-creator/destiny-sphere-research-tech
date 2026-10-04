import { HEX_POINTS } from './geometry.mjs';
import {
  extractSilhouetteShadowSegments,
  resolveSilhouetteStrokeWidth,
} from './silhouetteShadow.mjs?v=20261005-1';

const ASSET_BASE = '../../assets/tech/environment/exports/';
const CENTER_X = 24;
const CENTER_Y = 13;
const SURFACE_VARIANT = 'sandstone';
const GROUND_FILE = 'voraner-sandy-hive-background-v01.png';

const DEFINITIONS = Object.freeze({
  arch: Object.freeze({ file: 'voraner-ruin-arch-v02-color.png', width: 54 }),
  pylon: Object.freeze({ file: 'voraner-ruin-pylon-v02-color.png', width: 56 }),
  conduit: Object.freeze({ file: 'voraner-ruin-conduit-v02-color.png', width: 54 }),
});

export const RESIDENTIAL_ENVIRONMENT_ASSET_FILES = Object.freeze(
  Object.values(DEFINITIONS).map(({ file }) => file),
);
export const RESIDENTIAL_GROUND_ASSET_FILE = GROUND_FILE;

const RUIN_ASSIGNMENTS = Object.freeze([
  Object.freeze([20, 'arch']),
  Object.freeze([29, 'pylon']),
  Object.freeze([35, 'conduit']),
]);

const sprites = new Map();
let groundSprite = null;
const ruinShadowCache = new WeakMap();

function requestRender() {
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new Event('residential-environment-assets-ready'));
  }
}

if (typeof Image !== 'undefined') {
  groundSprite = new Image();
  groundSprite.decoding = 'async';
  groundSprite.addEventListener('load', requestRender, { once: true });
  groundSprite.src = new URL(`${ASSET_BASE}${GROUND_FILE}?v=20261005-1`, import.meta.url).href;

  Object.entries(DEFINITIONS).forEach(([key, definition]) => {
    const image = new Image();
    image.decoding = 'async';
    image.addEventListener('load', requestRender, { once: true });
    image.src = new URL(`${ASSET_BASE}${definition.file}?v=20261004-2`, import.meta.url).href;
    sprites.set(key, image);
  });
}

function appendHex(ctx, cell) {
  HEX_POINTS.forEach(([x, y], index) => {
    if (index === 0) ctx.moveTo(cell.x + x, cell.y + y);
    else ctx.lineTo(cell.x + x, cell.y + y);
  });
  ctx.closePath();
}

function ruinDescriptor(cellByNum, [cellNum, kind]) {
  const cell = cellByNum.get(cellNum);
  const definition = DEFINITIONS[kind];
  const image = sprites.get(kind);
  if (!cell || !definition || !image?.complete || image.naturalWidth === 0) return null;
  const width = definition.width;
  const height = width * (image.naturalHeight / image.naturalWidth);
  return Object.freeze({
    cellNum,
    kind,
    image,
    width,
    height,
    anchorX: cell.x + CENTER_X,
    anchorY: cell.y + 27,
  });
}

export function buildResidentialEnvironmentPlan(cells) {
  const cellByNum = new Map(cells.map((cell) => [cell.num, cell]));
  const structures = cells.map((cell) => Object.freeze({
    cellNum: cell.num,
    cell,
    variant: SURFACE_VARIANT,
  }));
  const ruins = RUIN_ASSIGNMENTS
    .map((assignment) => ruinDescriptor(cellByNum, assignment))
    .filter(Boolean)
    .sort((left, right) => left.anchorY - right.anchorY || left.cellNum - right.cellNum);
  return Object.freeze({
    structures: Object.freeze(structures),
    channels: Object.freeze([]),
    ruins: Object.freeze(ruins),
  });
}

export function drawResidentialEnvironmentGround(ctx, plan) {
  if (plan.structures.length === 0) return;
  const cells = plan.structures.map(({ cell }) => cell);
  const minX = Math.min(...cells.map((cell) => cell.x));
  const minY = Math.min(...cells.map((cell) => cell.y));
  const maxX = Math.max(...cells.map((cell) => cell.x + 48));
  const maxY = Math.max(...cells.map((cell) => cell.y + 26));
  const mapWidth = maxX - minX;
  const mapHeight = maxY - minY;
  if (groundSprite?.complete && groundSprite.naturalWidth > 0) {
    const aspect = groundSprite.naturalWidth / groundSprite.naturalHeight;
    let width = mapWidth + 26;
    let height = width / aspect;
    if (height < mapHeight + 32) {
      height = mapHeight + 32;
      width = height * aspect;
    }
    ctx.drawImage(
      groundSprite,
      (minX + maxX - width) / 2,
      (minY + maxY - height) / 2 + 1,
      width,
      height,
    );
    return;
  }

  // Flat fallback while the authored background is loading.
  ctx.save();
  ctx.beginPath();
  cells.forEach((cell) => appendHex(ctx, cell));
  ctx.clip();
  ctx.fillStyle = '#a9875d';
  ctx.fillRect(minX, minY, mapWidth, mapHeight);
  ctx.restore();
}

export function drawResidentialEnvironmentRuins(ctx, plan) {
  ctx.save();
  plan.ruins.forEach((descriptor) => {
    ctx.drawImage(
      descriptor.image,
      descriptor.anchorX - descriptor.width / 2,
      descriptor.anchorY - descriptor.height,
      descriptor.width,
      descriptor.height,
    );
  });
  ctx.restore();
}

function ruinShadowFor(image, tuning, logicalWidth) {
  if (!image?.complete || image.naturalWidth === 0 || !tuning) return null;
  let byTuning = ruinShadowCache.get(image);
  if (!byTuning) {
    byTuning = new Map();
    ruinShadowCache.set(image, byTuning);
  }
  const cacheKey = [
    tuning.azimuth,
    tuning.elevation,
    tuning.compression,
    tuning.alphaThreshold,
    tuning.columnStep,
    tuning.rayWidth,
    tuning.shadowJoin,
    logicalWidth,
    tuning.baselineOffset,
  ].join(':');
  if (byTuning.has(cacheKey)) return byTuning.get(cacheKey);

  const source = document.createElement('canvas');
  source.width = image.naturalWidth;
  source.height = image.naturalHeight;
  const sourceCtx = source.getContext('2d', { willReadFrequently: true });
  sourceCtx.drawImage(image, 0, 0);
  const rgba = sourceCtx.getImageData(0, 0, source.width, source.height).data;
  const alpha = new Uint8ClampedArray(source.width * source.height);
  for (let index = 0; index < alpha.length; index += 1) alpha[index] = rgba[index * 4 + 3];

  const segments = extractSilhouetteShadowSegments({
    alpha,
    width: source.width,
    height: source.height,
    anchorY: 0.98 + tuning.baselineOffset,
    threshold: tuning.alphaThreshold,
    direction: tuning.direction,
    lengthPerScreenHeight: tuning.lengthPerScreenHeight,
    sampleStep: tuning.columnStep,
  });
  const shadow = document.createElement('canvas');
  shadow.width = source.width;
  shadow.height = source.height;
  const shadowCtx = shadow.getContext('2d');
  shadowCtx.strokeStyle = 'rgba(0, 0, 0, 0.88)';
  shadowCtx.lineWidth = resolveSilhouetteStrokeWidth({
    rayWidth: tuning.rayWidth,
    joinRadius: tuning.shadowJoin,
    sourceWidth: source.width,
    logicalWidth,
  });
  shadowCtx.lineCap = tuning.shadowJoin > 0 ? 'round' : 'butt';
  shadowCtx.lineJoin = 'round';
  shadowCtx.beginPath();
  segments.forEach(({ start, end }) => {
    shadowCtx.moveTo(start[0], start[1]);
    shadowCtx.lineTo(end[0], end[1]);
  });
  shadowCtx.stroke();
  byTuning.set(cacheKey, shadow);
  return shadow;
}

export function drawResidentialEnvironmentShadows(ctx, plan, tuning) {
  if (!tuning) return;
  ctx.save();
  ctx.globalCompositeOperation = 'multiply';
  ctx.globalAlpha = tuning.mathOpacity;
  ctx.filter = tuning.blur > 0 ? `blur(${tuning.blur}px)` : 'none';
  plan.ruins.forEach((descriptor) => {
    const shadow = ruinShadowFor(descriptor.image, tuning, descriptor.width);
    if (!shadow) return;
    ctx.drawImage(
      shadow,
      descriptor.anchorX - descriptor.width / 2,
      descriptor.anchorY - descriptor.height,
      descriptor.width,
      descriptor.height,
    );
  });
  ctx.restore();
}

export const RESIDENTIAL_ENVIRONMENT_CELLS = Object.freeze({
  ruins: Object.freeze(RUIN_ASSIGNMENTS.map(([cellNum]) => cellNum)),
});
