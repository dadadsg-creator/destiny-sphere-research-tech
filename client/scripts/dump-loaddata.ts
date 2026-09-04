/**
 * Разовый живой запрос — сохранить полный mark loaddata (статическая
 * локализация/характеристики юнитов, зданий и т.д., см.
 * ds/classes/UA/UA_Load.class.php) в файл для инспекции. НЕ commit'ится
 * как постоянный скрипт назначения — просто even one-off dump (аналог
 * debug-cookies.ts).
 */
import { writeFileSync } from 'fs';
import { GameSession, SimpleCookieJar } from '../src/protocol/gameProtocol';

const login = process.env.DS_TEST_LOGIN!;
const pass = process.env.DS_TEST_PASS!;
const uiid = process.env.DS_TEST_UIID!;

async function main() {
  const jar = new SimpleCookieJar();
  const session = new GameSession({ baseUrl: 'https://destinysphere.ru', cookieJar: jar });
  await session.login(login, pass);
  await session.enterWorld(uiid);
  const marks = await session.sendCommand('<load locations="3"/>');
  const loaddata = marks.loaddata ?? '';
  writeFileSync(process.argv[2] ?? 'loaddata.xml', loaddata, 'utf-8');
  console.log('Сохранено, длина =', loaddata.length);
}

main().catch((err) => {
  console.error('FAILED:', err);
  process.exit(1);
});
