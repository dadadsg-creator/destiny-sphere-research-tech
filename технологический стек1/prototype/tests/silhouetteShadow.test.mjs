import test from 'node:test';
import assert from 'node:assert/strict';
import { extractSilhouetteShadowSegments, resolveScreenShadowProjection } from '../src/silhouetteShadow.mjs';

function alphaMask(width, height, columns) {
  const alpha = new Uint8ClampedArray(width * height);
  for (const [x, top, bottom] of columns) {
    for (let y = top; y <= bottom; y += 1) alpha[y * width + x] = 255;
  }
  return alpha;
}

test('silhouette columns stay attached to the footprint and project upper-left', () => {
  const width = 8;
  const height = 10;
  const segments = extractSilhouetteShadowSegments({
    alpha: alphaMask(width, height, [[3, 2, 8], [4, 4, 8]]),
    width,
    height,
    anchorY: 0.9,
  });
  assert.equal(segments.length, 2);
  assert.deepEqual(segments.map(({ start }) => start), [[3, 8], [4, 8]]);
  assert.ok(segments.every(({ start, end }) => end[0] < start[0] && end[1] < start[1]));
});

test('a taller silhouette column produces a longer shadow ray', () => {
  const width = 8;
  const height = 10;
  const segments = extractSilhouetteShadowSegments({
    alpha: alphaMask(width, height, [[3, 1, 8], [4, 5, 8]]),
    width,
    height,
    anchorY: 0.9,
  });
  const lengths = segments.map(({ start, end }) => Math.hypot(end[0] - start[0], end[1] - start[1]));
  assert.ok(lengths[0] > lengths[1]);
});

test('screen projection converts star elevation and isometric compression into ray length', () => {
  const projection = resolveScreenShadowProjection({
    azimuthDegrees: 220,
    elevationDegrees: 33,
    compression: 0.84,
  });
  assert.ok(projection.direction.x < 0 && projection.direction.y < 0);
  assert.ok(Math.abs(projection.lengthPerScreenHeight - 1.2935) < 0.001);
});
