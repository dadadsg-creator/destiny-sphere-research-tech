import assert from 'node:assert/strict';
import { access, readFile } from 'node:fs/promises';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import {
  loadBuildingVisual,
  resolveBuildingVisual,
  validateBuildingVisual,
} from '../src/visualManifest.mjs';

const manifestUrl = new URL('../../assets/tech/residential/metadata/diagnostic-blue.json', import.meta.url);
const diagnosticManifestUrls = ['blue', 'gold', 'teal'].map(
  (variant) => new URL(`../../assets/tech/residential/metadata/diagnostic-${variant}.json`, import.meta.url),
);
const artCandidateUrl = new URL('../../assets/tech/residential/metadata/voraner-residential-v01.json', import.meta.url);
const simpleHomeUrl = new URL('../../assets/tech/residential/metadata/voraner-residential-v02.json', import.meta.url);
const simpleHomeUrls = ['v02', 'v03', 'v05'].map(
  (variant) => new URL(`../../assets/tech/residential/metadata/voraner-residential-${variant}.json`, import.meta.url),
);
const fusedComplexUrls = [3, 7].map(
  (size) => new URL(`../../assets/tech/residential/metadata/voraner-residential-complex-${size}.json`, import.meta.url),
);
const blockout3dUrl = new URL('../../assets/tech/residential/metadata/voraner-l1-blockout.json', import.meta.url);
const shadowProxyUrl = new URL('../../assets/tech/residential/metadata/voraner-v02-shadow-proxy.json', import.meta.url);
const stasisCollectorUrl = new URL('../../assets/tech/energy/metadata/voraner-stasis-collector-v01.json', import.meta.url);
const kaderiumForgeUrl = new URL('../../assets/tech/industry/metadata/voraner-kaderium-forge-v02.json', import.meta.url);
const kaderiumDispatchForgeUrl = new URL('../../assets/tech/industry/metadata/voraner-kaderium-forge-v03-dispatch.json', import.meta.url);
const nanoCrystallizerUrl = new URL('../../assets/tech/materials/metadata/voraner-nano-crystallizer-v01.json', import.meta.url);
const nanoDispatchCrystallizerUrl = new URL('../../assets/tech/materials/metadata/voraner-nano-crystallizer-v02-dispatch.json', import.meta.url);
const greenhouseFarmUrl = new URL('../../assets/tech/food/metadata/voraner-greenhouse-farm-v01.json', import.meta.url);
const compactGreenhouseFarmUrl = new URL('../../assets/tech/food/metadata/voraner-greenhouse-farm-v02.json', import.meta.url);
const lightWeaponsFactoryUrl = new URL('../../assets/tech/military/metadata/voraner-light-weapons-factory-v01.json', import.meta.url);
const shieldedLightWeaponsFactoryUrl = new URL('../../assets/tech/military/metadata/voraner-light-weapons-factory-v02.json', import.meta.url);
const heavyWeaponsFactoryUrl = new URL('../../assets/tech/military/metadata/voraner-heavy-weapons-factory-v01.json', import.meta.url);
const compactHeavyWeaponsFactoryUrl = new URL('../../assets/tech/military/metadata/voraner-heavy-weapons-factory-v02.json', import.meta.url);
const militaryBaseUrl = new URL('../../assets/tech/military/metadata/voraner-military-base-v03.json', import.meta.url);
const manifest = JSON.parse(await readFile(manifestUrl, 'utf8'));

test('valid manifest passes without depending on file names', () => {
  assert.equal(validateBuildingVisual(manifest), manifest);
  const renamed = structuredClone(manifest);
  renamed.layers.color = '../exports/anything-can-be-called-this.png';
  assert.equal(validateBuildingVisual(renamed), renamed);
});

test('unknown fields and malformed ports are rejected', () => {
  assert.throws(() => validateBuildingVisual({ ...manifest, level: 9 }), /Неизвестное поле/);
  assert.throws(() => validateBuildingVisual({ ...manifest, ports: [true] }), /шесть/);
});

test('layer paths resolve relative to the manifest', () => {
  const visual = resolveBuildingVisual(manifest, manifestUrl.href);
  assert.match(visual.layers.color, /assets\/tech\/residential\/exports\/diagnostic-blue-color\.png$/);
});

