import './style.css';

// Точка входа HTML5-клиента Destiny Sphere.
// Сетевой слой — см. src/protocol/. Карта/UI будут добавлены в Фазе 2/3
// (см. CLIENT_PORTING_PLAN.md в корне репозитория).

document.querySelector<HTMLDivElement>('#app')!.innerHTML = `
  <p>Destiny Sphere HTML5 client — Фаза 1 (сетевой слой) готова.</p>
`;
