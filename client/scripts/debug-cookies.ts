/**
 * Диагностика без браузера: PHP-сессия резолвит session.cookie_domain по
 * $_SERVER['HTTP_HOST'] (SU_GameSessionWork::_setDomain(),
 * SU_GameSessionWork.class.php:168-195) — на destinysphere.ru/game7.../
 * www... это явно "destinysphere.ru". Vite dev-proxy (changeOrigin:true)
 * переписывает исходящий Host в destinysphere.ru, так что PHP видит тот же
 * HTTP_HOST что и напрямую. Но настоящий браузер получает ответ от
 * localhost:5184 — Set-Cookie с Domain=destinysphere.ru для него невалиден
 * и будет отброшен. Проверяем это напрямую через заголовки Node (fetch в
 * браузере их скрывает, Node — нет).
 */
const login = process.env.DS_TEST_LOGIN!;
const pass = process.env.DS_TEST_PASS!;
const uiid = process.env.DS_TEST_UIID!;
const PROXY_BASE = 'http://localhost:5184';

function getSetCookies(res: Response): string[] {
  const anyHeaders = res.headers as unknown as { getSetCookie?: () => string[] };
  return anyHeaders.getSetCookie ? anyHeaders.getSetCookie() : [];
}

async function run(label: string, base: string) {
  console.log(`\n=== ${label} (base=${base}) ===`);
  const cookieJar = new Map<string, string>();

  const loginBody = new URLSearchParams({ login, pass, enter: 'Login' });
  const loginRes = await fetch(base + '/ds/index_login.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: loginBody.toString(),
    redirect: 'manual',
  });
  console.log('[login] status=', loginRes.status, 'location=', loginRes.headers.get('location'));
  const loginSetCookies = getSetCookies(loginRes);
  console.log('[login] Set-Cookie:', JSON.stringify(loginSetCookies));
  for (const raw of loginSetCookies) {
    const first = raw.split(';')[0];
    const eq = first.indexOf('=');
    if (eq !== -1) cookieJar.set(first.slice(0, eq).trim(), first.slice(eq + 1).trim());
  }

  const loc = loginRes.headers.get('location');
  if (!loc) {
    console.log('Нет редиректа после логина — прерываю.');
    return;
  }
  const startUrl = new URL(loc, base);
  const ck = startUrl.searchParams.get('ck');
  console.log('[login] ck=', ck, 'cookieJar=', JSON.stringify([...cookieJar.entries()]));

  // index_start.php: заходим в мир, используя cookie из логина.
  const cookieHeader = [...cookieJar.entries()].map(([k, v]) => `${k}=${v}`).join('; ');
  const startBody = new URLSearchParams({ ck: ck!, start: '1', uiid });
  const startRes = await fetch(base + '/ds/index_start.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      ...(cookieHeader ? { Cookie: cookieHeader } : {}),
    },
    body: startBody.toString(),
    redirect: 'manual',
  });
  console.log('[enterWorld] status=', startRes.status, 'location=', startRes.headers.get('location'));
  const startSetCookies = getSetCookies(startRes);
  console.log('[enterWorld] Set-Cookie:', JSON.stringify(startSetCookies));
  if (startRes.status === 200) {
    const text = await startRes.text();
    console.log('[enterWorld] body (первые 500 симв.):', text.slice(0, 500));
  }
}

async function main() {
  await run('ПРЯМОЙ ЗАПРОС (без прокси)', 'https://destinysphere.ru');
  await run('ЧЕРЕЗ VITE-ПРОКСИ (localhost:5184)', PROXY_BASE);
}

main().catch((err) => {
  console.error('FAILED:', err);
  process.exit(1);
});
