const MACRO_PLATES = Object.freeze([
  Object.freeze([[-204, -32], [-151, -72], [-72, -82], [-51, -39], [-118, -15]]),
  Object.freeze([[18, -81], [102, -79], [190, -31], [143, -5], [63, -22]]),
  Object.freeze([[-205, 9], [-143, 18], [-105, 58], [-167, 81], [-216, 43]]),
  Object.freeze([[49, 37], [126, 17], [211, 24], [179, 79], [90, 85]]),
]);

const PANEL_SEAMS = Object.freeze([
  Object.freeze([[-181, -8], [-142, -27], [-114, -56], [-69, -70]]),
  Object.freeze([[-112, 74], [-58, 55], [-15, 68], [30, 84]]),
  Object.freeze([[54, -72], [91, -48], [143, -45], [187, -15]]),
  Object.freeze([[83, 75], [112, 45], [159, 36], [198, 5]]),
]);

const GOLD_TRIMS = Object.freeze([
  Object.freeze([[-198, -28], [-164, -44], [-126, -64], [-91, -65]]),
  Object.freeze([[-194, 31], [-156, 40], [-128, 60], [-97, 67]]),
  Object.freeze([[94, -67], [137, -57], [174, -35], [198, -13]]),
  Object.freeze([[100, 67], [138, 54], [169, 36], [198, 26]]),
]);

export const RING_GATE_ENTRANCE_OFFSET = Object.freeze([178, 15]);

const ENERGY_CHANNELS = Object.freeze([
  Object.freeze([[-205, -2], [-163, -9], [-133, -38], [-91, -55]]),
  Object.freeze([[207, 2], [193, 5], [184, 10], RING_GATE_ENTRANCE_OFFSET]),
  Object.freeze([[-77, 83], [-28, 70], [13, 82], [58, 75]]),
]);

const HATCHES = Object.freeze([
  Object.freeze([-145, 35, 10]),
  Object.freeze([144, -29, 9]),
  Object.freeze([82, 72, 8]),
  Object.freeze([-72, -70, 7]),
]);

const SERVICE_PANELS = Object.freeze([
  Object.freeze([-174, 24, 20, 7]),
  Object.freeze([128, 59, 19, 6.5]),
  Object.freeze([132, -55, 18, 6]),
]);

const ARCHAEOLOGY_NODES = Object.freeze([
  Object.freeze({ x: -185, y: 17, kind: 'scanner' }),
  Object.freeze({ x: -137, y: 70, kind: 'excavation' }),
  Object.freeze({ x: 105, y: 73, kind: 'relay' }),
]);

const GATE_SPRITE_URL = new URL(
  '../../assets/tech/infrastructure/exports/voraner-ring-gate-v02-color.png?v=20261002-19',
  import.meta.url,
);
export const RING_GATE_CELL_NUMS = Object.freeze([22, 42]);
const RING_GATE_OFFSET = Object.freeze([154, 17]);
const RING_GATE_WIDTH = 80;
const gateShadowCache = new Map();
const gateSprite = typeof Image === 'undefined' ? null : new Image();
if (gateSprite) {
  gateSprite.decoding = 'async';
  gateSprite.addEventListener('load', () => {
    window.dispatchEvent(new Event('ancient-environment-assets-ready'));
  }, { once: true });
  gateSprite.src = GATE_SPRITE_URL.href;
}

const BACKDROP_STARS = Object.freeze([
  [0.08, 0.14, 0.7], [0.13, 0.31, 0.45], [0.19, 0.09, 0.5], [0.24, 0.24, 0.8],
  [0.31, 0.13, 0.38], [0.37, 0.34, 0.58], [0.43, 0.08, 0.72], [0.5, 0.22, 0.4],
  [0.57, 0.12, 0.55], [0.63, 0.3, 0.74], [0.69, 0.07, 0.42], [0.75, 0.21, 0.62],
  [0.82, 0.11, 0.82], [0.88, 0.29, 0.46], [0.93, 0.17, 0.64], [0.71, 0.4, 0.36],
]);

