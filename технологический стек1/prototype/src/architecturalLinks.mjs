import { PORTS } from './geometry.mjs';
import { buildNeighborTable } from './complexes.mjs';

const FRONT_DIRECTIONS = Object.freeze([2, 3]);
const REAR_DIRECTIONS = Object.freeze([0, 5]);
const MAX_FREE_ROUTE_CELLS = 2;

function stableRank(value) {
  let hash = 2166136261;
  for (const character of value) {
    hash ^= character.charCodeAt(0);
    hash = Math.imul(hash, 16777619);
  }
  return hash >>> 0;
}

function freezePoint(point) {
  return Object.freeze(point);
}

function centerOf(cell) {
  return [cell.x + 24, cell.y + 13];
}

function uniquePoints(points) {
  const result = [];
  for (const point of points) {
    const previous = result.at(-1);
    if (previous && previous[0] === point[0] && previous[1] === point[1]) continue;
    result.push(freezePoint(point));
  }
  return Object.freeze(result);
}

function visualObjects(occupiedCellNums, fusions) {
  const objectByCell = new Map();
  const maxDegreeByObject = new Map();
  const cellNumsByObject = new Map();
  for (const fusion of fusions) {
    const objectId = `fusion:${fusion.complexId}:${fusion.kind}`;
    const cellNums = [...fusion.cellNums].sort((left, right) => left - right);
    cellNums.forEach((cellNum) => objectByCell.set(cellNum, objectId));
    cellNumsByObject.set(objectId, cellNums);
    maxDegreeByObject.set(objectId, fusion.kind === 'seven' ? 3 : 2);
  }
  for (const cellNum of occupiedCellNums) {
    if (objectByCell.has(cellNum)) continue;
    const objectId = `cell:${cellNum}`;
    objectByCell.set(cellNum, objectId);
    cellNumsByObject.set(objectId, [cellNum]);
    maxDegreeByObject.set(objectId, 1);
  }
  return { objectByCell, maxDegreeByObject, cellNumsByObject };
}

function unionFind(ids) {
  const parent = new Map(ids.map((id) => [id, id]));
  const find = (id) => {
    let root = id;
    while (parent.get(root) !== root) root = parent.get(root);
    while (parent.get(id) !== id) {
      const next = parent.get(id);
      parent.set(id, root);
      id = next;
    }
    return root;
  };
  const join = (left, right) => {
    const leftRoot = find(left);
    const rightRoot = find(right);
    if (leftRoot === rightRoot) return false;
    parent.set(rightRoot, leftRoot);
    return true;
  };
  return { find, join };
}

function objectEndpoints(cellNums, cellByNum, endpointOffset = [0, 0]) {
  const cells = cellNums.map((cellNum) => cellByNum.get(cellNum));
  const centerX = cells.reduce((sum, cell) => sum + centerOf(cell)[0], 0) / cells.length;
  return Object.freeze({
    front: freezePoint([
      centerX + endpointOffset[0],
      Math.max(...cells.map((cell) => cell.y + 26)) + endpointOffset[1],
    ]),
    rear: freezePoint([
      centerX + endpointOffset[0],
      Math.min(...cells.map((cell) => cell.y)) + endpointOffset[1],
    ]),
  });
}

function buildAccessOptions(objectId, cellNums, cellByNum, neighborTable, occupiedSet, endpointOffset) {
  const endpoints = objectEndpoints(cellNums, cellByNum, endpointOffset);
  const bestByExit = new Map();
  for (const cellNum of cellNums) {
    const cell = cellByNum.get(cellNum);
    for (const [side, directions] of [['front', FRONT_DIRECTIONS], ['rear', REAR_DIRECTIONS]]) {
      for (const direction of directions) {
        const neighborCellNum = neighborTable.get(cellNum)?.[direction] ?? null;
        if (neighborCellNum === null || occupiedSet.has(neighborCellNum)) continue;
        const port = PORTS[direction];
        const gateway = [cell.x + port.point[0], cell.y + port.point[1]];
        const endpoint = endpoints[side];
        const distance = Math.hypot(gateway[0] - endpoint[0], gateway[1] - endpoint[1]);
        const key = `${side}:${neighborCellNum}`;
        const current = bestByExit.get(key);
        if (current && current.distance <= distance) continue;
        bestByExit.set(key, {
          objectId,
          side,
          cellNum,
          direction,
          neighborCellNum,
          endpoint,
          gateway: freezePoint(gateway),
          distance,
        });
      }
    }
  }
  return [...bestByExit.values()].sort((left, right) => (
    (left.side === 'front' ? 0 : 1) - (right.side === 'front' ? 0 : 1)
    || left.neighborCellNum - right.neighborCellNum
    || left.cellNum - right.cellNum
  ));
}

