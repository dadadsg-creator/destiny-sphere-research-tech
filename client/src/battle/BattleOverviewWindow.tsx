/**
 * Окно «Обзор боёв» — battleoverviewclass.as, см. docs/client-combat-ui.md
 * §3. Данные — battleOverviewData.ts (parseBattleOverview). Фильтры и
 * действия построчно сверены с оригиналом:
 *  - `status`/`winner` — подтверждённые enum'ы ("A"/"D"/"ALL",
 *    "0"/"1"/"ALL", battleoverviewclass.as:246-266), опонент/сота — из
 *    живых `filters.opponents`/`filters.sotes` (+"ALL");
 *  - диапазон дней — обычные `<input type="date">` (владелец: "нужен
 *    нормальный выбор даты календарём", не выпадающий список меток
 *    месяцев), а не индекс из `filters.allowdays` напрямую: сервер
 *    строит этот список как непрерывный календарный диапазон "сегодня
 *    минус N дней" (`PN_BattleOverview.class.php:212-247`,
 *    `_getBattleDates()`, `$endDate = time()`) — значит индекс и
 *    реальная дата взаимно однозначны чистой арифметикой, без разбора
 *    меток месяцев вообще (см. `dateFromDaysAgo`/`toIndex` ниже),
 *    итоговые `fromdays`/`days` (1-based индекс, как в оригинале)
 *    получаются обратным пересчётом выбранной даты;
 *  - "Показать бой" (`getShowBattle`) — всегда; "Сдаться"/"Отступить"
 *    (`retreat`) — только для ЕЩЁ ИДУЩИХ боёв (`battle.ra==="1" &&
 *    filters.ra==="1"`), это именно НЕ-премиум ветка оригинала
 *    (:600-648, отдельная кнопка на каждую строку, а не чекбоксы
 *    массового выбора — та ветка премиум-гейтится, здесь сознательно не
 *    воспроизведена, см. план про isPremium=false);
 *  - "Написать письмо" (`opponentName`) — переиспользует уже
 *    подключённый `renderCreateMessage`.
 *
 * НЕ реализовано: текстовый отчёт о завершённом бою (`getReportBattle`,
 * REPORT_BUT) — ответ сервера на `report="1"` НЕ является XML
 * (`UA_GetBattleInfo::outReport()` кладёт в марку `xmldata` сырую
 * `#`-разделённую строку `#start#end#winner#opponent#текст`, минуя
 * XML-парсер вообще, UA_GetBattleInfo.class.php:63-157) — это отдельный,
 * структурно другой формат ответа, не вписывающийся в архитектуру
 * "xmldata → корневой тег → парсер"; строить для него отдельный путь вне
 * этой архитектуры не стали, вместо гадания.
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import type { BattleFilters, BattleListEntry, BattleOverview } from './battleOverviewData';

export interface BattleFilterValues {
  fromDays: number;
  days: number;
  opp: string;
  sot: string;
  stat: string;
  winner: string;
}

export interface BattleOverviewWindowProps {
  data: BattleOverview;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onApplyFilter: (values: BattleFilterValues) => void;
  onViewBattle: (idBattle: number) => void;
  onSurrender: (idBattle: number) => void;
  onWriteToOpponent: (nick: string) => void;
}

const BATTLE_TYPE_LABEL: Record<string, string> = { A: 'Атака', D: 'Оборона', E: 'Защита коалиции' };

/**
 * `filters.allowdays` — непрерывный диапазон КАЛЕНДАРНЫХ дней от
 * `сегодня` назад (сервер строит его именно так, `PN_BattleOverview.
 * class.php:212-247`, `_getBattleDates()`: `$endDate = time()`, то есть
 * последний элемент массива — всегда сегодня), а не абстрактный список
 * меток — значит, индексу внутри массива соответствует РЕАЛЬНАЯ
 * календарная дата, которую можно вычислить арифметикой "сегодня минус
 * N дней", не разбирая метки месяцев (`d.ln`) вообще. Это и даёт обычный
 * `<input type="date">` вместо выбора по индексу — `fromdays`/`days`
 * (1-based индекс, как в оригинале) получаются обратным пересчётом.
 */
function dateFromDaysAgo(daysAgo: number): Date {
  const d = new Date();
  d.setHours(0, 0, 0, 0);
  d.setDate(d.getDate() - daysAgo);
  return d;
}
function toISODate(d: Date): string {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}
function daysAgoFromISODate(iso: string): number {
  const picked = new Date(`${iso}T00:00:00`);
  const today = dateFromDaysAgo(0);
  return Math.round((today.getTime() - picked.getTime()) / 86_400_000);
}

