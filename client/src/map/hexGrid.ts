/**
 * Гексагональная сетка соты — портировано формула-в-формулу из sota.as
 * (см. docs/client-map-and-grid.md §3 в основном репозитории документации,
 * разбор live-теста подтверждён 2026-09-04). НЕ придумывать заново —
 * координаты/константы должны совпадать 1:1 с оригиналом.
 *
 * Раскладка — pointy-top гексагоны рядами, "гексагон из гексагонов"
 * радиусом lengthSide=9: 17 рядов, 217 ячеек, линейный индекс num
 * присваивается в порядке сканирования (не axial/offset координаты).
 */

export const CELL_WEIGHT_H = 24; // sota.as:5 "Default weight cell (horizontal)"
export const CELL_WEIGHT_V = 13; // sota.as:6 "Default height cell (vertical)"
export const LENGTH_SIDE = 9;
export const TOTAL_CELLS = 3 * LENGTH_SIDE * LENGTH_SIDE - 3 * LENGTH_SIDE + 1; // 217

export interface CellPosition {
  /** Линейный индекс ячейки, 0..216 — тот же num, что в 217-символьной строке грунта. */
  num: number;
  row: number; // i, 0..16
  col: number; // j в пределах ряда
  /** Экранные координаты ЛЕВОГО ВЕРХНЕГО угла ячейки (как в sota.as, без зума). */
  x: number;
  y: number;
}

/**
 * Полная сетка в порядке сканирования sota.as (sota.as:120-131) —
 * index в массиве == num.
 */
export function buildHexGrid(): CellPosition[] {
  const cells: CellPosition[] = [];
  let num = 0;
  let NN = 0;
  let dNN = 1;

  for (let i = 0; i < LENGTH_SIDE * 2 - 1; i++) {
    const rowLength = LENGTH_SIDE + NN;
    for (let j = 0; j < rowLength; j++) {
      const x = (j * 2 - NN + (LENGTH_SIDE - 1)) * CELL_WEIGHT_H;
      const y = i * 1.5 * CELL_WEIGHT_V;
      cells.push({ num, row: i, col: j, x, y });
      num++;
    }
    dNN = i < LENGTH_SIDE - 1 ? 1 : -1;
    NN += dNN;
  }

  return cells;
}

/**
 * 6 вершин силуэта ОДНОЙ ячейки, в ЛОКАЛЬНЫХ координатах (0,0 = левый
 * верхний угол bounding box 2h×2v) — буквально `sota.as:68-88`
 * (`createSample()`, `sizeX=sota.h`, `sizeY=sota.v`, те же H/V, что и
 * шаг сетки выше). В оригинале эта форма используется как `setMask()`
 * для тайла грунта (`sota.as:417-422`) — без неё соседние тайлы просто
 * закрывают друг друга сплошным прямоугольником без видимой границы
 * ячейки (найдено 2026-09-10, владелец сравнил с реальным Flash-клиентом
 * — там чётко видна гексагональная сетка, у нас её не было вовсе).
 */
export function hexPolygonPoints(): number[] {
  const h = CELL_WEIGHT_H;
  const v = CELL_WEIGHT_V;
  return [h, 0, 2 * h, 0.5 * v, 2 * h, 1.5 * v, h, 2 * v, 0, 1.5 * v, 0, 0.5 * v];
}

/** Якорь постройки — нижняя вершина ячейки, НЕ центр (sota.as, см. §3.4 документа). */
export function buildingAnchor(cell: CellPosition): { x: number; y: number } {
  return { x: cell.x + CELL_WEIGHT_H, y: cell.y + 2 * CELL_WEIGHT_V };
}

/** Габарит всей соты (sota.as:431-432) — для fit/центрирования вида. */
export function mapPixelSize(): { width: number; height: number } {
  const width = (LENGTH_SIDE - 1 + LENGTH_SIDE) * 2 * CELL_WEIGHT_H;
  const height = (LENGTH_SIDE * 2 - 1) * 1.5 * CELL_WEIGHT_V + 0.5 * CELL_WEIGHT_V;
  return { width, height };
}
