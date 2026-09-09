import puppeteer from 'puppeteer-core';

/**
 * Живой прогон "Сшивания" (см. CLIENT_PORTING_PLAN.md, 2026-09-08/09):
 * открыть HTML5-порт в режиме ?live=1, дождаться живого рендера карты,
 * кликнуть по указанной ячейке соты (по её `num`, см. hexGrid.ts —
 * `groundLayer.children[num]` соответствует ячейке с этим num), собрать
 * консоль (диспетчер логирует непортированные домены через console.log/
 * console.warn) и сделать скриншот открывшегося окна.
 *
 * Использование: npx tsx scripts/live-map-click.ts <url> <cellNum> <outPath>
 * Например:      npx tsx scripts/live-map-click.ts http://localhost:5184/?live=1 39 click.png
 */
const url = process.argv[2]!;
const cellNum = Number(process.argv[3]);
const outPath = process.argv[4] ?? 'live-click.png';

async function main() {
  const browser = await puppeteer.launch({
    executablePath: 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    headless: true,
    args: ['--no-sandbox', '--enable-unsafe-swiftshader'],
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 1000, height: 700 });
  page.on('console', (msg) => console.log('[page]', msg.text()));
  page.on('pageerror', (err) => console.log('[pageerror]', err));

  await page.goto(url, { waitUntil: 'load', timeout: 45000 });
  await page.waitForFunction('window.__renderDone === true || window.__renderError !== undefined', { timeout: 30000 });

  const renderError = await page.evaluate('window.__renderError');
  if (renderError) {
    console.error('Ошибка рендера в браузере:', renderError);
  }

  // __renderDone фиксируется до завершения renderGround() (fire-and-forget
  // в dispatch.tsx, см. handleResponse — не await'ится) — спрайты грунта
  // грузятся асинхронно (Assets.load), ждём отдельно.
  await page.waitForFunction(
    (num) => {
      const renderer = (window as any).__mapRenderer;
      return (renderer?.groundLayer?.children?.length ?? 0) > num;
    },
    { timeout: 20000 },
    cellNum,
  );

  const point = await page.evaluate((num) => {
    const renderer = (window as any).__mapRenderer;
    const sprite = renderer?.groundLayer?.children?.[num];
    if (!sprite) return null;
    const global = sprite.getGlobalPosition();
    return { x: global.x + sprite.width / 2, y: global.y + sprite.height / 2 };
  }, cellNum);

  if (!point) {
    console.error(`Не нашли спрайт ячейки num=${cellNum} (groundLayer ещё не отрисован?)`);
    await page.screenshot({ path: outPath as `${string}.png` });
    await browser.close();
    process.exit(1);
  }

  console.log('Клик по экранным координатам:', point);
  await page.mouse.click(point.x, point.y);
  await new Promise((r) => setTimeout(r, 700));
  await page.screenshot({ path: outPath as `${string}.png` });
  console.log('Screenshot saved to', outPath);

  await browser.close();
}

main().catch((err) => {
  console.error('FAILED:', err);
  process.exit(1);
});
