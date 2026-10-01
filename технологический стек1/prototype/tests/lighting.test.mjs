import test from 'node:test';
import assert from 'node:assert/strict';
import {
  SUN_ELEVATION_DEGREES,
  buildGroundShadowFootprint,
  buildGroundShadowProjection,
  canReceiveProjectedShadow,
  projectedShadowTip,
  resolveGroundCastHeightRatio,
} from '../src/lighting.mjs';

test('alpha projection sends the top of a building upper-left like the original game', () => {
  const tip = projectedShadowTip(50);
  assert.equal(SUN_ELEVATION_DEGREES, 33);
  assert.ok(tip.x < 0);
  assert.ok(tip.y < 0);
});

test('only a different building inside the projected shadow corridor receives it', () => {
  const caster = { anchor: [100, 100], width: 56, height: 50 };
  const northWest = { anchor: [76, 70], width: 54, height: 48 };
  const southEast = { anchor: [124, 130], width: 54, height: 48 };
  const farSouth = { anchor: [100, 180], width: 54, height: 48 };
  assert.equal(canReceiveProjectedShadow(caster, northWest), true);
  assert.equal(canReceiveProjectedShadow(caster, southEast), false);
  assert.equal(canReceiveProjectedShadow(caster, farSouth), false);
  assert.equal(canReceiveProjectedShadow(caster, caster), false);
});

test('a 33 degree sun produces a shadow about 1.54 times the height', () => {
  const projection = buildGroundShadowProjection();
  assert.ok(Math.abs(projection.lengthPerHeight - 1.5399) < 0.001);
  const tip = projectedShadowTip(50);
  assert.ok(Math.abs(Math.hypot(tip.x, tip.y) - 50 * projection.lengthPerHeight) < 0.001);
});

test('large complexes can cast proportionally longer shadows', () => {
  const single = buildGroundShadowProjection();
  const complex = buildGroundShadowProjection({ lengthMultiplier: 1.5 });
  assert.ok(Math.abs(complex.lengthPerHeight / single.lengthPerHeight - 1.5) < 0.001);
});

test('a complex can widen its shadow without changing its length', () => {
  const single = buildGroundShadowProjection();
  const complex = buildGroundShadowProjection({ widthMultiplier: 1.3 });
  assert.equal(complex.matrix[0], 1.3);
  assert.equal(complex.lengthPerHeight, single.lengthPerHeight);
  assert.equal(complex.matrix[2], single.matrix[2]);
  assert.equal(complex.matrix[3], single.matrix[3]);
});

test('the connected ground footprint uses the full width across the light ray', () => {
  const single = buildGroundShadowFootprint({ width: 56, height: 50 });
  const complex = buildGroundShadowFootprint({ width: 126, height: 90, widthMultiplier: 1.3 });
  assert.equal(single.baseWidth, 56);
  assert.equal(complex.baseWidth, 163.8);
  assert.ok(complex.baseWidth > single.baseWidth * 2.9);
  assert.ok(complex.length > single.length);
});

test('ground-shadow length of a complex follows its perimeter height', () => {
  assert.equal(resolveGroundCastHeightRatio(1), 1);
  assert.equal(resolveGroundCastHeightRatio(3), 0.64);
  assert.equal(resolveGroundCastHeightRatio(7), 0.55);
});
