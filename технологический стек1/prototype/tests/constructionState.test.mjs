import assert from 'node:assert/strict';
import test from 'node:test';

import {
  appendConstructionBuildings,
  applyBuildingLevels,
  applyBuildingPower,
  beginConstruction,
  completeConstruction,
  removeBuildings,
  setBuildingCondition,
  setBuildingPower,
  upgradeBuilding,
} from '../src/constructionState.mjs';

test('first action starts construction and second action completes it', () => {
  const started = beginConstruction(new Map(), 11, 2);
  assert.deepEqual(started.get(11), { cellNum: 11, buildingVariant: 2, state: 'construction' });
  const completed = completeConstruction(started, 11);
  assert.deepEqual(completed.get(11), { cellNum: 11, buildingVariant: 2, state: 'working' });
});

test('one building upgrades independently and stops at the maximum level', () => {
  let levels = upgradeBuilding(new Map(), 2);
  levels = upgradeBuilding(levels, 2);
  const capped = upgradeBuilding(levels, 2);
  assert.equal(levels.get(2), 3);
  assert.equal(capped, levels);
  assert.deepEqual(applyBuildingLevels([{ cellNum: 1 }, { cellNum: 2 }], levels), [
    { cellNum: 1 },
    { cellNum: 2, level: 3 },
  ]);
});

test('demolition removes one server building and leaves the source definition intact', () => {
  const base = [{ cellNum: 0 }, { cellNum: 1 }, { cellNum: 2 }];
  const result = removeBuildings(base, new Set([1]));
  assert.deepEqual(result, [{ cellNum: 0 }, { cellNum: 2 }]);
  assert.equal(base.length, 3);
});

test('damage belongs to one cell and repair restores its working state', () => {
  const damaged = setBuildingCondition(new Map(), 4, 'damaged');
  assert.deepEqual(applyBuildingPower([{ cellNum: 4 }, { cellNum: 5 }], damaged), [
    { cellNum: 4, state: 'damaged' },
    { cellNum: 5 },
  ]);
  const repaired = setBuildingCondition(damaged, 4, 'working');
  assert.equal(repaired.has(4), false);
});

test('power state disables and enables one cell without changing its neighbours', () => {
  const base = [{ cellNum: 0 }, { cellNum: 1 }, { cellNum: 2 }];
  const disabled = setBuildingPower(new Map(), 1, false);
  assert.deepEqual(applyBuildingPower(base, disabled), [
    { cellNum: 0 },
    { cellNum: 1, state: 'disabled' },
    { cellNum: 2 },
  ]);
  const enabled = setBuildingPower(disabled, 1, true);
  assert.deepEqual(applyBuildingPower(base, enabled), base);
});

test('construction cannot start on an occupied cell', () => {
  const occupied = beginConstruction(new Map(), 8, 0);
  assert.throws(() => beginConstruction(occupied, 8, 1), /уже занята/);
});

test('construction buildings join the scenario without modifying its base definition', () => {
  const base = [{ cellNum: 0, complexGroup: 'residential-core' }];
  const buildings = beginConstruction(new Map(), 14, 1);
  const merged = appendConstructionBuildings(base, buildings);
  assert.equal(base.length, 1);
  assert.deepEqual(merged.at(-1), {
    cellNum: 14,
    buildingVariant: 1,
    state: 'construction',
    complexGroup: 'residential-ring',
  });
});
