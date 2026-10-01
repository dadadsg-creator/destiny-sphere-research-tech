import assert from 'node:assert/strict';
import test from 'node:test';

import { canonicalizeMask, resolveComplexes } from '../src/complexes.mjs';
import { buildHexGrid, sevenCellCluster } from '../src/geometry.mjs';

const cluster = sevenCellCluster();
const building = (cellNum, overrides = {}) => ({ cellNum, complexGroup: 'residential', ...overrides });

test('a neighboring pair receives opposite connection bits', () => {
  const [complex] = resolveComplexes([building(0), building(2)], cluster, { location: 'PILOT' });
  assert.equal(complex.id, 'PILOT:residential:0');
  assert.equal(complex.members.find((item) => item.cellNum === 0).mask, 0b000010);
  assert.equal(complex.members.find((item) => item.cellNum === 2).mask, 0b010000);
});

test('input order does not affect ids, masks or selected center', () => {
  const records = [0, 1, 2, 3, 4, 5, 6].map((cellNum) => building(cellNum));
  const direct = resolveComplexes(records, cluster);
  const reversed = resolveComplexes([...records].reverse(), cluster);
  assert.deepEqual(direct, reversed);
  assert.equal(direct[0].centerCellNum, 0);
  assert.equal(direct[0].members.find((item) => item.cellNum === 0).mask, 0b111111);
});

test('incompatible groups and blocked ports split components', () => {
  const incompatible = resolveComplexes([building(0), building(2, { complexGroup: 'industrial' })], cluster);
  assert.equal(incompatible.length, 2);
  const blocked = resolveComplexes([building(0, { connectable: false }), building(2)], cluster);
  assert.equal(blocked.length, 2);
});

test('removing the middle of a line creates two stable complexes', () => {
  const complete = resolveComplexes([building(5), building(0), building(2)], cluster);
  assert.equal(complete.length, 1);
  const broken = resolveComplexes([building(5), building(2)], cluster);
  assert.deepEqual(broken.map((item) => item.cellNums), [[2], [5]]);
});

test('disabled and construction states remain connected', () => {
  const [complex] = resolveComplexes([
    building(0, { state: 'disabled' }),
    building(2, { state: 'construction' }),
  ], cluster);
  assert.deepEqual(complex.cellNums, [0, 2]);
});

test('canonical masks are invariant under rotation', () => {
  const base = canonicalizeMask(0b001011).mask;
  for (const rotated of [0b010110, 0b101100, 0b011001, 0b110010, 0b100101]) {
    assert.equal(canonicalizeMask(rotated).mask, base);
  }
});

test('full 217-cell grid resolves neighbors at edges without crossing gaps', () => {
  const grid = buildHexGrid();
  const complexes = resolveComplexes(grid.map((cell) => building(cell.num)), grid);
  assert.equal(complexes.length, 1);
  assert.equal(complexes[0].cellNums.length, 217);
});
