import puppeteer from 'puppeteer-core';

// Разовая проверка интерактивности (клик -> попап) — не входит в
// постоянный набор скриптов, аналог screenshot.ts с одним доп. кликом.
const url = process.argv[2]!;
const selector = process.argv[3]!;
const outPath = process.argv[4] ?? 'screenshot.png';

async function main() {
  const browser = await puppeteer.launch({
    executablePath: 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    headless: true,
    args: ['--no-sandbox', '--enable-unsafe-swiftshader'],
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 920, height: 450 });
  page.on('console', (msg) => console.log('[page]', msg.text()));

  await page.goto(url, { waitUntil: 'load', timeout: 45000 });
  await page.waitForFunction('window.__renderDone === true', { timeout: 20000 });
  await page.click(selector);
  await new Promise((r) => setTimeout(r, 200));
  await page.screenshot({ path: outPath as `${string}.png` });
  console.log('Screenshot saved to', outPath);

  await browser.close();
}

main().catch((err) => {
  console.error('FAILED:', err);
  process.exit(1);
});