const EDGE_LIGHTS = Object.freeze([
  Object.freeze([[-164, 72], [-148, 81]]),
  Object.freeze([[-113, 88], [-99, 95]]),
  Object.freeze([[-65, 87], [-51, 95]]),
  Object.freeze([[51, 95], [65, 87]]),
  Object.freeze([[99, 95], [113, 88]]),
  Object.freeze([[148, 81], [164, 72]]),
]);

function path(ctx, [centerX, centerY], points, offsetX = 0, offsetY = 0) {
  ctx.beginPath();
  points.forEach(([x, y], index) => {
    const pointX = centerX + x + offsetX;
    const pointY = centerY + y + offsetY;
    if (index === 0) ctx.moveTo(pointX, pointY);
    else ctx.lineTo(pointX, pointY);
  });
}

export function drawRingWorldBackdrop(ctx, width, height) {
  ctx.save();
  const glow = ctx.createRadialGradient(
    width * 0.82,
    height * 0.06,
    0,
    width * 0.82,
    height * 0.06,
    width * 0.64,
  );
  glow.addColorStop(0, 'rgba(41, 99, 135, 0.17)');
  glow.addColorStop(0.45, 'rgba(11, 45, 72, 0.08)');
  glow.addColorStop(1, 'rgba(2, 16, 28, 0)');
  ctx.fillStyle = glow;
  ctx.fillRect(0, 0, width, height);

  BACKDROP_STARS.forEach(([u, v, strength], index) => {
    const x = width * u;
    const y = height * v;
    const radius = index % 5 === 0 ? 1.15 : 0.62;
    ctx.fillStyle = `rgba(174, 224, 246, ${strength})`;
    ctx.beginPath();
    ctx.arc(x, y, radius, 0, Math.PI * 2);
    ctx.fill();
  });

  ctx.strokeStyle = 'rgba(91, 145, 173, 0.12)';
  ctx.lineWidth = 5;
  ctx.beginPath();
  ctx.ellipse(width * 0.51, height * 0.08, width * 0.59, height * 0.22, -0.035, 0.08, Math.PI - 0.05);
  ctx.stroke();
  ctx.strokeStyle = 'rgba(62, 192, 224, 0.16)';
  ctx.lineWidth = 1.1;
  ctx.setLineDash([24, 13]);
  ctx.beginPath();
  ctx.ellipse(width * 0.51, height * 0.08, width * 0.59, height * 0.22, -0.035, 0.08, Math.PI - 0.05);
  ctx.stroke();
  ctx.setLineDash([]);

  ctx.strokeStyle = 'rgba(205, 186, 133, 0.09)';
  ctx.lineWidth = 2;
  ctx.beginPath();
  ctx.ellipse(width * 0.52, height * -0.04, width * 0.43, height * 0.16, 0.02, 0.12, Math.PI - 0.08);
  ctx.stroke();
  ctx.restore();
}

export function drawAncientPlatformEdgeLights(ctx, center) {
  ctx.save();
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
  EDGE_LIGHTS.forEach((points) => {
    path(ctx, center, points, 0, 0.4);
    ctx.strokeStyle = 'rgba(7, 23, 32, 0.92)';
    ctx.lineWidth = 3.4;
    ctx.stroke();
    ctx.save();
    path(ctx, center, points, 0, 0.25);
    ctx.globalCompositeOperation = 'screen';
    ctx.strokeStyle = 'rgba(27, 179, 219, 0.72)';
    ctx.lineWidth = 1.55;
    ctx.shadowColor = 'rgba(46, 218, 255, 0.75)';
    ctx.shadowBlur = 3;
    ctx.stroke();
    path(ctx, center, points, 0, 0.2);
    ctx.strokeStyle = 'rgba(187, 244, 255, 0.72)';
    ctx.lineWidth = 0.5;
    ctx.shadowBlur = 0;
    ctx.stroke();
    ctx.restore();
  });
  ctx.restore();
}

