/**
 * Порт `game.turnToTime()` (`game.as:2537-2598`) — секунды в строку вида
 * "Xд Yч Zм Wс", показывая только ненулевые разряды, от старшего к
 * младшему (подтверждено визуально скриншотом реального Flash-клиента,
 * 2026-09-10: постройка с `C=2` турна на мире со `turn=12000` — "4ч 20м").
 * Суффиксы "д"/"ч"/"м"/"с" — оригинал берёт их из живой строковой таблицы
 * `game.getLocalNames("D_"/"H_"/"M_"/"S_")`, не per-домену атрибута,
 * которую порт ещё не разбирает — значения зафиксированы буквально по
 * тому же скриншоту.
 *
 * Изначально жил только в `buildings/buildMenuData.ts` (там и найден,
 * при сверке `build_menu.as`), но формула "цена_в_ходах × C × живой
 * `turn`" — общий паттерн минимум для 9 файлов категории «В»
 * (`docs/client-side-computed-logic.md` §2.2: `archeology_class.as`,
 * `researchmenu.as`, `spying_class.as`, `powershieldclass.as`,
 * `army_overview_class.as`, `trainvarunitclass.as`, `reincarnate.as`,
 * `trade_menu_class.as`, `warstatistic.as`) — вынесено сюда 2026-09-11
 * при переносе первого из них (`archeology_class.as`), чтобы не плодить
 * копии по доменам.
 */
export function formatTurnTime(totalSeconds: number): string {
  let turn = Math.floor(totalSeconds);
  if (turn <= 0) return `0с`;

  const sec = turn % 60;
  turn = Math.floor(turn / 60);
  const mins = turn % 60;
  turn = Math.floor(turn / 60);
  const hour = turn % 24;
  turn = Math.floor(turn / 24);
  const days = turn;

  const parts: string[] = [];
  if (days > 0) parts.push(`${days}д`);
  if (hour > 0) parts.push(`${hour}ч`);
  if (mins > 0) parts.push(`${mins}м`);
  if (sec > 0) parts.push(`${sec}с`);
  return parts.join(' ');
}
