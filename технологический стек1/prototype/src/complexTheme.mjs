const DIAGNOSTIC_THEME = Object.freeze({
  id: 'diagnostic',
  showTopology: true,
  connector: Object.freeze({
    outerColor: 'rgba(2, 14, 24, 0.92)',
    outerWidth: 8,
    innerColor: 'rgba(86, 225, 255, 0.88)',
    innerWidth: 3,
    highlight: true,
  }),
  perimeter: Object.freeze({ color: 'rgba(255, 201, 93, 0.9)', width: 1.8 }),
  courtyard: Object.freeze({
    fill: 'rgba(20, 90, 112, 0.88)',
    stroke: 'rgba(107, 239, 211, 0.95)',
    detail: 'rgba(190, 255, 240, 0.72)',
  }),
});

const VORANER_THEME = Object.freeze({
  id: 'voraner',
  showTopology: false,
  connector: Object.freeze({
    outerColor: 'rgba(233, 224, 197, 0.96)',
    outerWidth: 4.4,
    innerColor: 'rgba(42, 183, 238, 0.82)',
    innerWidth: 1.35,
    highlight: false,
  }),
  perimeter: Object.freeze({ color: 'rgba(190, 151, 67, 0.72)', width: 1.05 }),
  courtyard: Object.freeze({
    fill: 'rgba(21, 75, 102, 0.88)',
    stroke: 'rgba(224, 204, 151, 0.9)',
    detail: 'rgba(73, 205, 244, 0.78)',
  }),
});

export function resolveComplexTheme(visual) {
  if (visual?.race === 'tech' && !visual.id.includes('/diagnostic-')) return VORANER_THEME;
  return DIAGNOSTIC_THEME;
}

export { DIAGNOSTIC_THEME, VORANER_THEME };
