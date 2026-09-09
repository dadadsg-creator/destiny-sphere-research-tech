import puppeteer from 'puppeteer-core';

const url = process.argv[2] ?? 'http://localhost:5184/';
const outPath = process.argv[3] ?? 'screenshot.png';

async function main() {
  const browser = await puppeteer.launch({
    executablePath: 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    headless: true,
    args: ['--no-sandbox', '--enable-unsafe-swiftshader'],
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 920, height: 450 });
  page.on('console', (msg) => console.log('[page]', msg.text()));
  page.on('pageerror', (err) => console.error('[page error]', err));

  // 'load', не 'networkidle0' — Vite HMR держит открытый WebSocket, из-за
  // которого networkidle0 никогда не срабатывает (см. повторявшиеся
  // таймауты навигации в CLIENT_PORTING_PLAN.md). Готовность страницы всё
  // равно определяется через window.__renderDone ниже, не через сеть.
  await page.goto(url, { waitUntil: 'load', timeout: 45000 });
  await page.waitForFunction(
    'window.__renderDone === true || window.__renderError !== undefined',
    { timeout: 20000 },
  );
  const err = await page.evaluate(() => (window as any).__renderError);
  if (err) {
    console.error('Страница сообщила об ошибке рендера:', err);
    process.exitCode = 1;
  }
  // Известный баг headless Chrome + WebGL: CDP-скриншот сразу после
  // __renderDone иногда даёт полностью пустой canvas, хотя сцена в JS уже
  // готова (спрайты/текстуры/позиции — всё верно, подтверждено чтением
  // через toDataURL()+2D-readback) — обнаружено при живой проверке карты
  // (?live=1, 2026-09-09). Ни один способ по отдельности не чинит это
  // надёжно (проверено эмпирически): просто requestAnimationFrame-wait —
  // не помогает, просто форсированный render() без паузы — тоже не
  // помогает (текстуры, видимо, ещё не успевают догрузиться на GPU).
  // Помогает только пауза НА ЗАГРУЗКУ + явный render() + кадр отрисовки
  // все вместе. `window.__mapRenderer` — API, которое main.tsx выставляет
  // для live-режима; для окон без PixiJS (обычные ?screen=) evaluate ниже
  // просто no-op.
  await new Promise((r) => setTimeout(r, 2000));
  await page.evaluate(() => {
    const renderer = (window as any).__mapRenderer;
    if (renderer?.app?.renderer && renderer?.app?.stage) {
      renderer.app.renderer.render(renderer.app.stage);
    }
  });
  await page.evaluate(() => new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r))));
  await page.screenshot({ path: outPath as `${string}.png` });
  console.log('Screenshot saved to', outPath);

  await browser.close();
}

main().catch((err) => {
  console.error('FAILED:', err);
  process.exit(1);
});
