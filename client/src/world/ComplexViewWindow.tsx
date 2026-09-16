/**
 * Экран «Обзор комплекса» — `fillComplex()` (`sota.as:1013-1300+`).
 * Данные — complexViewData.ts.
 *
 * ТРЕТЬЯ переделка в этой сессии (2026-09-16), на этот раз по
 * развёрнутому UX-разбору владельца (не по коду оригинала — это уже
 * сделано в предыдущей версии, врата/состояния портированы верно;
 * здесь чисто читаемость/компоновка). Главные структурные изменения:
 *
 * - Карточки игроков БОЛЬШЕ НЕ висят над картой (перекрывали сектора,
 *   было неясно, к какому сектору какая карточка) — карта слева
 *   (крупная, компактные маркеры на гексах), список карточек СПРАВА
 *   (`.ds-complex-sidebar`), связь — общая подсветка при клике/наведении
 *   на гекс ИЛИ на карточку (`selected`/`hovered` state, обе стороны).
 * - Подписи секторов — на непрозрачной плашке (`.ds-complex-hex__label`,
 *   foreignObject), не прямо на шумной текстуре.
 * - Текстура грунта приглушена (opacity вместо доминирующей текстуры —
 *   теперь доминирует цвет владения, текстура лишь лёгкий намёк).
 * - Линии от хаба к каждому сектору — видна структура "1 узел + 6
 *   секторов", а не набор несвязанных гексов.
 * - Верхняя панель перегруппирована: кольцо / номер комплекса+"Перейти"
 *   / навигация (Пред./Домой/След., без сокращений) — три явных
 *   визуальных блока вместо одного ряда с "случайным" разделителем.
 * - Добавлена легенда (цвета владения + смысл значков).
 *
 * Врата/портал-оверлей/приоритет статусов — без изменений с прошлой
 * версии, см. `sota.as:1486-1533`, комментарии на местах ниже.
 *
 * Графика — `public/assets/complex/` (извлечена из `complex_lib.swf`,
 * FFDec, `client-assets/complex_lib/sprites/`).
 *
 * Сознательные отличия от оригинала: VK-профиль/кнопки не портированы
 * (см. memory remaster_vk_integration_rebuild); полноэкранный каркас и
 * компактные маркеры на карте вместо полноразмерных карточек — решение
 * читаемости этого порта, не 1:1 с Flash-раскладкой.
 */
import { useRef, useState } from 'preact/hooks';
import type { ComplexViewComb, ComplexViewData, ComplexViewNavigateCommand } from './complexViewData';

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
  '1': '/assets/complex/VORAN_LOGO_DEF.png',
  '2': '/assets/complex/LIENSU_LOGO_DEF.png',
  '3': '/assets/complex/PSOLAO_LOGO_DEF.png',
};

const RACE_GATE_KEY: Record<string, 'TECH' | 'BIO' | 'PSI'> = { '1': 'TECH', '2': 'BIO', '3': 'PSI' };

function gateAssetUrl(race: string | null, open: boolean): string {
  const key = RACE_GATE_KEY[race ?? '1'] ?? 'TECH';
  return `/assets/complex/GATE_${key}${open ? '' : '_WRECK'}.png`;
}

/** Приоритет портал-оверлея — sota.as:1491-1513, каждая проверка перекрывает предыдущую (npc "побеждает" всех). Только при открытых воротах. */
function portalOverlay(comb: ComplexViewComb): { url: string; title: string } | null {
  if (!comb.gateOpen) return null;
  let type = 'OPEN';
  let title = 'Врата открыты';
  if (comb.banned) {
    type = 'BAN';
    title = 'Игрок забанен';
  }
  if (comb.frozen) {
    type = 'FREEZE';
    title = 'Заморожен / отпуск';
  }
  if (comb.godModeMs !== null) {
    type = 'GODPRO';
    title = 'Защита богов (новичок)';
  }
  if (comb.captured) {
    type = 'CAPTURED';
    title = 'Захвачена';
  }
  if (comb.npc) {
    type = 'NPC';
    title = 'NPC';
  }
  return { url: `/assets/complex/PORTAL_${type}.png`, title };
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
  empty: '#3a4d7a',
};

