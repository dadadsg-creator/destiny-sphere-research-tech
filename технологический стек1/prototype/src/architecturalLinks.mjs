import { PORTS } from './geometry.mjs';
import { buildNeighborTable } from './complexes.mjs';

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

function towardBoundary(cell, port, ratio = 0.34) {
  const center = centerOf(cell);
  const boundary = [cell.x + port.point[0], cell.y + port.point[1]];
  return [
    center[0] + (boundary[0] - center[0]) * ratio,
    center[1] + (boundary[1] - center[1]) * ratio,
  ];
}

function visualObjects(occupiedCellNums, fusions) {
  const objectByCell = new Map();
  const maxDegreeByObject = new Map();
  for (const fusion of fusions) {
    const objectId = `fusion:${fusion.complexId}:${fusion.kind}`;
    fusion.cellNums.forEach((cellNum) => objectByCell.set(cellNum, objectId));
    maxDegreeByObject.set(objectId, fusion.kind === 'seven' ? 3 : 2);
  }
  for (const cellNum of occupiedCellNums) {
    if (objectByCell.has(cellNum)) continue;
    const objectId = `cell:${cellNum}`;
    objectByCell.set(cellNum, objectId);
    maxDegreeByObject.set(objectId, 2);
  }
  return { objectByCell, maxDegreeByObject };
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

function candidateLinks(cells, objectByCell) {
  const cellByNum = new Map(cells.map((cell) => [cell.num, cell]));
  const neighbors = buildNeighborTable(cells);
  const candidates = [];
  for (const [fromCellNum, fromObjectId] of objectByCell) {
    (neighbors.get(fromCellNum) ?? []).forEach((toCellNum, direction) => {
      if (toCellNum === null || fromCellNum >= toCellNum || !objectByCell.has(toCellNum)) return;
      const toObjectId = objectByCell.get(toCellNum);
      if (fromObjectId === toObjectId) return;
      const fromCell = cellByNum.get(fromCellNum);
      const toCell = cellByNum.get(toCellNum);
      const fromPort = PORTS[direction];
      const toPort = PORTS[(direction + 3) % 6];
      const key = `${Math.min(fromCellNum, toCellNum)}:${Math.max(fromCellNum, toCellNum)}`;
      candidates.push(Object.freeze({
        key,
        rank: stableRank(`${fromObjectId}|${toObjectId}|${key}`),
        fromCellNum,
        toCellNum,
        fromObjectId,
        toObjectId,
        direction,
        from: freezePoint(towardBoundary(fromCell, fromPort)),
        port: freezePoint([fromCell.x + fromPort.point[0], fromCell.y + fromPort.point[1]]),
        to: freezePoint(towardBoundary(toCell, toPort)),
      }));
    });
  }
  return candidates.sort((left, right) => left.rank - right.rank || left.key.localeCompare(right.key));
}

export function buildArchitecturalLinks({ cells, occupiedCellNums, fusions = [] }) {
  const occupied = [...new Set(occupiedCellNums)].sort((left, right) => left - right);
  const { objectByCell, maxDegreeByObject } = visualObjects(occupied, fusions);
  const objectIds = [...new Set(objectByCell.values())];
  const candidates = candidateLinks(cells, objectByCell);
  const selected = [];
  const selectedKeys = new Set();
  const degree = new Map(objectIds.map((id) => [id, 0]));
  const forest = unionFind(objectIds);

  const accept = (candidate) => {
    if (forest.find(candidate.fromObjectId) === forest.find(candidate.toObjectId)) return false;
    forest.join(candidate.fromObjectId, candidate.toObjectId);
    selected.push(candidate);
    selectedKeys.add(candidate.key);
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
  for (const candidate of candidates) {
    if (!selectedKeys.has(candidate.key)) accept(candidate);
  }

  return Object.freeze(selected.sort((left, right) => (
    left.port[1] - right.port[1]
    || left.port[0] - right.port[0]
    || left.key.localeCompare(right.key)
  )));
}
