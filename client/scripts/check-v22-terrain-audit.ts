import assert from 'node:assert/strict';
import { restoreV22Audit, v22AuditCounts } from '../src/map/terrainV22Audit';
import { HOME_ART_GROUND_V14_AUDIT } from '../src/map/locationGround';
import type { HomeTerrainCode } from '../src/map/buildingGroundRules';
import { allowedVariantsForTerrain, canBuildVariantOnTerrain, type BuildingVariant } from '../src/map/buildingGroundRules';
import { V22_SHOWCASE } from '../src/map/showcaseV22';

const base = HOME_ART_GROUND_V14_AUDIT;
const fresh = restoreV22Audit(null, null);
assert.equal(fresh.reference, base);
assert.equal(fresh.actual, base);
assert.deepEqual(fresh.reviewed, []);

const customV14 = `S${base.slice(1)}`;
const inherited = restoreV22Audit(null, customV14);
assert.equal(inherited.reference, customV14);
assert.equal(inherited.actual, customV14);

const actual = `W${base.slice(1)}`;
const restored = restoreV22Audit(JSON.stringify({ version: 1, reference: base, actual, reviewed: [0, 1, 1, -1, 217, '2'] }), customV14);
assert.equal(restored.reference, base, 'Saved V22 reference must not silently follow later V14 changes');
assert.equal(restored.actual, actual);
assert.deepEqual(restored.reviewed, [0, 1]);
const refArray = base.split('') as HomeTerrainCode[];
const actualArray = actual.split('') as HomeTerrainCode[];
assert.deepEqual(v22AuditCounts(refArray, actualArray, new Set()), { reviewed: 0, mismatches: 0 }, 'Unchecked cells are not automatically classified');
assert.deepEqual(v22AuditCounts(refArray, actualArray, new Set([0, 1])), { reviewed: 2, mismatches: 1 });
assert.deepEqual(v22AuditCounts(refArray, actualArray, new Set([0, 999, -1])), { reviewed: 1, mismatches: 1 });
assert.equal(base[0], 'D', 'Reviewing a changed image must not change the accepted assignment');
for (const bad of ['broken JSON', 'null', JSON.stringify({ version: 1, reference: 'W', actual: base, reviewed: [] })]) {
  assert.equal(restoreV22Audit(bad, customV14).reference, customV14);
}
assert.equal(restoreV22Audit(null, 'not-a-map').reference, base);
assert.equal(V22_SHOWCASE.ground, 'ring-v22');
assert.equal(V22_SHOWCASE.terrain.length, 217);
assert.equal(V22_SHOWCASE.buildings.length, 23);
assert.equal(new Set(V22_SHOWCASE.buildings.map(b => b.cell)).size, 23);
for (const building of V22_SHOWCASE.buildings) {
  assert.ok(building.cell >= 0 && building.cell < 217);
  assert.ok(canBuildVariantOnTerrain(building.variant, V22_SHOWCASE.terrain[building.cell] as HomeTerrainCode), `Invalid showcase cell ${building.cell}`);
}
for (const variant of [0, 1, 2] as BuildingVariant[]) {
  assert.ok(canBuildVariantOnTerrain(variant, 'G'), 'All residential types must be accepted on grass');
  assert.ok(canBuildVariantOnTerrain(variant, 'S'));
  for (const terrain of ['D', 'W', 'X'] as HomeTerrainCode[]) assert.equal(canBuildVariantOnTerrain(variant, terrain), false);
}
assert.deepEqual(allowedVariantsForTerrain('G'), [0, 1, 2, 6]);
for (const variant of [3, 4, 5, 7, 8, 9] as BuildingVariant[]) assert.equal(canBuildVariantOnTerrain(variant, 'G'), false);
console.log('V22 audit and showcase: persistence, mismatch counts, building compatibility and residential grass rules passed.');