test('all three diagnostic variants and their declared layers exist', async () => {
  const visuals = await Promise.all(diagnosticManifestUrls.map(async (url) => {
    const packageManifest = JSON.parse(await readFile(url, 'utf8'));
    return resolveBuildingVisual(validateBuildingVisual(packageManifest), url.href);
  }));
  assert.deepEqual(visuals.map((visual) => visual.variant), [0, 1, 2]);
  await Promise.all(visuals.flatMap((visual) => Object.values(visual.layers).map((url) => access(fileURLToPath(url)))));
});

test('loader accepts an injected fetch implementation', async () => {
  const visual = await loadBuildingVisual('https://example.test/package.json', async () => ({
    ok: true,
    status: 200,
    url: 'https://example.test/visual/package.json',
    json: async () => manifest,
  }));
  assert.equal(visual.layers.color, 'https://example.test/exports/diagnostic-blue-color.png');
});

test('Voraner art candidate has a valid manifest and a readable transparent source', async () => {
  const candidate = JSON.parse(await readFile(artCandidateUrl, 'utf8'));
  const visual = resolveBuildingVisual(validateBuildingVisual(candidate), artCandidateUrl.href);
  assert.equal(visual.race, 'tech');
  assert.equal(visual.complexGroup, 'residential');
  assert.equal(visual.logicalWidth, 68);
  await access(fileURLToPath(visual.layers.color));
});

test('simple Voraner home has a valid one-cell manifest', async () => {
  const candidate = JSON.parse(await readFile(simpleHomeUrl, 'utf8'));
  const visual = resolveBuildingVisual(validateBuildingVisual(candidate), simpleHomeUrl.href);
  assert.equal(visual.id, 'tech/residential/voraner-v02-simple');
  assert.equal(visual.logicalWidth, 56);
  assert.deepEqual(visual.footprint, [1, 1]);
  await access(fileURLToPath(visual.layers.color));
});

test('three simple Voraner homes form a complete deterministic variant set', async () => {
  const visuals = await Promise.all(simpleHomeUrls.map(async (url) => {
    const candidate = JSON.parse(await readFile(url, 'utf8'));
    return resolveBuildingVisual(validateBuildingVisual(candidate), url.href);
  }));
  assert.deepEqual(visuals.map((visual) => visual.variant), [0, 1, 2]);
  assert.ok(visuals.every((visual) => visual.race === 'tech' && visual.complexGroup === 'residential'));
  assert.equal(new Set(visuals.map((visual) => visual.layers.construction)).size, 1);
  await Promise.all(visuals.flatMap((visual) => Object.values(visual.layers).map((url) => access(fileURLToPath(url)))));
});

test('three-cell and seven-cell fused complex packages are valid and readable', async () => {
  const visuals = await Promise.all(fusedComplexUrls.map(async (url) => {
    const candidate = JSON.parse(await readFile(url, 'utf8'));
    return resolveBuildingVisual(validateBuildingVisual(candidate), url.href);
  }));
  assert.deepEqual(visuals.map((visual) => visual.footprint), [[2, 2], [3, 3]]);
  assert.deepEqual(visuals.map((visual) => visual.logicalWidth), [92, 126]);
  await Promise.all(visuals.flatMap((visual) => Object.values(visual.layers).map((url) => access(fileURLToPath(url)))));
});

test('3D blockout package contains aligned authored render layers', async () => {
  const candidate = JSON.parse(await readFile(blockout3dUrl, 'utf8'));
  const visual = resolveBuildingVisual(validateBuildingVisual(candidate), blockout3dUrl.href);
  assert.deepEqual(visual.footprint, [1, 1]);
  assert.ok(visual.layers.castShadow);
  await Promise.all(Object.values(visual.layers).map((url) => access(fileURLToPath(url))));
});

test('tower shadow proxy exposes aligned color, physical shadow and emissive layers', async () => {
  const candidate = JSON.parse(await readFile(shadowProxyUrl, 'utf8'));
  const visual = resolveBuildingVisual(validateBuildingVisual(candidate), shadowProxyUrl.href);
  assert.equal(visual.id, 'tech/residential/voraner-v02-shadow-proxy');
  assert.equal(visual.variant, 1);
  assert.ok(visual.layers.castShadow);
  await Promise.all(Object.values(visual.layers).map((url) => access(fileURLToPath(url))));
});

