import assert from 'node:assert/strict';
import { stat } from 'node:fs/promises';
import test from 'node:test';

import { sixtyOneCellCluster } from '../src/geometry.mjs';
import {
  buildResidentialEnvironmentPlan,
  RESIDENTIAL_ENVIRONMENT_ASSET_FILES,
  RESIDENTIAL_ENVIRONMENT_CELLS,
} from '../src/residentialEnvironment.mjs';

test('one borderless environment structure is assigned to every wide-map cell', () => {
  const cells = sixtyOneCellCluster();
  const available = new Set(cells.map(({ num }) => num));
  const plan = buildResidentialEnvironmentPlan(cells);
  assert.equal(plan.structures.length, 61);
  assert.equal(new Set(plan.structures.map(({ variant }) => variant)).size, 3);
  assert.equal(RESIDENTIAL_ENVIRONMENT_CELLS.ruins.length, 3);
  RESIDENTIAL_ENVIRONMENT_CELLS.ruins.forEach((cellNum) => assert.equal(available.has(cellNum), true));
});

test('technical channels form one connected network across all environment cells', () => {
  const cells = sixtyOneCellCluster();
  const plan = buildResidentialEnvironmentPlan(cells);
  const adjacency = new Map(cells.map(({ num }) => [num, []]));
  plan.channels.forEach(({ fromCellNum, toCellNum }) => {
    adjacency.get(fromCellNum).push(toCellNum);
    adjacency.get(toCellNum).push(fromCellNum);
  });
  const visited = new Set([cells[0].num]);
  const queue = [cells[0].num];
  while (queue.length > 0) {
    const cellNum = queue.shift();
    adjacency.get(cellNum).forEach((neighborCellNum) => {
      if (visited.has(neighborCellNum)) return;
      visited.add(neighborCellNum);
      queue.push(neighborCellNum);
    });
  }
  assert.equal(visited.size, cells.length);
  assert.ok(plan.channels.length >= cells.length - 1);
});

test('three standalone ruin sprites stay inside the runtime size budget', async () => {
  const exportRoot = new URL('../../assets/tech/environment/exports/', import.meta.url);
  const sizes = await Promise.all(RESIDENTIAL_ENVIRONMENT_ASSET_FILES.map(async (file) => (
    await stat(new URL(file, exportRoot))
  ).size));
  assert.equal(sizes.length, 3);
  assert.ok(sizes.every((size) => size < 350_000));
  assert.ok(sizes.reduce((total, size) => total + size, 0) < 900_000);
});
