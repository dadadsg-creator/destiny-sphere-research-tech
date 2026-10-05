import {
  BUILDING_ANCHOR,
  CELL_HEIGHT,
  CELL_WIDTH,
  HEX_POINTS,
  PILOT_ENVELOPE,
  PORTS,
  TOTAL_CELLS,
  rotateMask,
  nineteenCellCluster,
  sevenCellCluster,
  sixtyOneCellCluster,
} from './geometry.mjs?v=20261002-13';
import { loadBuildingVisual, loadVisualLayers } from './visualManifest.mjs?v=20260930-2';
import { resolveComplexes } from './complexes.mjs?v=20260930-2';
import { buildComplexVisualPlan, pickCell } from './complexVisuals.mjs?v=20260930-2';
import { resolveComplexTheme } from './complexTheme.mjs?v=20260930-2';
import { resolveComplexFusions, sortComplexRenderItems } from './complexFusion.mjs?v=20261005-16';
import { assignDeterministicVariants } from './deterministicVariants.mjs?v=20260930-2';
import { buildArchitecturalLinks } from './architecturalLinks.mjs?v=20261004-3';
import { buildResidentialGroundPlan } from './residentialGround.mjs?v=20261004-1';
import {
  buildResidentialEnvironmentPlan,
  drawResidentialEnvironmentGround,
  drawResidentialEnvironmentRuins,
  drawResidentialEnvironmentShadows,
} from './residentialEnvironment.mjs?v=20261005-1';
import {
  RING_GATE_CELL_NUMS,
  drawAncientEnvironment,
  drawAncientInfrastructure,
  drawAncientPlatformEdgeLights,
  drawRingWorldBackdrop,
  hitTestAncientInfrastructure,
} from './environmentArt.mjs?v=20261002-24';
import {
  buildGroundShadowFootprint,
  buildGroundShadowProjection,
  canReceiveProjectedShadow,
  resolveGroundCastHeightRatio,
} from './lighting.mjs?v=20261002-15';
import {
  extractSilhouetteShadowSegments,
  resolveScreenShadowProjection,
  resolveSilhouetteStrokeWidth,
} from './silhouetteShadow.mjs?v=20261005-1';
import {
  appendConstructionBuildings,
  applyBuildingLevels,
  applyBuildingPower,
  beginConstruction,
  completeConstruction,
  removeBuildings,
  setBuildingPower,
  upgradeBuilding,
} from './constructionState.mjs?v=20261003-1';

const canvas = document.querySelector('#scene');
const ctx = canvas.getContext('2d');
const effectsCanvas = document.querySelector('#sceneEffects');
const effectsCtx = effectsCanvas.getContext('2d');
const castShadowCanvas = document.createElement('canvas');
const receiverMaskCanvas = document.createElement('canvas');
let calibratedShadowCache = new WeakMap();
const v02RinglessLayerCache = new WeakMap();
const controls = {
  ports: document.querySelector('#showPorts'),
  anchors: document.querySelector('#showAnchors'),
  envelope: document.querySelector('#showEnvelope'),
  labels: document.querySelector('#showLabels'),
  residentialGround: document.querySelector('#showResidentialGround'),
  residentialEnvironment: document.querySelector('#showResidentialEnvironment'),
  animateScene: document.querySelector('#animateScene'),
  waterIntensity: document.querySelector('#waterIntensity'),
  waterIntensityValue: document.querySelector('#waterIntensityValue'),
  ringOffsetX: document.querySelector('#ringOffsetX'),
  ringOffsetXValue: document.querySelector('#ringOffsetXValue'),
  ringOffsetY: document.querySelector('#ringOffsetY'),
  ringOffsetYValue: document.querySelector('#ringOffsetYValue'),
  ringScaleX: document.querySelector('#ringScaleX'),
  ringScaleXValue: document.querySelector('#ringScaleXValue'),
  ringScaleY: document.querySelector('#ringScaleY'),
  ringScaleYValue: document.querySelector('#ringScaleYValue'),
  resetRingTuning: document.querySelector('#resetRingTuning'),
  rippleOffsetX: document.querySelector('#rippleOffsetX'),
  rippleOffsetXValue: document.querySelector('#rippleOffsetXValue'),
  rippleOffsetY: document.querySelector('#rippleOffsetY'),
  rippleOffsetYValue: document.querySelector('#rippleOffsetYValue'),
  rippleScaleX: document.querySelector('#rippleScaleX'),
  rippleScaleXValue: document.querySelector('#rippleScaleXValue'),
  rippleScaleY: document.querySelector('#rippleScaleY'),
  rippleScaleYValue: document.querySelector('#rippleScaleYValue'),
  resetRippleTuning: document.querySelector('#resetRippleTuning'),
  architecturalLinks: document.querySelector('#showArchitecturalLinks'),
  linkDebug: document.querySelector('#showLinkDebug'),
  mathShadow: document.querySelector('#showMathShadow'),
  physicalShadow: document.querySelector('#showPhysicalShadow'),
  facadeShadow: document.querySelector('#showFacadeShadow'),
  shadowAzimuth: document.querySelector('#shadowAzimuth'),
  shadowAzimuthValue: document.querySelector('#shadowAzimuthValue'),
  sunElevation: document.querySelector('#sunElevation'),
  sunElevationValue: document.querySelector('#sunElevationValue'),
  projectionCompression: document.querySelector('#projectionCompression'),
  projectionCompressionValue: document.querySelector('#projectionCompressionValue'),
  shadowLengthValue: document.querySelector('#shadowLengthValue'),
  alphaThreshold: document.querySelector('#alphaThreshold'),
  alphaThresholdValue: document.querySelector('#alphaThresholdValue'),
  columnStep: document.querySelector('#columnStep'),
  columnStepValue: document.querySelector('#columnStepValue'),
  rayWidth: document.querySelector('#rayWidth'),
  rayWidthValue: document.querySelector('#rayWidthValue'),
  shadowJoin: document.querySelector('#shadowJoin'),
  shadowJoinValue: document.querySelector('#shadowJoinValue'),
  baselineOffset: document.querySelector('#baselineOffset'),
  baselineOffsetValue: document.querySelector('#baselineOffsetValue'),
  mathOpacity: document.querySelector('#mathOpacity'),
  mathOpacityValue: document.querySelector('#mathOpacityValue'),
  physicalOpacity: document.querySelector('#physicalOpacity'),
  physicalOpacityValue: document.querySelector('#physicalOpacityValue'),
  mathFacadeOpacity: document.querySelector('#mathFacadeOpacity'),
  mathFacadeOpacityValue: document.querySelector('#mathFacadeOpacityValue'),
  facadeOpacity: document.querySelector('#facadeOpacity'),
  facadeOpacityValue: document.querySelector('#facadeOpacityValue'),
  shadowBlur: document.querySelector('#shadowBlur'),
  shadowBlurValue: document.querySelector('#shadowBlurValue'),
  resetShadowTuning: document.querySelector('#resetShadowTuning'),
  rotate: document.querySelector('#rotate'),
  metrics: document.querySelector('#metrics'),
  packageSelect: document.querySelector('#packageSelect'),
  packageStatus: document.querySelector('#packageStatus'),
  zoom: document.querySelector('#zoom'),
  zoomValue: document.querySelector('#zoomValue'),
  surfaceSelect: document.querySelector('#surfaceSelect'),
  scenarioSelect: document.querySelector('#scenarioSelect'),
  buildPanel: document.querySelector('#buildPanel'),
  buildHint: document.querySelector('#buildHint'),
  buildMenu: document.querySelector('#buildMenu'),
  powerMenu: document.querySelector('#powerMenu'),
  togglePower: document.querySelector('#togglePower'),
  upgradeBuilding: document.querySelector('#upgradeBuilding'),
  demolishBuilding: document.querySelector('#demolishBuilding'),
  controlsPanel: document.querySelector('#controlsPanel'),
  scrollControlsUp: document.querySelector('#scrollControlsUp'),
  scrollControlsDown: document.querySelector('#scrollControlsDown'),
};

let mask = 0b100111;
let activeVisual = null;
let activePackages = [];
let activeFusionPackages = new Map();
let packageRequest = 0;
let selectedCellNum = 0;
let hoveredCellNum = null;
let latestScene = null;
let latestEffectsScene = null;
let sceneAnimationTimeMs = 0;
let sceneAnimationFrame = null;
let lastAnimatedRenderMs = -Infinity;
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

const WATER_SURFACES = Object.freeze({
  'tech/residential/voraner-v02-simple': Object.freeze([
    Object.freeze({ x: 0.37, y: 0.885, rx: 0.078, ry: 0.018 }),
  ]),
  'tech/residential/voraner-v03-dome': Object.freeze([
    Object.freeze({ x: 0.805, y: 0.69, rx: 0.082, ry: 0.023 }),
  ]),
  'tech/residential/voraner-complex-3': Object.freeze([
    Object.freeze({ x: 0.555, y: 0.59, rx: 0.145, ry: 0.052, jet: 0.125 }),
  ]),
  'tech/residential/voraner-complex-7': Object.freeze([
    Object.freeze({ x: 0.515, y: 0.35, rx: 0.07, ry: 0.021, jet: 0.11 }),
    Object.freeze({ x: 0.205, y: 0.625, rx: 0.06, ry: 0.021, jet: 0.075 }),
    Object.freeze({ x: 0.52, y: 0.625, rx: 0.055, ry: 0.019, jet: 0.08 }),
    Object.freeze({ x: 0.825, y: 0.625, rx: 0.06, ry: 0.021, jet: 0.075 }),
    Object.freeze({ x: 0.405, y: 0.825, rx: 0.075, ry: 0.022 }),
    Object.freeze({ x: 0.63, y: 0.825, rx: 0.075, ry: 0.022 }),
  ]),
});

window.addEventListener('ancient-environment-assets-ready', () => {
  if (activeVisual) render();
});
window.addEventListener('residential-environment-assets-ready', () => {
  if (activeVisual) render();
});

// Browsers may restore the previous value of native selects on reload.  The
// current art review must always open on the expanded district.
controls.packageSelect.value = 'voranerMix';
controls.scenarioSelect.value = 'district';
controls.ports.checked = false;
controls.anchors.checked = false;
controls.envelope.checked = false;
controls.labels.checked = false;
controls.residentialGround.checked = true;
controls.residentialEnvironment.checked = true;
controls.animateScene.checked = true;
controls.architecturalLinks.checked = false;
controls.linkDebug.checked = false;
controls.zoom.value = '145';
controls.surfaceSelect.value = 'ancient';

function zoomFactor() {
  return Number(controls.zoom.value) / 100;
}

function updateZoomLabel() {
  controls.zoomValue.value = `${controls.zoom.value}%`;
}

updateZoomLabel();

function waterEffectIntensity() {
  return Number(controls.waterIntensity.value);
}

function updateWaterIntensityLabel() {
  controls.waterIntensityValue.value = `${waterEffectIntensity().toFixed(1).replace('.', ',')}×`;
}

updateWaterIntensityLabel();

function ringTuning() {
  return {
    offsetX: Number(controls.ringOffsetX.value),
    offsetY: Number(controls.ringOffsetY.value),
    scaleX: Number(controls.ringScaleX.value) / 100,
    scaleY: Number(controls.ringScaleY.value) / 100,
  };
}

function updateRingTuningLabels() {
  const tuning = ringTuning();
  controls.ringOffsetXValue.value = `${tuning.offsetX.toFixed(1).replace('.0', '').replace('.', ',')} px`;
  controls.ringOffsetYValue.value = `${tuning.offsetY.toFixed(1).replace('.0', '').replace('.', ',')} px`;
  controls.ringScaleXValue.value = `${Math.round(tuning.scaleX * 100)}%`;
  controls.ringScaleYValue.value = `${Math.round(tuning.scaleY * 100)}%`;
}

function resetRingTuning() {
  controls.ringOffsetX.value = '0';
  controls.ringOffsetY.value = '0';
  controls.ringScaleX.value = '100';
  controls.ringScaleY.value = '100';
  updateRingTuningLabels();
}

updateRingTuningLabels();

function rippleTuning() {
  return {
    offsetX: Number(controls.rippleOffsetX.value),
    offsetY: Number(controls.rippleOffsetY.value),
    scaleX: Number(controls.rippleScaleX.value) / 100,
    scaleY: Number(controls.rippleScaleY.value) / 100,
  };
}

function updateRippleTuningLabels() {
  const tuning = rippleTuning();
  controls.rippleOffsetXValue.value = `${tuning.offsetX.toFixed(1).replace('.0', '').replace('.', ',')} px`;
  controls.rippleOffsetYValue.value = `${tuning.offsetY.toFixed(1).replace('.0', '').replace('.', ',')} px`;
  controls.rippleScaleXValue.value = `${Math.round(tuning.scaleX * 100)}%`;
  controls.rippleScaleYValue.value = `${Math.round(tuning.scaleY * 100)}%`;
}

function resetRippleTuning() {
  controls.rippleOffsetX.value = '0';
  controls.rippleOffsetY.value = '0';
  controls.rippleScaleX.value = '100';
  controls.rippleScaleY.value = '100';
  updateRippleTuningLabels();
}

updateRippleTuningLabels();

const SHADOW_TUNING_DEFAULTS = Object.freeze({
  azimuth: 220,
  elevation: 33,
  compression: 0.84,
  alphaThreshold: 26,
  columnStep: 1,
  rayWidth: 2.25,
  shadowJoin: 0.8,
  baselineOffset: 0.06,
  mathOpacity: 0.74,
  physicalOpacity: 0.74,
  mathFacadeOpacity: 0.42,
  facadeOpacity: 0.42,
  blur: 2,
});

const shadowModifierInputs = [
  controls.shadowAzimuth,
  controls.sunElevation,
  controls.projectionCompression,
  controls.alphaThreshold,
  controls.columnStep,
  controls.rayWidth,
  controls.shadowJoin,
  controls.baselineOffset,
  controls.mathOpacity,
  controls.physicalOpacity,
  controls.mathFacadeOpacity,
  controls.facadeOpacity,
  controls.shadowBlur,
];

function shadowTuning() {
  const azimuth = Number(controls.shadowAzimuth.value);
  const elevation = Number(controls.sunElevation.value);
  const compression = Number(controls.projectionCompression.value);
  const projection = resolveScreenShadowProjection({
    azimuthDegrees: azimuth,
    elevationDegrees: elevation,
    compression,
  });
  return Object.freeze({
    azimuth,
    elevation,
    compression,
    direction: projection.direction,
    lengthPerScreenHeight: projection.lengthPerScreenHeight,
    alphaThreshold: Number(controls.alphaThreshold.value),
    columnStep: Number(controls.columnStep.value),
    rayWidth: Number(controls.rayWidth.value),
    shadowJoin: Number(controls.shadowJoin.value),
    baselineOffset: Number(controls.baselineOffset.value),
    mathOpacity: Number(controls.mathOpacity.value),
    physicalOpacity: Number(controls.physicalOpacity.value),
    mathFacadeOpacity: Number(controls.mathFacadeOpacity.value),
    facadeOpacity: Number(controls.facadeOpacity.value),
    blur: Number(controls.shadowBlur.value),
  });
}

