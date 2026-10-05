import assert from 'node:assert/strict';
import test from 'node:test';

import { resolveComplexes } from '../src/complexes.mjs';
import { resolveComplexFusions, sortComplexRenderItems } from '../src/complexFusion.mjs';
import { nineteenCellCluster, sevenCellCluster } from '../src/geometry.mjs';

const building = (cellNum) => ({ cellNum, complexGroup: 'residential' });

test('closed triangle fuses three cells into one visual complex', () => {
  const cells = sevenCellCluster();
  const complexes = resolveComplexes([0, 1, 2].map(building), cells);
  const fusions = resolveComplexFusions(complexes, cells);
  assert.equal(fusions.length, 1);
  assert.equal(fusions[0].kind, 'three');
  assert.deepEqual(fusions[0].cellNums, [0, 1, 2]);
});

test('center and six neighbors fuse into one seven-cell quarter', () => {
  const cells = sevenCellCluster();
  const complexes = resolveComplexes(cells.map((cell) => building(cell.num)), cells);
  const fusions = resolveComplexFusions(complexes, cells);
  assert.equal(fusions.length, 1);
  assert.equal(fusions[0].kind, 'seven');
  assert.equal(fusions[0].cellNums.length, 7);
  const lowestAnchorY = Math.max(...cells.map((cell) => cell.y + 26));
  assert.equal(fusions[0].anchor[1], lowestAnchorY);
  const footprintCenterY = cells.reduce((sum, cell) => sum + cell.y + 26, 0) / cells.length;
  assert.equal(fusions[0].depthY, footprintCenterY);
});

test('line and pair keep their individual modules', () => {
  const cells = sevenCellCluster();
  const pair = resolveComplexes([0, 2].map(building), cells);
  const line = resolveComplexes([5, 0, 2].map(building), cells);
  assert.deepEqual(resolveComplexFusions(pair, cells), []);
  assert.deepEqual(resolveComplexFusions(line, cells), []);
});

test('groups of four to six with the center fuse one triangle and keep the rest separate', () => {
  const cells = sevenCellCluster();
  for (const occupied of [
    [0, 1, 2, 3],
    [0, 1, 2, 3, 4],
    [0, 1, 2, 3, 4, 5],
  ]) {
    const complexes = resolveComplexes(occupied.map(building), cells);
    const fusions = resolveComplexFusions(complexes, cells);
    assert.equal(fusions.length, 1);
    assert.equal(fusions[0].kind, 'three');
    assert.deepEqual(fusions[0].cellNums, [0, 1, 2]);
  }
});

test('three to six occupied ring cells never fuse without the center', () => {
  const cells = sevenCellCluster();
  for (const occupied of [
    [1, 2, 3],
    [1, 2, 3, 4],
    [1, 2, 3, 4, 5],
    [1, 2, 3, 4, 5, 6],
  ]) {
    const complexes = resolveComplexes(occupied.map(building), cells);
    assert.deepEqual(resolveComplexFusions(complexes, cells), []);
  }
});

test('foreground single modules render after a fused complex', () => {
  const queue = sortComplexRenderItems([
    { kind: 'building', cellNum: 5, anchor: [20, 140] },
    { kind: 'fusion', anchor: [40, 110] },
    { kind: 'building', cellNum: 2, anchor: [60, 90] },
  ]);
  assert.deepEqual(queue.map((item) => item.kind), ['building', 'fusion', 'building']);
  assert.equal(queue.at(-1).cellNum, 5);
});

test('a fusion foundation is ordered by its footprint centre instead of its lowest edge', () => {
  const queue = sortComplexRenderItems([
    { kind: 'building', cellNum: 8, anchor: [20, 104] },
    { kind: 'fusion', anchor: [40, 126], fusion: { depthY: 100 } },
    { kind: 'building', cellNum: 9, anchor: [60, 96] },
  ]);
  assert.deepEqual(queue.map((item) => item.cellNum ?? item.kind), [9, 'fusion', 8]);
});

test('nineteen-cell district fuses its central seven and keeps the outer ring separate', () => {
  const cells = nineteenCellCluster();
  const buildings = cells.map((cell) => ({
    cellNum: cell.num,
    complexGroup: cell.num < 7 ? 'residential-core' : 'residential-ring',
  }));
  const complexes = resolveComplexes(buildings, cells);
  const fusions = resolveComplexFusions(complexes, cells);
  assert.equal(complexes.length, 2);
  assert.equal(fusions.length, 1);
  assert.equal(fusions[0].kind, 'seven');
  assert.deepEqual(fusions[0].cellNums, [0, 1, 2, 3, 4, 5, 6]);
});
