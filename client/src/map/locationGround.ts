import { TOTAL_CELLS } from './hexGrid';

/**
 * Стартовая логическая карта центральной соты из оригинального клиента.
 *
 * Игровых клеток здесь 28: 16 земли (S) и 12 скал (R). Остальные
 * позиции 217-элементной серверной сетки заняты служебными масками:
 * E — граница центрального комплекса, F — закрытая туманом область.
 * Графический рендер может скрывать границы клеток, но не имеет права
 * менять эти коды или считать E/F доступным грунтом.
 */
export const CENTRAL_INITIAL_GROUND =
  'EFFFFFFFEFEFFFFFFEFFFEFFFFFEFFFFFEFFFFEFFFFFFFEFFFEFFFFFFFFFEFFEFFFFFFFFFFFEFEFFFFFFFFFFFFFEEFFFFFFFEEEEEEEEEEEEEEEEEFFFFFFFEEFFFFFFFFFFFFFESEFFFFFFFFFFFESREFFFFFFFFFERSSEFFFFFFFESSSREFFFFFESRRSSEFFFESRRRSSEFESSRRSRRE';

/** Нейтральная сота начинается как 217 игровых клеток песка/пустыни. */
export const NEUTRAL_INITIAL_GROUND = 'D'.repeat(TOTAL_CELLS);

export type InitialGroundLocation = 'CENTRAL' | 'NEUTRAL';

/** Стартовый fallback; после загрузки мира приоритет всегда у серверного `<refresh><sota>`. */
export function initialGroundForLocation(location: InitialGroundLocation): string {
  return location === 'CENTRAL' ? CENTRAL_INITIAL_GROUND : NEUTRAL_INITIAL_GROUND;
}

function countCode(source: string, code: string): number {
  return [...source].filter((value) => value === code).length;
}

if (
  CENTRAL_INITIAL_GROUND.length !== TOTAL_CELLS
  || countCode(CENTRAL_INITIAL_GROUND, 'S') !== 16
  || countCode(CENTRAL_INITIAL_GROUND, 'R') !== 12
  || NEUTRAL_INITIAL_GROUND.length !== TOTAL_CELLS
) {
  throw new Error('Стартовые карты грунта не соответствуют игровому контракту');
}
