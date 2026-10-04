const ASSET_BASE = '../../assets/tech/environment/exports/';

const DEFINITIONS = Object.freeze({
  intact: Object.freeze({ file: 'voraner-courtyard-intact-color.png', width: 50 }),
  weathered: Object.freeze({ file: 'voraner-courtyard-weathered-color.png', width: 50 }),
  service: Object.freeze({ file: 'voraner-courtyard-service-color.png', width: 50 }),
  arch: Object.freeze({ file: 'voraner-ruin-arch-color.png', width: 56 }),
  pylon: Object.freeze({ file: 'voraner-ruin-pylon-color.png', width: 58 }),
  conduit: Object.freeze({ file: 'voraner-ruin-conduit-color.png', width: 56 }),
});

export const RESIDENTIAL_ENVIRONMENT_ASSET_FILES = Object.freeze(
  Object.values(DEFINITIONS).map(({ file }) => file),
);

const GROUND_ASSIGNMENTS = Object.freeze([
  [19, 'intact'], [20, 'weathered'], [21, 'service'],
  [28, 'service'], [29, 'weathered'], [30, 'intact'],
  [34, 'intact'], [35, 'weathered'], [36, 'service'],
]);

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
    image.src = new URL(`${ASSET_BASE}${definition.file}?v=20261004-1`, import.meta.url).href;
    sprites.set(key, image);
  });
}

function renderDescriptor(cellByNum, [cellNum, kind]) {
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
    anchorX: cell.x + 24,
    anchorY: cell.y + 28,
  });
}

function drawDescriptor(ctx, descriptor) {
  ctx.drawImage(
    descriptor.image,
    descriptor.anchorX - descriptor.width / 2,
    descriptor.anchorY - descriptor.height,
    descriptor.width,
    descriptor.height,
  );
}

export function buildResidentialEnvironmentPlan(cells) {
  const cellByNum = new Map(cells.map((cell) => [cell.num, cell]));
  const ground = GROUND_ASSIGNMENTS
    .map((assignment) => renderDescriptor(cellByNum, assignment))
    .filter(Boolean)
    .sort((left, right) => left.anchorY - right.anchorY || left.cellNum - right.cellNum);
  const ruins = RUIN_ASSIGNMENTS
    .map((assignment) => renderDescriptor(cellByNum, assignment))
    .filter(Boolean)
    .sort((left, right) => left.anchorY - right.anchorY || left.cellNum - right.cellNum);
  return Object.freeze({ ground: Object.freeze(ground), ruins: Object.freeze(ruins) });
}

export function drawResidentialEnvironmentGround(ctx, plan) {
  ctx.save();
  ctx.globalAlpha = 0.92;
  plan.ground.forEach((descriptor) => drawDescriptor(ctx, descriptor));
  ctx.restore();
}

export function drawResidentialEnvironmentRuins(ctx, plan) {
  ctx.save();
  plan.ruins.forEach((descriptor) => drawDescriptor(ctx, descriptor));
  ctx.restore();
}

export const RESIDENTIAL_ENVIRONMENT_CELLS = Object.freeze({
  ground: Object.freeze(GROUND_ASSIGNMENTS.map(([cellNum]) => cellNum)),
  ruins: Object.freeze(RUIN_ASSIGNMENTS.map(([cellNum]) => cellNum)),
});
