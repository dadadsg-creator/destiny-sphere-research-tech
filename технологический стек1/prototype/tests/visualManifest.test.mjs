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
