import test from 'node:test';
import assert from 'node:assert/strict';
import {
  BUILDING_ANCHOR,
  HEX_POINTS,
  PORTS,
  TOTAL_CELLS,
  buildHexGrid,
  nineteenCellCluster,
  rotateMask,
  sevenCellCluster,
  sixtyOneCellCluster,
} from '../src/geometry.mjs';

test('home grid contains exactly 217 cells with stable indexes', () => {
  const grid = buildHexGrid();
  assert.equal(TOTAL_CELLS, 217);
  assert.equal(grid.length, 217);
  assert.deepEqual(grid.map((cell) => cell.num), Array.from({ length: 217 }, (_, index) => index));
});

test('building anchor is the lower vertex of the hexagon', () => {
  assert.deepEqual(BUILDING_ANCHOR, [24, 26]);
  assert.deepEqual(HEX_POINTS[3], BUILDING_ANCHOR);
});

test('ports are midpoints of the six edges', () => {
  PORTS.forEach((port, index) => {
    const a = HEX_POINTS[index];
    const b = HEX_POINTS[(index + 1) % HEX_POINTS.length];
    assert.deepEqual(port.point, [(a[0] + b[0]) / 2, (a[1] + b[1]) / 2]);
  });
});

test('seven-cell cluster contains one center and six unique neighbors', () => {
  const cluster = sevenCellCluster(100, 200);
  assert.equal(cluster.length, 7);
  assert.equal(new Set(cluster.map(({ x, y }) => `${x},${y}`)).size, 7);
  assert.deepEqual(cluster[0], { id: 0, num: 0, name: 'CENTER', x: 100, y: 200 });
});

test('nineteen-cell art district preserves the central seven-cell cluster', () => {
  const district = nineteenCellCluster(100, 200);
  assert.equal(district.length, 19);
  assert.equal(new Set(district.map(({ x, y }) => `${x},${y}`)).size, 19);
  assert.deepEqual(district.slice(0, 7), sevenCellCluster(100, 200));
});

test('sixty-one-cell environment preserves the central art district', () => {
  const environment = sixtyOneCellCluster(100, 200);
  assert.equal(environment.length, 61);
  assert.equal(new Set(environment.map(({ x, y }) => `${x},${y}`)).size, 61);
  assert.deepEqual(environment.slice(0, 19), nineteenCellCluster(100, 200));
});

test('six rotations restore an adjacency mask', () => {
  const mask = 0b100101;
  let rotated = mask;
  for (let step = 0; step < 6; step += 1) rotated = rotateMask(rotated);
  assert.equal(rotated, mask);
});
