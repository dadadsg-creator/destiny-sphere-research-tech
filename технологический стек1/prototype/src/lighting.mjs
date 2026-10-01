export const SUN_ELEVATION_DEGREES = 33;
export const SHADOW_SCREEN_DIRECTION = Object.freeze({ x: -0.7660444431, y: -0.6427876097 });

function normalize(vector) {
  const length = Math.hypot(vector.x, vector.y) || 1;
  return { x: vector.x / length, y: vector.y / length };
}

export function buildGroundShadowProjection({
  elevationDegrees = SUN_ELEVATION_DEGREES,
  screenDirection = SHADOW_SCREEN_DIRECTION,
  lengthMultiplier = 1,
  widthMultiplier = 1,
} = {}) {
  const direction = normalize(screenDirection);
  const elevationRadians = elevationDegrees * Math.PI / 180;
  const lengthPerHeight = lengthMultiplier / Math.tan(elevationRadians);
  return Object.freeze({
    direction: Object.freeze(direction),
    lengthPerHeight,
    // Artwork rises above a bottom anchor, so local y is negative. These
    // coefficients send higher pixels farther along the ground-shadow ray.
    matrix: Object.freeze([
      widthMultiplier,
      0,
      -direction.x * lengthPerHeight,
      -direction.y * lengthPerHeight,
      0,
      0,
    ]),
  });
}

export function projectedShadowTip(height, options) {
  const projection = buildGroundShadowProjection(options);
  return Object.freeze({
    x: projection.matrix[2] * -height,
    y: projection.matrix[3] * -height,
  });
}

export function buildGroundShadowFootprint({
  anchor = [0, 0],
  width,
  height,
  widthMultiplier = 1,
  lengthScale = 0.72,
  tipWidthMultiplier = 0.58,
  ...projectionOptions
}) {
  const projection = buildGroundShadowProjection(projectionOptions);
  const direction = projection.direction;
  const perpendicular = { x: -direction.y, y: direction.x };
  const baseWidth = width * widthMultiplier;
  const tipWidth = baseWidth * tipWidthMultiplier;
  const length = height * projection.lengthPerHeight * lengthScale;
  const tip = [anchor[0] + direction.x * length, anchor[1] + direction.y * length];
  const point = (center, halfWidth, sign) => [
    center[0] + perpendicular.x * halfWidth * sign,
    center[1] + perpendicular.y * halfWidth * sign,
  ];
  return Object.freeze({
    baseWidth,
    tipWidth,
    length,
    points: Object.freeze([
      Object.freeze(point(anchor, baseWidth * 0.5, -1)),
      Object.freeze(point(anchor, baseWidth * 0.5, 1)),
      Object.freeze(point(tip, tipWidth * 0.5, 1)),
      Object.freeze(point(tip, tipWidth * 0.5, -1)),
    ]),
  });
}

export function resolveGroundCastHeightRatio(occupiedCellCount) {
  if (occupiedCellCount >= 7) return 0.55;
  if (occupiedCellCount >= 3) return 0.64;
  return 1;
}

export function canReceiveProjectedShadow(caster, receiver, options) {
  if (!caster || !receiver || caster === receiver) return false;
  const projection = buildGroundShadowProjection(options);
  const dx = receiver.anchor[0] - caster.anchor[0];
  const dy = receiver.anchor[1] - caster.anchor[1];
  const alongRay = dx * projection.direction.x + dy * projection.direction.y;
  const distanceFromRay = Math.abs(
    dx * projection.direction.y - dy * projection.direction.x,
  );
  const maximumDistance = caster.height * projection.lengthPerHeight + receiver.width * 0.5;
  const corridorWidth = caster.width * 0.48 + receiver.width * 0.35;
  return alongRay > 0 && alongRay <= maximumDistance && distanceFromRay <= corridorWidth;
}
