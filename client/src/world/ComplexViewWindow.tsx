/**
 * Экран «Обзор комплекса» — `fillComplex()` (`sota.as:1013-1300+`).
 * Данные — complexViewData.ts.
 *
 * ЧЕТВЁРТАЯ переделка в этой сессии (2026-09-16) — полировка по второму
 * развёрнутому разбору владельца. Структура (карта слева / список
 * карточек справа / легенда / перегруппированная навигация) сохранена
 * без изменений — владелец её одобрил ("уже рабочий интерфейс"),
 * менялась только читаемость/масштаб/атмосфера:
 *
 * - `viewBox` карты подогнан ВПЛОТНУЮ к реальным границам контента
 *   (раньше 900×600 при фактическом содержимом ~640×285 — лишние
 *   ~300px "мёртвого" поля в системе координат ужимали видимый размер
 *   гекс-цветка при подгонке под контейнер, отсюда "карта занимает
 *   четверть области"). Теперь 720×350 — почти без запаса.
 * - Подсветка ВЫБОРА отделена от пунктира "свободно": раньше обе
 *   использовали белый пунктир — смешивались смыслы. Теперь выбор —
 *   отдельное сплошное голубое свечение (`filter:drop-shadow`) поверх
 *   обычной (уже не белой) рамки владения; строка в сайдбаре при
 *   выборе получает собственный акцентный фон+рамку, не просто тонкую
 *   обводку.
 * - Аватар-квадрат на гексе убран (visually "случайный") — на карте
 *   остались только: врата (крупнее, по центру, главный визуальный
 *   элемент сектора — важно для геймплея) и компактный герб альянса
 *   (маленький, только если есть альянс). Портрет/ник — в сайдбаре.
 * - Легенда крупнее (+20% шрифт/иконки), у врат в легенде и на карте
 *   добавлена подложка-индикатор (голубое свечение под открытыми,
 *   тусклый диск под закрытыми) — растровая разница open/wreck сама по
 *   себе слишком тонкая, подложка даёт однозначный сигнал состояния.
 * - Сайдбар — единая базовая высота строки для всех 6 слотов (было
 *   "карточки крупные, свободные — узкие"), подробности разворачиваются
 *   только у ВЫБРАННОГО слота (accordion), а не показаны все разом —
 *   меньше визуального шума в базовом состоянии.
 * - Немного игровой атмосферы возвращено: свечение хаба, глубина теней
 *   на гексах, акцентная кнопка "Перейти".
 *
 * Сознательно НЕ сделано (см. фидбэк): тонкая светящаяся линия от
 * выбранной карточки в сайдбаре до сектора на карте — карточка живёт
 * в обычном HTML-потоке сайдбара, а сектор в независимой системе
 * координат SVG; линия через границу DOM/SVG требует постоянного
 * пересчёта экранных координат обоих концов (ResizeObserver/rAF) ради
 * декоративного эффекта — вместо этого связь подчёркнута совпадающей
 * яркой подсветкой на обеих сторонах одновременно (см. `active` ниже).
 *
 * Врата/портал-оверлей/приоритет статусов — без изменений с прошлой
 * версии, см. `sota.as:1486-1533`, комментарии на местах ниже.
 * Графика — `public/assets/complex/` (извлечена из `complex_lib.swf`).
 * VK-профиль/кнопки не портированы — см. memory
 * remaster_vk_integration_rebuild.
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

const RACE_GATE_KEY: Record<string, 'TECH' | 'BIO' | 'PSI'> = { '1': 'TECH', '2': 'BIO', '3': 'PSI' };

function gateAssetUrl(race: string | null, open: boolean): string {
  const key = RACE_GATE_KEY[race ?? '1'] ?? 'TECH';
  return `/assets/complex/GATE_${key}${open ? '' : '_WRECK'}.png`;
}

const RACE_LOGO: Record<string, string> = {
  '1': '/assets/complex/VORAN_LOGO_DEF.png',
  '2': '/assets/complex/LIENSU_LOGO_DEF.png',
  '3': '/assets/complex/PSOLAO_LOGO_DEF.png',
};

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
  empty: '#5c72b0',
};

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

  // viewBox подогнан вплотную к реальным границам контента (гекс-цветок
  // + подписи-плашки на mult=1.35) — см. докстринг выше.
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
            ⌂ Домой
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
        <span class="ds-complex-legend__item">★ рейтинг</span>
        <span class="ds-complex-legend__item">✦ премиум</span>
        <span class="ds-complex-legend__item">
          <span class="ds-complex-legend__gatewrap ds-complex-legend__gatewrap--open">
            <img src="/assets/complex/GATE_TECH.png" class="ds-complex-legend__icon" alt="" />
          </span>
          врата открыты
        </span>
        <span class="ds-complex-legend__item">
          <span class="ds-complex-legend__gatewrap ds-complex-legend__gatewrap--closed">
            <img src="/assets/complex/GATE_TECH_WRECK.png" class="ds-complex-legend__icon ds-complex-gate--closed" alt="" />
          </span>
          врата закрыты
        </span>
      </div>

      <div class="ds-complex-screen__main">
        <div class="ds-complex-scene">
          <svg width="100%" height="100%" viewBox={`0 0 ${sceneW} ${sceneH}`} preserveAspectRatio="xMidYMid meet">
            <defs>
              <pattern id="ds-complex-ground" patternUnits="userSpaceOnUse" width="96" height="96">
                <image href="/assets/complex/GROUND_TEXTURE.jpg" width="96" height="96" />
              </pattern>
              <radialGradient id="ds-complex-hub-glow" cx="50%" cy="50%" r="50%">
                <stop offset="0%" stop-color="#6fd6ff" stop-opacity={0.45} />
                <stop offset="100%" stop-color="#6fd6ff" stop-opacity="0" />
              </radialGradient>
            </defs>

            <g transform={`translate(${originX},${originY})`}>
              {/* Линии-переходы хаб → сектор — видна структура связи, не набор несвязанных гексов. */}
              {SLOT_DIRECTIONS.map((dir, i) => (
                <line key={i} x1={0} y1={0} x2={dir.x} y2={dir.y} stroke="#2a3a66" stroke-width={3} opacity={0.6} />
              ))}

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
                const gateUrl = gateAssetUrl(comb?.race ?? null, gateOpen);
                const overlay = comb ? portalOverlay(comb) : null;

                const labelX = cx * 1.35;
                const labelY = cy * 1.35;
                const labelW = 120;
                const labelH = 36;

                return (
                  <g
                    key={num}
                    class={`ds-complex-hex${active ? ' ds-complex-hex--active' : ''}`}
                    onClick={() => selectSlot(num)}
                    onMouseEnter={() => setHovered(num)}
                    onMouseLeave={() => setHovered((h) => (h === num ? null : h))}
                    style={{ cursor: 'pointer' }}
                  >
                    <polygon points={hexPoints(cx, cy)} fill={OWNER_COLOR[occ]} opacity={occ === 'empty' ? 0.32 : 0.58} />
                    <polygon points={hexPoints(cx, cy)} fill="url(#ds-complex-ground)" opacity={occ === 'empty' ? 0.3 : 0.2} />
                    <polygon
                      points={hexPoints(cx, cy)}
                      fill="none"
                      stroke={OWNER_COLOR[occ]}
                      stroke-width={2}
                      stroke-dasharray={occ === 'empty' ? '6 4' : undefined}
                    />
                    {/* Подсветка ВЫБОРА — отдельный сплошной контур поверх, не путается с пунктиром "свободно". */}
                    {active && (
                      <polygon points={hexPoints(cx, cy)} fill="none" stroke="#4fc3ff" stroke-width={4} class="ds-complex-hex__active-ring" />
                    )}

                    {/* Врата — главный визуальный элемент сектора (важны для
                        геймплея), крупные, по центру. Подложка под открытыми —
                        светлое голубое свечение (контраст с тёмной сотой);
                        под закрытыми — светло-серый диск с ободком, НЕ тёмный:
                        сама по себе разница ассетов open/_WRECK слишком тонкая
                        (см. докстринг файла), тёмная подложка на тёмном фоне
                        делала закрытые врата на свободных секторах почти
                        невидимыми — ровно то, на что владелец и указал. */}
                    {gateOpen ? (
                      <circle cx={cx} cy={cy} r={32} fill="#2f7fae" opacity={0.55} />
                    ) : (
                      <circle cx={cx} cy={cy} r={32} fill="#5c6a9c" opacity={0.35} stroke="#8092c8" stroke-width={1.5} />
                    )}
                    <g class={gateOpen ? undefined : 'ds-complex-gate--closed'}>
                      <image href={gateUrl} x={cx - 28} y={cy - 38} width={56} height={74} />
                    </g>
                    {overlay && (
                      <g>
                        <circle cx={cx} cy={cy - 30} r={11} fill="#0b1730" opacity={0.75} />
                        <image href={overlay.url} x={cx - 9} y={cy - 39} width={18} height={18} />
                      </g>
                    )}
                    {comb?.newbee && (
                      <image href="/assets/complex/NEWBEE.png" x={cx - 42} y={cy - 54} width={20} height={16} />
                    )}
                    {/* Компактный маркер владельца — герб альянса, только если есть. Без него — просто цвет+врата, без лишних картинок. */}
                    {comb?.allianceTag && (
                      <image
                        href={comb.allianceIconUrl || '/assets/complex/ALLIANCE_LOGO_DEF.png'}
                        x={cx - 11}
                        y={cy + 24}
                        width={22}
                        height={22}
                      />
                    )}

                    {/* Подпись — на непрозрачной плашке, вынесена наружу от гекса, не прямо на текстуре. */}
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
            const open = selected === num;
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
                  <div class="ds-complex-side-card__row1">
                    <span class="ds-complex-side-card__addr">
                      {data.ringName}.{data.complexNumber}.{num}
                    </span>
                    <span class="ds-complex-side-card__free">Свободно</span>
                  </div>
                  {open && (
                    <div class="ds-complex-side-card__expand">Сектор свободен — пока никем не заселён.</div>
                  )}
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
                    {comb2.gateOpen ? 'врата открыты' : 'врата закрыты'}
                  </span>
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
                        {overlay && overlay.title !== 'Врата открыты' && <span title={overlay.title}>{overlay.title}</span>}
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
