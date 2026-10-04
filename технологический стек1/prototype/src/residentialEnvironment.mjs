import { HEX_POINTS } from './geometry.mjs';
import { buildNeighborTable } from './complexes.mjs';

const ASSET_BASE = '../../assets/tech/environment/exports/';
const CENTER_X = 24;
const CENTER_Y = 13;
const SURFACE_VARIANTS = Object.freeze(['plain', 'weathered', 'service']);

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

function buildChannelNetwork(cells) {
  if (cells.length === 0) return [];
  const cellByNum = new Map(cells.map((cell) => [cell.num, cell]));
  const neighbors = buildNeighborTable(cells);
  const first = Math.min(...cellByNum.keys());
  const queue = [first];
  const visited = new Set([first]);
  const pairs = [];
  const pairKeys = new Set();
  const addPair = (left, right) => {
    const fromCellNum = Math.min(left, right);
    const toCellNum = Math.max(left, right);
    const key = `${fromCellNum}:${toCellNum}`;
    if (pairKeys.has(key)) return;
    pairKeys.add(key);
    pairs.push([fromCellNum, toCellNum]);
  };

  while (queue.length > 0) {
    const cellNum = queue.shift();
    const adjacent = (neighbors.get(cellNum) ?? [])
      .filter((neighborCellNum) => Number.isInteger(neighborCellNum))
      .sort((left, right) => left - right);
    for (const neighborCellNum of adjacent) {
      if (visited.has(neighborCellNum)) continue;
      visited.add(neighborCellNum);
      queue.push(neighborCellNum);
      addPair(cellNum, neighborCellNum);
    }
  }

  for (const cell of cells) {
    for (const neighborCellNum of neighbors.get(cell.num) ?? []) {
      if (!Number.isInteger(neighborCellNum) || cell.num >= neighborCellNum) continue;
      if ((cell.num * 11 + neighborCellNum * 7) % 19 === 0) addPair(cell.num, neighborCellNum);
    }
  }

  return pairs.map(([fromCellNum, toCellNum]) => {
    const from = cellByNum.get(fromCellNum);
    const to = cellByNum.get(toCellNum);
    return Object.freeze({
      fromCellNum,
      toCellNum,
      from: Object.freeze([from.x + CENTER_X, from.y + CENTER_Y]),
      to: Object.freeze([to.x + CENTER_X, to.y + CENTER_Y]),
    });
  });
}

export function buildResidentialEnvironmentPlan(cells) {
  const cellByNum = new Map(cells.map((cell) => [cell.num, cell]));
  const structures = cells.map((cell) => Object.freeze({
    cellNum: cell.num,
    cell,
    variant: SURFACE_VARIANTS[(cell.num * 7 + 1) % SURFACE_VARIANTS.length],
  }));
  const ruins = RUIN_ASSIGNMENTS
    .map((assignment) => ruinDescriptor(cellByNum, assignment))
    .filter(Boolean)
    .sort((left, right) => left.anchorY - right.anchorY || left.cellNum - right.cellNum);
  return Object.freeze({
    structures: Object.freeze(structures),
    channels: Object.freeze(buildChannelNetwork(cells)),
    ruins: Object.freeze(ruins),
  });
}

export function drawResidentialEnvironmentGround(ctx, plan) {
  if (plan.structures.length === 0) return;
  const cells = plan.structures.map(({ cell }) => cell);
  const minX = Math.min(...cells.map((cell) => cell.x));
  const maxX = Math.max(...cells.map((cell) => cell.x + 48));
  const minY = Math.min(...cells.map((cell) => cell.y));
  const maxY = Math.max(...cells.map((cell) => cell.y + 26));

  ctx.save();
  ctx.beginPath();
  cells.forEach((cell) => appendHex(ctx, cell));
  ctx.clip();

  const stone = ctx.createLinearGradient(0, minY, 0, maxY);
  stone.addColorStop(0, '#b6b4a6');
  stone.addColorStop(0.5, '#9da19a');
  stone.addColorStop(1, '#7e8988');
  ctx.fillStyle = stone;
  ctx.fillRect(minX - 2, minY - 2, maxX - minX + 4, maxY - minY + 4);

  const halfWidth = 12;
  const halfHeight = 3.4;
  let row = 0;
  for (let y = minY - 8; y <= maxY + 8; y += halfHeight * 2) {
    const stagger = row % 2 === 0 ? 0 : halfWidth;
    for (let x = minX - 24 + stagger; x <= maxX + 24; x += halfWidth * 2) {
      ctx.beginPath();
      ctx.moveTo(x, y - halfHeight);
      ctx.lineTo(x + halfWidth, y);
      ctx.lineTo(x, y + halfHeight);
      ctx.lineTo(x - halfWidth, y);
      ctx.closePath();
      ctx.strokeStyle = 'rgba(43, 58, 57, 0.22)';
      ctx.lineWidth = 0.48;
      ctx.stroke();
    }
    row += 1;
  }

  for (const structure of plan.structures) {
    const x = structure.cell.x + CENTER_X;
    const y = structure.cell.y + CENTER_Y;
    if (structure.variant === 'service') {
      ctx.fillStyle = 'rgba(58, 70, 67, 0.38)';
      ctx.strokeStyle = 'rgba(226, 200, 132, 0.52)';
      ctx.lineWidth = 0.55;
      ctx.beginPath();
      ctx.ellipse(x, y, 5.2, 2.3, 0, 0, Math.PI * 2);
      ctx.fill();
      ctx.stroke();
    } else if (structure.variant === 'weathered') {
      ctx.strokeStyle = 'rgba(61, 66, 60, 0.34)';
      ctx.lineWidth = 0.55;
      ctx.beginPath();
      ctx.moveTo(x - 7, y - 1);
      ctx.lineTo(x - 2, y + 1.8);
      ctx.lineTo(x + 2, y - 0.4);
      ctx.lineTo(x + 6, y + 2.2);
      ctx.stroke();
    } else {
      ctx.strokeStyle = 'rgba(244, 228, 190, 0.2)';
      ctx.lineWidth = 0.5;
      ctx.beginPath();
      ctx.ellipse(x, y, 7.4, 3.1, 0, 0, Math.PI * 2);
      ctx.stroke();
    }
  }
  ctx.restore();
}

export function drawResidentialEnvironmentChannels(ctx, plan) {
  ctx.save();
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
  for (const channel of plan.channels) {
    const stroke = (color, width) => {
      ctx.beginPath();
      ctx.moveTo(...channel.from);
      ctx.lineTo(...channel.to);
      ctx.strokeStyle = color;
      ctx.lineWidth = width;
      ctx.stroke();
    };
    stroke('rgba(20, 42, 47, 0.48)', 2.35);
    stroke('rgba(28, 171, 205, 0.4)', 0.95);
    stroke('rgba(159, 236, 244, 0.52)', 0.28);
  }
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

export const RESIDENTIAL_ENVIRONMENT_CELLS = Object.freeze({
  ruins: Object.freeze(RUIN_ASSIGNMENTS.map(([cellNum]) => cellNum)),
});
