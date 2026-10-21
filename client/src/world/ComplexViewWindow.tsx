/**
 * Экран «Обзор комплекса» — `fillComplex()` (`sota.as:1013-1300+`).
 * Данные — complexViewData.ts.
 *
 * ПЯТАЯ переделка в этой сессии (2026-09-16) — по третьему развёрнутому
 * разбору владельца, узко про качество/масштаб конкретных элементов
 * (структура снова не менялась):
 *
 * - **Врата перерисованы как векторная SVG-графика вместо растровых
 *   `GATE_*`/`GATE_*_WRECK` PNG.** Причина прямая: исходные ассеты из
 *   `complex_lib.swf` — крошечные растры (~23×30, FFDec-экспорт
 *   плоского превью символа), при увеличении до нужного на экране
 *   размера растягивались ~2.4× и давали заметную пикселизацию —
 *   ровно то, на что владелец указал ("простое увеличение старых Flash
 *   изображений даёт заметную пикселизацию"). Векторная замена не
 *   размывается ни при каком масштабе и вдобавок делает состояние
 *   открыто/закрыто однозначным без сравнения двух похожих растров:
 *   открытые — голубое кольцо+свечение+заполненное ядро, закрытые —
 *   серое ПУНКТИРНОЕ кольцо без свечения (кольцо разорвано физически,
 *   не только цветом). Растровые `GATE_*` PNG остались в
 *   `public/assets/complex/` неиспользуемыми в этом файле — на случай
 *   будущего апскейла набора графики (см. TODO в docs), не удалены.
 * - Размер врат уменьшен (диаметр кольца ~44 против прежних 56×74).
 * - **Герб (портрет расы) вернулся, но не на гекс, а В ПОДПИСЬ**, рядом
 *   с именем — маленький (18px), нигде не увеличивается сверх
 *   нативного размера ассета (60×60), поэтому не размывается.
 * - Плашка подписи переработана под макет, буквально описанный
 *   владельцем: сверху — номер сектора (мелко), затем герб+имя одной
 *   строкой, в углу плашки — компактный статус ворот (точка-индикатор).
 * - Одна из "прозрачных прослоек" убрана: грунтовая текстура на гексах
 *   и соединительные линии хаб→сектор — оба источника лишнего
 *   визуального шума ("тёмная полоса на секторах" — это и была линия,
 *   которая просвечивала сквозь полупрозрачную заливку гекса) — сняты
 *   полностью. Осталась одна заливка цветом владения + один контур.
 * - Легенда приведена в соответствие содержимому: ★ рейтинг и ✦ премиум
 *   не показывались нигде, кроме раскрытой карточки (где они и так
 *   подписаны словами) — убраны из легенды как отдельные пункты.
 * - Статус "врата открыты/закрыты" в сайдбаре — рядом с текстом теперь
 *   маленький светящийся/пунктирный индикатор (та же графика, что и на
 *   карте), а не только текст низкого контраста.
 * - Кнопка письма продублирована компактной иконкой в базовой
 *   (свёрнутой) строке сайдбара — не только в развёрнутой карточке.
 * - "Домой" — убран непонятный эмодзи-домик, осталась только подпись.
 * - Шрифт в сайдбаре/навигации увеличен (+2px по большинству строк),
 *   поля/кнопки навигации — выше (padding увеличен).
 *
 * Сознательно НЕ сделано: см. предыдущую версию про светящуюся линию
 * карточка↔сектор (тот же компромисс в силе).
 *
 * Графика — `public/assets/complex/` (извлечена из `complex_lib.swf`).
 * VK-профиль/кнопки не портированы — см. memory
 * remaster_vk_integration_rebuild.
 */
import { useRef, useState } from 'preact/hooks';
import type { ComplexViewComb, ComplexViewData, ComplexViewNavigateCommand } from './complexViewData';
import { publicAssetUrl } from '../assets/publicAssetUrl';

export interface ComplexViewWindowProps {
  data: ComplexViewData;
  onClose: () => void;
  onNavigate: (cmd: ComplexViewNavigateCommand) => void;
  onWriteMail: (nick: string) => void;
}

const H = 100;
const V = 57;

function hexPoints(cx: number, cy: number): string {
  return [
    [cx, cy - V],
    [cx + H, cy - 0.5 * V],
    [cx + H, cy + 0.5 * V],
    [cx, cy + V],
    [cx - H, cy + 0.5 * V],
    [cx - H, cy - 0.5 * V],
  ]
    .map(([x, y]) => `${x},${y}`)
    .join(' ');
}