export function ComplexViewWindow({ data, onClose, onNavigate, onWriteMail }: ComplexViewWindowProps) {
  const [ring, setRing] = useState(data.rings.find((r) => r.ln === data.ringName)?.id ?? data.rings[0]?.id ?? '');
  const [complexNum, setComplexNum] = useState(data.complexNumber);
  const [selected, setSelected] = useState<number | null>(null);
  const [hovered, setHovered] = useState<number | null>(null);
  const sideRefs = useRef<Record<number, HTMLDivElement | null>>({});

  const slots = Array.from({ length: 6 }, (_, i) => data.combs.find((c) => c.num === i + 1) ?? null);

  function selectSlot(num: number) {
    setSelected(num);
    sideRefs.current[num]?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  }

  const sceneW = 900;
  const sceneH = 600;
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
          <button type="button" class="ds-popup__btn" onClick={() => onNavigate({ ring, complexnum: complexNum })}>
            Перейти
          </button>
        </div>

        <div class="ds-complex-nav__group">
          <button type="button" class="ds-popup__btn" onClick={() => onNavigate({ direction: 'LEFT' })}>
            ← Предыдущий
          </button>
          <button type="button" class="ds-popup__btn" onClick={() => onNavigate({ direction: 'HOME' })}>
            Домой
          </button>
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
        <span class="ds-complex-legend__item">★ рейтинг</span>
        <span class="ds-complex-legend__item">✦ премиум</span>
        <span class="ds-complex-legend__item">
          <img src="/assets/complex/GATE_TECH.png" class="ds-complex-legend__icon" alt="" /> врата открыты
        </span>
        <span class="ds-complex-legend__item">
          <img src="/assets/complex/GATE_TECH_WRECK.png" class="ds-complex-legend__icon ds-complex-gate--closed" alt="" /> врата закрыты
        </span>
      </div>

      <div class="ds-complex-screen__main">
        <div class="ds-complex-scene">
          <svg width="100%" height="100%" viewBox={`0 0 ${sceneW} ${sceneH}`} preserveAspectRatio="xMidYMid meet">
            <defs>
              <pattern id="ds-complex-ground" patternUnits="userSpaceOnUse" width="96" height="96">
                <image href="/assets/complex/GROUND_TEXTURE.jpg" width="96" height="96" />
              </pattern>
            </defs>

            <g transform={`translate(${originX},${originY})`}>
              {/* Линии-переходы хаб → сектор — видна структура связи, не набор несвязанных гексов. */}
              {SLOT_DIRECTIONS.map((dir, i) => (
                <line
                  key={i}
                  x1={0}
                  y1={0}
                  x2={dir.x}
                  y2={dir.y}
                  stroke="#2a3a66"
                  stroke-width={3}
                  opacity={0.6}
                />
              ))}

              <polygon points={hexPoints(0, 0)} fill="#111a35" stroke="#4a5d9a" stroke-width={2} />
              <text x={0} y={5} text-anchor="middle" font-size="15" font-weight="bold" fill="#c6d2f5">
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
                const gateUrl = gateAssetUrl(comb?.race ?? null, gateOpen);
                const overlay = comb ? portalOverlay(comb) : null;

                const labelX = cx * 1.3;
                const labelY = cy * 1.3;
                const labelW = 118;
                const labelH = 40;

                return (
                  <g
                    key={num}
                    class="ds-complex-hex"
                    onClick={() => selectSlot(num)}
                    onMouseEnter={() => setHovered(num)}
                    onMouseLeave={() => setHovered((h) => (h === num ? null : h))}
                    style={{ cursor: 'pointer' }}
                  >
                    <polygon points={hexPoints(cx, cy)} fill={OWNER_COLOR[occ]} opacity={occ === 'empty' ? 0.22 : 0.58} />
                    <polygon points={hexPoints(cx, cy)} fill="url(#ds-complex-ground)" opacity={0.22} />
                    <polygon
                      points={hexPoints(cx, cy)}
                      fill="none"
                      stroke={active ? '#ffffff' : OWNER_COLOR[occ]}
                      stroke-width={active ? 4 : 2}
                      stroke-dasharray={occ === 'empty' ? '6 4' : undefined}
                    />

                    <g class={gateOpen ? undefined : 'ds-complex-gate--closed'}>
                      <image href={gateUrl} x={cx - 22} y={cy - 32} width={44} height={58} />
                    </g>
                    {overlay && (
                      <g>
                        <circle cx={cx} cy={cy - 24} r={10} fill="#0b1730" opacity={0.7} />
                        <image href={overlay.url} x={cx - 8} y={cy - 32} width={16} height={16} />
                      </g>
                    )}
                    {comb?.newbee && (
                      <image href="/assets/complex/NEWBEE.png" x={cx - 11} y={cy + 10} width={22} height={18} />
                    )}
                    {comb?.race && RACE_LOGO[comb.race] && (
                      <image href={RACE_LOGO[comb.race]} x={cx - 15} y={cy + 12} width={30} height={30} opacity={0.9} />
                    )}

                    {/* Подпись — на непрозрачной плашке, не прямо на текстуре. */}
                    <foreignObject x={labelX - labelW / 2} y={labelY - labelH / 2} width={labelW} height={labelH}>
                      <div class="ds-complex-hex__label">
                        <span class="ds-complex-hex__addr">
                          {data.ringName}.{data.complexNumber}.{num}
                        </span>
                        <span class="ds-complex-hex__name">{comb?.userName ?? 'свободно'}</span>
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
            const overlay = comb ? portalOverlay(comb) : null;

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
                  <span class="ds-complex-side-card__addr">
                    {data.ringName}.{data.complexNumber}.{num}
                  </span>
                  <span class="ds-complex-side-card__free">Свободно</span>
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
                <div class="ds-complex-side-card__avatar" style={{ borderColor: OWNER_COLOR[occ] }}>
                  {comb2.race && RACE_LOGO[comb2.race] && <img src={RACE_LOGO[comb2.race]} alt="" />}
                  <img
                    class="ds-complex-side-card__alliance"
                    src={
                      comb2.allianceIconUrl ||
                      (comb2.allianceTag ? '/assets/complex/ALLIANCE_LOGO_DEF.png' : '/assets/complex/NEUTRAL_LOGO_DEF.png')
                    }
                    alt=""
                    title={comb2.allianceTag ?? 'Без альянса'}
                  />
                </div>
                <div class="ds-complex-side-card__body">
                  <div class="ds-complex-side-card__row1">
                    <span class="ds-complex-side-card__name">{comb2.userName}</span>
                    <span class="ds-complex-side-card__league">Лига {comb2.league}</span>
                  </div>
                  <div class="ds-complex-side-card__addr2">
                    {data.ringName}.{data.complexNumber}.{num}
                    {comb2.combName ? ` · ${comb2.combName}` : ''}
                  </div>
                  <div class="ds-complex-side-card__row2">
                    <span title="Рейтинг">★ {comb2.cscore}</span>
                    {comb2.allianceTag && <span title="Альянс">{comb2.allianceTag}</span>}
                    {comb2.premium && (
                      <span title="Премиум" class="ds-complex-side-card__premium">
                        ✦ премиум
                      </span>
                    )}
                    {overlay && <span title={overlay.title}>{overlay.title}</span>}
                  </div>
                </div>
                <button
                  type="button"
                  class="ds-complex-side-card__mail"
                  title="Написать письмо"
                  onClick={(e) => {
                    e.stopPropagation();
                    onWriteMail(comb2.userName!);
                  }}
                >
                  ✉
                </button>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}
