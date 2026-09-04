import './style.css';
import { MapRenderer } from './map/renderer';

// Точка входа HTML5-клиента Destiny Sphere.
// Сетевой слой — src/protocol/. Рендерер карты — src/map/ (Фаза 2,
// см. CLIENT_PORTING_PLAN.md). UI-меню будут добавлены в Фазе 3.

const app = document.querySelector<HTMLDivElement>('#app')!;
app.innerHTML = '';
app.style.width = '900px';
app.style.height = '400px';

// Временная тестовая строка грунта (217 симв.) — реальная берётся из
// mark `refresh`, тег <sota> (см. docs/client-map-and-grid.md §4.2).
// Здесь просто перемешка типов для визуальной проверки рендерера.
const testGround =
  'G'.repeat(60) + 'D'.repeat(30) + 'S'.repeat(30) + 'W'.repeat(30) +
  'R'.repeat(20) + 'E'.repeat(20) + 'F'.repeat(27);

MapRenderer.create(app).then(async (renderer) => {
  await renderer.renderGround(testGround);
  console.log('MapRenderer готов, грунт отрисован', renderer.app.renderer.type);
  (window as any).__renderDone = true;
});