function FilterBar({ filters, onApply }: { filters: BattleFilters; onApply: (v: BattleFilterValues) => void }) {
  const total = Math.max(1, filters.allowdays.length);
  const todayIso = toISODate(dateFromDaysAgo(0));
  const oldestIso = toISODate(dateFromDaysAgo(total - 1));

  const [dateStart, setDateStart] = useState(oldestIso);
  const [dateEnd, setDateEnd] = useState(todayIso);
  const [opp, setOpp] = useState('ALL');
  const [sot, setSot] = useState('ALL');
  const [stat, setStat] = useState('ALL');
  const [winner, setWinner] = useState('ALL');

  /** Индекс внутри allowdays (1-based, старейший день = 1) — обратное преобразование "дата → fromdays/days". */
  const toIndex = (iso: string) => Math.min(total, Math.max(1, total - daysAgoFromISODate(iso)));

  return (
    <div class="ds-spying__row" style={{ flexWrap: 'wrap' }}>
      <input
        type="date"
        min={oldestIso}
        max={todayIso}
        value={dateStart}
        onInput={(e) => setDateStart((e.target as HTMLInputElement).value)}
      />
      <input
        type="date"
        min={oldestIso}
        max={todayIso}
        value={dateEnd}
        onInput={(e) => setDateEnd((e.target as HTMLInputElement).value)}
      />
      <select value={opp} onChange={(e) => setOpp((e.target as HTMLSelectElement).value)}>
        <option value="ALL">Все противники</option>
        {filters.opponents.map((o) => (
          <option key={o} value={o}>
            {o}
          </option>
        ))}
      </select>
      <select value={sot} onChange={(e) => setSot((e.target as HTMLSelectElement).value)}>
        <option value="ALL">Все соты</option>
        {filters.sotes.map((s) => (
          <option key={s} value={s}>
            {s}
          </option>
        ))}
      </select>
      <select value={stat} onChange={(e) => setStat((e.target as HTMLSelectElement).value)}>
        <option value="ALL">Любой тип</option>
        <option value="A">Атака</option>
        <option value="D">Оборона</option>
      </select>
      <select value={winner} onChange={(e) => setWinner((e.target as HTMLSelectElement).value)}>
        <option value="ALL">Любой исход</option>
        <option value="1">Я победил</option>
        <option value="0">Я проиграл</option>
      </select>
      <button
        type="button"
        class="ds-popup__btn"
        onClick={() => onApply({ fromDays: toIndex(dateStart), days: toIndex(dateEnd), opp, sot, stat, winner })}
      >
        Показать
      </button>
    </div>
  );
}

function BattleRow({ battle, ra, onView, onSurrender, onWrite }: { battle: BattleListEntry; ra: string | null; onView: () => void; onSurrender: () => void; onWrite: () => void }) {
  const ongoing = battle.ra === '1' && ra === '1';
  return (
    <tr>
      <td>{battle.startBattle}</td>
      <td>{BATTLE_TYPE_LABEL[battle.typeBattle] ?? battle.typeBattle}</td>
      <td>
        {battle.combBattleName} : {battle.combBattleComplex}
      </td>
      <td>
        {battle.opponentName ?? '—'}
        {battle.opponentComplexName && ` [${battle.opponentComplexName}:${battle.opponentComplexNum}]`}
      </td>
      <td>{battle.winnerBattle || (ongoing ? 'идёт бой' : '—')}</td>
      <td>{battle.youRanking ?? '—'}</td>
      <td>
        <button type="button" class="ds-popup__btn" onClick={onView}>
          Показать бой
        </button>{' '}
        {ongoing && (battle.typeBattle === 'A' || battle.typeBattle === 'D') && (
          <button type="button" class="ds-popup__btn" onClick={onSurrender}>
            {battle.typeBattle === 'A' ? 'Отступить' : 'Сдаться'}
          </button>
        )}{' '}
        {battle.opponentName && (
          <button type="button" class="ds-popup__btn" onClick={onWrite}>
            Написать письмо
          </button>
        )}
      </td>
    </tr>
  );
}

export function BattleOverviewWindow({ data, tabs, onTabClick, onClose, onApplyFilter, onViewBattle, onSurrender, onWriteToOpponent }: BattleOverviewWindowProps) {
  return (
    <Window title="Обзор боёв" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <FilterBar filters={data.filters} onApply={onApplyFilter} />
      <table>
        <thead>
          <tr>
            <th>Начало</th>
            <th>Тип</th>
            <th>Наша сота</th>
            <th>Противник</th>
            <th>Победитель</th>
            <th>Рейтинг</th>
            <th />
          </tr>
        </thead>
        <tbody>
          {data.battles.map((b) => (
            <BattleRow
              key={b.idBattle}
              battle={b}
              ra={data.filters.ra}
              onView={() => onViewBattle(b.idBattle)}
              onSurrender={() => onSurrender(b.idBattle)}
              onWrite={() => b.opponentName && onWriteToOpponent(b.opponentName)}
            />
          ))}
        </tbody>
      </table>
      {data.battles.length === 0 && <p class="ds-train-unit__idle">Боёв не найдено.</p>}
    </Window>
  );
}