test('realistic Voraner stasis collector is a readable single-cell energy package', async () => {
  const candidate = JSON.parse(await readFile(stasisCollectorUrl, 'utf8'));
  const visual = resolveBuildingVisual(validateBuildingVisual(candidate), stasisCollectorUrl.href);
  assert.equal(visual.id, 'tech/energy/voraner-stasis-collector-v01');
  assert.equal(visual.race, 'tech');
  assert.equal(visual.complexGroup, 'stasis-energy');
  assert.equal(visual.logicalWidth, 56);
  assert.deepEqual(visual.footprint, [1, 1]);
  assert.ok(visual.ports.every((port) => port === false));
  await access(fileURLToPath(visual.layers.color));
});

test('realistic Voraner Kaderium forge is a readable isolated industrial package', async () => {
  const candidate = JSON.parse(await readFile(kaderiumForgeUrl, 'utf8'));
  const visual = resolveBuildingVisual(validateBuildingVisual(candidate), kaderiumForgeUrl.href);
  assert.equal(visual.id, 'tech/industry/voraner-kaderium-forge-v02');
  assert.equal(visual.race, 'tech');
  assert.equal(visual.complexGroup, 'kaderium-industry');
  assert.equal(visual.logicalWidth, 62);
  assert.deepEqual(visual.footprint, [1, 1]);
  assert.ok(visual.ports.every((port) => port === false));
  await access(fileURLToPath(visual.layers.color));
});

test('single-cell Voraner Kaderium forge exposes the corrected product dispatch package', async () => {
  const candidate = JSON.parse(await readFile(kaderiumDispatchForgeUrl, 'utf8'));
  const visual = resolveBuildingVisual(validateBuildingVisual(candidate), kaderiumDispatchForgeUrl.href);
  assert.equal(visual.id, 'tech/industry/voraner-kaderium-forge-v03-dispatch');
  assert.equal(visual.race, 'tech');
  assert.equal(visual.complexGroup, 'kaderium-industry');
  assert.equal(visual.logicalWidth, 56);
  assert.deepEqual(visual.footprint, [1, 1]);
  assert.ok(visual.ports.every((port) => port === false));
  await access(fileURLToPath(visual.layers.color));
});

test('realistic Voraner nano crystallizer is a readable isolated production package', async () => {
  const candidate = JSON.parse(await readFile(nanoCrystallizerUrl, 'utf8'));
  const visual = resolveBuildingVisual(validateBuildingVisual(candidate), nanoCrystallizerUrl.href);
  assert.equal(visual.id, 'tech/materials/voraner-nano-crystallizer-v01');
  assert.equal(visual.race, 'tech');
  assert.equal(visual.complexGroup, 'nano-industry');
  assert.equal(visual.logicalWidth, 62);
  assert.deepEqual(visual.footprint, [1, 1]);
  assert.ok(visual.ports.every((port) => port === false));
  await access(fileURLToPath(visual.layers.color));
});

test('Voraner nano crystallizer exposes the finished-crystal dispatch package', async () => {
  const candidate = JSON.parse(await readFile(nanoDispatchCrystallizerUrl, 'utf8'));
  const visual = resolveBuildingVisual(validateBuildingVisual(candidate), nanoDispatchCrystallizerUrl.href);
  assert.equal(visual.id, 'tech/materials/voraner-nano-crystallizer-v02-dispatch');
  assert.equal(visual.race, 'tech');
  assert.equal(visual.complexGroup, 'nano-industry');
  assert.equal(visual.logicalWidth, 62);
  assert.deepEqual(visual.footprint, [1, 1]);
  assert.ok(visual.ports.every((port) => port === false));
  await access(fileURLToPath(visual.layers.color));
});

test('Voraner greenhouse farm is a readable isolated food-production package', async () => {
  const candidate = JSON.parse(await readFile(greenhouseFarmUrl, 'utf8'));
  const visual = resolveBuildingVisual(validateBuildingVisual(candidate), greenhouseFarmUrl.href);
  assert.equal(visual.id, 'tech/food/voraner-greenhouse-farm-v01');
  assert.equal(visual.race, 'tech');
  assert.equal(visual.complexGroup, 'food-industry');
  assert.equal(visual.logicalWidth, 60);
  assert.deepEqual(visual.footprint, [1, 1]);
  assert.ok(visual.ports.every((port) => port === false));
  await access(fileURLToPath(visual.layers.color));
});