function drawMacroPlates(ctx, center) {
  MACRO_PLATES.forEach((points, index) => {
    path(ctx, center, points);
    ctx.closePath();
    ctx.fillStyle = index % 2 === 0 ? 'rgba(224, 245, 241, 0.035)' : 'rgba(23, 49, 57, 0.055)';
    ctx.fill();
  });
}

function drawPanelSeams(ctx, center) {
  PANEL_SEAMS.forEach((points) => {
    path(ctx, center, points, -1.1, -0.8);
    ctx.strokeStyle = 'rgba(28, 52, 59, 0.32)';
    ctx.lineWidth = 1.35;
    ctx.stroke();
    path(ctx, center, points, 0.75, 0.55);
    ctx.strokeStyle = 'rgba(196, 229, 225, 0.22)';
    ctx.lineWidth = 0.7;
    ctx.stroke();
  });

  GOLD_TRIMS.forEach((points) => {
    path(ctx, center, points);
    ctx.strokeStyle = 'rgba(31, 48, 52, 0.62)';
    ctx.lineWidth = 2.5;
    ctx.stroke();
    path(ctx, center, points, 0.2, 0.15);
    ctx.strokeStyle = 'rgba(224, 191, 111, 0.72)';
    ctx.lineWidth = 0.72;
    ctx.stroke();
  });
}

function drawEnergyChannels(ctx, center) {
  ENERGY_CHANNELS.forEach((points) => {
    path(ctx, center, points);
    ctx.strokeStyle = 'rgba(14, 39, 48, 0.92)';
    ctx.lineWidth = 4.4;
    ctx.lineJoin = 'round';
    ctx.stroke();

    ctx.save();
    path(ctx, center, points);
    ctx.globalCompositeOperation = 'screen';
    ctx.strokeStyle = 'rgba(18, 193, 235, 0.74)';
    ctx.lineWidth = 2.25;
    ctx.shadowColor = 'rgba(44, 220, 255, 0.88)';
    ctx.shadowBlur = 5;
    ctx.stroke();
    path(ctx, center, points);
    ctx.strokeStyle = 'rgba(180, 249, 255, 0.9)';
    ctx.lineWidth = 0.55;
    ctx.shadowBlur = 0;
    ctx.stroke();
    ctx.restore();
  });
}

function drawHatches(ctx, [centerX, centerY]) {
  HATCHES.forEach(([x, y, radius]) => {
    const hatchX = centerX + x;
    const hatchY = centerY + y;
    ctx.fillStyle = 'rgba(17, 39, 47, 0.46)';
    ctx.beginPath();
    ctx.ellipse(hatchX - 1.5, hatchY - 1, radius + 1.8, radius * 0.46 + 1, -0.08, 0, Math.PI * 2);
    ctx.fill();
    ctx.fillStyle = 'rgba(85, 112, 117, 0.88)';
    ctx.strokeStyle = 'rgba(212, 188, 122, 0.66)';
    ctx.lineWidth = 0.8;
    ctx.beginPath();
    ctx.ellipse(hatchX, hatchY, radius, radius * 0.46, -0.08, 0, Math.PI * 2);
    ctx.fill();
    ctx.stroke();
    ctx.strokeStyle = 'rgba(28, 56, 64, 0.72)';
    ctx.beginPath();
    ctx.ellipse(hatchX, hatchY, radius * 0.57, radius * 0.25, -0.08, 0, Math.PI * 2);
    ctx.stroke();
  });
}

function diamond(ctx, x, y, halfWidth, halfHeight, inset = 0) {
  ctx.beginPath();
  ctx.moveTo(x - halfWidth + inset, y);
  ctx.lineTo(x, y - halfHeight + inset * 0.45);
  ctx.lineTo(x + halfWidth - inset, y);
  ctx.lineTo(x, y + halfHeight - inset * 0.45);
  ctx.closePath();
}

