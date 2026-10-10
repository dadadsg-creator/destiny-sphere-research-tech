import puppeteer from 'puppeteer-core';
import path from 'node:path';

const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const base = 'http://127.0.0.1:5174/?screen=maxim-buildings';
const cases = [
  { name: 'tech-61-core-current', query: '&race=tech&cells=61&scope=core&scale=current&zoom=115', screenshot: true },
  { name: 'tech-217-all-current', query: '&race=tech&cells=217&scope=all&scale=current&zoom=90', screenshot: false },
  { name: 'psi-217-all-cell', query: '&race=psi&cells=217&scope=all&scale=cell&zoom=90', screenshot: false },
];

const browser = await puppeteer.launch({
  executablePath: chrome,
  headless: true,
  args: ['--disable-gpu-sandbox', '--disable-dev-shm-usage', '--no-first-run'],
});

try {
  for (const testCase of cases) {
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 1 });
    const errors: string[] = [];
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => {
      if (message.type() === 'error') errors.push(message.text());
    });
    const started = performance.now();
    const response = await page.goto(`${base}${testCase.query}`, { waitUntil: 'networkidle0', timeout: 60_000 });
    await page.waitForFunction(() => (window as any).__renderDone === true, { timeout: 60_000 });
    await new Promise((resolve) => setTimeout(resolve, 900));
    const result = await page.evaluate(() => ({
      renderDone: (window as any).__renderDone === true,
      renderError: (window as any).__renderError ?? null,
      metrics: document.querySelector('[data-section="metrics"]')?.textContent?.replace(/\s+/g, ' ').trim() ?? '',
      selection: document.querySelector('[data-section="selection"]')?.textContent?.replace(/\s+/g, ' ').trim() ?? '',
      canvas: document.querySelector('canvas')?.getBoundingClientRect().toJSON() ?? null,
    }));
    if (testCase.screenshot) {
      await page.screenshot({
        path: path.resolve('../технологический стек1/tmp/maxim-building-audit.png'),
        fullPage: true,
      });
    }
    console.log(JSON.stringify({
      case: testCase.name,
      status: response?.status() ?? null,
      wallMs: Math.round(performance.now() - started),
      errors,
      ...result,
    }));
    await page.close();
  }
} finally {
  await browser.close();
}