function decimal(value, digits) {
  return Number(value).toFixed(digits).replace('.', ',');
}

function updateShadowTuningLabels() {
  const tuning = shadowTuning();
  controls.shadowAzimuthValue.value = `${tuning.azimuth}°`;
  controls.sunElevationValue.value = `${tuning.elevation}°`;
  controls.projectionCompressionValue.value = decimal(tuning.compression, 2);
  controls.shadowLengthValue.value = `${decimal(tuning.lengthPerScreenHeight, 2)} px`;
  controls.alphaThresholdValue.value = String(tuning.alphaThreshold);
  controls.columnStepValue.value = `${tuning.columnStep} px`;
  controls.rayWidthValue.value = `${decimal(tuning.rayWidth, 2)} px`;
  controls.shadowJoinValue.value = `${decimal(tuning.shadowJoin, 1)} px`;
  controls.baselineOffsetValue.value = decimal(tuning.baselineOffset, 3);
  controls.mathOpacityValue.value = `${Math.round(tuning.mathOpacity * 100)}%`;
  controls.physicalOpacityValue.value = `${Math.round(tuning.physicalOpacity * 100)}%`;
  controls.mathFacadeOpacityValue.value = `${Math.round(tuning.mathFacadeOpacity * 100)}%`;
  controls.facadeOpacityValue.value = `${Math.round(tuning.facadeOpacity * 100)}%`;
  controls.shadowBlurValue.value = `${decimal(tuning.blur, 1)} px`;
}

function resetShadowTuning() {
  controls.shadowAzimuth.value = String(SHADOW_TUNING_DEFAULTS.azimuth);
  controls.sunElevation.value = String(SHADOW_TUNING_DEFAULTS.elevation);
  controls.projectionCompression.value = String(SHADOW_TUNING_DEFAULTS.compression);
  controls.alphaThreshold.value = String(SHADOW_TUNING_DEFAULTS.alphaThreshold);
  controls.columnStep.value = String(SHADOW_TUNING_DEFAULTS.columnStep);
  controls.rayWidth.value = String(SHADOW_TUNING_DEFAULTS.rayWidth);
  controls.shadowJoin.value = String(SHADOW_TUNING_DEFAULTS.shadowJoin);
  controls.baselineOffset.value = String(SHADOW_TUNING_DEFAULTS.baselineOffset);
  controls.mathOpacity.value = String(SHADOW_TUNING_DEFAULTS.mathOpacity);
  controls.physicalOpacity.value = String(SHADOW_TUNING_DEFAULTS.physicalOpacity);
  controls.mathFacadeOpacity.value = String(SHADOW_TUNING_DEFAULTS.mathFacadeOpacity);
  controls.facadeOpacity.value = String(SHADOW_TUNING_DEFAULTS.facadeOpacity);
  controls.shadowBlur.value = String(SHADOW_TUNING_DEFAULTS.blur);
  calibratedShadowCache = new WeakMap();
  updateShadowTuningLabels();
}

resetShadowTuning();

let pilotCells = sevenCellCluster();
const VARIANT_CONTEXT = Object.freeze({ worldId: 'pilot-world', location: 'HOME', sotaId: 'residential-demo' });
const PACKAGE_URLS = Object.freeze({
  blue: '../assets/tech/residential/metadata/diagnostic-blue.json',
  gold: '../assets/tech/residential/metadata/diagnostic-gold.json',
  teal: '../assets/tech/residential/metadata/diagnostic-teal.json',
  voraner01: '../assets/tech/residential/metadata/voraner-residential-v01.json',
  voraner02: '../assets/tech/residential/metadata/voraner-residential-v02.json?v=20261001-13',
  voraner03: '../assets/tech/residential/metadata/voraner-residential-v03.json?v=20261001-13',
  voraner04: '../assets/tech/residential/metadata/voraner-residential-v04.json?v=20261001-13',
  voraner05: '../assets/tech/residential/metadata/voraner-residential-v05.json?v=20261001-13',
  voraner3d: '../assets/tech/residential/metadata/voraner-l1-blockout.json?v=20261002-3',
  voranerProxy: '../assets/tech/residential/metadata/voraner-v02-shadow-proxy.json?v=20261002-1',
});
const PACKAGE_PRESETS = Object.freeze({
  auto: Object.freeze(['blue', 'gold', 'teal']),
  voranerMix: Object.freeze(['voraner02', 'voraner03', 'voraner05']),
  shadowLab: Object.freeze(['voraner02', 'voranerProxy']),
});
const FUSION_PACKAGE_URLS = Object.freeze({
  three: '../assets/tech/residential/metadata/voraner-residential-complex-3.json?v=20261001-13',
  seven: '../assets/tech/residential/metadata/voraner-residential-complex-7.json?v=20261001-13',
});
const DISTRICT_BUILD_SLOTS = new Set([8, 11, 14, 17]);
const ART_PLACEMENT_OFFSET = Object.freeze([0, -4.5]);
const SEVEN_COMPLEX_TOWER_CLIP = Object.freeze([0.255, 0, 0.15, 0.49]);
const SURFACE_THEMES = Object.freeze({
  ancient: Object.freeze({ top: '#79949a', bottom: '#536d76', center: '#8aa4a5', stroke: '#9bc4c8', detail: 'rgba(176, 225, 222, 0.18)' }),
  grass: Object.freeze({ top: '#668164', bottom: '#385a4d', center: '#718d68', stroke: '#8eb596', detail: 'rgba(191, 222, 137, 0.2)' }),
  desert: Object.freeze({ top: '#a5865f', bottom: '#72583f', center: '#b29168', stroke: '#d4b483', detail: 'rgba(255, 220, 160, 0.18)' }),
});
const DISTRICT_SCENARIO = Object.freeze([
  ...Array.from({ length: 7 }, (_, cellNum) => ({ cellNum, complexGroup: 'residential-core' })),
  ...Array.from({ length: 12 }, (_, index) => ({ cellNum: index + 7, complexGroup: 'residential-ring' }))
    .filter(({ cellNum }) => !DISTRICT_BUILD_SLOTS.has(cellNum)),
]);
const SCENARIOS = Object.freeze({
  district: DISTRICT_SCENARIO,
  districtWide: DISTRICT_SCENARIO,
  complexShadow: [0, 1, 2, 3, 4, 5, 6],
  sevenFull: [0, 1, 2, 3, 4, 5, 6],
  sixCenter: [0, 1, 2, 3, 4, 5],
  sixRing: [1, 2, 3, 4, 5, 6],
  fiveCenter: [0, 1, 2, 3, 4],
  fiveRing: [1, 2, 3, 4, 5],
  fourCenter: [0, 1, 2, 3],
  fourRing: [1, 2, 3, 4],
  threeCenter: [0, 1, 2],
  threeRing: [1, 2, 3],
  single: [0],
  waterLab: [0],
  pair: [0, 2],
  line: [5, 0, 2],
  break: [5, 2],
  states: [
    { cellNum: 0, state: 'working' },
    { cellNum: 1, state: 'construction' },
    { cellNum: 2, state: 'disabled' },
    { cellNum: 3, state: 'working' },
    { cellNum: 4, state: 'working' },
    { cellNum: 5, state: 'working' },
    { cellNum: 6, state: 'working' },
  ],
  shadowLab: [
    { cellNum: 0, buildingVariant: 0, complexGroup: 'physical-shadow-receiver' },
    { cellNum: 3, buildingVariant: 1, complexGroup: 'physical-shadow-caster' },
    { cellNum: 2, buildingVariant: 0, complexGroup: 'math-shadow-receiver' },
    { cellNum: 10, buildingVariant: 0, complexGroup: 'math-shadow-caster' },
  ],
});

function cellsForScenario(originX = 0, originY = 0) {
  if (controls.scenarioSelect.value === 'districtWide') {
    return sixtyOneCellCluster(originX, originY);
  }
  if (['district', 'complexShadow', 'shadowLab'].includes(controls.scenarioSelect.value)) {
    return nineteenCellCluster(originX, originY);
  }
  if (controls.scenarioSelect.value === 'waterLab') {
    return [sevenCellCluster(originX, originY)[0]];
  }
  return sevenCellCluster(originX, originY);
}

function isEditableScenario() {
  return ['district', 'districtWide', 'shadowLab'].includes(controls.scenarioSelect.value);
}

function usesCalibratedMathShadows() {
  return controls.mathShadow.checked && (
    ['district', 'districtWide'].includes(controls.scenarioSelect.value)
    || controls.packageSelect.value === 'shadowLab'
    || shadowMode === 'calibrated'
  );
}

function isBuildableCell(cellNum) {
  if (controls.scenarioSelect.value === 'shadowLab') {
    return pilotCells.some((cell) => cell.num === cellNum);
  }
  return DISTRICT_BUILD_SLOTS.has(cellNum) || removedCells.has(cellNum);
}

function constructionComplexGroup(cellNum) {
  const scenarioDefinition = SCENARIOS[controls.scenarioSelect.value] ?? [];
  const original = scenarioDefinition.find((entry) => (
    (typeof entry === 'number' ? entry : entry.cellNum) === cellNum
  ));
  if (typeof original === 'object' && original?.complexGroup) return original.complexGroup;
  return ['district', 'districtWide'].includes(controls.scenarioSelect.value)
    ? 'residential-ring'
    : 'residential';
}

function showsAncientEnvironment() {
  return (
    controls.scenarioSelect.value === 'districtWide'
    && controls.surfaceSelect.value === 'ancient'
  );
}

function showsResidentialEnvironment() {
  return (
    ['district', 'districtWide'].includes(controls.scenarioSelect.value)
    && controls.surfaceSelect.value === 'ancient'
  );
}

function isRingGateCell(cellNum) {
  return RING_GATE_CELL_NUMS.includes(cellNum);
}

function interactionCellNums(cellNum) {
  return isRingGateCell(cellNum) ? RING_GATE_CELL_NUMS : [cellNum];
}

const previewParams = new URLSearchParams(window.location.search);
if (previewParams.get('mathShadow') === '0') controls.mathShadow.checked = false;
if (previewParams.get('physicalShadow') === '0') controls.physicalShadow.checked = false;
if (previewParams.get('facadeShadow') === '0') controls.facadeShadow.checked = false;
if (previewParams.get('courtyard') === '0') controls.residentialGround.checked = false;
if (previewParams.get('courtyard') === '1') controls.residentialGround.checked = true;
if (previewParams.get('environment') === '0') controls.residentialEnvironment.checked = false;
if (previewParams.get('environment') === '1') controls.residentialEnvironment.checked = true;
if (previewParams.get('links') === '0') controls.architecturalLinks.checked = false;
if (previewParams.get('links') === '1') controls.architecturalLinks.checked = true;
if (previewParams.get('linkDebug') === '0') controls.linkDebug.checked = false;
if (previewParams.get('linkDebug') === '1') controls.linkDebug.checked = true;
const requestedShadowMode = previewParams.get('shadowMode');
const shadowMode = requestedShadowMode === 'projection' || requestedShadowMode === 'calibrated'
  ? requestedShadowMode
  : 'contact';
const previewPackage = previewParams.get('package');
if (previewPackage && (Object.hasOwn(PACKAGE_URLS, previewPackage) || Object.hasOwn(PACKAGE_PRESETS, previewPackage))) {
  controls.packageSelect.value = previewPackage;
}
const previewScenario = previewParams.get('scenario');
if (previewScenario && Object.hasOwn(SCENARIOS, previewScenario)) {
  controls.scenarioSelect.value = previewScenario;
}
const previewZoom = Number(previewParams.get('zoom'));
if (Number.isFinite(previewZoom) && previewZoom >= Number(controls.zoom.min) && previewZoom <= Number(controls.zoom.max)) {
  controls.zoom.value = String(previewZoom);
  updateZoomLabel();
}
const previewSelectedCell = Number(previewParams.get('selectedCell'));
if (Number.isInteger(previewSelectedCell) && previewSelectedCell >= 0) {
  selectedCellNum = previewSelectedCell;
}

let scenarioComplexes = [];
let memberByCell = new Map();
let variantAssignments = [];
let variantByCell = new Map();
let constructionBuildings = new Map();
let powerStates = new Map();
let removedCells = new Set();
let buildingLevels = new Map();
const previewRemovedParam = previewParams.get('removedCell');
const previewRemovedCell = previewRemovedParam === null ? null : Number(previewRemovedParam);
if (previewRemovedCell !== null && Number.isInteger(previewRemovedCell) && previewRemovedCell >= 0) {
  removedCells.add(previewRemovedCell);
}
const previewDisabledParam = new URLSearchParams(window.location.search).get('disabledCell');
const previewDisabledCell = previewDisabledParam === null ? null : Number(previewDisabledParam);
if (previewDisabledCell !== null && Number.isInteger(previewDisabledCell) && previewDisabledCell >= 0) {
  powerStates.set(previewDisabledCell, 'disabled');
}
const previewUpgradedParam = previewParams.get('upgradedCell');
const previewUpgradedCell = previewUpgradedParam === null ? null : Number(previewUpgradedParam);
if (previewUpgradedCell !== null && Number.isInteger(previewUpgradedCell) && previewUpgradedCell >= 0) {
  buildingLevels.set(previewUpgradedCell, 2);
}

function rebuildVariants() {
  variantAssignments = assignDeterministicVariants(scenarioComplexes, pilotCells, VARIANT_CONTEXT);
  variantByCell = new Map(variantAssignments.map((assignment) => [assignment.cellNum, assignment]));
}

function rebuildScenario() {
  const scenarioDefinition = SCENARIOS[controls.scenarioSelect.value] ?? SCENARIOS.sevenFull;
  const editable = isEditableScenario();
  const baseDefinition = editable
    ? removeBuildings(scenarioDefinition, removedCells)
    : scenarioDefinition;
  const assembledDefinition = editable
    ? appendConstructionBuildings(baseDefinition, constructionBuildings)
    : baseDefinition;
  const poweredDefinition = editable
    ? applyBuildingPower(assembledDefinition, powerStates)
    : assembledDefinition;
  const definition = editable
    ? applyBuildingLevels(poweredDefinition, buildingLevels)
    : poweredDefinition;
  pilotCells = cellsForScenario();
  const buildings = definition.map((entry) => ({
    ...(typeof entry === 'number' ? { cellNum: entry } : entry),
    complexGroup: typeof entry === 'number' ? 'residential' : (entry.complexGroup ?? 'residential'),
    ports: activeVisual?.ports,
  }));
  scenarioComplexes = resolveComplexes(buildings, pilotCells, { location: 'PILOT' });
  memberByCell = new Map(scenarioComplexes.flatMap((complex) => complex.members.map((member) => [member.cellNum, member])));
  rebuildVariants();
  if (!pilotCells.some((cell) => cell.num === selectedCellNum)) {
    selectedCellNum = scenarioComplexes[0]?.cellNums[0] ?? pilotCells[0]?.num ?? null;
  }
}

