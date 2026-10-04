import { HEX_POINTS } from './geometry.mjs';

const ASSET_BASE = '../../assets/tech/environment/exports/';
const CENTER_X = 24;
const CENTER_Y = 13;
const SURFACE_VARIANT = 'sandstone';

const DEFINITIONS = Object.freeze({
  arch: Object.freeze({ file: 'voraner-ruin-arch-v02-color.png', width: 54 }),
  pylon: Object.freeze({ file: 'voraner-ruin-pylon-v02-color.png', width: 56 }),
  conduit: Object.freeze({ file: 'voraner-ruin-conduit-v02-color.png', width: 54 }),
});

export const RESIDENTIAL_ENVIRONMENT_ASSET_FILES = Object.freeze(
  Object.values(DEFINITIONS).map(({ file }) => file),
);

const RUIN_ASSIGNMENTS = Object.freeze([
  Object.freeze([20, 'arch']),
  Object.freeze([29, 'pylon']),
  Object.freeze([35, 'conduit']),
]);

const sprites = new Map();

function hash01(seed) {
  const value = Math.sin(seed * 12.9898 + 78.233) * 43758.5453;
  return value - Math.floor(value);
}

function requestRender() {
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new Event('residential-environment-assets-ready'));
  }
}

if (typeof Image !== 'undefined') {
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

function drawRock(ctx, x, y, width, height, tone) {
  ctx.fillStyle = 'rgba(46, 38, 29, 0.2)';
  ctx.beginPath();
  ctx.ellipse(x - 1.3, y - 0.7, width * 0.58, height * 0.62, -0.12, 0, Math.PI * 2);
  ctx.fill();

  const rock = ctx.createLinearGradient(x - width / 2, y - height / 2, x + width / 2, y + height / 2);
  rock.addColorStop(0, `rgba(${tone - 25}, ${tone - 31}, ${tone - 39}, 0.96)`);
  rock.addColorStop(0.58, `rgba(${tone}, ${tone - 7}, ${tone - 17}, 0.98)`);
  rock.addColorStop(1, `rgba(${tone + 22}, ${tone + 13}, ${tone - 2}, 0.98)`);
  ctx.fillStyle = rock;
  ctx.beginPath();
  ctx.moveTo(x - width * 0.48, y + height * 0.02);
  ctx.lineTo(x - width * 0.2, y - height * 0.47);
  ctx.lineTo(x + width * 0.28, y - height * 0.36);
  ctx.lineTo(x + width * 0.5, y + height * 0.04);
  ctx.lineTo(x + width * 0.13, y + height * 0.46);
  ctx.lineTo(x - width * 0.32, y + height * 0.34);
  ctx.closePath();
  ctx.fill();

  ctx.strokeStyle = 'rgba(255, 232, 184, 0.42)';
  ctx.lineWidth = 0.34;
  ctx.beginPath();
  ctx.moveTo(x - width * 0.18, y - height * 0.37);
  ctx.lineTo(x + width * 0.27, y - height * 0.27);
  ctx.lineTo(x + width * 0.43, y + height * 0.01);
  ctx.stroke();
}

function drawSandstoneCell(ctx, structure) {
  const { cell, cellNum } = structure;
  const centerX = cell.x + CENTER_X;
  const centerY = cell.y + CENTER_Y;

  ctx.save();
  ctx.beginPath();
  appendHex(ctx, cell);
  ctx.clip();

  // Broad sediment bands follow the same 2:1 projection as the buildings.
  ctx.strokeStyle = 'rgba(86, 66, 43, 0.2)';
  ctx.lineWidth = 0.52;
  const bandShift = hash01(cellNum * 19 + 5) * 7;
  ctx.beginPath();
  ctx.moveTo(cell.x - 5, cell.y + 8 + bandShift * 0.25);
  ctx.lineTo(cell.x + 19, cell.y + 14 + bandShift * 0.25);
  ctx.lineTo(cell.x + 48, cell.y + 8 + bandShift * 0.25);
  ctx.moveTo(cell.x + 2, cell.y + 20 - bandShift * 0.2);
  ctx.lineTo(cell.x + 25, cell.y + 14 - bandShift * 0.2);
  ctx.lineTo(cell.x + 52, cell.y + 21 - bandShift * 0.2);
  ctx.stroke();

  ctx.strokeStyle = 'rgba(245, 210, 153, 0.18)';
  ctx.lineWidth = 0.34;
  ctx.beginPath();
  ctx.moveTo(cell.x + 1, cell.y + 6 + bandShift * 0.22);
  ctx.lineTo(cell.x + 20, cell.y + 10.7 + bandShift * 0.22);
  ctx.lineTo(cell.x + 43, cell.y + 6 + bandShift * 0.22);
  ctx.stroke();

  const patchX = cell.x + 8 + hash01(cellNum * 43 + 3) * 29;
  const patchY = cell.y + 8 + hash01(cellNum * 61 + 17) * 10;
  ctx.fillStyle = 'rgba(91, 74, 52, 0.11)';
  ctx.beginPath();
  ctx.ellipse(patchX, patchY, 7 + hash01(cellNum * 13) * 5, 1.6, -0.08, 0, Math.PI * 2);
  ctx.fill();

  const rockCount = 2 + (cellNum % 2);
  for (let index = 0; index < rockCount; index += 1) {
    const seed = cellNum * 101 + index * 29;
    const x = cell.x + 8 + hash01(seed + 1) * 32;
    const y = cell.y + 7 + hash01(seed + 2) * 12;
    const width = 2.4 + hash01(seed + 3) * 3.4;
    const height = 1.3 + hash01(seed + 4) * 1.8;
    const tone = 118 + Math.round(hash01(seed + 5) * 32);
    drawRock(ctx, x, y, width, height, tone);
  }

  ctx.fillStyle = 'rgba(66, 52, 37, 0.38)';
  for (let index = 0; index < 7; index += 1) {
    const seed = cellNum * 173 + index * 41;
    const x = cell.x + 5 + hash01(seed + 7) * 38;
    const y = cell.y + 5 + hash01(seed + 9) * 16;
    const radius = 0.18 + hash01(seed + 11) * 0.34;
    ctx.beginPath();
    ctx.ellipse(x, y, radius * 1.4, radius, 0, 0, Math.PI * 2);
    ctx.fill();
  }

  // A weak common light wash keeps neighbouring cells visually continuous.
  const light = ctx.createLinearGradient(centerX - 20, centerY - 8, centerX + 20, centerY + 8);
  light.addColorStop(0, 'rgba(46, 37, 27, 0.08)');
  light.addColorStop(1, 'rgba(255, 228, 177, 0.08)');
  ctx.fillStyle = light;
  ctx.fillRect(cell.x, cell.y, 48, 26);
  ctx.restore();
}

export function drawResidentialEnvironmentGround(ctx, plan) {
  if (plan.structures.length === 0) return;
  const cells = plan.structures.map(({ cell }) => cell);
  const minX = Math.min(...cells.map((cell) => cell.x));
  const minY = Math.min(...cells.map((cell) => cell.y));
  const maxX = Math.max(...cells.map((cell) => cell.x + 48));
  const maxY = Math.max(...cells.map((cell) => cell.y + 26));
  ctx.save();
  ctx.beginPath();
  cells.forEach((cell) => appendHex(ctx, cell));
  ctx.clip();
  const soil = ctx.createLinearGradient(maxX, minY, minX, maxY);
  soil.addColorStop(0, '#c9ab79');
  soil.addColorStop(0.48, '#aa8b61');
  soil.addColorStop(1, '#816c52');
  ctx.fillStyle = soil;
  ctx.fillRect(minX - 1, minY - 1, maxX - minX + 2, maxY - minY + 2);

  const sunWash = ctx.createRadialGradient(maxX - 18, minY + 8, 2, maxX - 18, minY + 8, maxX - minX);
  sunWash.addColorStop(0, 'rgba(255, 224, 166, 0.22)');
  sunWash.addColorStop(0.48, 'rgba(233, 192, 128, 0.05)');
  sunWash.addColorStop(1, 'rgba(50, 45, 38, 0.12)');
  ctx.fillStyle = sunWash;
  ctx.fillRect(minX - 1, minY - 1, maxX - minX + 2, maxY - minY + 2);
  ctx.restore();

  plan.structures.forEach((structure) => drawSandstoneCell(ctx, structure));
}

export function drawResidentialEnvironmentChannels(ctx, plan) {
  // Intentionally empty. The simplified surface has no channels or roads.
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

export const RESIDENTIAL_ENVIRONMENT_CELLS = Object.freeze({
  ruins: Object.freeze(RUIN_ASSIGNMENTS.map(([cellNum]) => cellNum)),
});
