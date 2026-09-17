/**
 * Игровое серверное время (docs/main_screen.md §10: "слева отображается
 * текущее игровое время" — было честно системным временем браузера, см.
 * BottomBar.tsx, т.к. марка с ним нигде не парсилась).
 *
 * Найдено 2026-09-17 живым дампом полного ответа `<load locations="3"/>`
 * (top-level марки, не вложенный XML) — `stime` и `userGMT` присутствуют
 * в КАЖДОМ ответе сервера, не только при входе. Подтверждено исходником:
 * `parser.as:165-177` (`parse_respond`) — `if (obj.stime != undefined)
 * { ...; init_clock(obj.stime); }`, `init_clock` (`parser.as:402-415`)
 * синхронизирует локальные часы через дельту "время отправки запроса
 * минус stime ответа", дальше тикает локально без повторных запросов.
 * `userGMT` (`parser.as:169-176`, число часов) — часовой пояс ИГРЫ
 * (Москва, GMT+3 на живом тесте), а не браузера игрока — оригинал
 * форматирует часы под него, не под локальный пояс клиента.
 *
 * Порт упрощает синхронизацию (нет отдельного "время отправки минус
 * время ответа" — сетевая задержка не измеряется отдельно, `stime`
 * трактуется как "время на момент получения ответа") — для отображения
 * в HUD (не для игровой логики с точностью до секунды) этого достаточно,
 * та же порядок погрешности, что и в оригинале (там тоже не вычитается
 * половина RTT).
 */
import { signal } from '@preact/signals';

export interface ServerClock {
  /** offsetMs = stime(ответ) - Date.now()(на момент получения) — прибавляется к текущему Date.now() на каждый тик. */
  offsetMs: number;
  /** Часовой пояс игры в часах от UTC (`userGMT` марки, 0 если сервер его не прислал). */
  gmtHours: number;
}

export const serverClock = signal<ServerClock | null>(null);

/** Текущее игровое время (UTC-мс) — undefined, пока `stime` ни разу не приходил. */
export function getGameTimeMs(): number | undefined {
  const clock = serverClock.value;
  if (!clock) return undefined;
  return Date.now() + clock.offsetMs;
}

/** Игровое время, сдвинутое под часовой пояс игры — читать через getUTC*, не через локальные getHours/getMinutes (см. докстринг файла). */
export function getGameTimeInGameZone(): Date | undefined {
  const clock = serverClock.value;
  const gameMs = getGameTimeMs();
  if (!clock || gameMs === undefined) return undefined;
  return new Date(gameMs + clock.gmtHours * 3600_000);
}
