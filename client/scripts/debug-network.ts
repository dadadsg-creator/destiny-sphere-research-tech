import puppeteer from 'puppeteer-core';

// Диагностика бага "enterWorld после login редиректит на index_login.php":
// логируем реальные заголовки (включая Set-Cookie, недоступный из page-context
// fetch()) для каждого запроса/ответа /ds/* через CDP-уровень Puppeteer.
const url = process.argv[2] ?? 'http://localhost:5184/?live=1';

async function main() {
  const browser = await puppeteer.launch({
    executablePath: 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    headless: true,
    args: ['--no-sandbox', '--enable-unsafe-swiftshader'],
  });
  const page = await browser.newPage();
  const client = await page.createCDPSession();
  await client.send('Network.enable');

  client.on('Network.responseReceivedExtraInfo', (ev: any) => {
    const setCookie = ev.headers['set-cookie'] ?? ev.headers['Set-Cookie'];
    if (setCookie) {
      console.log('[SET-COOKIE]', ev.requestId, JSON.stringify(setCookie));
    }
  });
  client.on('Network.requestWillBeSentExtraInfo', (ev: any) => {
    const cookie = ev.headers['cookie'] ?? ev.headers['Cookie'];
    console.log('[REQUEST COOKIE HEADER]', ev.requestId, JSON.stringify(cookie ?? '(none)'));
  });
  page.on('request', (req) => {
    if (req.url().includes('/ds/')) {
      console.log('[REQ]', req.method(), req.url());
    }
  });
  page.on('response', async (res) => {
    if (res.url().includes('/ds/')) {
      console.log('[RES]', res.status(), res.url());
    }
  });
  page.on('console', (msg) => console.log('[page]', msg.text()));
  page.on('pageerror', (err) => console.error('[page error]', err));

  await page.goto(url, { waitUntil: 'networkidle0', timeout: 45000 });
  await page
    .waitForFunction('window.__renderDone === true || window.__renderError !== undefined', { timeout: 20000 })
    .catch(() => console.error('waitForFunction timeout'));

  await browser.close();
}

main().catch((err) => {
  console.error('FAILED:', err);
  process.exit(1);
});
