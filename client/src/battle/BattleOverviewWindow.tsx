/**
 * Окно «Обзор боёв» — battleoverviewclass.as, см. docs/client-combat-ui.md
 * §3. Данные — battleOverviewData.ts (parseBattleOverview). Фильтры и
 * действия построчно сверены с оригиналом:
 *  - `status`/`winner` — подтверждённые enum'ы ("A"/"D"/"ALL",
 *    "0"/"1"/"ALL", battleoverviewclass.as:246-266), опонент/сота — из
 *    живых `filters.opponents`/`filters.sotes` (+"ALL");
 *  - диапазон дней (`day_start`/`day_end`) — индекс внутри
 *    `filters.allowdays` (тот же способ, каким сам оригинал строит
 *    `id=gg`, :178-186), отправляется как `fromdays`/`days` = индекс+1;
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

function FilterBar({ filters, onApply }: { filters: BattleFilters; onApply: (v: BattleFilterValues) => void }) {
  const [dayStart, setDayStart] = useState(0);
  const [dayEnd, setDayEnd] = useState(Math.max(0, filters.allowdays.length - 1));
  const [opp, setOpp] = useState('ALL');
  const [sot, setSot] = useState('ALL');
  const [stat, setStat] = useState('ALL');
  const [winner, setWinner] = useState('ALL');

  return (
    <div class="ds-spying__row" style={{ flexWrap: 'wrap' }}>
      <select value={String(dayStart)} onChange={(e) => setDayStart(Number((e.target as HTMLSelectElement).value))}>
        {filters.allowdays.map((d, i) => (
          <option key={i} value={i}>
            {d.ln}
          </option>
        ))}
      </select>
      <select value={String(dayEnd)} onChange={(e) => setDayEnd(Number((e.target as HTMLSelectElement).value))}>
        {filters.allowdays.map((d, i) => (
          <option key={i} value={i}>
            {d.ln}
          </option>
        ))}
      </select>
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
        onClick={() => onApply({ fromDays: dayStart + 1, days: dayEnd + 1, opp, sot, stat, winner })}
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
