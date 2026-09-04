/**
 * Ручной end-to-end тест сетевого слоя против живого сервера.
 * Учётные данные передаются через переменные окружения, не хардкодятся:
 *
 *   DS_TEST_LOGIN=... DS_TEST_PASS=... DS_TEST_UIID=... npx tsx scripts/test-protocol.ts
 *
 * DS_TEST_UIID — необязательно; если не задан, скрипт остановится после
 * логина и выведет список доступных uiid для повторного запуска.
 */
import { GameSession, SimpleCookieJar } from '../src/protocol/gameProtocol';

const BASE_URL = process.env.DS_TEST_BASE_URL ?? 'https://destinysphere.ru';
const login = process.env.DS_TEST_LOGIN;
const pass = process.env.DS_TEST_PASS;
const uiid = process.env.DS_TEST_UIID;

if (!login || !pass) {
  console.error('Нужны DS_TEST_LOGIN и DS_TEST_PASS в окружении.');
  process.exit(1);
}

async function main() {
  const jar = new SimpleCookieJar();
  const session = new GameSession({ baseUrl: BASE_URL, cookieJar: jar });

  console.log('-> login()');
  await session.login(login!, pass!);
  console.log('   OK, ck =', session.getClientKey());

  if (!uiid) {
    console.log('DS_TEST_UIID не задан — останавливаюсь после логина (успех).');
    return;
  }

  console.log('-> enterWorld(', uiid, ')');
  const gameUrl = await session.enterWorld(uiid);
  console.log('   OK, game URL =', gameUrl);

  console.log('-> sendCommand(<load locations="3"/>)');
  const marks = await session.sendCommand('<load locations="3"/>');
  console.log('   marks:', Object.keys(marks));
  console.log('   newbee =', marks.newbee);
  console.log('   loadkey =', marks.loadkey?.slice(0, 120));
  console.log('   loaddata length =', marks.loaddata?.length);
  console.log('   loaddata preview =', marks.loaddata?.slice(0, 300));

  console.log('\nВсё прошло успешно — TS-реализация сетевого слоя рабочая.');
}

main().catch((err) => {
  console.error('ОШИБКА:', err);
  process.exit(1);
});
