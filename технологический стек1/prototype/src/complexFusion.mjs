import { BUILDING_ANCHOR } from './geometry.mjs';
import { buildNeighborTable } from './complexes.mjs';

function anchorOf(cell) {
  return [cell.x + BUILDING_ANCHOR[0], cell.y + BUILDING_ANCHOR[1]];
}

function internalEdgeCount(complex) {
  return complex.members.reduce((sum, member) => sum + member.internalDirections.length, 0) / 2;
}

function groupAnchorOf(cellNums, cellByNum) {
  const anchors = cellNums.map((cellNum) => anchorOf(cellByNum.get(cellNum)));
  return [
    anchors.reduce((sum, point) => sum + point[0], 0) / anchors.length,
    Math.max(...anchors.map((point) => point[1])),
  ];
}

function findTriangle(complex, neighborTable) {
  const members = [...complex.cellNums].sort((a, b) => a - b);
  const adjacent = (left, right) => neighborTable.get(left)?.includes(right) ?? false;
  for (let a = 0; a < members.length - 2; a += 1) {
    for (let b = a + 1; b < members.length - 1; b += 1) {
      if (!adjacent(members[a], members[b])) continue;
      for (let c = b + 1; c < members.length; c += 1) {
        if (adjacent(members[a], members[c]) && adjacent(members[b], members[c])) {
          return [members[a], members[b], members[c]];
        }
      }
    }
  }
  return null;
}

export function resolveComplexFusions(complexes, cells) {
  const cellByNum = new Map(cells.map((cell) => [cell.num, cell]));
  const neighborTable = buildNeighborTable(cells);
  const fusions = [];

  for (const complex of complexes) {
    const edges = internalEdgeCount(complex);
    let kind = null;
    let fusedCellNums = null;
    if (complex.members.length === 7) {
      const center = complex.members.find((member) => member.cellNum === complex.centerCellNum);
      if (center?.internalDirections.length === 6 && edges === 12) {
        kind = 'seven';
        fusedCellNums = [...complex.cellNums];
      }
    }
    if (!kind) {
      const triangle = findTriangle(complex, neighborTable);
      if (triangle) {
        kind = 'three';
        fusedCellNums = triangle;
      }
    }
    if (!kind) continue;

    fusions.push(Object.freeze({
      complexId: complex.id,
      kind,
      anchor: Object.freeze(groupAnchorOf(fusedCellNums, cellByNum)),
      cellNums: Object.freeze(fusedCellNums),
    }));
  }

  return Object.freeze(fusions.sort((a, b) => a.anchor[1] - b.anchor[1] || a.anchor[0] - b.anchor[0]));
}

export function sortComplexRenderItems(items) {
  return Object.freeze([...items].sort((left, right) => (
    left.anchor[1] - right.anchor[1]
    || left.anchor[0] - right.anchor[0]
    || left.kind.localeCompare(right.kind)
    || (left.cellNum ?? -1) - (right.cellNum ?? -1)
  )));
}
