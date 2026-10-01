import assert from 'node:assert/strict';
import test from 'node:test';

import { resolveComplexes } from '../src/complexes.mjs';
import { buildComplexVisualPlan, pickCell, pointInHex } from '../src/complexVisuals.mjs';
import { sevenCellCluster } from '../src/geometry.mjs';

const cells = sevenCellCluster();
const buildings = (cellNums) => cellNums.map((cellNum) => ({ cellNum, complexGroup: 'residential' }));
const plan = (cellNums) => buildComplexVisualPlan(resolveComplexes(buildings(cellNums), cells), cells);

test('a pair creates one connector and ten external perimeter edges', () => {
  const visual = plan([0, 2]);
  assert.equal(visual.connectors.length, 1);
  assert.equal(visual.perimeters.length, 10);
});

test('triangle creates three connectors and one empty courtyard', () => {
  const visual = plan([0, 1, 2]);
  assert.equal(visual.connectors.length, 3);
  assert.equal(visual.courtyards.length, 1);
  assert.equal(visual.centerpieces.length, 0);
});

test('six-cell ring keeps its center empty', () => {
  const visual = plan([1, 2, 3, 4, 5, 6]);
  assert.equal(visual.connectors.length, 6);
  assert.equal(visual.courtyards.length, 1);
  assert.equal(visual.centerpieces.length, 0);
  assert.deepEqual(visual.courtyards[0].center, [24, 26]);
});

test('seven-cell cluster receives one centerpiece and no false courtyard', () => {
  const visual = plan([0, 1, 2, 3, 4, 5, 6]);
  assert.equal(visual.connectors.length, 12);
  assert.equal(visual.courtyards.length, 0);
  assert.deepEqual(visual.centerpieces.map((item) => item.cellNum), [0]);
});

test('hit testing belongs to cells rather than oversized building sprites', () => {
  const center = cells[0];
  assert.equal(pointInHex(center, 24, 13), true);
  assert.equal(pointInHex(center, 24, -20), false);
  assert.equal(pickCell(cells, 24, 13), 0);
  assert.equal(pickCell(cells, 72, 13), 2);
  assert.equal(pickCell(cells, 500, 500), null);
});
