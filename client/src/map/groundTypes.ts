/**
 * Коды типов грунта в 217-символьной строке <sota> (docs/client-map-and-grid.md §4.2).
 * E/F — служебные заливки маски вида CENTRAL (граница/туман соседей), не
 * настоящий терраформируемый грунт — сервер их не отдаёт как реальную клетку
 * с точки зрения террformинга, но клиент их всё равно рисует как фон.
 */
export const GROUND_LINKAGE: Record<string, string> = {
  W: 'WATER',
  G: 'GRASS',
  S: 'SOIL',
  D: 'DESERT',
  E: 'E_FIELD',
  F: 'FROG',
  R: 'ROCK',
};

export function parseGroundString(sota: string): string[] {
  if (sota.length !== 217) {
    throw new Error(`<sota> должна быть 217 символов, получено ${sota.length}`);
  }
  return sota.split('').map((ch) => {
    const name = GROUND_LINKAGE[ch];
    if (!name) throw new Error(`Неизвестный код грунта: "${ch}"`);
    return name;
  });
}