rebuildScenario();

function hexPathOn(target, x, y, scale) {
  target.beginPath();
  appendHexPathOn(target, x, y, scale);
}

function appendHexPathOn(target, x, y, scale) {
  HEX_POINTS.forEach(([px, py], index) => {
    const sx = x + px * scale;
    const sy = y + py * scale;
    if (index === 0) target.moveTo(sx, sy);
    else target.lineTo(sx, sy);
  });
  target.closePath();
}

function hexPath(x, y, scale) {
  hexPathOn(ctx, x, y, scale);
}

function drawVariantDecor(assignment, width, height) {
  if (!assignment) return;
  ctx.save();
  ctx.lineWidth = 1.4;
  ctx.strokeStyle = 'rgba(215, 250, 255, 0.9)';
  ctx.fillStyle = 'rgba(94, 226, 162, 0.9)';

  if (assignment.garden === 'planter') {
    ctx.beginPath();
    ctx.ellipse(-width * 0.28, -4, 5, 2.4, 0, 0, Math.PI * 2);
    ctx.ellipse(width * 0.28, -4, 5, 2.4, 0, 0, Math.PI * 2);
    ctx.fill();
  } else if (assignment.garden === 'terrace') {
    ctx.beginPath();
    ctx.ellipse(0, -height * 0.32, 9, 3, 0, 0, Math.PI * 2);
    ctx.fill();
  }

  const antennaY = -height * 0.73;
  if (assignment.antenna === 'needle') {
    ctx.beginPath();
    ctx.moveTo(width * 0.18, antennaY);
    ctx.lineTo(width * 0.18, antennaY - 14);
    ctx.stroke();
  } else if (assignment.antenna === 'ring') {
    ctx.beginPath();
    ctx.ellipse(width * 0.18, antennaY - 5, 5, 2.4, 0, 0, Math.PI * 2);
    ctx.stroke();
  } else if (assignment.antenna === 'fork') {
    ctx.beginPath();
    ctx.moveTo(width * 0.18, antennaY);
    ctx.lineTo(width * 0.14, antennaY - 11);
    ctx.moveTo(width * 0.18, antennaY);
    ctx.lineTo(width * 0.24, antennaY - 11);
    ctx.stroke();
  }

  ctx.strokeStyle = 'rgba(255, 219, 123, 0.95)';
  ctx.beginPath();
  if (assignment.entrance === 'round') {
    ctx.arc(0, -height * 0.115, 4.5, Math.PI, 0);
  } else if (assignment.entrance === 'split') {
    ctx.moveTo(-5, -height * 0.06);
    ctx.lineTo(-5, -height * 0.17);
    ctx.moveTo(5, -height * 0.06);
    ctx.lineTo(5, -height * 0.17);
  } else {
    ctx.moveTo(-6, -height * 0.06);
    ctx.quadraticCurveTo(0, -height * 0.2, 6, -height * 0.06);
  }
  ctx.stroke();
  ctx.restore();
}

function drawBuilding(cell, scale, center, member) {
  const anchorX = cell.x + BUILDING_ANCHOR[0];
  const anchorY = cell.y + BUILDING_ANCHOR[1];
  const assignment = variantByCell.get(cell.num);
  const selectedPackage = activePackages.length > 0
    ? activePackages[(assignment?.variant ?? 0) % activePackages.length]
    : null;
  const visual = selectedPackage?.visual;
  const layers = selectedPackage?.layers;

  if (visual && layers) {
    const drawLayer = (name, offset = [0, 0], composite = 'source-over', alpha = 1) => {
      const image = layers[name];
      if (!image) return;
      const width = visual.logicalWidth;
      const heightScale = name === 'shadow' ? 1 : (assignment?.heightScale ?? 1);
      const height = width * image.naturalHeight / image.naturalWidth * heightScale;
      const x = -visual.anchor[0] * width + offset[0];
      const y = -visual.anchor[1] * height + offset[1];
      ctx.save();
      ctx.globalCompositeOperation = composite;
      ctx.globalAlpha = alpha;
      ctx.drawImage(image, x * scale, y * scale, width * scale, height * scale);
      ctx.restore();
    };

    ctx.save();
    ctx.translate(anchorX * scale, anchorY * scale);
    drawLayer('shadow', visual.shadowOffset ?? [0, 0], 'source-over', 0.86);
    drawLayer('color', [0, 0], 'source-over', member?.state === 'disabled' ? 0.56 : 1);
    if (member?.state !== 'disabled') {
      drawLayer('emissive', visual.emissiveOffset ?? [0, 0], 'screen', center ? 0.95 : 0.72);
    }
    const logicalHeight = visual.logicalWidth * layers.color.naturalHeight / layers.color.naturalWidth * (assignment?.heightScale ?? 1);
    if (visual.id.includes('/diagnostic-')) drawVariantDecor(assignment, visual.logicalWidth, logicalHeight);
    ctx.restore();
    return;
  }

  const width = 78;
  const height = cell.id === 0 ? 98 : 72 + (cell.id % 3) * 8;

  ctx.save();
  ctx.translate(anchorX * scale, anchorY * scale);
  const gradient = ctx.createLinearGradient(0, -height * scale, 0, 0);
  gradient.addColorStop(0, center ? '#dff8ff' : '#a9e8ff');
  gradient.addColorStop(0.48, center ? '#55cfff' : '#238bc7');
  gradient.addColorStop(1, '#0b3b68');
  ctx.fillStyle = gradient;
  ctx.strokeStyle = center ? '#effcff' : '#72d6ff';
  ctx.lineWidth = 1.3 * scale;
  ctx.beginPath();
  ctx.moveTo(-width * 0.26 * scale, 0);
  ctx.lineTo(-width * 0.42 * scale, -height * 0.18 * scale);
  ctx.lineTo(-width * 0.22 * scale, -height * 0.72 * scale);
  ctx.lineTo(0, -height * scale);
  ctx.lineTo(width * 0.22 * scale, -height * 0.72 * scale);
  ctx.lineTo(width * 0.42 * scale, -height * 0.18 * scale);
  ctx.lineTo(width * 0.26 * scale, 0);
  ctx.closePath();
  ctx.fill();
  ctx.stroke();
  ctx.restore();
}

function drawFusion(fusion) {
  const selectedPackage = activeFusionPackages.get(fusion.kind);
  const visual = selectedPackage?.visual;
  const layers = selectedPackage?.layers;
  if (!visual || !layers?.color) return;
  const drawLayer = (name, offset = [0, 0], composite = 'source-over', alpha = 1) => {
    const image = layers[name];
    if (!image) return;
    const width = visual.logicalWidth;
    const height = width * image.naturalHeight / image.naturalWidth;
    ctx.save();
    ctx.globalCompositeOperation = composite;
    ctx.globalAlpha = alpha;
    ctx.drawImage(
      image,
      -visual.anchor[0] * width + offset[0],
      -visual.anchor[1] * height + offset[1],
      width,
      height,
    );
    ctx.restore();
  };

  ctx.save();
  ctx.translate(...fusion.anchor);
  drawLayer('shadow', visual.shadowOffset ?? [0, 0], 'source-over', 0.86);
  drawLayer('color');
  drawLayer('emissive', visual.emissiveOffset ?? [0, 0], 'screen', 0.84);
  ctx.restore();
}

function resolveArtItem(item) {
  if (item.kind === 'fusion') {
    const selectedPackage = activeFusionPackages.get(item.fusion.kind);
    if (!selectedPackage) return null;
    return {
      ...selectedPackage,
      anchor: [
        item.fusion.anchor[0] + ART_PLACEMENT_OFFSET[0],
        item.fusion.anchor[1] + ART_PLACEMENT_OFFSET[1],
      ],
      heightScale: 1,
      state: 'working',
      level: 1,
      tone: item.fusion.kind === 'three' ? 1 : 2,
    };
  }

  const assignment = variantByCell.get(item.cell.num);
  const member = memberByCell.get(item.cell.num);
  const packageVariant = member?.buildingVariant ?? assignment?.variant ?? 0;
  const selectedPackage = activePackages.length > 0
    ? activePackages[packageVariant % activePackages.length]
    : null;
  if (!selectedPackage) return null;
  return {
    ...selectedPackage,
    anchor: [
      item.cell.x + BUILDING_ANCHOR[0] + ART_PLACEMENT_OFFSET[0],
      item.cell.y + BUILDING_ANCHOR[1] + ART_PLACEMENT_OFFSET[1],
    ],
    heightScale: assignment?.heightScale ?? 1,
    state: member?.state ?? 'working',
    level: member?.level ?? 1,
    tone: packageVariant,
  };
}

function drawArtItemLayer(item, name, options = {}) {
  const descriptor = resolveArtItem(item);
  const image = descriptor?.layers[name];
  if (!descriptor || !image) return;
  const { visual } = descriptor;
  const levelScale = descriptor.level > 1 ? 1 + (descriptor.level - 1) * 0.035 : 1;
  const width = visual.logicalWidth * levelScale;
  const heightScale = name === 'shadow' || name === 'construction' ? 1 : descriptor.heightScale;
  const height = width * image.naturalHeight / image.naturalWidth * heightScale;
  const layerAnchor = name === 'construction' ? [0.5, 0.95] : visual.anchor;
  const manifestOffset = name === 'shadow'
    ? (visual.shadowOffset ?? [0, 0])
    : name === 'emissive' ? (visual.emissiveOffset ?? [0, 0]) : [0, 0];
  const screenOffset = options.screenOffset ?? [0, 0];
  const toneFilters = [
    'brightness(0.97) saturate(0.94) contrast(1.03) hue-rotate(-3deg)',
    'brightness(1.035) saturate(0.88) contrast(1.02) sepia(0.055)',
    'brightness(0.95) saturate(1.02) contrast(1.045) sepia(0.018)',
  ];
  const fusionToneFilter = item.kind === 'fusion'
    ? item.fusion.kind === 'seven'
      ? 'brightness(0.93) saturate(0.88) contrast(1.07) hue-rotate(-4deg)'
      : 'brightness(1.01) saturate(0.92) contrast(1.035) sepia(0.035)'
    : null;

  const target = options.targetCtx ?? ctx;
  let drawable = image;
  if (
    descriptor.visual.id === 'tech/residential/voraner-v02-simple'
    && (name === 'color' || name === 'emissive')
  ) {
    let masked = v02RinglessLayerCache.get(image);
    if (!masked) {
      const sourceWidth = image.naturalWidth;
      const sourceHeight = image.naturalHeight;
      masked = document.createElement('canvas');
      masked.width = sourceWidth;
      masked.height = sourceHeight;
      const maskedCtx = masked.getContext('2d');
      maskedCtx.drawImage(image, 0, 0);

      // The source art contains an entire cyan holographic ornament above the
      // rooftop basin. Remove its cyan/white pixels from the derived layer,
      // while keeping the gold basin, dome and original source files intact.
      const maskLeft = Math.floor(sourceWidth * 0.49);
      const maskTop = Math.floor(sourceHeight * 0.275);
      const maskRight = Math.ceil(sourceWidth * 0.60);
      const maskBottom = Math.ceil(sourceHeight * 0.445);
      const maskWidth = maskRight - maskLeft;
      const maskHeight = maskBottom - maskTop;
      const pixels = maskedCtx.getImageData(maskLeft, maskTop, maskWidth, maskHeight);
      for (let pixelIndex = 0; pixelIndex < pixels.data.length; pixelIndex += 4) {
        const red = pixels.data[pixelIndex];
        const green = pixels.data[pixelIndex + 1];
        const blue = pixels.data[pixelIndex + 2];
        const alpha = pixels.data[pixelIndex + 3];
        const isCyanEnergy = alpha > 0
          && blue > 145
          && green > 90
          && blue - red > 18
          && blue > red * 1.04;
        if (isCyanEnergy) pixels.data[pixelIndex + 3] = 0;
      }
      maskedCtx.putImageData(pixels, maskLeft, maskTop);
      v02RinglessLayerCache.set(image, masked);
    }
    drawable = masked;
  }
  target.save();
  target.translate(descriptor.anchor[0] + screenOffset[0], descriptor.anchor[1] + screenOffset[1]);
  if (options.matrix) target.transform(...options.matrix);
  if (options.localClip) {
    const [clipX, clipY, clipWidth, clipHeight] = options.localClip;
    const imageLeft = -layerAnchor[0] * width + manifestOffset[0];
    const imageTop = -layerAnchor[1] * height + manifestOffset[1];
    target.beginPath();
    target.rect(
      imageLeft + clipX * width,
      imageTop + clipY * height,
      clipWidth * width,
      clipHeight * height,
    );
    target.clip();
  }
  target.globalCompositeOperation = options.composite ?? 'source-over';
  target.globalAlpha = options.alpha ?? 1;
  target.filter = options.filter ?? (
    name === 'color'
      ? fusionToneFilter ?? toneFilters[descriptor.tone % toneFilters.length]
      : 'none'
  );
  target.drawImage(
    drawable,
    -layerAnchor[0] * width + manifestOffset[0],
    -layerAnchor[1] * height + manifestOffset[1],
    width,
    height,
  );
  target.restore();
}

function motionSeedFor(item) {
  return item.kind === 'fusion'
    ? item.fusion.cellNums.reduce((sum, cellNum) => sum + cellNum, 0) + item.fusion.cellNums.length * 3
    : item.cellNum * 1.73;
}

