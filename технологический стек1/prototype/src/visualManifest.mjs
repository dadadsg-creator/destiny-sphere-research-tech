const MANIFEST_KEYS = new Set([
  'id', 'race', 'complexGroup', 'variant', 'footprint', 'logicalWidth', 'anchor',
  'shadowOffset', 'emissiveOffset', 'ports', 'layers',
]);
const REQUIRED_KEYS = [
  'id', 'race', 'complexGroup', 'footprint', 'logicalWidth', 'anchor', 'ports', 'layers',
];
const RACES = new Set(['tech', 'bio', 'psi', 'common']);
const LAYER_KEYS = new Set(['color', 'shadow', 'castShadow', 'emissive', 'construction', 'disabled', 'damage']);

function isRecord(value) {
  return value !== null && typeof value === 'object' && !Array.isArray(value);
}

function isFiniteNumber(value) {
  return typeof value === 'number' && Number.isFinite(value);
}

function assertVector(value, path, predicate = isFiniteNumber) {
  if (!Array.isArray(value) || value.length !== 2 || !value.every(predicate)) {
    throw new TypeError(`${path} должен содержать ровно два допустимых числа`);
  }
}

export function validateBuildingVisual(value) {
  if (!isRecord(value)) throw new TypeError('Манифест должен быть объектом');

  for (const key of REQUIRED_KEYS) {
    if (!(key in value)) throw new TypeError(`Отсутствует обязательное поле: ${key}`);
  }
  for (const key of Object.keys(value)) {
    if (!MANIFEST_KEYS.has(key)) throw new TypeError(`Неизвестное поле манифеста: ${key}`);
  }

  if (typeof value.id !== 'string' || value.id.length === 0) throw new TypeError('id должен быть непустой строкой');
  if (!RACES.has(value.race)) throw new TypeError(`Недопустимая раса: ${value.race}`);
  if (value.complexGroup !== null && (typeof value.complexGroup !== 'string' || value.complexGroup.length === 0)) {
    throw new TypeError('complexGroup должен быть непустой строкой или null');
  }
  if ('variant' in value && (!Number.isInteger(value.variant) || value.variant < 0)) {
    throw new TypeError('variant должен быть целым неотрицательным числом');
  }

  assertVector(value.footprint, 'footprint', (item) => Number.isInteger(item) && item >= 1);
  if (!isFiniteNumber(value.logicalWidth) || value.logicalWidth <= 0) {
    throw new TypeError('logicalWidth должен быть положительным числом');
  }
  assertVector(value.anchor, 'anchor', (item) => isFiniteNumber(item) && item >= 0 && item <= 1);
  if ('shadowOffset' in value) assertVector(value.shadowOffset, 'shadowOffset');
  if ('emissiveOffset' in value) assertVector(value.emissiveOffset, 'emissiveOffset');

  if (!Array.isArray(value.ports) || value.ports.length !== 6 || !value.ports.every((item) => typeof item === 'boolean')) {
    throw new TypeError('ports должен содержать шесть логических значений');
  }
  if (!isRecord(value.layers)) throw new TypeError('layers должен быть объектом');
  for (const key of Object.keys(value.layers)) {
    if (!LAYER_KEYS.has(key)) throw new TypeError(`Неизвестный слой: ${key}`);
    if (typeof value.layers[key] !== 'string' || value.layers[key].length === 0) {
      throw new TypeError(`layers.${key} должен быть непустой строкой`);
    }
  }
  if (typeof value.layers.color !== 'string' || value.layers.color.length === 0) {
    throw new TypeError('Обязательный слой layers.color не задан');
  }
  return value;
}

export function resolveBuildingVisual(value, manifestUrl) {
  const manifest = validateBuildingVisual(value);
  const absoluteManifestUrl = new URL(manifestUrl, globalThis.location?.href ?? 'file:///').href;
  const layers = Object.fromEntries(
    Object.entries(manifest.layers).map(([name, path]) => [name, new URL(path, absoluteManifestUrl).href]),
  );
  return Object.freeze({ ...manifest, manifestUrl: absoluteManifestUrl, layers: Object.freeze(layers) });
}

export async function loadBuildingVisual(manifestUrl, fetchImpl = globalThis.fetch) {
  if (typeof fetchImpl !== 'function') throw new TypeError('Для загрузки манифеста требуется fetch');
  const response = await fetchImpl(manifestUrl);
  if (!response.ok) throw new Error(`Не удалось загрузить манифест (${response.status}): ${manifestUrl}`);
  return resolveBuildingVisual(await response.json(), response.url || manifestUrl);
}

export async function loadVisualLayers(visual, imageFactory = () => new Image()) {
  const entries = await Promise.all(Object.entries(visual.layers).map(([name, url]) => new Promise((resolve, reject) => {
    const image = imageFactory();
    image.onload = () => resolve([name, image]);
    image.onerror = () => reject(new Error(`Не удалось загрузить слой ${name}: ${url}`));
    image.src = url;
  })));
  return Object.freeze(Object.fromEntries(entries));
}
