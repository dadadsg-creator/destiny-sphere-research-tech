/**
 * Временная клиентская матрица для художественного стенда домашней соты.
 *
 * В живой игре окончательное решение принимает серверная таблица
 * `ds_buildingcanbebuildon`; эта матрица лишь не даёт стенду предлагать
 * очевидно неверную постройку до подключения ответа AllowBuildings.
 * Основа ограничений — официальный FAQ Destiny Sphere:
 * земля расходуется под основные здания, пустыня — под леса парусов,
 * трава — под фермы, вода — только под гидрофермы.
 */

export type BuildingVariant = 0 | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9;
export type HomeTerrainCode = 'S' | 'D' | 'G' | 'W' | 'X';

export const BUILDING_LABELS: Record<BuildingVariant, string> = {
  0: 'Башенный дом',
  1: 'Купольный дом',
  2: 'Овальный дом',
  3: 'Стази-сборщик',
  4: 'Кадериумная кузница',
  5: 'Нанокристаллизатор',
  6: 'Фермерский парник',
  7: 'Завод лёгкого вооружения',
  8: 'Фабрика тяжёлого вооружения',
  9: 'Военная база',
};

const ALLOWED_TERRAINS: Record<BuildingVariant, readonly HomeTerrainCode[]> = {
  0: ['S'],
  1: ['S'],
  2: ['S'],
  3: ['D'],
  4: ['S'],
  5: ['S'],
  6: ['G'],
  7: ['S'],
  8: ['S'],
  9: ['S'],
};

export const TERRAIN_BUILD_HINTS: Record<HomeTerrainCode, string> = {
  S: 'Земля: жилые, производственные и военные здания. Это самая дефицитная поверхность домашней соты.',
  D: 'Пустыня: энергетические леса парусов. В стенде их представляет стази-сборщик.',
  G: 'Трава: обычные фермы; позднее её освобождают под археологические институты.',
  W: 'Вода: только гидрофермы. Их игровой рисунок ещё не включён в текущий набор.',
  X: 'Системный узел Конструкторов: обычное строительство недоступно.',
};

export function isHomeTerrainCode(value: string): value is HomeTerrainCode {
  return value === 'S' || value === 'D' || value === 'G' || value === 'W' || value === 'X';
}

export function canBuildVariantOnTerrain(variant: BuildingVariant, terrain: HomeTerrainCode): boolean {
  return ALLOWED_TERRAINS[variant].includes(terrain);
}

export function allowedVariantsForTerrain(terrain: HomeTerrainCode): BuildingVariant[] {
  return (Object.keys(BUILDING_LABELS).map(Number) as BuildingVariant[])
    .filter((variant) => canBuildVariantOnTerrain(variant, terrain));
}

export function buildRestrictionReason(variant: BuildingVariant, terrain: HomeTerrainCode): string {
  if (canBuildVariantOnTerrain(variant, terrain)) return '';
  return `${BUILDING_LABELS[variant]}: неподходящий тип поверхности. ${TERRAIN_BUILD_HINTS[terrain]}`;
}