function drawWaterSurfaceEffects(item, timeMs) {
  const descriptor = resolveArtItem(item);
  const surfaces = WATER_SURFACES[descriptor?.visual.id];
  const image = descriptor?.layers.color;
  if (!descriptor || !image || !surfaces?.length || descriptor.state !== 'working') return;

  const levelScale = descriptor.level > 1 ? 1 + (descriptor.level - 1) * 0.035 : 1;
  const width = descriptor.visual.logicalWidth * levelScale;
  const height = width * image.naturalHeight / image.naturalWidth * descriptor.heightScale;
  const imageLeft = descriptor.anchor[0] - descriptor.visual.anchor[0] * width;
  const imageTop = descriptor.anchor[1] - descriptor.visual.anchor[1] * height;
  const seconds = timeMs / 1000;
  const seed = motionSeedFor(item);
  const intensity = waterEffectIntensity();
  const rings = ringTuning();
  const ripples = rippleTuning();

  for (const [surfaceIndex, surface] of surfaces.entries()) {
    const centerX = imageLeft + surface.x * width;
    const centerY = imageTop + surface.y * height;
    const radiusX = surface.rx * width;
    const radiusY = surface.ry * height;
    const rippleCenterX = centerX + ripples.offsetX;
    const rippleCenterY = centerY + ripples.offsetY;
    const rippleRadiusX = radiusX * ripples.scaleX;
    const rippleRadiusY = radiusY * ripples.scaleY;
    const phase = seconds * 0.23 + seed * 0.17 + surfaceIndex * 0.31;

    if (surface.caustics !== false) {
      effectsCtx.save();
      effectsCtx.beginPath();
      effectsCtx.ellipse(rippleCenterX, rippleCenterY, rippleRadiusX, rippleRadiusY, 0, 0, Math.PI * 2);
      effectsCtx.clip();
      effectsCtx.lineCap = 'round';

      // Dark troughs make the movement readable against already bright water.
      // They travel with the highlights, but remain broad and soft enough to
      // avoid looking like animated scratches at the normal game scale.
      effectsCtx.globalCompositeOperation = 'source-over';
      for (let lineIndex = 0; lineIndex < 4; lineIndex += 1) {
        const progress = (phase * 0.91 + lineIndex / 4) % 1;
        const y = rippleCenterY + (progress * 2 - 1) * rippleRadiusY;
        const bend = Math.cos(phase * Math.PI * 2 + lineIndex * 1.35) * rippleRadiusY * 0.32;
        const alpha = Math.min(0.34, (0.09 + (1 - Math.abs(progress * 2 - 1)) * 0.08) * intensity);
        effectsCtx.strokeStyle = `rgba(2, 45, 78, ${alpha})`;
        effectsCtx.lineWidth = 0.82;
        effectsCtx.beginPath();
        effectsCtx.moveTo(rippleCenterX - rippleRadiusX, y);
        effectsCtx.quadraticCurveTo(rippleCenterX, y - bend, rippleCenterX + rippleRadiusX, y + bend * 0.4);
        effectsCtx.stroke();
      }

      effectsCtx.globalCompositeOperation = 'screen';
      // Two families of caustic strokes drift in opposite directions. They are
      // clipped to the authored basin and therefore never cross a stone edge.
      for (let lineIndex = 0; lineIndex < 5; lineIndex += 1) {
        const progress = (phase + lineIndex / 5) % 1;
        const y = rippleCenterY + (progress * 2 - 1) * rippleRadiusY;
        const bend = Math.sin(phase * Math.PI * 2 + lineIndex * 1.7) * rippleRadiusY * 0.42;
        const alpha = Math.min(0.58, (0.13 + (1 - Math.abs(progress * 2 - 1)) * 0.14) * intensity);
        effectsCtx.strokeStyle = `rgba(143, 239, 255, ${alpha})`;
        effectsCtx.lineWidth = 0.5;
        effectsCtx.beginPath();
        effectsCtx.moveTo(rippleCenterX - rippleRadiusX, y);
        effectsCtx.quadraticCurveTo(rippleCenterX, y + bend, rippleCenterX + rippleRadiusX, y - bend * 0.35);
        effectsCtx.stroke();
      }
      for (let lineIndex = 0; lineIndex < 3; lineIndex += 1) {
        const rawProgress = 1 - phase * 0.73 + lineIndex / 3;
        const progress = ((rawProgress % 1) + 1) % 1;
        const x = rippleCenterX + (progress * 2 - 1) * rippleRadiusX;
        effectsCtx.strokeStyle = `rgba(78, 194, 255, ${Math.min(0.42, 0.16 * intensity)})`;
        effectsCtx.lineWidth = 0.38;
        effectsCtx.beginPath();
        effectsCtx.moveTo(x, rippleCenterY - rippleRadiusY);
        effectsCtx.quadraticCurveTo(
          x + rippleRadiusX * 0.12,
          rippleCenterY,
          x - rippleRadiusX * 0.08,
          rippleCenterY + rippleRadiusY,
        );
        effectsCtx.stroke();
      }
      effectsCtx.restore();
    }

    if (surface.rings !== false) {
      // Expanding rings make the pool readable as water even at game scale.
      effectsCtx.save();
      effectsCtx.globalCompositeOperation = 'screen';
      const ringCenterX = centerX + rings.offsetX;
      const ringCenterY = centerY + rings.offsetY;
      const ringRadiusX = radiusX * rings.scaleX;
      const ringRadiusY = radiusY * rings.scaleY;
      for (let ringIndex = 0; ringIndex < 3; ringIndex += 1) {
        const progress = (phase * 0.58 + ringIndex / 3) % 1;
        effectsCtx.strokeStyle = `rgba(177, 246, 255, ${Math.min(0.64, (1 - progress) * 0.34 * intensity)})`;
        effectsCtx.lineWidth = 0.62 - progress * 0.2;
        effectsCtx.beginPath();
        effectsCtx.ellipse(
          ringCenterX,
          ringCenterY,
          Math.max(0.2, ringRadiusX * progress),
          Math.max(0.1, ringRadiusY * progress),
          0,
          0,
          Math.PI * 2,
        );
        effectsCtx.stroke();
      }
      effectsCtx.restore();
    }

    if (surface.jet) {
      const jetHeight = surface.jet * height;
      effectsCtx.save();
      effectsCtx.globalCompositeOperation = 'screen';
      effectsCtx.lineCap = 'round';
      const sway = Math.sin(seconds * 0.9 + seed + surfaceIndex * 0.7) * radiusX * 0.055;
      const topX = centerX + sway;
      const topY = centerY - jetHeight;
      const jetGradient = effectsCtx.createLinearGradient(topX, topY, centerX, centerY);
      jetGradient.addColorStop(0, `rgba(156, 232, 255, ${Math.min(0.36, 0.14 * intensity)})`);
      jetGradient.addColorStop(0.55, `rgba(92, 211, 255, ${Math.min(0.58, 0.25 * intensity)})`);
      jetGradient.addColorStop(1, `rgba(190, 249, 255, ${Math.min(0.72, 0.34 * intensity)})`);

      // One uninterrupted water column keeps every animated highlight visually
      // connected to the basin instead of leaving bright dashes in mid-air.
      effectsCtx.strokeStyle = jetGradient;
      effectsCtx.lineWidth = 0.72;
      effectsCtx.beginPath();
      effectsCtx.moveTo(centerX, centerY);
      effectsCtx.quadraticCurveTo(centerX - sway * 0.5, centerY - jetHeight * 0.48, topX, topY);
      effectsCtx.stroke();

      effectsCtx.strokeStyle = `rgba(218, 252, 255, ${Math.min(0.66, (0.2 + Math.sin(seconds * 1.4 + seed) * 0.04) * intensity)})`;
      effectsCtx.lineWidth = 0.24;
      effectsCtx.beginPath();
      effectsCtx.moveTo(centerX + 0.12, centerY);
      effectsCtx.quadraticCurveTo(centerX - sway * 0.34, centerY - jetHeight * 0.5, topX + 0.08, topY);
      effectsCtx.stroke();
      effectsCtx.restore();
    }
  }
}

function drawDynamicEffects(timeMs = sceneAnimationTimeMs) {
  effectsCtx.setTransform(1, 0, 0, 1, 0, 0);
  effectsCtx.clearRect(0, 0, effectsCanvas.width, effectsCanvas.height);
  if (!latestEffectsScene || !sceneAnimationAllowed()) return;

  const { renderItems, scale, dpr } = latestEffectsScene;
  effectsCtx.setTransform(dpr * scale, 0, 0, dpr * scale, 0, 0);
  for (const item of renderItems) {
    const descriptor = resolveArtItem(item);
    if (descriptor?.state !== 'working') continue;
    const phase = timeMs / 1000 * 0.68 + motionSeedFor(item);
    drawArtItemLayer(item, 'emissive', {
      targetCtx: effectsCtx,
      alpha: 0.045 + (Math.sin(phase) + 1) * 0.027,
      composite: 'screen',
      filter: 'brightness(1.18) blur(0.32px)',
    });
    drawWaterSurfaceEffects(item, timeMs);
  }
}

function calibratedShadowFor(image, anchorY, logicalWidth) {
  const tuning = shadowTuning();
  let byAnchor = calibratedShadowCache.get(image);
  if (!byAnchor) {
    byAnchor = new Map();
    calibratedShadowCache.set(image, byAnchor);
  }
  const cacheKey = [
    anchorY,
    logicalWidth,
    tuning.azimuth,
    tuning.elevation,
    tuning.compression,
    tuning.alphaThreshold,
    tuning.columnStep,
    tuning.rayWidth,
    tuning.shadowJoin,
    tuning.baselineOffset,
  ].join(':');
  if (byAnchor.has(cacheKey)) return byAnchor.get(cacheKey);

  const source = document.createElement('canvas');
  source.width = image.naturalWidth;
  source.height = image.naturalHeight;
  const sourceCtx = source.getContext('2d', { willReadFrequently: true });
  sourceCtx.drawImage(image, 0, 0);
  const rgba = sourceCtx.getImageData(0, 0, source.width, source.height).data;
  const alpha = new Uint8ClampedArray(source.width * source.height);
  for (let index = 0; index < alpha.length; index += 1) alpha[index] = rgba[index * 4 + 3];

  const segments = extractSilhouetteShadowSegments({
    alpha,
    width: source.width,
    height: source.height,
    anchorY: anchorY + tuning.baselineOffset,
    threshold: tuning.alphaThreshold,
    direction: tuning.direction,
    lengthPerScreenHeight: tuning.lengthPerScreenHeight,
    sampleStep: tuning.columnStep,
  });
  const shadow = document.createElement('canvas');
  shadow.width = source.width;
  shadow.height = source.height;
  const shadowCtx = shadow.getContext('2d');
  shadowCtx.strokeStyle = 'rgba(0, 0, 0, 0.88)';
  shadowCtx.lineWidth = resolveSilhouetteStrokeWidth({
    rayWidth: tuning.rayWidth,
    joinRadius: tuning.shadowJoin,
    sourceWidth: source.width,
    logicalWidth,
  });
  shadowCtx.lineCap = tuning.shadowJoin > 0 ? 'round' : 'butt';
  shadowCtx.lineJoin = 'round';
  shadowCtx.beginPath();
  segments.forEach(({ start, end }) => {
    shadowCtx.moveTo(start[0], start[1]);
    shadowCtx.lineTo(end[0], end[1]);
  });
  shadowCtx.stroke();
  byAnchor.set(cacheKey, shadow);
  return shadow;
}

function drawCalibratedShadowLayer(item, options = {}) {
  const descriptor = resolveArtItem(item);
  if (!descriptor) return;
  const usesConstructionLayer = descriptor.state === 'construction' && descriptor.layers.construction;
  const image = usesConstructionLayer ? descriptor.layers.construction : descriptor.layers.color;
  if (!image) return;
  const { visual } = descriptor;
  const levelScale = descriptor.level > 1 ? 1 + (descriptor.level - 1) * 0.035 : 1;
  const width = visual.logicalWidth * levelScale;
  const heightScale = usesConstructionLayer ? 1 : descriptor.heightScale;
  const layerAnchor = usesConstructionLayer ? [0.5, 0.95] : visual.anchor;
  const height = width * image.naturalHeight / image.naturalWidth * heightScale;
  const shadow = calibratedShadowFor(image, layerAnchor[1], width);
  const tuning = shadowTuning();
  const target = options.targetCtx ?? ctx;
  target.save();
  target.translate(...descriptor.anchor);
  target.globalCompositeOperation = options.composite ?? 'multiply';
  target.globalAlpha = options.alpha ?? tuning.mathOpacity;
  target.filter = options.filter ?? (tuning.blur > 0 ? `blur(${tuning.blur}px)` : 'none');
  target.drawImage(
    shadow,
    -layerAnchor[0] * width,
    -layerAnchor[1] * height,
    width,
    height,
  );
  target.restore();
}

function drawCalibratedGroundShadow(item) {
  drawCalibratedShadowLayer(item);
}

function drawContactOcclusion(item) {
  const descriptor = resolveArtItem(item);
  if (!descriptor) return;
  const levelScale = descriptor.level > 1 ? 1 + (descriptor.level - 1) * 0.035 : 1;
  const width = descriptor.visual.logicalWidth * levelScale;
  const radiusX = width * (item.kind === 'fusion' ? 0.42 : 0.39);
  const radiusY = Math.max(1.8, width * 0.052);
  ctx.save();
  ctx.translate(descriptor.anchor[0] - width * 0.018, descriptor.anchor[1] - radiusY * 0.56);
  ctx.globalCompositeOperation = 'multiply';
  ctx.filter = 'blur(1.2px)';
  const gradient = ctx.createRadialGradient(0, 0, radiusX * 0.08, 0, 0, radiusX);
  gradient.addColorStop(0, 'rgba(10, 16, 18, 0.22)');
  gradient.addColorStop(0.64, 'rgba(10, 16, 18, 0.12)');
  gradient.addColorStop(1, 'rgba(10, 16, 18, 0)');
  ctx.fillStyle = gradient;
  ctx.beginPath();
  ctx.ellipse(0, 0, radiusX, radiusY, -0.07, 0, Math.PI * 2);
  ctx.fill();
  ctx.restore();
}

function drawDisabledArtItem(item) {
  drawArtItemLayer(item, 'color', {
    alpha: 0.42,
    composite: 'screen',
    filter: 'brightness(0) saturate(100%) invert(83%) sepia(20%) saturate(1250%) hue-rotate(153deg) brightness(1.08) blur(0.9px)',
  });
  drawArtItemLayer(item, 'color', {
    filter: 'brightness(0.68) saturate(0.24) contrast(1.08) sepia(0.08) hue-rotate(155deg)',
  });
}

function drawGroundShadow(item) {
  const metrics = shadowMetrics(item);
  if (!metrics) return;
  const { descriptor, width, height } = metrics;
  if (controls.packageSelect.value === 'shadowLab') {
    const tuning = shadowTuning();
    const isPhysicalProxy = descriptor.visual.id.includes('shadow-proxy');
    if (isPhysicalProxy && controls.physicalShadow.checked && descriptor.layers.castShadow) {
      drawArtItemLayer(item, 'castShadow', {
        composite: 'multiply',
        alpha: tuning.physicalOpacity,
        filter: tuning.blur > 0 ? `blur(${tuning.blur}px)` : 'none',
      });
    } else if (!isPhysicalProxy && controls.mathShadow.checked) {
      drawCalibratedGroundShadow(item);
    }
    return;
  }
  if (usesCalibratedMathShadows()) {
    drawCalibratedGroundShadow(item);
    return;
  }
  if (shadowMode !== 'projection' && descriptor.layers.castShadow) {
    drawArtItemLayer(item, 'castShadow', {
      composite: 'multiply',
      alpha: 0.82,
      filter: 'none',
    });
    return;
  }
  if (shadowMode === 'projection') {
    const projection = shadowProjectionFor(metrics);
    const footprint = buildGroundShadowFootprint({
      anchor: descriptor.anchor,
      width,
      height: height * metrics.groundCastHeightRatio,
      widthMultiplier: metrics.widthMultiplier,
    });
    ctx.save();
    ctx.globalCompositeOperation = 'multiply';
    ctx.filter = 'blur(2.2px)';
    ctx.fillStyle = 'rgba(3, 11, 17, 0.16)';
    ctx.beginPath();
    ctx.moveTo(...footprint.points[0]);
    footprint.points.slice(1).forEach((point) => ctx.lineTo(...point));
    ctx.closePath();
    ctx.fill();
    ctx.restore();
    drawArtItemLayer(item, 'color', {
      composite: 'multiply',
      alpha: 0.19,
      filter: 'brightness(0) saturate(0) blur(1.35px)',
      matrix: projection.matrix,
    });
    if (item.kind === 'fusion' && item.fusion.kind === 'seven') {
      drawArtItemLayer(item, 'color', {
        composite: 'multiply',
        alpha: 0.19,
        filter: 'brightness(0) saturate(0) blur(1.15px)',
        matrix: buildGroundShadowProjection().matrix,
        localClip: SEVEN_COMPLEX_TOWER_CLIP,
      });
    }
  }
  ctx.save();
  ctx.translate(...descriptor.anchor);
  ctx.globalCompositeOperation = 'multiply';
  ctx.filter = 'blur(1.4px)';
  ctx.fillStyle = 'rgba(3, 11, 17, 0.16)';
  ctx.beginPath();
  ctx.ellipse(
    -width * 0.025,
    -height * 0.012,
    width * 0.48 * metrics.widthMultiplier,
    Math.max(2.2, width * 0.065),
    -0.08,
    0,
    Math.PI * 2,
  );
  ctx.fill();
  ctx.restore();
}

