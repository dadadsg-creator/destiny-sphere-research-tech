import assert from 'node:assert/strict';
import test from 'node:test';

import { resolveComplexes } from '../src/complexes.mjs';
import { buildNeighborTable } from '../src/complexes.mjs';
import { assignDeterministicVariants, createCellSeed, hash32 } from '../src/deterministicVariants.mjs';
import { buildHexGrid, sevenCellCluster } from '../src/geometry.mjs';

const context = { worldId: 'alpha', location: 'HOME', sotaId: '42' };
const building = (cellNum) => ({ cellNum, complexGroup: 'residential' });

test('hash and cell seeds are stable', () => {
  assert.equal(hash32('DestinySphere'), hash32('DestinySphere'));
  assert.equal(createCellSeed(context, 'residential', 7), createCellSeed(context, 'residential', 7));
  assert.notEqual(createCellSeed(context, 'residential', 7), createCellSeed(context, 'residential', 8));
});

test('same map produces identical assignments after reload and shuffled input', () => {
  const cells = sevenCellCluster();
  const records = [0, 1, 2, 3, 4, 5, 6].map(building);
  const direct = assignDeterministicVariants(resolveComplexes(records, cells), cells, context);
  const shuffled = assignDeterministicVariants(resolveComplexes([...records].reverse(), cells), cells, context);
  assert.deepEqual(direct, shuffled);
});

test('all neighboring cells receive different structural variants', () => {
  const cells = buildHexGrid();
  const complexes = resolveComplexes(cells.map((cell) => building(cell.num)), cells);
  const assignments = assignDeterministicVariants(complexes, cells, context);
  const byCell = new Map(assignments.map((item) => [item.cellNum, item]));
  const neighbors = buildNeighborTable(cells);
  for (const assignment of assignments) {
    for (const neighbor of neighbors.get(assignment.cellNum)) {
      if (neighbor === null) continue;
      assert.notEqual(assignment.variant, byCell.get(neighbor).variant);
    }
  }
});

test('adding a remote building does not change existing assignments', () => {
  const cells = buildHexGrid();
  const first = [building(0), building(1), building(216)];
  const before = assignDeterministicVariants(resolveComplexes(first, cells), cells, context);
  const after = assignDeterministicVariants(resolveComplexes([...first, building(100)], cells), cells, context);
  const afterByCell = new Map(after.map((item) => [item.cellNum, item]));
  for (const assignment of before) assert.deepEqual(afterByCell.get(assignment.cellNum), assignment);
});

test('height and decor stay inside declared option ranges', () => {
  const cells = buildHexGrid();
  const assignments = assignDeterministicVariants(resolveComplexes(cells.slice(0, 40).map((cell) => building(cell.num)), cells), cells, context);
  assert.ok(assignments.every((item) => item.heightScale >= 0.94 && item.heightScale <= 1.06));
  assert.ok(new Set(assignments.map((item) => item.garden)).size > 1);
  assert.ok(new Set(assignments.map((item) => item.antenna)).size > 1);
  assert.ok(new Set(assignments.map((item) => item.entrance)).size > 1);
});