const SLOT_DIRECTIONS: Array<{ x: number; y: number }> = [
  { x: H, y: -1.5 * V },
  { x: 2 * H, y: 0 },
  { x: H, y: 1.5 * V },
  { x: -H, y: 1.5 * V },
  { x: -2 * H, y: 0 },
  { x: -H, y: -1.5 * V },
];

const RACE_LOGO: Record<string, string> = {
  '1': publicAssetUrl('/assets/complex/VORAN_LOGO_DEF.png'),
  '2': publicAssetUrl('/assets/complex/LIENSU_LOGO_DEF.png'),
  '3': publicAssetUrl('/assets/complex/PSOLAO_LOGO_DEF.png'),
};

/** Приоритет статуса — sota.as:1491-1513 (каждая проверка перекрывает предыдущую, npc "побеждает" всех). `null` — обычные открытые/закрытые врата без особого статуса, отдельный значок не нужен (сам факт открыт/закрыт уже показан кольцом). */
function specialStatus(comb: ComplexViewComb): { color: string; title: string } | null {
  if (!comb.gateOpen) return null;
  if (comb.npc) return { color: '#9aa4c8', title: 'NPC' };
  if (comb.captured) return { color: '#ff9933', title: 'Захвачена' };
  if (comb.godModeMs !== null) return { color: '#ffcc33', title: 'Защита богов (новичок)' };
  if (comb.frozen) return { color: '#4fc3ff', title: 'Заморожен / отпуск' };
  if (comb.banned) return { color: '#ff5555', title: 'Игрок забанен' };
  return null;
}

type Ownership = 'home' | 'ally' | 'other' | 'empty';

function ownership(comb: ComplexViewComb | null): Ownership {
  if (!comb?.userName) return 'empty';
  if (comb.home) return 'home';
  if (comb.sameAlliance) return 'ally';
  return 'other';
}

const OWNER_COLOR: Record<Ownership, string> = {
  home: '#a44bf0',
  ally: '#33dd55',
  other: '#ffcc33',
  empty: '#5c72b0',
};

/** 8 коротких делений по окружности радиуса r с центром (cx,cy) — "технологический ободок" GateGlyph ниже, не просто голая окружность. */
function ringTicks(cx: number, cy: number, rInner: number, rOuter: number, count = 8) {
  return Array.from({ length: count }, (_, i) => {
    const a = (i / count) * Math.PI * 2;
    return {
      x1: cx + Math.cos(a) * rInner,
      y1: cy + Math.sin(a) * rInner,
      x2: cx + Math.cos(a) * rOuter,
      y2: cy + Math.sin(a) * rOuter,
    };
  });
}

/**
 * Векторный портал врат — см. докстринг файла про отказ от растровых
 * GATE_-иконок (обычной и _WRECK, пикселизация при увеличении). Первая
 * векторная версия (простое кольцо+точка) владелец справедливо назвал
 * "убого" — здесь тот же принцип (крупная простая форма, никакого
 * растра), но с настоящей многослойной композицией: внешний
 * технологический ободок с делениями, энергетическое кольцо с бликом,
 * светящееся ядро на радиальном градиенте (`ds-complex-gate-core-*`,
 * `<defs>` выше). r внешнего ободка=28 ⇒ диаметр 56 — сопоставимо с
 * прежним растровым размером, но чёткое на любом масштабе.
 */
function GateGlyph({ cx, cy, open }: { cx: number; cy: number; open: boolean }) {
  const ticks = ringTicks(cx, cy, 24, 28);

  if (open) {
    return (
      <g class="ds-complex-gate-glyph ds-complex-gate-glyph--open">
        <circle cx={cx} cy={cy} r={26} fill="none" stroke="#3d5580" stroke-width={1.5} opacity={0.6} />
        {ticks.map((t, i) => (
          <line key={i} x1={t.x1} y1={t.y1} x2={t.x2} y2={t.y2} stroke="#a8e4ff" stroke-width={1.5} opacity={0.85} />
        ))}
        <circle cx={cx} cy={cy} r={19} fill="none" stroke="#4fc3ff" stroke-width={4.5} />
        <circle cx={cx} cy={cy} r={19} fill="none" stroke="#eafcff" stroke-width={1} opacity={0.65} />
        <circle cx={cx} cy={cy} r={11} fill="url(#ds-complex-gate-core-open)" />
      </g>
    );
  }
  return (
    <g class="ds-complex-gate-glyph ds-complex-gate-glyph--closed">
      <circle cx={cx} cy={cy} r={26} fill="none" stroke="#2c3a5e" stroke-width={1.5} opacity={0.5} />
      {ticks.map((t, i) => (
        <line key={i} x1={t.x1} y1={t.y1} x2={t.x2} y2={t.y2} stroke="#5c6a9c" stroke-width={1.2} opacity={0.55} />
      ))}
      <circle cx={cx} cy={cy} r={19} fill="none" stroke="#5c6a9c" stroke-width={3} stroke-dasharray="6 7" opacity={0.85} />
      <circle cx={cx} cy={cy} r={10} fill="url(#ds-complex-gate-core-closed)" />
    </g>
  );
}