function drawPhysicalFacadeShadowLab(renderItems, scale, dpr) {
  if (controls.packageSelect.value !== 'shadowLab' || !controls.facadeShadow.checked) return;
  const tuning = shadowTuning();
  const descriptors = renderItems
    .map((item) => ({ item, descriptor: resolveArtItem(item) }))
    .filter(({ descriptor }) => descriptor && descriptor.state !== 'construction');
  const casters = descriptors.filter(({ descriptor }) => (
    descriptor.visual.id.includes('shadow-proxy') && descriptor.layers.castShadow
  ));
  const receivers = descriptors.filter(({ descriptor }) => !descriptor.visual.id.includes('shadow-proxy'));
  if (casters.length === 0 || receivers.length === 0) return;

  const shadowCtx = resetLayerCanvas(castShadowCanvas);
  const maskCtx = resetLayerCanvas(receiverMaskCanvas);
  shadowCtx.setTransform(dpr * scale, 0, 0, dpr * scale, 0, 0);
  maskCtx.setTransform(dpr * scale, 0, 0, dpr * scale, 0, 0);
  casters.forEach(({ item }) => drawArtItemLayer(item, 'castShadow', {
    targetCtx: shadowCtx,
    filter: 'none',
  }));
  receivers.forEach(({ item }) => drawArtItemLayer(item, 'color', {
    targetCtx: maskCtx,
    filter: 'brightness(0) saturate(0)',
  }));

  shadowCtx.setTransform(1, 0, 0, 1, 0, 0);
  shadowCtx.globalCompositeOperation = 'destination-in';
  shadowCtx.drawImage(receiverMaskCanvas, 0, 0);
  ctx.save();
  ctx.setTransform(1, 0, 0, 1, 0, 0);
  ctx.globalCompositeOperation = 'multiply';
  ctx.globalAlpha = tuning.facadeOpacity;
  ctx.filter = tuning.blur > 0 ? `blur(${tuning.blur}px)` : 'none';
  ctx.drawImage(castShadowCanvas, 0, 0);
  ctx.restore();
}

function drawMathematicalFacadeShadows(renderItems, scale, dpr) {
  if (
    !usesCalibratedMathShadows()
    || !controls.facadeShadow.checked
  ) return;
  const tuning = shadowTuning();
  const descriptors = renderItems
    .map((item) => ({ item, descriptor: resolveArtItem(item) }))
    .filter(({ descriptor }) => descriptor && descriptor.state !== 'construction');
  const casters = descriptors.filter(({ descriptor }) => !descriptor.visual.id.includes('shadow-proxy'));

  for (const caster of casters) {
    const receivers = descriptors.filter(({ item }) => item !== caster.item);
    if (receivers.length === 0) continue;
    const shadowCtx = resetLayerCanvas(castShadowCanvas);
    const maskCtx = resetLayerCanvas(receiverMaskCanvas);
    shadowCtx.setTransform(dpr * scale, 0, 0, dpr * scale, 0, 0);
    maskCtx.setTransform(dpr * scale, 0, 0, dpr * scale, 0, 0);
    drawCalibratedShadowLayer(caster.item, {
      targetCtx: shadowCtx,
      composite: 'source-over',
      alpha: 1,
      filter: 'none',
    });
    receivers.forEach(({ item }) => drawArtItemLayer(item, 'color', {
      targetCtx: maskCtx,
      filter: 'brightness(0) saturate(0)',
    }));

    shadowCtx.setTransform(1, 0, 0, 1, 0, 0);
    shadowCtx.globalCompositeOperation = 'destination-in';
    shadowCtx.drawImage(receiverMaskCanvas, 0, 0);
    ctx.save();
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.globalCompositeOperation = 'multiply';
    ctx.globalAlpha = tuning.mathFacadeOpacity;
    ctx.filter = tuning.blur > 0 ? `blur(${tuning.blur}px)` : 'none';
    ctx.drawImage(castShadowCanvas, 0, 0);
    ctx.restore();
  }
}

function shadowMetrics(item) {
  const descriptor = resolveArtItem(item);
  if (!descriptor) return null;
  const usesConstructionLayer = descriptor.state === 'construction' && descriptor.layers.construction;
  const image = usesConstructionLayer ? descriptor.layers.construction : descriptor.layers.color;
  if (!image) return null;
  const levelScale = descriptor.level > 1 ? 1 + (descriptor.level - 1) * 0.035 : 1;
  const width = descriptor.visual.logicalWidth * levelScale;
  const occupiedCellCount = item.kind === 'fusion' ? item.fusion.cellNums.length : 1;
  const widthMultiplier = 1 + (Math.min(occupiedCellCount, 7) - 1) * 0.05;
  const groundCastHeightRatio = resolveGroundCastHeightRatio(occupiedCellCount);
  return {
    item,
    descriptor,
    anchor: descriptor.anchor,
    width,
    occupiedCellCount,
    widthMultiplier,
    groundCastHeightRatio,
    height: width * image.naturalHeight / image.naturalWidth * (usesConstructionLayer ? 1 : descriptor.heightScale),
  };
}

function shadowProjectionFor(metrics) {
  return buildGroundShadowProjection({
    lengthMultiplier: metrics?.groundCastHeightRatio ?? 1,
  });
}

function resetLayerCanvas(targetCanvas) {
  if (targetCanvas.width !== canvas.width) targetCanvas.width = canvas.width;
  if (targetCanvas.height !== canvas.height) targetCanvas.height = canvas.height;
  const targetCtx = targetCanvas.getContext('2d');
  targetCtx.setTransform(1, 0, 0, 1, 0, 0);
  targetCtx.globalCompositeOperation = 'source-over';
  targetCtx.globalAlpha = 1;
  targetCtx.filter = 'none';
  targetCtx.clearRect(0, 0, targetCanvas.width, targetCanvas.height);
  return targetCtx;
}

function drawFacadeCastShadows(renderItems, scale, dpr) {
  if (shadowMode !== 'projection') return;
  const metrics = renderItems.map(shadowMetrics).filter(Boolean);
  for (const caster of metrics) {
    if (caster.descriptor.state === 'construction') continue;
    const receivers = metrics.filter((receiver) => (
      receiver.descriptor.state !== 'construction'
      && canReceiveProjectedShadow(
        { ...caster, width: caster.width * caster.widthMultiplier },
        receiver,
      )
    ));
    if (receivers.length === 0) continue;

    const shadowCtx = resetLayerCanvas(castShadowCanvas);
    const maskCtx = resetLayerCanvas(receiverMaskCanvas);
    shadowCtx.setTransform(dpr * scale, 0, 0, dpr * scale, 0, 0);
    maskCtx.setTransform(dpr * scale, 0, 0, dpr * scale, 0, 0);
    const projection = shadowProjectionFor(caster);
    drawArtItemLayer(caster.item, 'color', {
      targetCtx: shadowCtx,
      filter: 'brightness(0) saturate(0) blur(0.7px)',
      matrix: projection.matrix,
    });
    receivers.forEach((receiver) => drawArtItemLayer(receiver.item, 'color', {
      targetCtx: maskCtx,
      filter: 'brightness(0) saturate(0)',
    }));

    shadowCtx.setTransform(1, 0, 0, 1, 0, 0);
    shadowCtx.globalCompositeOperation = 'destination-in';
    shadowCtx.drawImage(receiverMaskCanvas, 0, 0);
    ctx.save();
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.globalCompositeOperation = 'multiply';
    ctx.globalAlpha = 0.13;
    ctx.filter = 'blur(0.55px)';
    ctx.drawImage(castShadowCanvas, 0, 0);
    ctx.restore();
  }
}

function drawArtRenderQueue(renderItems, cells, scale, dpr, options = {}) {
  // Each ground shadow is a projection of the actual building alpha mask.
  // Legacy shadow PNGs and facade-to-facade masks remain excluded.
  if (!options.skipShadows) {
    ctx.save();
    ctx.beginPath();
    cells.forEach((cell) => appendHexPathOn(ctx, cell.x, cell.y, 1));
    ctx.clip();
    renderItems.forEach(drawGroundShadow);
    renderItems.forEach(drawContactOcclusion);
    ctx.restore();
  }
  renderItems.forEach((item) => {
    const descriptor = resolveArtItem(item);
    const state = descriptor?.state;
    if (state === 'construction' && descriptor?.layers.construction) {
      drawArtItemLayer(item, 'construction');
    } else if (state === 'disabled') {
      drawDisabledArtItem(item);
    } else {
      drawArtItemLayer(item, 'color', { alpha: state === 'construction' ? 0.42 : 1 });
    }
  });
  if (!options.skipShadows) {
    drawFacadeCastShadows(renderItems, scale, dpr);
    drawMathematicalFacadeShadows(renderItems, scale, dpr);
    drawPhysicalFacadeShadowLab(renderItems, scale, dpr);
  }
  renderItems.forEach((item) => {
    const state = resolveArtItem(item)?.state;
    if (state === 'working') drawArtItemLayer(item, 'emissive', { alpha: 0.7, composite: 'screen' });
  });
  renderItems
    .filter((item) => {
      const descriptor = resolveArtItem(item);
      return descriptor?.state === 'construction' && !descriptor.layers.construction;
    })
    .forEach(drawConstructionMarker);
}

function drawConstructionMarker(item) {
  const descriptor = resolveArtItem(item);
  if (!descriptor) return;
  const width = descriptor.visual.logicalWidth;
  ctx.save();
  ctx.translate(...descriptor.anchor);
  ctx.setLineDash([3, 2]);
  ctx.strokeStyle = 'rgba(255, 205, 92, 0.96)';
  ctx.lineWidth = 1.2;
  ctx.beginPath();
  ctx.ellipse(0, -5, width * 0.48, width * 0.19, 0, 0, Math.PI * 2);
  ctx.stroke();
  ctx.setLineDash([]);
  ctx.strokeStyle = 'rgba(91, 226, 255, 0.9)';
  ctx.beginPath();
  ctx.moveTo(-width * 0.38, -4);
  ctx.lineTo(-width * 0.2, -width * 0.62);
  ctx.lineTo(width * 0.2, -width * 0.62);
  ctx.lineTo(width * 0.38, -4);
  ctx.stroke();
  ctx.restore();
}

function drawConnector(connector, theme) {
  ctx.save();
  ctx.lineCap = 'round';
  ctx.strokeStyle = theme.connector.outerColor;
  ctx.lineWidth = theme.connector.outerWidth;
  ctx.beginPath();
  ctx.moveTo(...connector.from);
  ctx.lineTo(...connector.to);
  ctx.stroke();
  ctx.strokeStyle = theme.connector.innerColor;
  ctx.lineWidth = theme.connector.innerWidth;
  ctx.stroke();
  ctx.restore();
}

function appendArchitecturalPath(points) {
  ctx.beginPath();
  ctx.moveTo(...points[0]);
  for (const point of points.slice(1)) ctx.lineTo(...point);
}

