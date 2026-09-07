/**
 * Логинится тестовым аккаунтом (client/.env.local) на живом сайте
 * destinysphere.ru и скриншотит результат — используется для Фазы 4
 * (визуальное сравнение старого Flash-клиента, отрисованного через
 * Ruffle, с новым HTML5-портом). Явно разрешено владельцем проекта, см.
 * .claude/settings.local.json (autoMode.allow).
 *
 * Использование:
 *   npx tsx scripts/liveClientScreenshot.ts <output.png> [world|linkSubstring] [--inspect]
 *
 * Без 2-го аргумента — скриншот сразу после логина (список миров), с
 * дампом в консоль опций <select> мира. С аргументом, совпадающим со
 * значением/текстом опции world-select — выбирает этот мир и кликает
 * кнопку «ВХОД» (или сабмитит ближайшую форму), затем дампит
 * embed/object (SWF FlashVars) и скриншотит результат. `--inspect`
 * третьим/четвёртым аргументом — вместо клика только дампит структуру
 * DOM вокруг кнопки входа (для отладки селектора).
 */
import puppeteer from 'puppeteer-core';
import { readFileSync } from 'fs';

function loadEnvLocal(): Record<string, string> {
  const text = readFileSync('.env.local', 'utf-8');
  const env: Record<string, string> = {};
  for (const line of text.split('\n')) {
    const m = line.match(/^([A-Z_]+)=(.*)$/);
    if (m) env[m[1]] = m[2].trim();
  }
  return env;
}

async function main() {
  const outPath = process.argv[2] ?? 'live-client.png';
  const target = process.argv[3];
  const inspect = process.argv.includes('--inspect');

  const env = loadEnvLocal();
  const login = env.VITE_TEST_LOGIN;
  const pass = env.VITE_TEST_PASS;
  if (!login || !pass) {
    throw new Error('VITE_TEST_LOGIN/VITE_TEST_PASS не заданы в .env.local');
  }

  const browser = await puppeteer.launch({
    executablePath: 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    headless: true,
    args: ['--no-sandbox', '--enable-unsafe-swiftshader'],
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 1024, height: 768 });
  page.on('pageerror', (err) => console.error('[page error]', err));

  await page.goto('https://destinysphere.ru/ds/index_login.php', { waitUntil: 'load', timeout: 45000 });
  await page.type('input[name="login"]', login);
  await page.type('input[name="pass"]', pass);
  await Promise.all([page.waitForNavigation({ waitUntil: 'load', timeout: 45000 }), page.click('input[type="image"]')]);
  console.log('POST-LOGIN URL:', page.url());

  const worldOptions = await page.evaluate(() => {
    const select = document.querySelector('select');
    if (!select) return null;
    return Array.from((select as HTMLSelectElement).options).map((o) => ({ value: o.value, text: o.text }));
  });
  console.log('WORLD OPTIONS:', JSON.stringify(worldOptions, null, 2));

  if (inspect) {
    const structure = await page.evaluate(() => {
      const select = document.querySelector('select');
      if (!select) return 'no <select> found';
      let el: Element | null = select;
      const lines: string[] = [];
      for (let depth = 0; depth < 8 && el; depth++) {
        let attrStr = '';
        for (let ai = 0; ai < el.attributes.length; ai++) {
          attrStr += el.attributes[ai].name + '="' + el.attributes[ai].value + '" ';
        }
        lines.push('<' + el.tagName + ' ' + attrStr + '>');
        el = el.parentElement;
      }
      const controlEls = document.querySelectorAll('input,button,a[href*="detect_player"],a[onclick]');
      const controlLines: string[] = [];
      for (let ci = 0; ci < controlEls.length; ci++) {
        const e = controlEls[ci];
        let attrStr = '';
        for (let ai = 0; ai < e.attributes.length; ai++) {
          attrStr += e.attributes[ai].name + '="' + e.attributes[ai].value + '" ';
        }
        controlLines.push('<' + e.tagName + ' ' + attrStr + '>');
      }
      return lines.join('\n') + '\nALL CONTROLS:\n' + controlLines.join('\n');
    });
    console.log('STRUCTURE:\n', structure);
    await browser.close();
    return;
  }

  const worldMatch = worldOptions?.find((o) => o.value === target || o.text.includes(target ?? '\u0000'));

  if (target && worldMatch) {
    await page.evaluate((value) => {
      const selectEl = document.querySelector('select') as HTMLSelectElement;
      selectEl.value = value;
      selectEl.dispatchEvent(new Event('change', { bubbles: true }));
    }, worldMatch.value);

    const enterClicked = await page.evaluate(() => {
      const selectEl = document.querySelector('select[name="uiid"]') as HTMLElement;
      const form = selectEl?.closest('form');
      const scope = form ?? document;
      const enterEl = Array.from(scope.querySelectorAll('input[type="submit"]')).find((e) =>
        (e as HTMLInputElement).value.toLowerCase().includes('вход'),
      ) as HTMLElement | undefined;
      if (!enterEl) return false;
      enterEl.click();
      return true;
    });
    console.log('ENTER CLICKED:', enterClicked);

    await new Promise((r) => setTimeout(r, 5000));
    console.log('AFTER ENTER WORLD URL:', page.url());

    const embedInfo = await page.evaluate(() => {
      const els = document.querySelectorAll('embed,object');
      const out: string[] = [];
      for (let i = 0; i < els.length; i++) {
        const e = els[i];
        let attrStr = '';
        for (let ai = 0; ai < e.attributes.length; ai++) {
          attrStr += e.attributes[ai].name + '="' + e.attributes[ai].value + '" ';
        }
        out.push('<' + e.tagName + ' ' + attrStr + '>');
        const params = e.querySelectorAll('param');
        for (let pi = 0; pi < params.length; pi++) {
          out.push('  param: ' + params[pi].getAttribute('name') + '=' + params[pi].getAttribute('value'));
        }
      }
      return out.join('\n');
    });
    console.log('EMBEDS:\n' + embedInfo);
  } else if (target) {
    const href = await page.evaluate((needle) => {
      const link = Array.from(document.querySelectorAll('a')).find(
        (a) => a.href.includes(needle) || (a.textContent ?? '').includes(needle),
      );
      return link?.href ?? null;
    }, target);

    if (!href) {
      console.warn(`Ни опция мира, ни ссылка, содержащая "${target}", не найдены.`);
    } else {
      await page.goto(href, { waitUntil: 'load', timeout: 45000 });
      console.log('NAVIGATED TO:', page.url());
      await new Promise((resolve) => setTimeout(resolve, 4000));
    }
  }

  await page.screenshot({ path: outPath as `${string}.png`, fullPage: true });
  console.log('Screenshot saved to', outPath);

  await browser.close();
}

main().catch((err) => {
  console.error('FAILED:', err);
  process.exit(1);
});
