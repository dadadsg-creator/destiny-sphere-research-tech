import { PORTS, rotateMask } from './geometry.mjs';

const ALL_PORTS = Object.freeze([true, true, true, true, true, true]);

function coordinateKey(x, y) {
  return `${x}:${y}`;
}

function popcount(mask) {
  let value = mask & 0b111111;
  let count = 0;
  while (value) {
    count += value & 1;
    value >>= 1;
  }
  return count;
}

export function canonicalizeMask(mask) {
  let canonicalMask = mask & 0b111111;
  let rotation = 0;
  for (let step = 1; step < 6; step += 1) {
    const candidate = rotateMask(mask, step);
    if (candidate < canonicalMask) {
      canonicalMask = candidate;
      rotation = step;
    }
  }
  return Object.freeze({ mask: canonicalMask, rotation });
}

export function buildNeighborTable(cells) {
  const byCoordinate = new Map(cells.map((cell) => [coordinateKey(cell.x, cell.y), cell.num]));
  return new Map(cells.map((cell) => [
    cell.num,
    PORTS.map((port) => byCoordinate.get(coordinateKey(cell.x + port.neighbor[0], cell.y + port.neighbor[1])) ?? null),
  ]));
}

function canParticipate(building) {
  return building?.complexGroup != null && building.state !== 'demolished';
}

function portsOf(building) {
  return building.ports ?? ALL_PORTS;
}

function canConnect(building, neighbor, direction) {
  if (!canParticipate(building) || !canParticipate(neighbor)) return false;
  if (building.connectable === false || neighbor.connectable === false) return false;
  if (building.complexGroup !== neighbor.complexGroup) return false;
  return portsOf(building)[direction] && portsOf(neighbor)[(direction + 3) % 6];
}

function distancesFrom(start, neighborTable) {
  const distance = new Map([[start, 0]]);
  const queue = [start];
  for (let index = 0; index < queue.length; index += 1) {
    const current = queue[index];
    for (const neighbor of neighborTable.get(current) ?? []) {
      if (neighbor === null || distance.has(neighbor)) continue;
      distance.set(neighbor, distance.get(current) + 1);
      queue.push(neighbor);
    }
  }
  return distance;
}

function chooseCenter(members, neighborTable) {
  const maximumDegree = Math.max(...members.map((member) => popcount(member.mask)));
  const candidates = members.filter((member) => popcount(member.mask) === maximumDegree);
  return candidates.map((member) => {
    const distances = distancesFrom(member.cellNum, neighborTable);
    const totalDistance = members.reduce((sum, other) => sum + distances.get(other.cellNum), 0);
    return { cellNum: member.cellNum, totalDistance };
  }).sort((a, b) => a.totalDistance - b.totalDistance || a.cellNum - b.cellNum)[0].cellNum;
}

export function resolveComplexes(buildings, cells, options = {}) {
  const location = options.location ?? 'HOME';
  const cellByNum = new Map(cells.map((cell) => [cell.num, cell]));
  const neighborTable = buildNeighborTable(cells);
  const buildingByCell = new Map();

  for (const building of buildings) {
    if (!cellByNum.has(building.cellNum)) throw new RangeError(`Неизвестная клетка: ${building.cellNum}`);
    if (buildingByCell.has(building.cellNum)) throw new TypeError(`На клетке ${building.cellNum} указано несколько зданий`);
    if (canParticipate(building)) buildingByCell.set(building.cellNum, building);
  }

  const unvisited = new Set([...buildingByCell.keys()].sort((a, b) => a - b));
  const complexes = [];
  while (unvisited.size > 0) {
    const start = unvisited.values().next().value;
    const group = buildingByCell.get(start).complexGroup;
    const queue = [start];
    unvisited.delete(start);
    const componentCells = [];

    for (let index = 0; index < queue.length; index += 1) {
      const cellNum = queue[index];
      componentCells.push(cellNum);
      const building = buildingByCell.get(cellNum);
      (neighborTable.get(cellNum) ?? []).forEach((neighborCell, direction) => {
        if (neighborCell === null || !unvisited.has(neighborCell)) return;
        const neighbor = buildingByCell.get(neighborCell);
        if (neighbor?.complexGroup === group && canConnect(building, neighbor, direction)) {
          unvisited.delete(neighborCell);
          queue.push(neighborCell);
        }
      });
    }

    componentCells.sort((a, b) => a - b);
    const memberSet = new Set(componentCells);
    const members = componentCells.map((cellNum) => {
      const building = buildingByCell.get(cellNum);
      let mask = 0;
      (neighborTable.get(cellNum) ?? []).forEach((neighborCell, direction) => {
        if (neighborCell === null || !memberSet.has(neighborCell)) return;
        if (canConnect(building, buildingByCell.get(neighborCell), direction)) mask |= 1 << direction;
      });
      const internalDirections = PORTS.filter((port) => mask & (1 << port.direction)).map((port) => port.direction);
      const externalDirections = PORTS.filter((port) => !(mask & (1 << port.direction))).map((port) => port.direction);
      return Object.freeze({
        ...building,
        mask,
        canonical: canonicalizeMask(mask),
        internalDirections: Object.freeze(internalDirections),
        externalDirections: Object.freeze(externalDirections),
      });
    });
    const minimumCell = componentCells[0];
    complexes.push(Object.freeze({
      id: `${location}:${group}:${minimumCell}`,
      complexGroup: group,
      cellNums: Object.freeze(componentCells),
      members: Object.freeze(members),
      centerCellNum: chooseCenter(members, neighborTable),
    }));
  }

  return Object.freeze(complexes.sort((a, b) => a.cellNums[0] - b.cellNums[0]));
}
