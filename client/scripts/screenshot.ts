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

  await page.goto(url, { waitUntil: 'networkidle0' });
  await page.waitForFunction('window.__renderDone === true', { timeout: 15000 });
  await page.screenshot({ path: outPath as `${string}.png` });
  console.log('Screenshot saved to', outPath);

  await browser.close();
}

main().catch((err) => {
  console.error('FAILED:', err);
  process.exit(1);
});
