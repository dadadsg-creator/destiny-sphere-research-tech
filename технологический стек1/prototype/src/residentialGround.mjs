import { HEX_POINTS } from './geometry.mjs';
import { buildNeighborTable } from './complexes.mjs';

function edgeOf(cell, direction) {
  const start = HEX_POINTS[direction];
  const end = HEX_POINTS[(direction + 1) % HEX_POINTS.length];
  return Object.freeze({
    cellNum: cell.num,
    direction,
    start: Object.freeze([cell.x + start[0], cell.y + start[1]]),
    end: Object.freeze([cell.x + end[0], cell.y + end[1]]),
  });
}

export function buildResidentialGroundPlan(cells, occupiedCellNums) {
  const cellByNum = new Map(cells.map((cell) => [cell.num, cell]));
  const occupied = [...new Set(occupiedCellNums)]
    .filter((cellNum) => cellByNum.has(cellNum))
    .sort((left, right) => left - right);
  const occupiedSet = new Set(occupied);
  const neighbors = buildNeighborTable(cells);
  const externalEdges = [];

  for (const cellNum of occupied) {
    const cell = cellByNum.get(cellNum);
    (neighbors.get(cellNum) ?? []).forEach((neighborCellNum, direction) => {
      if (!occupiedSet.has(neighborCellNum)) externalEdges.push(edgeOf(cell, direction));
    });
  }

  return Object.freeze({
    cellNums: Object.freeze(occupied),
    externalEdges: Object.freeze(externalEdges.sort((left, right) => (
      left.cellNum - right.cellNum || left.direction - right.direction
    ))),
  });
}