function drawArchitecturalLinkLayer(links) {
  ctx.save();
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
  for (const link of links) {
    const stroke = (color, width) => {
      appendArchitecturalPath(link.points);
      ctx.strokeStyle = color;
      ctx.lineWidth = width;
      ctx.stroke();
    };

    // Narrow pedestrian route. Its path starts at a visible entrance and is
    // allowed through empty cells only; it remains visual ground detail.
    stroke('rgba(4, 15, 23, 0.72)', 3.1);
    stroke('rgba(119, 88, 47, 0.86)', 2.45);
    stroke('rgba(211, 210, 194, 0.84)', 1.72);
    stroke('rgba(246, 239, 217, 0.56)', 0.72);
    stroke('rgba(48, 193, 226, 0.7)', 0.3);

    for (const [index, endpoint] of [link.from, link.to].entries()) {
      const neighbor = index === 0 ? link.points[1] : link.points.at(-2);
      const angle = Math.atan2(neighbor[1] - endpoint[1], neighbor[0] - endpoint[0]);
      ctx.beginPath();
      ctx.ellipse(endpoint[0], endpoint[1], 1.75, 0.95, angle, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(216, 213, 195, 0.88)';
      ctx.fill();
      ctx.strokeStyle = 'rgba(130, 96, 51, 0.9)';
      ctx.lineWidth = 0.4;
      ctx.stroke();
    }
  }
  ctx.restore();
}

function drawArchitecturalLinkDiagnostics(links) {
  ctx.save();
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
  ctx.setLineDash([3, 2]);
  for (const link of links) {
    ctx.beginPath();
    ctx.moveTo(...link.points[0]);
    for (const point of link.points.slice(1)) ctx.lineTo(...point);
    ctx.strokeStyle = 'rgba(55, 226, 255, 0.96)';
    ctx.lineWidth = 1.15;
    ctx.stroke();
    ctx.setLineDash([]);
    for (const [point, side] of [[link.from, link.fromSide], [link.to, link.toSide]]) {
      ctx.fillStyle = side === 'front' ? 'rgba(102, 255, 170, 0.98)' : 'rgba(201, 138, 255, 0.98)';
      ctx.beginPath();
      ctx.arc(point[0], point[1], 1.8, 0, Math.PI * 2);
      ctx.fill();
    }
    ctx.fillStyle = 'rgba(255, 210, 92, 0.96)';
    ctx.strokeStyle = 'rgba(4, 19, 27, 0.96)';
    ctx.lineWidth = 0.7;
    for (const point of link.points.slice(1, -1)) {
      ctx.beginPath();
      ctx.arc(point[0], point[1], 1.35, 0, Math.PI * 2);
      ctx.fill();
      ctx.stroke();
    }
    ctx.setLineDash([3, 2]);
  }
  ctx.setLineDash([]);
  ctx.restore();
}

function drawConnectorHighlight(connector) {
  const middleX = (connector.from[0] + connector.to[0]) / 2;
  const middleY = (connector.from[1] + connector.to[1]) / 2;
  const dx = connector.to[0] - connector.from[0];
  const dy = connector.to[1] - connector.from[1];
  const length = Math.hypot(dx, dy) || 1;
  const ux = dx / length;
  const uy = dy / length;
  ctx.save();
  ctx.lineCap = 'round';
  ctx.strokeStyle = 'rgba(3, 18, 29, 0.95)';
  ctx.lineWidth = 6;
  ctx.beginPath();
  ctx.moveTo(middleX - ux * 8, middleY - uy * 8);
  ctx.lineTo(middleX + ux * 8, middleY + uy * 8);
  ctx.stroke();
  ctx.strokeStyle = 'rgba(118, 240, 255, 0.96)';
  ctx.lineWidth = 2.2;
  ctx.stroke();
  ctx.restore();
}

function drawPerimeter(edge, theme) {
  ctx.save();
  ctx.lineCap = 'round';
  ctx.strokeStyle = theme.perimeter.color;
  ctx.lineWidth = theme.perimeter.width;
  ctx.beginPath();
  ctx.moveTo(...edge.start);
  ctx.lineTo(...edge.end);
  ctx.stroke();
  ctx.restore();
}

function drawCourtyard(courtyard, theme) {
  const [x, y] = courtyard.center;
  ctx.save();
  ctx.fillStyle = theme.courtyard.fill;
  ctx.strokeStyle = theme.courtyard.stroke;
  ctx.lineWidth = 1.4;
  ctx.beginPath();
  ctx.ellipse(x, y, courtyard.memberCount > 3 ? 17 : 10, courtyard.memberCount > 3 ? 8 : 5, 0, 0, Math.PI * 2);
  ctx.fill();
  ctx.stroke();
  ctx.beginPath();
  ctx.ellipse(x, y, courtyard.memberCount > 3 ? 9 : 5, courtyard.memberCount > 3 ? 4 : 2.5, 0, 0, Math.PI * 2);
  ctx.strokeStyle = theme.courtyard.detail;
  ctx.stroke();
  ctx.restore();
}

function drawCenterpiece(centerpiece) {
  const [x, y] = centerpiece.anchor;
  ctx.save();
  ctx.globalCompositeOperation = 'screen';
  const glow = ctx.createRadialGradient(x, y - 103, 1, x, y - 103, 12);
  glow.addColorStop(0, 'rgba(255, 255, 255, 0.98)');
  glow.addColorStop(0.35, 'rgba(80, 222, 255, 0.85)');
  glow.addColorStop(1, 'rgba(80, 222, 255, 0)');
  ctx.fillStyle = glow;
  ctx.beginPath();
  ctx.arc(x, y - 103, 12, 0, Math.PI * 2);
  ctx.fill();
  ctx.strokeStyle = 'rgba(186, 247, 255, 0.95)';
  ctx.lineWidth = 2;
  ctx.beginPath();
  ctx.moveTo(x, y - 91);
  ctx.lineTo(x, y - 112);
  ctx.moveTo(x - 6, y - 103);
  ctx.lineTo(x, y - 111);
  ctx.lineTo(x + 6, y - 103);
  ctx.lineTo(x, y - 96);
  ctx.closePath();
  ctx.stroke();
  ctx.restore();
}

function updateBuildPanel() {
  const editable = isEditableScenario();
  const inShadowLab = controls.scenarioSelect.value === 'shadowLab';
  const member = memberByCell.get(selectedCellNum);
  const buildButtons = [...controls.buildMenu.querySelectorAll('[data-building-variant]')];
  controls.buildPanel.hidden = !editable;
  controls.buildMenu.hidden = true;
  controls.powerMenu.hidden = true;
  if (!editable) return;
  if (showsAncientEnvironment() && isRingGateCell(selectedCellNum)) {
    controls.buildHint.textContent = 'Кольцевые ворота Воранеров: инфраструктурный объект занимает две клетки внешнего кольца.';
    return;
  }
  if (inShadowLab) {
    buildButtons[0].textContent = 'PNG-дом · математическая тень';
    buildButtons[1].textContent = '3D-дублёр · физическая тень';
    buildButtons[2].hidden = true;
  } else {
    buildButtons[0].textContent = 'Башенный дом v02';
    buildButtons[1].textContent = 'Купольный дом v03';
    buildButtons[2].textContent = 'Овальный дом v05';
    buildButtons[2].hidden = false;
  }
  if (member?.state === 'construction') {
    controls.buildHint.textContent = `Клетка ${selectedCellNum}: строительство идёт. Нажмите на дом ещё раз, чтобы завершить.`;
    return;
  }
  if (member) {
    const disabled = member.state === 'disabled';
    const level = member.level ?? 1;
    controls.buildHint.textContent = disabled
      ? `Клетка ${selectedCellNum}: здание уровня ${level} отключено.`
      : `Клетка ${selectedCellNum}: здание уровня ${level} работает.`;
    controls.togglePower.textContent = disabled ? 'Включить здание' : 'Отключить здание';
    controls.togglePower.hidden = false;
    controls.upgradeBuilding.textContent = level >= 3 ? 'Максимальный уровень 3' : `Улучшить до уровня ${level + 1}`;
    controls.upgradeBuilding.disabled = level >= 3;
    controls.upgradeBuilding.hidden = disabled;
    controls.powerMenu.hidden = false;
    return;
  }
  if (isBuildableCell(selectedCellNum)) {
    controls.buildHint.textContent = `Клетка ${selectedCellNum} свободна. Выберите одноклеточное здание:`;
    controls.buildMenu.hidden = false;
    return;
  }
  if (inShadowLab === false && controls.scenarioSelect.value === 'districtWide' && selectedCellNum >= 19) {
    controls.buildHint.textContent = `Клетка ${selectedCellNum}: свободная территория окружения. Жилая застройка здесь пока отключена.`;
    return;
  }
  controls.buildHint.textContent = 'Выберите подсвеченную свободную клетку внешнего кольца.';
}

function drawDisabledCellOverlay(cell) {
  const centerX = cell.x + BUILDING_ANCHOR[0];
  const groundY = cell.y + CELL_HEIGHT * 0.72;
  const seed = (cell.num * 17 + 11) % 7;
  ctx.save();
  hexPath(cell.x, cell.y, 1);
  const field = ctx.createRadialGradient(centerX, groundY - 8, 1, centerX, groundY - 5, 28);
  field.addColorStop(0, 'rgba(188, 242, 255, 0.22)');
  field.addColorStop(0.56, 'rgba(76, 174, 209, 0.12)');
  field.addColorStop(1, 'rgba(12, 56, 78, 0.04)');
  ctx.fillStyle = field;
  ctx.fill();
  ctx.strokeStyle = 'rgba(144, 224, 246, 0.62)';
  ctx.lineWidth = 0.8;
  ctx.stroke();

  ctx.globalCompositeOperation = 'screen';
  ctx.shadowColor = 'rgba(103, 221, 255, 0.8)';
  ctx.shadowBlur = 3;
  ctx.setLineDash([2.2, 2.2]);
  ctx.strokeStyle = 'rgba(155, 235, 255, 0.82)';
  ctx.lineWidth = 0.9;
  ctx.beginPath();
  ctx.ellipse(centerX, groundY - 1.4, 18, 5.7, 0, 0, Math.PI * 2);
  ctx.stroke();
  ctx.setLineDash([]);

  ctx.strokeStyle = 'rgba(190, 244, 255, 0.7)';
  ctx.lineWidth = 0.7;
  for (let index = 0; index < 5; index += 1) {
    const offsetX = -14 + index * 7 + ((seed + index) % 3 - 1) * 1.4;
    const height = 15 + ((seed + index * 3) % 5) * 4;
    ctx.beginPath();
    ctx.moveTo(centerX + offsetX, groundY - 1);
    ctx.lineTo(centerX + offsetX * 0.72, groundY - height);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(centerX + offsetX * 0.82, groundY - height * 0.48);
    ctx.lineTo(centerX + offsetX * 0.55 - 3, groundY - height * 0.6);
    ctx.moveTo(centerX + offsetX * 0.82, groundY - height * 0.48);
    ctx.lineTo(centerX + offsetX * 0.65 + 3, groundY - height * 0.67);
    ctx.stroke();
  }

  ctx.fillStyle = 'rgba(218, 250, 255, 0.92)';
  for (let index = 0; index < 4; index += 1) {
    const angle = (index + seed * 0.35) * Math.PI * 0.63;
    const radius = 8 + (index % 2) * 8;
    ctx.beginPath();
    ctx.arc(
      centerX + Math.cos(angle) * radius,
      groundY - 10 + Math.sin(angle) * 5,
      0.75,
      0,
      Math.PI * 2,
    );
    ctx.fill();
  }
  ctx.restore();
}

function drawUpgradeCellOverlay(cell) {
  const level = memberByCell.get(cell.num)?.level ?? 1;
  if (level <= 1) return;
  const x = cell.x + CELL_WIDTH * 0.78;
  const y = cell.y + CELL_HEIGHT * 0.22;
  ctx.save();
  ctx.fillStyle = 'rgba(20, 35, 45, 0.88)';
  ctx.strokeStyle = 'rgba(255, 218, 114, 0.98)';
  ctx.lineWidth = 1;
  ctx.beginPath();
  ctx.arc(x, y, 6.5, 0, Math.PI * 2);
  ctx.fill();
  ctx.stroke();
  ctx.fillStyle = '#ffe29a';
  ctx.font = 'bold 7px Segoe UI';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText(String(level), x, y + 0.4);
  ctx.restore();
}

function drawResidentialGroundLayer(plan, cells, options = {}) {
  if (plan.cellNums.length === 0) return;
  const cellByNum = new Map(cells.map((cell) => [cell.num, cell]));
  const pavedCells = plan.cellNums.map((cellNum) => cellByNum.get(cellNum));
  const minX = Math.min(...pavedCells.map((cell) => cell.x));
  const maxX = Math.max(...pavedCells.map((cell) => cell.x + CELL_WIDTH));
  const minY = Math.min(...pavedCells.map((cell) => cell.y));
  const maxY = Math.max(...pavedCells.map((cell) => cell.y + CELL_HEIGHT));

  ctx.save();
  ctx.beginPath();
  pavedCells.forEach((cell) => appendHexPathOn(ctx, cell.x, cell.y, 1));
  ctx.clip();

  const stone = ctx.createLinearGradient(0, minY, 0, maxY);
  stone.addColorStop(0, '#dcc9a4');
  stone.addColorStop(0.46, '#c8b58e');
  stone.addColorStop(1, '#a9916c');
  ctx.fillStyle = stone;
  ctx.fillRect(minX - 2, minY - 2, maxX - minX + 4, maxY - minY + 4);

  const tileHalfWidth = 18;
  const tileHalfHeight = 5.8;
  let row = 0;
  for (let y = minY - tileHalfHeight * 2; y <= maxY + tileHalfHeight * 2; y += tileHalfHeight * 2) {
    const stagger = row % 2 === 0 ? 0 : tileHalfWidth;
    let column = 0;
    for (let x = minX - tileHalfWidth * 2 + stagger; x <= maxX + tileHalfWidth * 2; x += tileHalfWidth * 2) {
      const diamond = (offsetY = 0) => {
        ctx.beginPath();
        ctx.moveTo(x, y - tileHalfHeight + offsetY);
        ctx.lineTo(x + tileHalfWidth, y + offsetY);
        ctx.lineTo(x, y + tileHalfHeight + offsetY);
        ctx.lineTo(x - tileHalfWidth, y + offsetY);
        ctx.closePath();
      };
      diamond();
      ctx.fillStyle = (row + column) % 4 === 0
        ? 'rgba(255, 239, 204, 0.055)'
        : 'rgba(89, 65, 42, 0.018)';
      ctx.fill();
      ctx.strokeStyle = 'rgba(88, 69, 47, 0.15)';
      ctx.lineWidth = 0.5;
      ctx.stroke();
      diamond(-0.32);
      ctx.strokeStyle = 'rgba(255, 239, 204, 0.12)';
      ctx.lineWidth = 0.28;
      ctx.stroke();
      column += 1;
    }
    row += 1;
  }

  const centerGlow = ctx.createRadialGradient(
    (minX + maxX) / 2,
    (minY + maxY) / 2,
    4,
    (minX + maxX) / 2,
    (minY + maxY) / 2,
    Math.max(maxX - minX, maxY - minY) * 0.55,
  );
  centerGlow.addColorStop(0, 'rgba(255, 238, 199, 0.09)');
  centerGlow.addColorStop(1, 'rgba(81, 56, 35, 0.055)');
  ctx.fillStyle = centerGlow;
  ctx.fillRect(minX - 2, minY - 2, maxX - minX + 4, maxY - minY + 4);

  // Broad oval courses tie each sprite foundation into the paved field without
  // reproducing the hex grid. Their overlaps read as one courtyard in clusters.
  for (const cell of pavedCells) {
    const centerX = cell.x + CELL_WIDTH / 2;
    const centerY = cell.y + CELL_HEIGHT * 0.57;
    ctx.beginPath();
    ctx.ellipse(centerX, centerY, 19, 8.1, 0, 0, Math.PI * 2);
    ctx.fillStyle = 'rgba(255, 239, 204, 0.045)';
    ctx.fill();
    ctx.strokeStyle = 'rgba(104, 81, 55, 0.13)';
    ctx.lineWidth = 0.58;
    ctx.stroke();
    ctx.beginPath();
    ctx.ellipse(centerX, centerY - 0.42, 15.8, 6.35, 0, 0, Math.PI * 2);
    ctx.strokeStyle = 'rgba(255, 239, 204, 0.16)';
    ctx.lineWidth = 0.34;
    ctx.stroke();
  }

  // A shared stone inlay crosses every occupied adjacency. It is deliberately
  // wider than a seam and therefore joins foundations instead of drawing roads.
  for (const edge of plan.sharedEdges) {
    const cell = cellByNum.get(edge.cellNum);
    const neighbor = cellByNum.get(edge.neighborCellNum);
    const fromX = cell.x + CELL_WIDTH / 2;
    const fromY = cell.y + CELL_HEIGHT * 0.54;
    const toX = neighbor.x + CELL_WIDTH / 2;
    const toY = neighbor.y + CELL_HEIGHT * 0.54;
    const stroke = (color, width) => {
      ctx.beginPath();
      ctx.moveTo(fromX, fromY);
      ctx.lineTo(toX, toY);
      ctx.strokeStyle = color;
      ctx.lineWidth = width;
      ctx.lineCap = 'round';
      ctx.stroke();
    };
    stroke('rgba(91, 69, 45, 0.11)', 5.2);
    stroke('rgba(222, 199, 157, 0.22)', 3.7);
    stroke('rgba(255, 231, 181, 0.18)', 0.46);
  }
  ctx.restore();

  if (options.outline === false) {
    // The courtyard ends in an irregular dusty fringe rather than a clean
    // hexagonal border. Broad warm strokes visually return its edge to sand.
    ctx.save();
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.filter = 'blur(1.5px)';
    for (const edge of plan.externalEdges) {
      ctx.beginPath();
      ctx.moveTo(...edge.start);
      ctx.lineTo(...edge.end);
      ctx.strokeStyle = 'rgba(191, 139, 83, 0.3)';
      ctx.lineWidth = 6.4;
      ctx.stroke();
    }
    ctx.filter = 'blur(0.45px)';
    plan.externalEdges.forEach((edge, index) => {
      if ((edge.cellNum * 5 + edge.direction + index) % 3 === 0) return;
      const x = (edge.start[0] + edge.end[0]) / 2 + ((edge.cellNum + index) % 3 - 1) * 2.1;
      const y = (edge.start[1] + edge.end[1]) / 2 + ((edge.direction + index) % 3 - 1) * 0.9;
      ctx.fillStyle = 'rgba(221, 169, 104, 0.34)';
      ctx.beginPath();
      ctx.ellipse(x, y, 3.8 + (index % 3), 1.2 + (index % 2) * 0.45, 0, 0, Math.PI * 2);
      ctx.fill();
    });
    ctx.restore();
  }

  if (options.outline !== false) {
    ctx.save();
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    for (const edge of plan.externalEdges) {
    const stroke = (color, width) => {
      ctx.beginPath();
      ctx.moveTo(...edge.start);
      ctx.lineTo(...edge.end);
      ctx.strokeStyle = color;
      ctx.lineWidth = width;
      ctx.stroke();
    };
      stroke('rgba(16, 30, 33, 0.78)', 2.85);
      stroke('rgba(124, 119, 104, 0.96)', 1.98);
      stroke('rgba(230, 217, 180, 0.82)', 0.58);
    }

  // Sparse stasis studs give the otherwise neutral courtyard a Voraner accent.
    plan.externalEdges.forEach((edge, index) => {
      if ((edge.cellNum * 7 + edge.direction * 3 + index) % 9 !== 0) return;
      const x = (edge.start[0] + edge.end[0]) / 2;
      const y = (edge.start[1] + edge.end[1]) / 2;
      ctx.shadowColor = 'rgba(64, 218, 255, 0.92)';
      ctx.shadowBlur = 3.2;
      ctx.fillStyle = 'rgba(116, 231, 255, 0.92)';
      ctx.beginPath();
      ctx.ellipse(x, y - 0.2, 1.3, 0.65, 0, 0, Math.PI * 2);
      ctx.fill();
      ctx.shadowBlur = 0;
    });
    ctx.restore();
  }
}

function drawSurface(cells) {
  const theme = SURFACE_THEMES[controls.surfaceSelect.value] ?? SURFACE_THEMES.ancient;
  const drawWideAncientEnvironment = showsAncientEnvironment();
  const minY = Math.min(...cells.map((cell) => cell.y));
  const maxY = Math.max(...cells.map((cell) => cell.y + CELL_HEIGHT));
  if (drawWideAncientEnvironment) {
    const hullGradient = ctx.createLinearGradient(0, minY + 4, 0, maxY + 14);
    hullGradient.addColorStop(0, '#405764');
    hullGradient.addColorStop(0.55, '#263d4a');
    hullGradient.addColorStop(1, '#101f2d');
    ctx.save();
    ctx.translate(0, 11);
    ctx.beginPath();
    cells.forEach((cell) => appendHexPathOn(ctx, cell.x, cell.y, 1));
    ctx.fillStyle = 'rgba(1, 9, 17, 0.62)';
    ctx.fill();
    ctx.restore();
    ctx.save();
    ctx.translate(0, 7);
    ctx.beginPath();
    cells.forEach((cell) => appendHexPathOn(ctx, cell.x, cell.y, 1));
    ctx.fillStyle = hullGradient;
    ctx.strokeStyle = 'rgba(95, 134, 148, 0.55)';
    ctx.lineWidth = 1.1;
    ctx.fill();
    ctx.stroke();
    ctx.restore();
  }
  const gradient = ctx.createLinearGradient(0, minY, 0, maxY);
  gradient.addColorStop(0, theme.top);
  gradient.addColorStop(0.46, theme.center);
  gradient.addColorStop(1, theme.bottom);
  ctx.beginPath();
  cells.forEach((cell) => appendHexPathOn(ctx, cell.x, cell.y, 1));
  ctx.fillStyle = gradient;
  ctx.fill();

  if (drawWideAncientEnvironment) {
    const center = cells.find((cell) => cell.num === 0) ?? cells[0];
    ctx.save();
    ctx.beginPath();
    cells.forEach((cell) => appendHexPathOn(ctx, cell.x, cell.y, 1));
    ctx.clip();
    if (!controls.residentialEnvironment.checked) {
      drawAncientEnvironment(ctx, [center.x + CELL_WIDTH / 2, center.y + CELL_HEIGHT / 2]);
    }
    ctx.restore();
    drawAncientPlatformEdgeLights(ctx, [center.x + CELL_WIDTH / 2, center.y + CELL_HEIGHT / 2]);
    return;
  }

  for (const cell of cells) {
    ctx.save();
    hexPath(cell.x, cell.y, 1);
    ctx.clip();
    ctx.strokeStyle = theme.detail;
    ctx.lineWidth = 0.65;
    const shift = (cell.num % 3) * 5;
    ctx.beginPath();
    ctx.moveTo(cell.x + 5 + shift, cell.y + 7);
    ctx.lineTo(cell.x + 24 + shift, cell.y + 13);
    ctx.lineTo(cell.x + 40 + shift, cell.y + 8);
    ctx.moveTo(cell.x + 9 - shift, cell.y + 20);
    ctx.lineTo(cell.x + 25 - shift, cell.y + 16);
    ctx.stroke();
    ctx.restore();
  }
}

function render() {
  const dpr = Math.min(window.devicePixelRatio || 1, 2);
  const rect = canvas.getBoundingClientRect();
  canvas.width = Math.max(1, Math.round(rect.width * dpr));
  canvas.height = Math.max(1, Math.round(rect.height * dpr));
  effectsCanvas.width = canvas.width;
  effectsCanvas.height = canvas.height;
  ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  ctx.clearRect(0, 0, rect.width, rect.height);

  if (showsAncientEnvironment()) drawRingWorldBackdrop(ctx, rect.width, rect.height);

  const wideEnvironment = controls.scenarioSelect.value === 'districtWide';
  const waterAnimationLab = controls.scenarioSelect.value === 'waterLab';
  const baseScale = Math.min(
    rect.width / (waterAnimationLab ? 150 : wideEnvironment ? 500 : 420),
    rect.height / (waterAnimationLab ? 165 : wideEnvironment ? 410 : 340),
  );
  const scale = baseScale * zoomFactor();
  const originX = rect.width / (2 * scale) - CELL_WIDTH / 2;
  const originY = rect.height / (2 * scale) - CELL_HEIGHT / 2 + (waterAnimationLab ? 5 : 35);
  const cells = cellsForScenario(originX, originY);
  canvas.setAttribute(
    'aria-label',
    `Художественный полигон из ${cells.length} гексагональных клеток. Нажмите на клетку, чтобы выбрать её.`,
  );
  const visualPlan = buildComplexVisualPlan(scenarioComplexes, cells);
  const fusions = activeFusionPackages.size > 0 ? resolveComplexFusions(scenarioComplexes, cells) : [];
  const occupiedCellNums = [...memberByCell.values()].map((member) => member.cellNum);
  const residentialGroundPlan = buildResidentialGroundPlan(cells, occupiedCellNums);
  const residentialEnvironmentPlan = buildResidentialEnvironmentPlan(cells);
  const fusedComplexIds = new Set(fusions.map((fusion) => fusion.complexId));
  const fusedCellNums = new Set(fusions.flatMap((fusion) => fusion.cellNums));
  const complexTheme = resolveComplexTheme(activeVisual);
  latestScene = { cells, scale };

  ctx.save();
  ctx.scale(scale, scale);

  if (complexTheme.showTopology) {
    for (const cell of cells) {
      hexPath(cell.x, cell.y, 1);
      ctx.fillStyle = cell.id === 0 ? 'rgba(31, 172, 235, 0.24)' : 'rgba(20, 75, 112, 0.58)';
      ctx.fill();
      ctx.strokeStyle = cell.id === 0 ? '#65e1ff' : 'rgba(116, 201, 241, 0.55)';
      ctx.lineWidth = cell.id === 0 ? 1.4 : 0.7;
      ctx.stroke();
    }
  } else {
    drawSurface(cells);
  }

  if (showsResidentialEnvironment() && controls.residentialEnvironment.checked) {
    drawResidentialEnvironmentGround(ctx, residentialEnvironmentPlan);
  }

  if (controls.residentialGround.checked && !complexTheme.showTopology && !waterAnimationLab) {
    drawResidentialGroundLayer(residentialGroundPlan, cells, {
      outline: !controls.residentialEnvironment.checked,
    });
  }

  const architecturalLinks = buildArchitecturalLinks({
    cells,
    occupiedCellNums: [...memberByCell.values()]
      .filter((member) => member.state !== 'construction')
      .map((member) => member.cellNum),
    fusions,
    endpointOffset: ART_PLACEMENT_OFFSET,
  });
  if (controls.architecturalLinks.checked && !complexTheme.showTopology) {
    drawArchitecturalLinkLayer(architecturalLinks);
  }

  const hoveredCellNums = interactionCellNums(hoveredCellNum);
  if (!waterAnimationLab && hoveredCellNum !== null && hoveredCellNum !== selectedCellNum) {
    for (const cellNum of hoveredCellNums) {
      const hoveredCell = cells.find((cell) => cell.num === cellNum);
      if (!hoveredCell) continue;
      hexPath(hoveredCell.x, hoveredCell.y, 1);
      ctx.fillStyle = 'rgba(75, 218, 255, 0.035)';
      ctx.fill();
      ctx.strokeStyle = 'rgba(98, 225, 255, 0.18)';
      ctx.lineWidth = 3;
      ctx.stroke();
      ctx.strokeStyle = 'rgba(142, 237, 255, 0.88)';
      ctx.lineWidth = 0.85;
      ctx.stroke();
    }
  }

  for (const cellNum of waterAnimationLab ? [] : interactionCellNums(selectedCellNum)) {
    const selectedCell = cells.find((cell) => cell.num === cellNum);
    if (!selectedCell) continue;
    hexPath(selectedCell.x, selectedCell.y, 1);
    ctx.fillStyle = 'rgba(255, 218, 112, 0.045)';
    ctx.fill();
    ctx.strokeStyle = 'rgba(255, 210, 92, 0.2)';
    ctx.lineWidth = 3.6;
    ctx.stroke();
    ctx.strokeStyle = 'rgba(255, 226, 139, 0.96)';
    ctx.lineWidth = 1.15;
    ctx.stroke();
  }

  if (showsAncientEnvironment()) {
    const center = cells.find((cell) => cell.num === 0) ?? cells[0];
    drawAncientInfrastructure(
      ctx,
      [center.x + CELL_WIDTH / 2, center.y + CELL_HEIGHT / 2],
      {
        shadow: controls.mathShadow.checked ? shadowTuning() : null,
        hideArchaeologyNodes: controls.residentialEnvironment.checked,
      },
    );
    if (controls.residentialEnvironment.checked) {
      drawResidentialEnvironmentShadows(
        ctx,
        residentialEnvironmentPlan,
        controls.mathShadow.checked ? shadowTuning() : null,
      );
      drawResidentialEnvironmentRuins(ctx, residentialEnvironmentPlan);
    }
  }

  if (complexTheme.showTopology) {
    visualPlan.courtyards.filter((item) => !fusedComplexIds.has(item.complexId)).forEach((item) => drawCourtyard(item, complexTheme));
    visualPlan.connectors
      .filter((item) => !(fusedCellNums.has(item.fromCellNum) && fusedCellNums.has(item.toCellNum)))
      .forEach((item) => drawConnector(item, complexTheme));
    visualPlan.perimeters
      .filter((item) => !fusedCellNums.has(item.cellNum))
      .forEach((item) => drawPerimeter(item, complexTheme));
  }

  if (controls.ports.checked) {
    for (const cell of cells) {
      PORTS.forEach((port) => {
        ctx.beginPath();
        ctx.arc(cell.x + port.point[0], cell.y + port.point[1], 1.8, 0, Math.PI * 2);
        ctx.fillStyle = '#63e5ff';
        ctx.fill();
      });
    }
  }

  if (controls.envelope.checked) {
    const center = cells[0];
    const anchorX = center.x + BUILDING_ANCHOR[0];
    const anchorY = center.y + BUILDING_ANCHOR[1];
    ctx.setLineDash([3, 2]);
    ctx.strokeStyle = 'rgba(255, 201, 93, 0.82)';
    ctx.lineWidth = 0.8;
    ctx.strokeRect(
      anchorX - PILOT_ENVELOPE.width / 2,
      anchorY - PILOT_ENVELOPE.height,
      PILOT_ENVELOPE.width,
      PILOT_ENVELOPE.height + PILOT_ENVELOPE.belowAnchor,
    );
    ctx.setLineDash([]);
  }

  const centerCells = new Set(scenarioComplexes.map((complex) => complex.centerCellNum));
  const renderItems = sortComplexRenderItems([
    ...cells.filter((cell) => memberByCell.has(cell.num) && !fusedCellNums.has(cell.num)).map((cell) => ({
      kind: 'building',
      cellNum: cell.num,
      cell,
      anchor: [cell.x + BUILDING_ANCHOR[0], cell.y + BUILDING_ANCHOR[1]],
    })),
    ...fusions.map((fusion) => ({ kind: 'fusion', anchor: fusion.anchor, fusion })),
  ]);
  const hasAuthoredCastShadow = activePackages.some((item) => Boolean(item.layers.castShadow));
  if (!complexTheme.showTopology && (
    activeFusionPackages.size > 0
    || hasAuthoredCastShadow
    || usesCalibratedMathShadows()
  )) {
    drawArtRenderQueue(renderItems, cells, scale, dpr, { skipShadows: waterAnimationLab });
  } else {
    for (const item of renderItems) {
      if (item.kind === 'fusion') drawFusion(item.fusion);
      else drawBuilding(item.cell, 1, centerCells.has(item.cellNum), memberByCell.get(item.cellNum));
    }
  }
  cells.filter((cell) => memberByCell.get(cell.num)?.state === 'disabled').forEach(drawDisabledCellOverlay);
  cells.filter((cell) => (memberByCell.get(cell.num)?.level ?? 1) > 1).forEach(drawUpgradeCellOverlay);

  if (controls.architecturalLinks.checked && controls.linkDebug.checked && !complexTheme.showTopology) {
    drawArchitecturalLinkDiagnostics(architecturalLinks);
  }

  if (complexTheme.showTopology && complexTheme.connector.highlight) {
    visualPlan.connectors
      .filter((item) => !(fusedCellNums.has(item.fromCellNum) && fusedCellNums.has(item.toCellNum)))
      .forEach(drawConnectorHighlight);
  }
  visualPlan.centerpieces.filter((item) => !fusedComplexIds.has(item.complexId)).forEach(drawCenterpiece);

  if (controls.anchors.checked) {
    for (const cell of cells) {
      ctx.beginPath();
      ctx.arc(cell.x + BUILDING_ANCHOR[0], cell.y + BUILDING_ANCHOR[1], 2.2, 0, Math.PI * 2);
      ctx.fillStyle = '#ff6178';
      ctx.fill();
    }
  }

  if (controls.labels.checked) {
    ctx.font = '7px Segoe UI';
    ctx.textAlign = 'center';
    ctx.fillStyle = '#eaf6ff';
    for (const cell of cells) {
      const member = memberByCell.get(cell.num);
      const label = member ? `${cell.name} ${member.mask.toString(2).padStart(6, '0')}` : `${cell.name} —`;
      ctx.fillText(label, cell.x + 24, cell.y + 36);
    }
    ctx.fillStyle = '#78dfff';
    ctx.fillText(`rotation test ${mask.toString(2).padStart(6, '0')}`, cells[0].x + 24, cells[0].y - 118);
  }
  ctx.restore();

  controls.metrics.innerHTML = `
    <dt>Клетка</dt><dd>${CELL_WIDTH} × ${CELL_HEIGHT}</dd>
    <dt>Anchor</dt><dd>${BUILDING_ANCHOR.join(', ')}</dd>
    <dt>Клеток в домашней сетке</dt><dd>${TOTAL_CELLS}</dd>
    <dt>Клеток на полигоне</dt><dd>${cells.length}</dd>
    <dt>Поверхность</dt><dd>${controls.surfaceSelect.selectedOptions[0].textContent}</dd>
    <dt>Конфигурация</dt><dd>${controls.scenarioSelect.selectedOptions[0].textContent}</dd>
    <dt>Комплексов</dt><dd>${scenarioComplexes.length}</dd>
    <dt>Устойчивые ID</dt><dd>${scenarioComplexes.map((item) => item.id).join('<br>') || '—'}</dd>
    <dt>Переходов</dt><dd>${visualPlan.connectors.length}</dd>
    <dt>Визуальных связей</dt><dd>${architecturalLinks.length} · клетки не занимают</dd>
    <dt>Дворовое мощение</dt><dd>${residentialGroundPlan.cellNums.length} клеток · внутренние швы скрыты</dd>
    <dt>Дворовых площадок</dt><dd>${residentialGroundPlan.components.length}</dd>
    <dt>Жилое окружение</dt><dd>${controls.residentialEnvironment.checked ? `единый фон · ${residentialEnvironmentPlan.structures.length} логических клеток · ${residentialEnvironmentPlan.ruins.length} руины` : 'выключено'}</dd>
    <dt>Внешних граней</dt><dd>${visualPlan.perimeters.length}</dd>
    <dt>Дворов</dt><dd>${visualPlan.courtyards.length}</dd>
    <dt>Центральных акцентов</dt><dd>${visualPlan.centerpieces.length}</dd>
    <dt>Визуальных слияний</dt><dd>${fusions.map((item) => `${item.kind === 'three' ? '3 → 1' : '7 → 1'} · ${item.complexId}`).join('<br>') || '—'}</dd>
    <dt>Скрыто отдельных модулей</dt><dd>${fusedCellNums.size}</dd>
    <dt>Выбрана клетка</dt><dd>${selectedCellNum ?? '—'}${memberByCell.has(selectedCellNum) ? ' · здание' : isRingGateCell(selectedCellNum) && showsAncientEnvironment() ? ' · кольцевые ворота' : ' · пусто'}</dd>
    <dt>Уровень здания</dt><dd>${memberByCell.has(selectedCellNum) ? (memberByCell.get(selectedCellNum).level ?? 1) : '—'}</dd>
    <dt>Вариант клетки</dt><dd>${variantByCell.has(selectedCellNum) ? `${variantByCell.get(selectedCellNum).variant} · h×${variantByCell.get(selectedCellNum).heightScale.toFixed(2)}` : '—'}</dd>
    <dt>Декор клетки</dt><dd>${variantByCell.has(selectedCellNum) ? `${variantByCell.get(selectedCellNum).garden}, ${variantByCell.get(selectedCellNum).antenna}, ${variantByCell.get(selectedCellNum).entrance}` : '—'}</dd>
    <dt>Envelope</dt><dd>${PILOT_ENVELOPE.width} × ${PILOT_ENVELOPE.height}</dd>
    <dt>Визуальный пакет</dt><dd>${activeVisual?.id ?? 'техническая форма'}</dd>
    <dt>Логическая ширина</dt><dd>${activeVisual?.logicalWidth ?? 78}</dd>
  `;
  latestEffectsScene = { renderItems, scale, dpr };
  drawDynamicEffects();
  updateBuildPanel();
}

async function selectPackage(manifestUrl) {
  const request = ++packageRequest;
  controls.packageStatus.classList.remove('error');
  controls.packageStatus.textContent = 'Загрузка пакета…';
  try {
    const packageKeys = PACKAGE_PRESETS[manifestUrl] ?? [manifestUrl];
    const urls = packageKeys.map((key) => PACKAGE_URLS[key]);
    const packages = await Promise.all(urls.map(async (url) => {
      const visual = await loadBuildingVisual(url);
      const layers = await loadVisualLayers(visual);
      return { visual, layers };
    }));
    const fusionPackages = manifestUrl === 'voranerMix'
      ? await Promise.all(Object.entries(FUSION_PACKAGE_URLS).map(async ([kind, url]) => {
        const visual = await loadBuildingVisual(url);
        const layers = await loadVisualLayers(visual);
        return [kind, { visual, layers }];
      }))
      : [];
    if (request !== packageRequest) return;
    packages.sort((a, b) => (a.visual.variant ?? 0) - (b.visual.variant ?? 0));
    activePackages = packages;
    activeFusionPackages = new Map(fusionPackages);
    activeVisual = packages[0].visual;
    rebuildScenario();
    const layerCount = [...packages, ...fusionPackages.map(([, item]) => item)]
      .reduce((sum, item) => sum + Object.keys(item.layers).length, 0);
    controls.packageStatus.textContent = fusionPackages.length > 0
      ? `${packages.length} дома · ${fusionPackages.length} комплекса · ${layerCount} слоёв`
      : `${packages.length} пакет(а) · ${layerCount} слоёв`;
    render();
  } catch (error) {
    if (request !== packageRequest) return;
    activeVisual = null;
    activePackages = [];
    activeFusionPackages = new Map();
    controls.packageStatus.classList.add('error');
    controls.packageStatus.textContent = error instanceof Error ? error.message : String(error);
    render();
  }
}

for (const control of [
  controls.ports,
  controls.anchors,
  controls.envelope,
  controls.labels,
  controls.residentialGround,
  controls.residentialEnvironment,
  controls.animateScene,
  controls.architecturalLinks,
  controls.linkDebug,
  controls.mathShadow,
  controls.physicalShadow,
  controls.facadeShadow,
]) {
  control.addEventListener('change', render);
}
for (const control of shadowModifierInputs) {
  control.addEventListener('input', () => {
    calibratedShadowCache = new WeakMap();
    updateShadowTuningLabels();
    render();
  });
}
controls.resetShadowTuning.addEventListener('click', () => {
  resetShadowTuning();
  render();
});
controls.rotate.addEventListener('click', () => {
  mask = rotateMask(mask, 1);
  render();
});
controls.packageSelect.addEventListener('change', () => {
  if (controls.packageSelect.value === 'shadowLab') {
    controls.scenarioSelect.value = 'shadowLab';
    rebuildScenario();
  }
  selectPackage(controls.packageSelect.value);
});
controls.scenarioSelect.addEventListener('change', () => {
  if (controls.scenarioSelect.value === 'districtWide' && Number(controls.zoom.value) > 125) {
    controls.zoom.value = '120';
    updateZoomLabel();
  }
  rebuildScenario();
  render();
});
controls.buildMenu.addEventListener('click', (event) => {
  const button = event.target.closest('[data-building-variant]');
  const buildable = isBuildableCell(selectedCellNum);
  if (!button || memberByCell.has(selectedCellNum) || !buildable) return;
  constructionBuildings = beginConstruction(
    constructionBuildings,
    selectedCellNum,
    Number(button.dataset.buildingVariant),
    constructionComplexGroup(selectedCellNum),
  );
  rebuildScenario();
  render();
});
controls.togglePower.addEventListener('click', () => {
  const member = memberByCell.get(selectedCellNum);
  if (!member || member.state === 'construction') return;
  powerStates = setBuildingPower(powerStates, selectedCellNum, member.state === 'disabled');
  rebuildScenario();
  render();
});
controls.upgradeBuilding.addEventListener('click', () => {
  const member = memberByCell.get(selectedCellNum);
  if (!member || (member.state ?? 'working') !== 'working') return;
  buildingLevels = upgradeBuilding(buildingLevels, selectedCellNum);
  rebuildScenario();
  render();
});
controls.demolishBuilding.addEventListener('click', () => {
  const member = memberByCell.get(selectedCellNum);
  if (!member || member.state === 'construction') return;
  removedCells = new Set(removedCells).add(selectedCellNum);
  constructionBuildings = new Map(constructionBuildings);
  constructionBuildings.delete(selectedCellNum);
  powerStates = new Map(powerStates);
  powerStates.delete(selectedCellNum);
  buildingLevels = new Map(buildingLevels);
  buildingLevels.delete(selectedCellNum);
  rebuildScenario();
  render();
});
controls.zoom.addEventListener('input', () => {
  updateZoomLabel();
  render();
});
controls.waterIntensity.addEventListener('input', () => {
  updateWaterIntensityLabel();
  drawDynamicEffects();
});
for (const control of [controls.ringOffsetX, controls.ringOffsetY, controls.ringScaleX, controls.ringScaleY]) {
  control.addEventListener('input', () => {
    updateRingTuningLabels();
    drawDynamicEffects();
  });
}
controls.resetRingTuning.addEventListener('click', () => {
  resetRingTuning();
  drawDynamicEffects();
});
for (const control of [controls.rippleOffsetX, controls.rippleOffsetY, controls.rippleScaleX, controls.rippleScaleY]) {
  control.addEventListener('input', () => {
    updateRippleTuningLabels();
    drawDynamicEffects();
  });
}
controls.resetRippleTuning.addEventListener('click', () => {
  resetRippleTuning();
  drawDynamicEffects();
});
controls.surfaceSelect.addEventListener('change', render);
controls.scrollControlsUp.addEventListener('click', () => {
  controls.controlsPanel.scrollBy({
    top: -Math.max(280, controls.controlsPanel.clientHeight * 0.78),
    behavior: 'smooth',
  });
  controls.controlsPanel.focus({ preventScroll: true });
});
controls.scrollControlsDown.addEventListener('click', () => {
  controls.controlsPanel.scrollBy({
    top: Math.max(280, controls.controlsPanel.clientHeight * 0.78),
    behavior: 'smooth',
  });
  controls.controlsPanel.focus({ preventScroll: true });
});
canvas.addEventListener('wheel', (event) => {
  event.preventDefault();
  const direction = event.deltaY < 0 ? 1 : -1;
  const next = Math.max(Number(controls.zoom.min), Math.min(
    Number(controls.zoom.max),
    Number(controls.zoom.value) + direction * Number(controls.zoom.step),
  ));
  controls.zoom.value = String(next);
  updateZoomLabel();
  render();
}, { passive: false });
function pickCanvasCell(event) {
  if (!latestScene) return null;
  const rect = canvas.getBoundingClientRect();
  const x = (event.clientX - rect.left) / latestScene.scale;
  const y = (event.clientY - rect.top) / latestScene.scale;
  if (showsAncientEnvironment()) {
    const center = latestScene.cells.find((cell) => cell.num === 0) ?? latestScene.cells[0];
    const hit = hitTestAncientInfrastructure(
      [center.x + CELL_WIDTH / 2, center.y + CELL_HEIGHT / 2],
      x,
      y,
    );
    if (hit === 'ringGate') return RING_GATE_CELL_NUMS[1];
  }
  return pickCell(latestScene.cells, x, y);
}
canvas.addEventListener('mousemove', (event) => {
  const picked = pickCanvasCell(event);
  if (picked === hoveredCellNum) return;
  hoveredCellNum = picked;
  render();
});
canvas.addEventListener('mouseleave', () => {
  if (hoveredCellNum === null) return;
  hoveredCellNum = null;
  render();
});
canvas.addEventListener('click', (event) => {
  const picked = pickCanvasCell(event);
  if (picked === null) return;
  selectedCellNum = picked;
  if (memberByCell.get(picked)?.state === 'construction' && constructionBuildings.has(picked)) {
    constructionBuildings = completeConstruction(constructionBuildings, picked);
    rebuildScenario();
  }
  render();
});
const scheduleRender = () => requestAnimationFrame(render);

function sceneAnimationAllowed() {
  return controls.animateScene.checked
    && !reducedMotion.matches
    && document.visibilityState === 'visible';
}

function animateScene(timeMs) {
  sceneAnimationFrame = null;
  if (!sceneAnimationAllowed()) return;
  sceneAnimationTimeMs = timeMs;
  // Only the transparent effects canvas is redrawn. Ground, buildings, shadow
  // masks and interface overlays stay cached on the main canvas.
  if (timeMs - lastAnimatedRenderMs >= 50) {
    lastAnimatedRenderMs = timeMs;
    if (activeVisual) drawDynamicEffects(timeMs);
  }
  sceneAnimationFrame = requestAnimationFrame(animateScene);
}

function syncSceneAnimation() {
  if (sceneAnimationAllowed()) {
    if (sceneAnimationFrame === null) sceneAnimationFrame = requestAnimationFrame(animateScene);
    return;
  }
  if (sceneAnimationFrame !== null) cancelAnimationFrame(sceneAnimationFrame);
  sceneAnimationFrame = null;
  drawDynamicEffects();
}

controls.animateScene.addEventListener('change', syncSceneAnimation);
document.addEventListener('visibilitychange', syncSceneAnimation);
reducedMotion.addEventListener('change', () => {
  syncSceneAnimation();
  if (activeVisual) render();
});
window.addEventListener('resize', scheduleRender);
window.addEventListener('load', scheduleRender, { once: true });
new ResizeObserver(scheduleRender).observe(canvas);
scheduleRender();
syncSceneAnimation();
selectPackage(controls.packageSelect.value);
