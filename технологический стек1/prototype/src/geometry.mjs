export const CELL_HALF_WIDTH = 24;
export const CELL_HALF_HEIGHT = 13;
export const CELL_WIDTH = CELL_HALF_WIDTH * 2;
export const CELL_HEIGHT = CELL_HALF_HEIGHT * 2;
export const LENGTH_SIDE = 9;
export const TOTAL_CELLS = 3 * LENGTH_SIDE * LENGTH_SIDE - 3 * LENGTH_SIDE + 1;

export const HEX_POINTS = Object.freeze([
  [24, 0], [48, 6.5], [48, 19.5], [24, 26], [0, 19.5], [0, 6.5],
]);

export const PORTS = Object.freeze([
  { direction: 0, name: 'NE', point: [36, 3.25], neighbor: [24, -19.5] },
  { direction: 1, name: 'E', point: [48, 13], neighbor: [48, 0] },
  { direction: 2, name: 'SE', point: [36, 22.75], neighbor: [24, 19.5] },
  { direction: 3, name: 'SW', point: [12, 22.75], neighbor: [-24, 19.5] },
  { direction: 4, name: 'W', point: [0, 13], neighbor: [-48, 0] },
  { direction: 5, name: 'NW', point: [12, 3.25], neighbor: [-24, -19.5] },
]);

export const BUILDING_ANCHOR = Object.freeze([24, 26]);
export const PILOT_ENVELOPE = Object.freeze({ width: 96, height: 112, belowAnchor: 2 });

export function buildHexGrid(lengthSide = LENGTH_SIDE) {
  const cells = [];
  let num = 0;
  let rowDelta = 0;
  for (let row = 0; row < lengthSide * 2 - 1; row += 1) {
    const rowLength = lengthSide + rowDelta;
    for (let col = 0; col < rowLength; col += 1) {
      cells.push({
        num,
        row,
        col,
        x: (col * 2 - rowDelta + (lengthSide - 1)) * CELL_HALF_WIDTH,
        y: row * 1.5 * CELL_HALF_HEIGHT,
      });
      num += 1;
    }
    rowDelta += row < lengthSide - 1 ? 1 : -1;
  }
  return cells;
}

export function sevenCellCluster(originX = 0, originY = 0) {
  return [
    { id: 0, num: 0, name: 'CENTER', x: originX, y: originY },
    ...PORTS.map((port) => ({
      id: port.direction + 1,
      num: port.direction + 1,
      name: port.name,
      x: originX + port.neighbor[0],
      y: originY + port.neighbor[1],
    })),
  ];
}

function ringOffsets(radius) {
  const [startX, startY] = PORTS[0].neighbor;
  const walkDirections = [PORTS[2], PORTS[3], PORTS[4], PORTS[5], PORTS[0], PORTS[1]];
  const offsets = [];
  let x = startX * radius;
  let y = startY * radius;
  for (const { neighbor } of walkDirections) {
    for (let step = 0; step < radius; step += 1) {
      offsets.push([x, y]);
      x += neighbor[0];
      y += neighbor[1];
    }
  }
  return offsets;
}

export function nineteenCellCluster(originX = 0, originY = 0) {
  const inner = sevenCellCluster(originX, originY);
  const outerOffsets = ringOffsets(2);
  return [
    ...inner,
    ...outerOffsets.map(([x, y], index) => ({
      id: index + 7,
      num: index + 7,
      name: `R2-${index + 1}`,
      x: originX + x,
      y: originY + y,
    })),
  ];
}

export function sixtyOneCellCluster(originX = 0, originY = 0) {
  const inner = nineteenCellCluster(originX, originY);
  const outerOffsets = [...ringOffsets(3), ...ringOffsets(4)];
  return [
    ...inner,
    ...outerOffsets.map(([x, y], index) => ({
      id: index + 19,
      num: index + 19,
      name: index < 18 ? `R3-${index + 1}` : `R4-${index - 17}`,
      x: originX + x,
      y: originY + y,
    })),
  ];
}

export function rotateMask(mask, steps = 1) {
  const normalized = ((steps % 6) + 6) % 6;
  const sixBits = mask & 0b111111;
  return ((sixBits << normalized) | (sixBits >> (6 - normalized))) & 0b111111;
}