function findFreeRoute(start, finish, neighborTable, freeCells) {
  const queue = [[start]];
  const visited = new Set([start]);
  for (let index = 0; index < queue.length; index += 1) {
    const route = queue[index];
    const current = route.at(-1);
    if (current === finish) return route;
    if (route.length >= MAX_FREE_ROUTE_CELLS) continue;
    for (const neighbor of neighborTable.get(current) ?? []) {
      if (neighbor === null || !freeCells.has(neighbor) || visited.has(neighbor)) continue;
      visited.add(neighbor);
      queue.push([...route, neighbor]);
    }
  }
  return null;
}

function sidePenalty(fromSide, toSide) {
  if (fromSide === 'front' && toSide === 'front') return 0;
  if (fromSide === 'rear' && toSide === 'rear') return 8;
  return 4;
}

function makeCandidate({ key, fromObjectId, toObjectId, fromAccess, toAccess, route, cellByNum, score }) {
  const routeCenters = route.map((cellNum) => centerOf(cellByNum.get(cellNum)));
  const points = uniquePoints([
    fromAccess.endpoint,
    fromAccess.gateway,
    ...routeCenters,
    toAccess.gateway,
    toAccess.endpoint,
  ]);
  return Object.freeze({
    key,
    rank: stableRank(key),
    score,
    fromCellNum: fromAccess.cellNum,
    toCellNum: toAccess.cellNum,
    fromObjectId,
    toObjectId,
    fromSide: fromAccess.side,
    toSide: toAccess.side,
    direction: fromAccess.direction,
    from: points[0],
    port: points[Math.floor(points.length / 2)],
    to: points.at(-1),
    points,
    throughCellNums: Object.freeze([...route]),
  });
}

function freeRouteCandidates(objectIds, accessByObject, cellByNum, neighborTable, freeCells) {
  const candidates = [];
  for (let fromIndex = 0; fromIndex < objectIds.length - 1; fromIndex += 1) {
    for (let toIndex = fromIndex + 1; toIndex < objectIds.length; toIndex += 1) {
      const fromObjectId = objectIds[fromIndex];
      const toObjectId = objectIds[toIndex];
      let best = null;
      for (const fromAccess of accessByObject.get(fromObjectId) ?? []) {
        for (const toAccess of accessByObject.get(toObjectId) ?? []) {
          const route = findFreeRoute(
            fromAccess.neighborCellNum,
            toAccess.neighborCellNum,
            neighborTable,
            freeCells,
          );
          if (!route) continue;
          const score = route.length * 10 + sidePenalty(fromAccess.side, toAccess.side);
          const key = `${fromObjectId}|${toObjectId}|${fromAccess.side}:${fromAccess.cellNum}:${fromAccess.direction}|${toAccess.side}:${toAccess.cellNum}:${toAccess.direction}|${route.join('.')}`;
          const candidate = makeCandidate({
            key,
            fromObjectId,
            toObjectId,
            fromAccess,
            toAccess,
            route,
            cellByNum,
            score,
          });
          if (!best || candidate.score < best.score || (
            candidate.score === best.score && candidate.rank < best.rank
          )) best = candidate;
        }
      }
      if (best) candidates.push(best);
    }
  }
  return candidates;
}

