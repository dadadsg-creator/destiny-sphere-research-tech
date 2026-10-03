import assert from 'node:assert/strict';
import test from 'node:test';

import { buildArchitecturalLinks } from '../src/architecturalLinks.mjs';
import { resolveComplexes } from '../src/complexes.mjs';
import { resolveComplexFusions } from '../src/complexFusion.mjs';
import { nineteenCellCluster, sevenCellCluster } from '../src/geometry.mjs';

test('visual links connect adjacent occupied objects without creating cell occupancy', () => {
  const cells = sevenCellCluster();
  const links = buildArchitecturalLinks({ cells, occupiedCellNums: [0, 2, 3] });
  assert.equal(links.length, 2);
  assert.deepEqual(
    [...new Set(links.flatMap((link) => [link.fromCellNum, link.toCellNum]))].sort((a, b) => a - b),
    [0, 2, 3],
  );
  assert.equal(links.every((link) => link.port.length === 2), true);
});

test('internal cells of a fused complex do not receive decorative links', () => {
  const cells = sevenCellCluster();
  const complexes = resolveComplexes(
    cells.map((cell) => ({ cellNum: cell.num, complexGroup: 'residential' })),
    cells,
    { location: 'PILOT' },
  );
  const fusions = resolveComplexFusions(complexes, cells);
  const links = buildArchitecturalLinks({
    cells,
    occupiedCellNums: cells.map((cell) => cell.num),
    fusions,
  });
  assert.equal(fusions[0].kind, 'seven');
  assert.equal(links.length, 0);
});

test('district links form a sparse deterministic forest between visual objects', () => {
  const cells = nineteenCellCluster();
  const occupiedCellNums = [0, 1, 2, 3, 4, 5, 6, 7, 9, 10, 12, 13, 15, 16, 18];
  const buildings = occupiedCellNums.map((cellNum) => ({
    cellNum,
    complexGroup: cellNum <= 6 ? 'residential-core' : 'residential-ring',
  }));
  const complexes = resolveComplexes(buildings, cells, { location: 'PILOT' });
  const fusions = resolveComplexFusions(complexes, cells);
  const first = buildArchitecturalLinks({ cells, occupiedCellNums, fusions });
  const second = buildArchitecturalLinks({ cells, occupiedCellNums: [...occupiedCellNums].reverse(), fusions });
  assert.deepEqual(second, first);
  assert.equal(first.length > 0, true);
  assert.equal(first.length < occupiedCellNums.length - 1, true);
  assert.equal(first.every((link) => link.fromObjectId !== link.toObjectId), true);
});