function drawServicePanels(ctx, [centerX, centerY]) {
  SERVICE_PANELS.forEach(([offsetX, offsetY, halfWidth, halfHeight]) => {
    const x = centerX + offsetX;
    const y = centerY + offsetY;
    diamond(ctx, x, y, halfWidth, halfHeight);
    ctx.fillStyle = 'rgba(41, 63, 68, 0.5)';
    ctx.strokeStyle = 'rgba(205, 179, 111, 0.62)';
    ctx.lineWidth = 0.75;
    ctx.fill();
    ctx.stroke();
    ctx.strokeStyle = 'rgba(32, 48, 54, 0.72)';
    ctx.lineWidth = 0.7;
    for (let slot = -2; slot <= 2; slot += 1) {
      ctx.beginPath();
      ctx.moveTo(x - 7 + slot * 2.1, y + 1.8);
      ctx.lineTo(x - 2 + slot * 2.1, y - 1.8);
      ctx.stroke();
    }
  });
}

function drawAttachedShadow(ctx, x, y, halfWidth, length) {
  ctx.save();
  ctx.globalCompositeOperation = 'multiply';
  ctx.filter = 'blur(1.2px)';
  ctx.fillStyle = 'rgba(2, 10, 16, 0.34)';
  ctx.beginPath();
  ctx.moveTo(x - halfWidth, y);
  ctx.lineTo(x + halfWidth, y);
  ctx.lineTo(x - length + halfWidth * 0.55, y - length * 0.3);
  ctx.lineTo(x - length - halfWidth * 0.55, y - length * 0.3);
  ctx.closePath();
  ctx.fill();
  ctx.restore();
}

function drawScannerPylon(ctx, x, y) {
  drawAttachedShadow(ctx, x, y + 2, 8, 18);
  ctx.fillStyle = 'rgba(16, 34, 43, 0.94)';
  ctx.strokeStyle = 'rgba(198, 177, 116, 0.78)';
  ctx.lineWidth = 0.8;
  ctx.beginPath();
  ctx.moveTo(x - 10, y + 4);
  ctx.lineTo(x, y - 1);
  ctx.lineTo(x + 10, y + 4);
  ctx.lineTo(x, y + 9);
  ctx.closePath();
  ctx.fill();
  ctx.stroke();
  ctx.fillStyle = '#62747a';
  ctx.beginPath();
  ctx.moveTo(x - 3.7, y + 2);
  ctx.lineTo(x - 2.2, y - 20);
  ctx.lineTo(x + 2.2, y - 20);
  ctx.lineTo(x + 3.7, y + 2);
  ctx.closePath();
  ctx.fill();
  ctx.stroke();
  ctx.save();
  ctx.globalCompositeOperation = 'screen';
  ctx.strokeStyle = 'rgba(89, 224, 255, 0.88)';
  ctx.shadowColor = 'rgba(38, 202, 255, 0.85)';
  ctx.shadowBlur = 4;
  ctx.lineWidth = 1.2;
  ctx.beginPath();
  ctx.ellipse(x, y - 19, 8.5, 3.4, 0, 0, Math.PI * 2);
  ctx.stroke();
  ctx.beginPath();
  ctx.moveTo(x, y - 19);
  ctx.lineTo(x, y - 6);
  ctx.stroke();
  ctx.restore();
}

