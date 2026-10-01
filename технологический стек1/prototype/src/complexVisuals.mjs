import { BUILDING_ANCHOR, HEX_POINTS, PORTS } from './geometry.mjs';
import { buildNeighborTable } from './complexes.mjs';

function freezeList(items) {
  return Object.freeze(items.map((item) => Object.freeze(item)));
}

function anchorOf(cell) {
  return [cell.x + BUILDING_ANCHOR[0], cell.y + BUILDING_ANCHOR[1]];
}

function edgeOf(cell, direction) {
  const start = HEX_POINTS[direction];
  const end = HEX_POINTS[(direction + 1) % HEX_POINTS.length];
  return {
    start: [cell.x + start[0], cell.y + start[1]],
    end: [cell.x + end[0], cell.y + end[1]],
  };
}

export function pointInHex(cell, x, y) {
  let inside = false;
  for (let current = 0, previous = HEX_POINTS.length - 1; current < HEX_POINTS.length; previous = current++) {
    const ax = cell.x + HEX_POINTS[current][0];
    const ay = cell.y + HEX_POINTS[current][1];
    const bx = cell.x + HEX_POINTS[previous][0];
    const by = cell.y + HEX_POINTS[previous][1];
    const crosses = ((ay > y) !== (by > y)) && (x < ((bx - ax) * (y - ay)) / (by - ay) + ax);
    if (crosses) inside = !inside;
  }
  return inside;
}

export function pickCell(cells, x, y) {
  const matches = cells.filter((cell) => pointInHex(cell, x, y));
  if (matches.length === 0) return null;
  return matches.sort((a, b) => b.y - a.y || b.x - a.x || a.num - b.num)[0].num;
}

export function buildComplexVisualPlan(complexes, cells) {
  const cellByNum = new Map(cells.map((cell) => [cell.num, cell]));
  const neighborTable = buildNeighborTable(cells);
  const bases = [];
  const connectors = [];
  const perimeters = [];
  const courtyards = [];
  const centerpieces = [];

  for (const complex of complexes) {
    const memberByCell = new Map(complex.members.map((member) => [member.cellNum, member]));
    for (const member of complex.members) {
      const cell = cellByNum.get(member.cellNum);
      bases.push({ complexId: complex.id, cellNum: member.cellNum, anchor: anchorOf(cell) });

      for (const direction of member.internalDirections) {
        const neighborCellNum = neighborTable.get(member.cellNum)[direction];
        if (neighborCellNum === null || member.cellNum >= neighborCellNum) continue;
        connectors.push({
          complexId: complex.id,
          fromCellNum: member.cellNum,
          toCellNum: neighborCellNum,
          direction,
          from: anchorOf(cell),
          to: anchorOf(cellByNum.get(neighborCellNum)),
        });
      }

      for (const direction of member.externalDirections) {
        perimeters.push({
          complexId: complex.id,
          cellNum: member.cellNum,
          direction,
          ...edgeOf(cell, direction),
        });
      }
    }

    const anchors = complex.cellNums.map((cellNum) => anchorOf(cellByNum.get(cellNum)));
    const centroid = [
      anchors.reduce((sum, point) => sum + point[0], 0) / anchors.length,
      anchors.reduce((sum, point) => sum + point[1], 0) / anchors.length,
    ];
    const internalEdgeCount = complex.members.reduce((sum, member) => sum + member.internalDirections.length, 0) / 2;
    const centroidOccupied = anchors.some((point) => Math.hypot(point[0] - centroid[0], point[1] - centroid[1]) < 1);
    if (complex.members.length >= 3 && internalEdgeCount >= complex.members.length && !centroidOccupied) {
      courtyards.push({ complexId: complex.id, center: centroid, memberCount: complex.members.length });
    }

    const center = memberByCell.get(complex.centerCellNum);
    if (center && center.internalDirections.length >= 4) {
      centerpieces.push({
        complexId: complex.id,
        cellNum: center.cellNum,
        anchor: anchorOf(cellByNum.get(center.cellNum)),
      });
    }
  }

  bases.sort((a, b) => a.anchor[1] - b.anchor[1] || a.anchor[0] - b.anchor[0] || a.cellNum - b.cellNum);
  return Object.freeze({
    bases: freezeList(bases),
    connectors: freezeList(connectors),
    perimeters: freezeList(perimeters),
    courtyards: freezeList(courtyards),
    centerpieces: freezeList(centerpieces),
  });
}