function directFrontToRearCandidates(objectByCell, cellNumsByObject, cellByNum, neighborTable, endpointOffset) {
  const bestByPair = new Map();
  for (const [upperCellNum, upperObjectId] of objectByCell) {
    const upperCell = cellByNum.get(upperCellNum);
    for (const direction of FRONT_DIRECTIONS) {
      const lowerCellNum = neighborTable.get(upperCellNum)?.[direction] ?? null;
      const lowerObjectId = objectByCell.get(lowerCellNum);
      if (!lowerObjectId || lowerObjectId === upperObjectId) continue;
      const pairKey = [upperObjectId, lowerObjectId].sort().join('|');
      const upperEndpoints = objectEndpoints(cellNumsByObject.get(upperObjectId), cellByNum, endpointOffset);
      const lowerEndpoints = objectEndpoints(cellNumsByObject.get(lowerObjectId), cellByNum, endpointOffset);
      const sharedPort = PORTS[direction];
      const points = uniquePoints([
        upperEndpoints.front,
        [upperCell.x + sharedPort.point[0], upperCell.y + sharedPort.point[1]],
        lowerEndpoints.rear,
      ]);
      const key = `${pairKey}|direct:${upperCellNum}:${lowerCellNum}`;
      const candidate = Object.freeze({
        key,
        rank: stableRank(key),
        score: 14,
        fromCellNum: upperCellNum,
        toCellNum: lowerCellNum,
        fromObjectId: upperObjectId,
        toObjectId: lowerObjectId,
        fromSide: 'front',
        toSide: 'rear',
        direction,
        from: points[0],
        port: points[1],
        to: points.at(-1),
        points,
        throughCellNums: Object.freeze([]),
      });
      const current = bestByPair.get(pairKey);
      if (!current || candidate.rank < current.rank) bestByPair.set(pairKey, candidate);
    }
  }
  return [...bestByPair.values()];
}

function candidateLinks(cells, occupiedCellNums, objectByCell, cellNumsByObject, endpointOffset) {
  const cellByNum = new Map(cells.map((cell) => [cell.num, cell]));
  const neighborTable = buildNeighborTable(cells);
  const occupiedSet = new Set(occupiedCellNums);
  const freeCells = new Set(cells.map((cell) => cell.num).filter((cellNum) => !occupiedSet.has(cellNum)));
  const objectIds = [...cellNumsByObject.keys()].sort();
  const accessByObject = new Map(objectIds.map((objectId) => [
    objectId,
    buildAccessOptions(objectId, cellNumsByObject.get(objectId), cellByNum, neighborTable, occupiedSet, endpointOffset),
  ]));
  const candidates = [
    ...freeRouteCandidates(objectIds, accessByObject, cellByNum, neighborTable, freeCells),
    ...directFrontToRearCandidates(objectByCell, cellNumsByObject, cellByNum, neighborTable, endpointOffset),
  ];
  const bestByPair = new Map();
  for (const candidate of candidates) {
    const pairKey = [candidate.fromObjectId, candidate.toObjectId].sort().join('|');
    const current = bestByPair.get(pairKey);
    if (!current || candidate.score < current.score || (
      candidate.score === current.score && candidate.rank < current.rank
    )) bestByPair.set(pairKey, candidate);
  }
  return [...bestByPair.values()].sort((left, right) => (
    left.score - right.score || left.rank - right.rank || left.key.localeCompare(right.key)
  ));
}

export function buildArchitecturalLinks({ cells, occupiedCellNums, fusions = [], endpointOffset = [0, 0] }) {
  const occupied = [...new Set(occupiedCellNums)].sort((left, right) => left - right);
  const { objectByCell, maxDegreeByObject, cellNumsByObject } = visualObjects(occupied, fusions);
  const objectIds = [...cellNumsByObject.keys()].sort();
  const candidates = candidateLinks(cells, occupied, objectByCell, cellNumsByObject, endpointOffset);
  const selected = [];
  const degree = new Map(objectIds.map((id) => [id, 0]));
  const forest = unionFind(objectIds);

  const accept = (candidate) => {
    if (forest.find(candidate.fromObjectId) === forest.find(candidate.toObjectId)) return false;
    forest.join(candidate.fromObjectId, candidate.toObjectId);
    selected.push(candidate);
    degree.set(candidate.fromObjectId, degree.get(candidate.fromObjectId) + 1);
    degree.set(candidate.toObjectId, degree.get(candidate.toObjectId) + 1);
    return true;
  };

  for (const candidate of candidates) {
    if (
      degree.get(candidate.fromObjectId) >= maxDegreeByObject.get(candidate.fromObjectId)
      || degree.get(candidate.toObjectId) >= maxDegreeByObject.get(candidate.toObjectId)
    ) continue;
    accept(candidate);
  }

  return Object.freeze(selected.sort((left, right) => (
    left.port[1] - right.port[1]
    || left.port[0] - right.port[0]
    || left.key.localeCompare(right.key)
  )));
}