function drawExcavationBay(ctx, x, y) {
  drawAttachedShadow(ctx, x, y + 5, 15, 11);
  diamond(ctx, x, y, 18, 7.5);
  ctx.fillStyle = 'rgba(18, 34, 40, 0.92)';
  ctx.strokeStyle = 'rgba(206, 179, 108, 0.82)';
  ctx.lineWidth = 0.9;
  ctx.fill();
  ctx.stroke();
  diamond(ctx, x, y, 13, 5.1);
  ctx.fillStyle = 'rgba(5, 17, 23, 0.96)';
  ctx.fill();
  ctx.setLineDash([2.2, 1.6]);
  ctx.strokeStyle = 'rgba(78, 215, 241, 0.65)';
  ctx.lineWidth = 0.65;
  ctx.stroke();
  ctx.setLineDash([]);
  ctx.fillStyle = 'rgba(118, 139, 141, 0.86)';
  for (const offsetX of [-11, 11]) {
    ctx.fillRect(x + offsetX - 1.2, y - 9, 2.4, 9);
    ctx.fillStyle = 'rgba(57, 207, 237, 0.9)';
    ctx.beginPath();
    ctx.arc(x + offsetX, y - 9, 1.25, 0, Math.PI * 2);
    ctx.fill();
    ctx.fillStyle = 'rgba(118, 139, 141, 0.86)';
  }
}

function drawRelayNode(ctx, x, y) {
  drawAttachedShadow(ctx, x, y + 3, 9, 14);
  ctx.fillStyle = 'rgba(23, 43, 50, 0.96)';
  ctx.strokeStyle = 'rgba(197, 171, 105, 0.76)';
  ctx.lineWidth = 0.8;
  ctx.beginPath();
  ctx.ellipse(x, y + 2, 10, 4.8, 0, 0, Math.PI * 2);
  ctx.fill();
  ctx.stroke();
  ctx.fillStyle = '#75878a';
  ctx.fillRect(x - 2.6, y - 14, 5.2, 16);
  ctx.strokeRect(x - 2.6, y - 14, 5.2, 16);
  ctx.save();
  ctx.globalCompositeOperation = 'screen';
  ctx.strokeStyle = 'rgba(81, 221, 251, 0.9)';
  ctx.shadowColor = 'rgba(45, 204, 255, 0.82)';
  ctx.shadowBlur = 3.5;
  ctx.lineWidth = 1.1;
  ctx.beginPath();
  ctx.arc(x, y - 13, 5.5, Math.PI * 0.1, Math.PI * 0.9);
  ctx.stroke();
  ctx.beginPath();
  ctx.arc(x, y - 13, 8.5, Math.PI * 0.12, Math.PI * 0.88);
  ctx.stroke();
  ctx.restore();
}

function drawArchaeologyNodes(ctx, [centerX, centerY]) {
  ARCHAEOLOGY_NODES.forEach(({ x, y, kind }) => {
    const drawX = centerX + x;
    const drawY = centerY + y;
    if (kind === 'scanner') drawScannerPylon(ctx, drawX, drawY);
    else if (kind === 'excavation') drawExcavationBay(ctx, drawX, drawY);
    else drawRelayNode(ctx, drawX, drawY);
  });
}

function drawGateNode(ctx, [centerX, centerY]) {
  if (!gateSprite?.complete || gateSprite.naturalWidth === 0) return;
  const width = RING_GATE_WIDTH;
  const height = width * (gateSprite.naturalHeight / gateSprite.naturalWidth);
  const anchorX = centerX + RING_GATE_OFFSET[0];
  const anchorY = centerY + RING_GATE_OFFSET[1];

  ctx.save();
  ctx.drawImage(gateSprite, anchorX - width / 2, anchorY - height, width, height);
  ctx.restore();
}