export function ComplexViewWindow({ data, onClose, onNavigate, onWriteMail }: ComplexViewWindowProps) {
  const [ring, setRing] = useState(data.rings.find((r) => r.ln === data.ringName)?.id ?? data.rings[0]?.id ?? '');
  const [complexNum, setComplexNum] = useState(data.complexNumber);
  const [selected, setSelected] = useState<number | null>(null);
  const [hovered, setHovered] = useState<number | null>(null);
  const sideRefs = useRef<Record<number, HTMLDivElement | null>>({});

  const slots = Array.from({ length: 6 }, (_, i) => data.combs.find((c) => c.num === i + 1) ?? null);

  function selectSlot(num: number) {
    setSelected((cur) => (cur === num ? null : num));
    sideRefs.current[num]?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  }

  const sceneW = 720;
  const sceneH = 350;
  const originX = sceneW / 2;
  const originY = sceneH / 2;

  return (
    <div class="ds-complex-screen">
      <div class="ds-complex-screen__header">
        <h2 class="ds-complex-screen__title">
          Комплекс {data.ringName}.{data.complexNumber}
        </h2>
        <button type="button" class="ds-complex-screen__close" onClick={onClose} aria-label="Закрыть">
          ×
        </button>
      </div>

      <div class="ds-complex-screen__nav">
        <div class="ds-complex-nav__group">
          <span class="ds-complex-nav__label">Кольцо</span>
          <select value={ring} onChange={(e) => setRing((e.target as HTMLSelectElement).value)}>
            {data.rings.map((r) => (
              <option key={r.id} value={r.id}>
                {r.ln ?? r.id}
              </option>
            ))}
          </select>
        </div>

        <div class="ds-complex-nav__group">
          <span class="ds-complex-nav__label">№ комплекса</span>
          <input
            type="number"
            class="ds-complex-nav__number"
            value={complexNum}
            onInput={(e) => setComplexNum(Number((e.target as HTMLInputElement).value))}
          />
          <button type="button" class="ds-popup__btn ds-popup__btn--primary" onClick={() => onNavigate({ ring, complexnum: complexNum })}>
            Перейти
          </button>
        </div>

        <div class="ds-complex-nav__group">
          <button type="button" class="ds-popup__btn" onClick={() => onNavigate({ direction: 'LEFT' })}>
            ← Предыдущий
          </button>
          <span class="ds-complex-nav__spacer" />
          <button type="button" class="ds-popup__btn" onClick={() => onNavigate({ direction: 'HOME' })}>
            Домой
          </button>
          <span class="ds-complex-nav__spacer" />
          <button type="button" class="ds-popup__btn" onClick={() => onNavigate({ direction: 'RIGHT' })}>
            Следующий →
          </button>
        </div>
      </div>

      <div class="ds-complex-legend">
        <span class="ds-complex-legend__item">
          <span class="ds-complex-legend__swatch" style={{ background: OWNER_COLOR.home }} /> Ваша сота
        </span>
        <span class="ds-complex-legend__item">
          <span class="ds-complex-legend__swatch" style={{ background: OWNER_COLOR.ally }} /> Союзник
        </span>
        <span class="ds-complex-legend__item">
          <span class="ds-complex-legend__swatch" style={{ background: OWNER_COLOR.other }} /> Другой игрок
        </span>
        <span class="ds-complex-legend__item">
          <span class="ds-complex-legend__swatch ds-complex-legend__swatch--empty" /> Свободно
        </span>
        <span class="ds-complex-legend__sep" />
        <span class="ds-complex-legend__item">
          <svg width="20" height="20" viewBox="-11 -11 22 22" class="ds-complex-legend__gateicon">
            <defs>
              <radialGradient id="ds-complex-legend-core-open" cx="38%" cy="35%" r="65%">
                <stop offset="0%" stop-color="#f2fdff" />
                <stop offset="40%" stop-color="#6fe0ff" />
                <stop offset="100%" stop-color="#0e5a86" stop-opacity="0" />
              </radialGradient>
            </defs>
            <circle cx={0} cy={0} r={8.5} fill="none" stroke="#4fc3ff" stroke-width={2.5} class="ds-complex-gate-glyph--open" />
            <circle cx={0} cy={0} r={4} fill="url(#ds-complex-legend-core-open)" />
          </svg>
          врата открыты
        </span>
        <span class="ds-complex-legend__item">
          <svg width="20" height="20" viewBox="-11 -11 22 22" class="ds-complex-legend__gateicon">
            <circle cx={0} cy={0} r={8.5} fill="none" stroke="#7683ad" stroke-width={2.2} stroke-dasharray="3.5 3" />
          </svg>
          врата закрыты
        </span>
      </div>

      <div class="ds-complex-screen__main">
        <div class="ds-complex-scene">
          <svg width="100%" height="100%" viewBox={`0 0 ${sceneW} ${sceneH}`} preserveAspectRatio="xMidYMid meet">
            <defs>
              <radialGradient id="ds-complex-hub-glow" cx="50%" cy="50%" r="50%">
                <stop offset="0%" stop-color="#6fd6ff" stop-opacity={0.45} />
                <stop offset="100%" stop-color="#6fd6ff" stop-opacity="0" />
              </radialGradient>
              <radialGradient id="ds-complex-gate-core-open" cx="38%" cy="35%" r="65%">
                <stop offset="0%" stop-color="#f2fdff" />
                <stop offset="40%" stop-color="#6fe0ff" />
                <stop offset="100%" stop-color="#0e5a86" stop-opacity="0" />
              </radialGradient>
              <radialGradient id="ds-complex-gate-core-closed" cx="38%" cy="35%" r="65%">
                <stop offset="0%" stop-color="#8892b8" stop-opacity="0.55" />
                <stop offset="100%" stop-color="#1a2242" stop-opacity="0" />
              </radialGradient>
            </defs>

            <g transform={`translate(${originX},${originY})`}>
              <circle cx={0} cy={0} r={H * 1.5} fill="url(#ds-complex-hub-glow)" />
              <polygon points={hexPoints(0, 0)} fill="#111a35" stroke="#4a5d9a" stroke-width={2} />
              <text x={0} y={5} text-anchor="middle" font-size="15" font-weight="bold" fill="#d3ddfa">
                {data.ringName}.{data.complexNumber}
              </text>

              {slots.map((comb, i) => {
                const dir = SLOT_DIRECTIONS[i];
                const cx = dir.x;
                const cy = dir.y;
                const num = i + 1;
                const occ = ownership(comb);
                const active = selected === num || hovered === num;
                const gateOpen = comb?.gateOpen ?? false;
                const status = comb ? specialStatus(comb) : null;

                const labelX = cx * 1.35;
                const labelY = cy * 1.35;
                const labelW = 128;
                const labelH = 40;

                return (
                  <g
                    key={num}
                    class={`ds-complex-hex${active ? ' ds-complex-hex--active' : ''}`}
                    onClick={() => selectSlot(num)}
                    onMouseEnter={() => setHovered(num)}
                    onMouseLeave={() => setHovered((h) => (h === num ? null : h))}
                    style={{ cursor: 'pointer' }}
                  >
                    {/* Одна заливка цветом владения + один контур — без
                        текстуры и без соединительных линий (обе были лишним
                        визуальным шумом, вторая ещё и просвечивала сквозь
                        полупрозрачную заливку тёмной полосой). */}
                    <polygon points={hexPoints(cx, cy)} fill={OWNER_COLOR[occ]} opacity={occ === 'empty' ? 0.3 : 0.5} />
                    <polygon
                      points={hexPoints(cx, cy)}
                      fill="none"
                      stroke={OWNER_COLOR[occ]}
                      stroke-width={2}
                      stroke-dasharray={occ === 'empty' ? '6 4' : undefined}
                    />
                    {active && (
                      <polygon points={hexPoints(cx, cy)} fill="none" stroke="#4fc3ff" stroke-width={4} class="ds-complex-hex__active-ring" />
                    )}

                    <GateGlyph cx={cx} cy={cy} open={gateOpen} />
                    {status && <circle cx={cx + 17} cy={cy - 17} r={5} fill={status.color} class="ds-complex-status-dot" />}

                    {/* Подпись — макет по запросу владельца: номер сверху,
                        герб+имя одной строкой, статус ворот компактной
                        точкой в углу плашки. */}
                    <foreignObject x={labelX - labelW / 2} y={labelY - labelH / 2} width={labelW} height={labelH}>
                      <div class="ds-complex-hex__label">
                        <span class="ds-complex-hex__addr">
                          {data.ringName}.{data.complexNumber}.{num}
                        </span>
                        <span class="ds-complex-hex__namerow">
                          {comb?.race && RACE_LOGO[comb.race] && <img class="ds-complex-hex__emblem" src={RACE_LOGO[comb.race]} alt="" />}
                          <span class="ds-complex-hex__name">{comb?.userName ?? 'Свободно'}</span>
                        </span>
                        <span
                          class={`ds-complex-hex__gatedot${gateOpen ? ' ds-complex-hex__gatedot--open' : ''}`}
                          title={gateOpen ? 'Врата открыты' : 'Врата закрыты'}
                        />
                      </div>
                    </foreignObject>
                  </g>
                );
              })}
            </g>
          </svg>
        </div>

        <div class="ds-complex-sidebar">
          {slots.map((comb, i) => {
            const num = i + 1;
            const occ = ownership(comb);
            const active = selected === num || hovered === num;
            const open = selected === num;
            const status = comb ? specialStatus(comb) : null;

            if (occ === 'empty') {
              return (
                <div
                  key={num}
                  ref={(el) => { sideRefs.current[num] = el; }}
                  class={`ds-complex-side-card ds-complex-side-card--empty${active ? ' ds-complex-side-card--active' : ''}`}
                  onClick={() => selectSlot(num)}
                  onMouseEnter={() => setHovered(num)}
                  onMouseLeave={() => setHovered((h) => (h === num ? null : h))}
                >
                  <div class="ds-complex-side-card__row1">
                    <span class="ds-complex-side-card__addr">
                      {data.ringName}.{data.complexNumber}.{num}
                    </span>
                    <span class="ds-complex-side-card__free">Свободно</span>
                  </div>
                  {open && <div class="ds-complex-side-card__expand">Сектор свободен — пока никем не заселён.</div>}
                </div>
              );
            }

            const comb2 = comb!;
            return (
              <div
                key={num}
                ref={(el) => { sideRefs.current[num] = el; }}
                class={`ds-complex-side-card${active ? ' ds-complex-side-card--active' : ''}`}
                style={{ borderColor: OWNER_COLOR[occ] }}
                onClick={() => selectSlot(num)}
                onMouseEnter={() => setHovered(num)}
                onMouseLeave={() => setHovered((h) => (h === num ? null : h))}
              >
                <div class="ds-complex-side-card__row1">
                  <span class="ds-complex-side-card__owner-dot" style={{ background: OWNER_COLOR[occ] }} />
                  <span class="ds-complex-side-card__name">{comb2.userName}</span>
                  <span class="ds-complex-side-card__addr">
                    {data.ringName}.{data.complexNumber}.{num}
                  </span>
                  <span class={`ds-complex-side-card__gatetag${comb2.gateOpen ? '' : ' ds-complex-side-card__gatetag--closed'}`}>
                    <span class={`ds-complex-hex__gatedot${comb2.gateOpen ? ' ds-complex-hex__gatedot--open' : ''}`} />
                    {comb2.gateOpen ? 'открыты' : 'закрыты'}
                  </span>
                  <button
                    type="button"
                    class="ds-complex-side-card__mail-compact"
                    title="Написать игроку"
                    onClick={(e) => {
                      e.stopPropagation();
                      onWriteMail(comb2.userName!);
                    }}
                  >
                    ✉
                  </button>
                </div>
                {open && (
                  <div class="ds-complex-side-card__expand">
                    <div class="ds-complex-side-card__avatar" style={{ borderColor: OWNER_COLOR[occ] }}>
                      {comb2.race && RACE_LOGO[comb2.race] && <img src={RACE_LOGO[comb2.race]} alt="" />}
                    </div>
                    <div class="ds-complex-side-card__body">
                      <div class="ds-complex-side-card__combname">{comb2.combName ?? ''}</div>
                      <div class="ds-complex-side-card__row2">
                        <span title="Лига">Лига {comb2.league}</span>
                        <span title="Рейтинг">★ {comb2.cscore}</span>
                        {comb2.allianceTag && <span title="Альянс">{comb2.allianceTag}</span>}
                        {comb2.premium && (
                          <span title="Премиум" class="ds-complex-side-card__premium">
                            ✦ премиум
                          </span>
                        )}
                        {status && <span title={status.title}>{status.title}</span>}
                      </div>
                      <button
                        type="button"
                        class="ds-complex-side-card__mail"
                        title="Написать игроку"
                        onClick={(e) => {
                          e.stopPropagation();
                          onWriteMail(comb2.userName!);
                        }}
                      >
                        ✉ Написать игроку
                      </button>
                    </div>
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}
