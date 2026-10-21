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

/**
 * Смешанная раскладка старого автономного стенда. Она остаётся fallback
 * обычного клиента, потому что код X не входит в серверный словарь грунтов.
 */
export const HOME_LAB_GROUND =
  'DDDDDSSSSDDDSSSDDDDDDDSSSDDDDDDDDDSSDDDGGGSDDDDSSDGGGGGSDDDGGSDGGGGGGSSSSGGSGGGWWWSGSSSSGGSGGGWWWSSSSSSGGGGSGWWWWSSSSSSGGGGSSWWWWSSSSSSGWWWWWWWWGSSSSSDWWWSSGGWGSSSSWWWSSGGWGGSSSSSWSSWWWSSSSSSWWWWWSSGSWWWWSSSSSSSSSSSSS';

/**
 * Художественная калибровочная маска домашней соты: официальные количества
 * 92 S / 35 D / 45 G / 44 W и одна системная позиция X в центре (num=108).
 * Она используется только стендом hive-217 для подгонки цельной подложки и
 * не выдаётся за строку, извлечённую с сервера.
 */
export const HOME_ART_GROUND =
  'DDDDDSSSSDDDSSSDDDDDDDSSSDDDDDDDDDSSDDDGGGSSDDDSSDGGGGGSDDDGGSDGGGGGGSSSSGGSGGGWWWSGSSSSGGSGGGWWWSSSSSSGGGGSXWWWWSSSSSSGGGGSSWWWWSSSSSSGWWWWWWWWGSSSSSWWWWSSGGWGSSSSWWWSSGGWGGSSSSSWSSWWWSSSSSSWWWWWSSGSWWWWSSSSSSSSSSSSS';

/**
 * Ручная визуальная классификация подложки V14, выполненная по всем
 * 217 клеткам 2026-10-20: 63 S / 32 D / 68 G / 53 W / 1 X.
 * Пока V14 используется как рабочая подложка, стенд принимает эту строку
 * как логическую карту строительства, чтобы видимый и игровой грунт не
 * расходились после перезагрузки. Серверная карта позднее имеет приоритет.
 */
export const HOME_ART_GROUND_V14_AUDIT =
  'DDDDDDDSDDDDDDDDDDDGDDDDDDDDDDGGGGGDDDDGGGGGGGGGGGGGGGGGGGGGGSGGGGGGGSSGGGGGGGGWWGGGSSGGGWWGGGWWWGGSSSGGGGWWXWWWWWGSSSSWWGWWWWWWWWSSSSSWWWWWGGWWWWWSSSSWWWWGGWWWWWSSSSSWWWWWWGSSSSSSWWWWGSSSSSSSSSSGSSGSSSSSSSSSSSSSSSSSS';

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
  || HOME_LAB_GROUND.length !== TOTAL_CELLS
  || HOME_ART_GROUND.length !== TOTAL_CELLS
  || HOME_ART_GROUND_V14_AUDIT.length !== TOTAL_CELLS
  || countCode(HOME_ART_GROUND, 'S') !== 92
  || countCode(HOME_ART_GROUND, 'D') !== 35
  || countCode(HOME_ART_GROUND, 'G') !== 45
  || countCode(HOME_ART_GROUND, 'W') !== 44
  || countCode(HOME_ART_GROUND, 'X') !== 1
  || HOME_ART_GROUND[108] !== 'X'
  || countCode(HOME_ART_GROUND_V14_AUDIT, 'X') !== 1
  || HOME_ART_GROUND_V14_AUDIT[108] !== 'X'
) {
  throw new Error('Стартовые карты грунта не соответствуют игровому контракту');
}