function gateShadowFor(tuning) {
  if (!gateSprite?.complete || gateSprite.naturalWidth === 0 || !tuning) return null;
  const cacheKey = [
    tuning.azimuth,
    tuning.elevation,
    tuning.compression,
    tuning.alphaThreshold,
    tuning.columnStep,
    tuning.rayWidth,
    tuning.baselineOffset,
  ].join(':');
  if (gateShadowCache.has(cacheKey)) return gateShadowCache.get(cacheKey);

  const source = document.createElement('canvas');
  source.width = gateSprite.naturalWidth;
  source.height = gateSprite.naturalHeight;
  const sourceCtx = source.getContext('2d', { willReadFrequently: true });
  sourceCtx.drawImage(gateSprite, 0, 0);
  const rgba = sourceCtx.getImageData(0, 0, source.width, source.height).data;
  const alpha = new Uint8ClampedArray(source.width * source.height);
  for (let index = 0; index < alpha.length; index += 1) alpha[index] = rgba[index * 4 + 3];

  const segments = extractSilhouetteShadowSegments({
    alpha,
    width: source.width,
    height: source.height,
    anchorY: 0.98 + tuning.baselineOffset,
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
  shadowCtx.lineWidth = tuning.rayWidth;
  shadowCtx.lineCap = 'butt';
  shadowCtx.beginPath();
  segments.forEach(({ start, end }) => {
    shadowCtx.moveTo(start[0], start[1]);
    shadowCtx.lineTo(end[0], end[1]);
  });
  shadowCtx.stroke();
  gateShadowCache.set(cacheKey, shadow);
  return shadow;
}

function drawGateShadow(ctx, [centerX, centerY], tuning) {
  const shadow = gateShadowFor(tuning);
  if (!shadow) return;
  const width = RING_GATE_WIDTH;
  const height = width * (gateSprite.naturalHeight / gateSprite.naturalWidth);
  const anchorX = centerX + RING_GATE_OFFSET[0];
  const anchorY = centerY + RING_GATE_OFFSET[1];
  ctx.save();
  ctx.globalCompositeOperation = 'multiply';
  ctx.globalAlpha = tuning.mathOpacity;
  ctx.filter = tuning.blur > 0 ? `blur(${tuning.blur}px)` : 'none';
  ctx.drawImage(shadow, anchorX - width / 2, anchorY - height, width, height);
  ctx.restore();
}

export function hitTestAncientInfrastructure([centerX, centerY], x, y) {
  const anchorX = centerX + RING_GATE_OFFSET[0];
  const anchorY = centerY + RING_GATE_OFFSET[1];
  const estimatedHeight = RING_GATE_WIDTH * (466 / 640);
  return (
    x >= anchorX - RING_GATE_WIDTH / 2
    && x <= anchorX + RING_GATE_WIDTH / 2
    && y >= anchorY - estimatedHeight
    && y <= anchorY
  ) ? 'ringGate' : null;
}

export function drawAncientInfrastructure(ctx, center, options = {}) {
  ctx.save();
  drawArchaeologyNodes(ctx, center);
  drawGateShadow(ctx, center, options.shadow);
  drawGateNode(ctx, center);
  ctx.restore();
}

function drawArchaeologyMarks(ctx, [centerX, centerY]) {
  ctx.save();
  ctx.setLineDash([3, 2]);
  ctx.strokeStyle = 'rgba(233, 201, 117, 0.72)';
  ctx.lineWidth = 0.85;
  diamond(ctx, centerX - 168, centerY + 48, 20, 8.5);
  ctx.stroke();
  ctx.setLineDash([]);
  ctx.fillStyle = 'rgba(70, 221, 245, 0.72)';
  for (const [x, y] of [[-177, 49], [-168, 45], [-158, 50]]) {
    ctx.beginPath();
    ctx.arc(centerX + x, centerY + y, 1.1, 0, Math.PI * 2);
    ctx.fill();
  }
  ctx.restore();
}

export function drawAncientEnvironment(ctx, center) {
  ctx.save();
  drawMacroPlates(ctx, center);
  drawPanelSeams(ctx, center);
  drawEnergyChannels(ctx, center);
  drawServicePanels(ctx, center);
  drawHatches(ctx, center);
  drawArchaeologyMarks(ctx, center);
  ctx.restore();
}
import { extractSilhouetteShadowSegments } from './silhouetteShadow.mjs?v=20261002-2';
