import './style.css';
import { MapRenderer } from './map/renderer';
import { GameSession } from './protocol/gameProtocol';
import { parseRefresh } from './map/refreshData';

// Точка входа HTML5-клиента Destiny Sphere.
// Живая интеграция сети (Фаза 1) + карты (Фаза 2), см. CLIENT_PORTING_PLAN.md.
//
// ВАЖНО: логин на живой сервер НЕ запускается автоматически при каждой
// загрузке страницы — только по явному ?live=1 в URL. Иначе каждая
// перезагрузка/скриншот бьёт по живому продакшену тестовым аккаунтом, а
// повторные логин+вход-в-мир в течение сессии, похоже, уже задели
// антибот/rate-limiting (см. CLIENT_PORTING_PLAN.md, Фаза 2 — открытая
// находка, не разобрана до конца, дальше живые тесты не гонять без
// необходимости).

const app = document.querySelector<HTMLDivElement>('#app')!;
app.innerHTML = '';
app.style.width = '900px';
app.style.height = '400px';

const wantsLive = new URLSearchParams(location.search).has('live');
const login = import.meta.env.VITE_TEST_LOGIN as string | undefined;
const pass = import.meta.env.VITE_TEST_PASS as string | undefined;
const uiid = import.meta.env.VITE_TEST_UIID as string | undefined;

async function main() {
  const renderer = await MapRenderer.create(app);

  if (!wantsLive || !login || !pass || !uiid) {
    await renderer.renderGround('G'.repeat(217));
    (window as any).__renderDone = true;
    return;
  }

  // baseUrl='' — запросы идут на текущий origin (localhost), прокси
  // Vite перенаправляет /ds/* на https://destinysphere.ru server-side
  // (иначе браузерный fetch() упрётся в CORS, см. vite.config.ts).
  const session = new GameSession({ baseUrl: '' });

  await session.login(login, pass);
  await session.enterWorld(uiid);
  const marks = await session.sendCommand('<load locations="3"/>');

  if (!marks.refresh) {
    throw new Error('В ответе нет mark "refresh" — нечего рисовать.');
  }
  const refresh = parseRefresh(marks.refresh);
  console.log('refresh:', refresh.location, 'id_comb=', refresh.idComb, 'sota.length=', refresh.sota.length);

  await renderer.renderGround(refresh.sota);
  console.log('MapRenderer готов, реальный грунт отрисован (location=' + refresh.location + ')');
  (window as any).__renderDone = true;
}

main().catch((err) => {
  console.error('ОШИБКА:', err);
  (window as any).__renderError = String(err);
});
