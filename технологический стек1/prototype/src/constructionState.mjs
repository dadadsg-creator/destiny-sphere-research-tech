export function beginConstruction(buildings, cellNum, buildingVariant, complexGroup = null) {
  if (!(buildings instanceof Map)) throw new TypeError('buildings должен быть Map');
  if (!Number.isInteger(cellNum) || cellNum < 0) throw new RangeError('cellNum должен быть неотрицательным целым');
  if (!Number.isInteger(buildingVariant) || buildingVariant < 0) throw new RangeError('buildingVariant должен быть неотрицательным целым');
  if (buildings.has(cellNum)) throw new TypeError(`Клетка ${cellNum} уже занята`);
  const next = new Map(buildings);
  next.set(cellNum, Object.freeze({
    cellNum,
    buildingVariant,
    state: 'construction',
    ...(complexGroup == null ? {} : { complexGroup }),
  }));
  return next;
}

export function completeConstruction(buildings, cellNum) {
  if (!(buildings instanceof Map)) throw new TypeError('buildings должен быть Map');
  const current = buildings.get(cellNum);
  if (current?.state !== 'construction') return buildings;
  const next = new Map(buildings);
  next.set(cellNum, Object.freeze({ ...current, state: 'working' }));
  return next;
}

export function appendConstructionBuildings(definition, buildings, complexGroup = 'residential-ring') {
  return [
    ...definition,
    ...[...buildings.values()].map((building) => ({
      ...building,
      complexGroup: building.complexGroup ?? complexGroup,
    })),
  ];
}

export function setBuildingPower(powerStates, cellNum, enabled) {
  if (!(powerStates instanceof Map)) throw new TypeError('powerStates должен быть Map');
  const next = new Map(powerStates);
  if (enabled) next.delete(cellNum);
  else next.set(cellNum, 'disabled');
  return next;
}

export function setBuildingCondition(states, cellNum, condition) {
  if (!(states instanceof Map)) throw new TypeError('states должен быть Map');
  if (!['working', 'disabled', 'damaged'].includes(condition)) {
    throw new TypeError(`Неизвестное состояние: ${condition}`);
  }
  const next = new Map(states);
  if (condition === 'working') next.delete(cellNum);
  else next.set(cellNum, condition);
  return next;
}

export function applyBuildingPower(definition, powerStates) {
  return definition.map((entry) => {
    const cellNum = typeof entry === 'number' ? entry : entry.cellNum;
    const state = powerStates.get(cellNum);
    return state ? { ...(typeof entry === 'number' ? { cellNum } : entry), state } : entry;
  });
}

export function removeBuildings(definition, removedCells) {
  if (!(removedCells instanceof Set)) throw new TypeError('removedCells должен быть Set');
  return definition.filter((entry) => {
    const cellNum = typeof entry === 'number' ? entry : entry.cellNum;
    return !removedCells.has(cellNum);
  });
}

export function upgradeBuilding(levels, cellNum, maximumLevel = 3) {
  if (!(levels instanceof Map)) throw new TypeError('levels должен быть Map');
  if (!Number.isInteger(maximumLevel) || maximumLevel < 2) throw new RangeError('maximumLevel должен быть не меньше 2');
  const currentLevel = levels.get(cellNum) ?? 1;
  const nextLevel = Math.min(maximumLevel, currentLevel + 1);
  if (nextLevel === currentLevel) return levels;
  const next = new Map(levels);
  next.set(cellNum, nextLevel);
  return next;
}

export function applyBuildingLevels(definition, levels) {
  return definition.map((entry) => {
    const cellNum = typeof entry === 'number' ? entry : entry.cellNum;
    const level = levels.get(cellNum);
    return level ? { ...(typeof entry === 'number' ? { cellNum } : entry), level } : entry;
  });
}
