export const CALIBRATED_SHADOW_DIRECTION = Object.freeze({ x: -0.62, y: -0.78 });
export const CALIBRATED_LENGTH_PER_SCREEN_HEIGHT = 0.7;

function normalize(vector) {
  const length = Math.hypot(vector.x, vector.y) || 1;
  return { x: vector.x / length, y: vector.y / length };
}

export function resolveScreenShadowProjection({ azimuthDegrees, elevationDegrees, compression }) {
  const azimuth = azimuthDegrees * Math.PI / 180;
  const elevation = elevationDegrees * Math.PI / 180;
  return Object.freeze({
    direction: Object.freeze(normalize({ x: Math.cos(azimuth), y: Math.sin(azimuth) })),
    lengthPerScreenHeight: compression / Math.tan(elevation),
  });
}

export function extractSilhouetteShadowSegments({
  alpha,
  width,
  height,
  anchorY,
  threshold = 26,
  direction = CALIBRATED_SHADOW_DIRECTION,
  lengthPerScreenHeight = CALIBRATED_LENGTH_PER_SCREEN_HEIGHT,
  sampleStep = 1,
}) {
  if (!alpha || alpha.length !== width * height) throw new TypeError('Некорректная альфа-маска');
  const ray = normalize(direction);
  const baseline = Math.max(0, Math.min(height - 1, anchorY * height));
  const segments = [];

  for (let x = 0; x < width; x += sampleStep) {
    let top = -1;
    let bottom = -1;
    for (let y = 0; y < height; y += 1) {
      if (alpha[y * width + x] < threshold) continue;
      if (top < 0) top = y;
      bottom = y;
    }
    if (top < 0 || bottom < 0) continue;
    const screenHeight = Math.max(0, baseline - top);
    if (screenHeight <= 0) continue;
    const length = screenHeight * lengthPerScreenHeight;
    const startY = Math.min(bottom, baseline);
    segments.push(Object.freeze({
      start: Object.freeze([x, startY]),
      end: Object.freeze([x + ray.x * length, startY + ray.y * length]),
      screenHeight,
    }));
  }
  return Object.freeze(segments);
}