test('compact Voraner greenhouse farm keeps the readable agricultural package', async () => {
  const candidate = JSON.parse(await readFile(compactGreenhouseFarmUrl, 'utf8'));
  const visual = resolveBuildingVisual(validateBuildingVisual(candidate), compactGreenhouseFarmUrl.href);
  assert.equal(visual.id, 'tech/food/voraner-greenhouse-farm-v02');
  assert.equal(visual.race, 'tech');
  assert.equal(visual.complexGroup, 'food-industry');
  assert.equal(visual.logicalWidth, 60);
  assert.deepEqual(visual.footprint, [1, 1]);
  assert.ok(visual.ports.every((port) => port === false));
  await access(fileURLToPath(visual.layers.color));
});

test('Voraner light weapons factory is a readable one-cell military package', async () => {
  const candidate = JSON.parse(await readFile(lightWeaponsFactoryUrl, 'utf8'));
  const visual = resolveBuildingVisual(validateBuildingVisual(candidate), lightWeaponsFactoryUrl.href);
  assert.equal(visual.id, 'tech/military/voraner-light-weapons-factory-v01');
  assert.equal(visual.race, 'tech');
  assert.equal(visual.complexGroup, 'light-weapons-industry');
  assert.equal(visual.logicalWidth, 58);
  assert.deepEqual(visual.footprint, [1, 1]);
  assert.ok(visual.ports.every((port) => port === false));
  await access(fileURLToPath(visual.layers.color));
});

test('shielded Voraner light weapons factory replaces the resource-factory silhouette', async () => {
  const candidate = JSON.parse(await readFile(shieldedLightWeaponsFactoryUrl, 'utf8'));
  const visual = resolveBuildingVisual(validateBuildingVisual(candidate), shieldedLightWeaponsFactoryUrl.href);
  assert.equal(visual.id, 'tech/military/voraner-light-weapons-factory-v02');
  assert.equal(visual.race, 'tech');
  assert.equal(visual.complexGroup, 'light-weapons-industry');
  assert.equal(visual.logicalWidth, 62);
  assert.deepEqual(visual.footprint, [1, 1]);
  assert.ok(visual.ports.every((port) => port === false));
  await access(fileURLToPath(visual.layers.color));
});

test('Voraner heavy weapons factory V01 is reserved as a two-cell armored hangar', async () => {
  const candidate = JSON.parse(await readFile(heavyWeaponsFactoryUrl, 'utf8'));
  const visual = resolveBuildingVisual(validateBuildingVisual(candidate), heavyWeaponsFactoryUrl.href);
  assert.equal(visual.id, 'tech/military/voraner-heavy-weapons-factory-v01');
  assert.equal(visual.race, 'tech');
  assert.equal(visual.complexGroup, 'heavy-weapons-industry');
  assert.equal(visual.logicalWidth, 104);
  assert.deepEqual(visual.footprint, [2, 1]);
  assert.ok(visual.ports.every((port) => port === false));
  await access(fileURLToPath(visual.layers.color));
});

test('Voraner heavy weapons factory V02 is a tall compact one-cell package', async () => {
  const candidate = JSON.parse(await readFile(compactHeavyWeaponsFactoryUrl, 'utf8'));
  const visual = resolveBuildingVisual(validateBuildingVisual(candidate), compactHeavyWeaponsFactoryUrl.href);
  assert.equal(visual.id, 'tech/military/voraner-heavy-weapons-factory-v02');
  assert.equal(visual.race, 'tech');
  assert.equal(visual.complexGroup, 'heavy-weapons-industry');
  assert.equal(visual.logicalWidth, 62);
  assert.deepEqual(visual.footprint, [1, 1]);
  assert.ok(visual.ports.every((port) => port === false));
  await access(fileURLToPath(visual.layers.color));
});

test('Voraner military base V03 exposes a one-cell command body and separate shield layer', async () => {
  const candidate = JSON.parse(await readFile(militaryBaseUrl, 'utf8'));
  const visual = resolveBuildingVisual(validateBuildingVisual(candidate), militaryBaseUrl.href);
  assert.equal(visual.id, 'tech/military/voraner-military-base-v03');
  assert.equal(visual.race, 'tech');
  assert.equal(visual.complexGroup, 'military-command');
  assert.equal(visual.logicalWidth, 70);
  assert.deepEqual(visual.footprint, [1, 1]);
  assert.ok(visual.ports.every((port) => port === false));
  await access(fileURLToPath(visual.layers.color));
  await access(fileURLToPath(visual.layers.rearShield));
});
