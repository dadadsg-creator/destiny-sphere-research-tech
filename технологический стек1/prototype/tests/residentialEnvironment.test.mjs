import assert from 'node:assert/strict';
import { stat } from 'node:fs/promises';
import test from 'node:test';

import { sixtyOneCellCluster } from '../src/geometry.mjs';
import {
  RESIDENTIAL_ENVIRONMENT_ASSET_FILES,
  RESIDENTIAL_ENVIRONMENT_CELLS,
} from '../src/residentialEnvironment.mjs';

test('residential environment reuses a bounded set of cells and keeps ring-gate cells free', () => {
  const available = new Set(sixtyOneCellCluster().map(({ num }) => num));
  assert.equal(RESIDENTIAL_ENVIRONMENT_CELLS.ground.length, 9);
  assert.equal(RESIDENTIAL_ENVIRONMENT_CELLS.ruins.length, 3);
  RESIDENTIAL_ENVIRONMENT_CELLS.ground.forEach((cellNum) => assert.equal(available.has(cellNum), true));
  RESIDENTIAL_ENVIRONMENT_CELLS.ruins.forEach((cellNum) => {
    assert.equal(RESIDENTIAL_ENVIRONMENT_CELLS.ground.includes(cellNum), true);
  });
  assert.equal(RESIDENTIAL_ENVIRONMENT_CELLS.ground.includes(22), false);
  assert.equal(RESIDENTIAL_ENVIRONMENT_CELLS.ground.includes(42), false);
});

test('six reusable environment sprites stay inside the runtime size budget', async () => {
  const exportRoot = new URL('../../assets/tech/environment/exports/', import.meta.url);
  const sizes = await Promise.all(RESIDENTIAL_ENVIRONMENT_ASSET_FILES.map(async (file) => (
    await stat(new URL(file, exportRoot))
  ).size));
  assert.equal(sizes.length, 6);
  assert.ok(sizes.every((size) => size < 350_000));
  assert.ok(sizes.reduce((total, size) => total + size, 0) < 1_600_000);
});
