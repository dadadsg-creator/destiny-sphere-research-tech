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

test('fixed functional buildings keep their own visuals and never join residential fusions', () => {
  const cells = nineteenCellCluster();
  const buildings = [
    ...Array.from({ length: 7 }, (_, cellNum) => ({ cellNum, complexGroup: 'residential-core' })),
    {
      cellNum: 8,
      complexGroup: 'stasis-energy',
      connectable: false,
      visualKey: 'voranerEnergy01',
    },
    {
      cellNum: 17,
      complexGroup: 'kaderium-industry',
      connectable: false,
      visualKey: 'voranerForge03',
    },
    {
      cellNum: 11,
      complexGroup: 'nano-industry',
      connectable: false,
      visualKey: 'voranerNano02',
    },
    {
      cellNum: 14,
      complexGroup: 'food-industry',
      connectable: false,
      visualKey: 'voranerFood02',
    },
  ];
  const complexes = resolveComplexes(buildings, cells);
  const fusions = resolveComplexFusions(complexes, cells);
  const collector = complexes.find((complex) => complex.complexGroup === 'stasis-energy');
  const forge = complexes.find((complex) => complex.complexGroup === 'kaderium-industry');
  const crystallizer = complexes.find((complex) => complex.complexGroup === 'nano-industry');
  const greenhouse = complexes.find((complex) => complex.complexGroup === 'food-industry');

  assert.equal(fusions.length, 1);
  assert.equal(fusions[0].kind, 'seven');
  assert.deepEqual(collector.cellNums, [8]);
  assert.equal(collector.members[0].visualKey, 'voranerEnergy01');
  assert.deepEqual(forge.cellNums, [17]);
  assert.equal(forge.members[0].visualKey, 'voranerForge03');
  assert.deepEqual(crystallizer.cellNums, [11]);
  assert.equal(crystallizer.members[0].visualKey, 'voranerNano02');
  assert.deepEqual(greenhouse.cellNums, [14]);
  assert.equal(greenhouse.members[0].visualKey, 'voranerFood02');
});
