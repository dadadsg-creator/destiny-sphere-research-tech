import assert from 'node:assert/strict';
import test from 'node:test';

import { sevenCellCluster } from '../src/geometry.mjs';
import { buildResidentialGroundPlan } from '../src/residentialGround.mjs';

test('two adjacent courtyards merge and keep only their outer perimeter', () => {
  const plan = buildResidentialGroundPlan(sevenCellCluster(), [0, 2]);
  assert.deepEqual(plan.cellNums, [0, 2]);
  assert.equal(plan.externalEdges.length, 10);
  assert.equal(plan.externalEdges.some(({ cellNum, direction }) => cellNum === 0 && direction === 1), false);
  assert.equal(plan.externalEdges.some(({ cellNum, direction }) => cellNum === 2 && direction === 4), false);
});

test('the full seven-cell quarter has one eighteen-edge outer contour', () => {
  const cells = sevenCellCluster();
  const plan = buildResidentialGroundPlan(cells, cells.map(({ num }) => num));
  assert.equal(plan.cellNums.length, 7);
  assert.equal(plan.externalEdges.length, 18);
});

test('courtyard plan is stable for duplicate and shuffled input', () => {
  const cells = sevenCellCluster();
  const first = buildResidentialGroundPlan(cells, [3, 0, 2, 2]);
  const second = buildResidentialGroundPlan(cells, [2, 3, 0]);
  assert.deepEqual(second, first);
});
