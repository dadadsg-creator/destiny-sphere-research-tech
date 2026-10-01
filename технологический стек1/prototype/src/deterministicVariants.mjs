const DEFAULT_CONTEXT = Object.freeze({ worldId: 'pilot', location: 'HOME', sotaId: '0' });

export function hash32(value) {
  let hash = 0x811c9dc5;
  for (const character of String(value)) {
    hash ^= character.codePointAt(0);
    hash = Math.imul(hash, 0x01000193);
  }
  return hash >>> 0;
}

export function createCellSeed(context, complexGroup, cellNum, salt = '') {
  const normalized = { ...DEFAULT_CONTEXT, ...context };
  return hash32([
    normalized.worldId,
    normalized.location,
    normalized.sotaId,
    complexGroup,
    cellNum,
    salt,
  ].join('|'));
}

function shuffledVariants(context, complexGroup, count) {
  const values = Array.from({ length: count }, (_, index) => index);
  let state = createCellSeed(context, complexGroup, 'group', 'permutation');
  for (let index = values.length - 1; index > 0; index -= 1) {
    state = (Math.imul(state, 1664525) + 1013904223) >>> 0;
    const target = state % (index + 1);
    [values[index], values[target]] = [values[target], values[index]];
  }
  return values;
}

function modulo(value, divisor) {
  return ((value % divisor) + divisor) % divisor;
}

export function assignDeterministicVariants(complexes, cells, context = DEFAULT_CONTEXT, options = {}) {
  const variantCount = options.variantCount ?? 3;
  if (!Number.isInteger(variantCount) || variantCount < 1) throw new RangeError('variantCount должен быть положительным целым числом');
  const cellByNum = new Map(cells.map((cell) => [cell.num, cell]));
  const permutations = new Map();
  const assignments = [];

  for (const complex of complexes) {
    if (!permutations.has(complex.complexGroup)) {
      permutations.set(complex.complexGroup, shuffledVariants(context, complex.complexGroup, variantCount));
    }
    const permutation = permutations.get(complex.complexGroup);
    for (const member of complex.members) {
      const cell = cellByNum.get(member.cellNum);
      if (!cell) throw new RangeError(`Неизвестная клетка: ${member.cellNum}`);
      const column = Math.round(cell.x / 24);
      const structuralVariant = variantCount >= 3
        ? permutation[modulo(column, 3)]
        : createCellSeed(context, complex.complexGroup, member.cellNum, 'variant') % variantCount;
      const heightStep = createCellSeed(context, complex.complexGroup, member.cellNum, 'height') % 5;
      const garden = ['none', 'planter', 'terrace'][createCellSeed(context, complex.complexGroup, member.cellNum, 'garden') % 3];
      const antenna = ['none', 'needle', 'ring', 'fork'][createCellSeed(context, complex.complexGroup, member.cellNum, 'antenna') % 4];
      const entrance = ['round', 'split', 'arched'][createCellSeed(context, complex.complexGroup, member.cellNum, 'entrance') % 3];
      assignments.push(Object.freeze({
        cellNum: member.cellNum,
        complexId: complex.id,
        variant: structuralVariant,
        heightScale: 0.94 + heightStep * 0.03,
        garden,
        antenna,
        entrance,
        cellSeed: createCellSeed(context, complex.complexGroup, member.cellNum),
        complexSeed: hash32(`${context.worldId}|${context.location}|${context.sotaId}|${complex.id}`),
      }));
    }
  }
  return Object.freeze(assignments.sort((a, b) => a.cellNum - b.cellNum));
}
