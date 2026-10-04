import assert from 'node:assert/strict';
import { stat } from 'node:fs/promises';
import test from 'node:test';

import { sixtyOneCellCluster } from '../src/geometry.mjs';
import {
  buildResidentialEnvironmentPlan,
  RESIDENTIAL_ENVIRONMENT_ASSET_FILES,
  RESIDENTIAL_ENVIRONMENT_CELLS,
  RESIDENTIAL_GROUND_ASSET_FILE,
} from '../src/residentialEnvironment.mjs';

test('one sandy-rock surface structure is assigned to every wide-map cell', () => {
  const cells = sixtyOneCellCluster();
  const available = new Set(cells.map(({ num }) => num));
  const plan = buildResidentialEnvironmentPlan(cells);
  assert.equal(plan.structures.length, 61);
  assert.deepEqual(new Set(plan.structures.map(({ variant }) => variant)), new Set(['sandstone']));
  assert.equal(RESIDENTIAL_ENVIRONMENT_CELLS.ruins.length, 3);
  RESIDENTIAL_ENVIRONMENT_CELLS.ruins.forEach((cellNum) => assert.equal(available.has(cellNum), true));
});

test('simplified sandy-rock surface contains no technical channels', () => {
  const cells = sixtyOneCellCluster();
  const plan = buildResidentialEnvironmentPlan(cells);
  assert.deepEqual(plan.channels, []);
});

test('authored ground and three standalone ruins stay inside the runtime size budget', async () => {
  const exportRoot = new URL('../../assets/tech/environment/exports/', import.meta.url);
  const sizes = await Promise.all(RESIDENTIAL_ENVIRONMENT_ASSET_FILES.map(async (file) => (
    await stat(new URL(file, exportRoot))
  ).size));
  assert.equal(sizes.length, 3);
  assert.ok(sizes.every((size) => size < 350_000));
  assert.ok(sizes.reduce((total, size) => total + size, 0) < 900_000);
  const groundSize = (await stat(new URL(RESIDENTIAL_GROUND_ASSET_FILE, exportRoot))).size;
  assert.ok(groundSize < 3_500_000);
});
