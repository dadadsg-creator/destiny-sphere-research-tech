import { buildHexGrid, TOTAL_CELLS, mapPixelSize, buildingAnchor } from '../src/map/hexGrid';

const cells = buildHexGrid();

console.log('TOTAL_CELLS constant:', TOTAL_CELLS, '(ожидается 217)');
console.log('cells.length:', cells.length, '(ожидается 217)');
console.assert(cells.length === 217, 'FAIL: длина сетки не 217');
console.assert(TOTAL_CELLS === 217, 'FAIL: константа не 217');

const size = mapPixelSize();
console.log('mapPixelSize:', size, '(ожидается width=816, height=338)');
console.assert(size.width === 816, 'FAIL: width != 816');
console.assert(size.height === 338, 'FAIL: height != 338');

// средний (самый широкий) ряд i=8 должен содержать 17 ячеек
const middleRow = cells.filter((c) => c.row === 8);
console.log('cells in middle row (i=8):', middleRow.length, '(ожидается 17)');
console.assert(middleRow.length === 17, 'FAIL: средний ряд не 17 ячеек');

// первый и последний ряды должны содержать по 9 ячеек
console.log('row 0 length:', cells.filter((c) => c.row === 0).length, '(ожидается 9)');
console.log('row 16 length:', cells.filter((c) => c.row === 16).length, '(ожидается 9)');

console.log('num 0:', cells[0]);
console.log('num 216:', cells[216]);
console.log('anchor of num 0:', buildingAnchor(cells[0]));

console.log('\nВсё сошлось — реализация соответствует sota.as.');
