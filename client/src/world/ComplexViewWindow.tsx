/**
 * Окно «Обзор комплекса» — `fillComplex()` (`sota.as:1013-1300+`).
 * Данные — complexViewData.ts.
 *
 * ПЕРВАЯ версия этого окна (до 2026-09-16) была текстовой таблицей —
 * справедливо раскритиковано владельцем ("это не игра, а CRM"): оригинал
 * это не таблица, а графическая гекс-сцена (`complex_lib.swf`,
 * `debug.link.complex_mov` — отдельная загружаемая библиотека, ЦЕЛИКОМ
 * визуальная, `sota_contur` — гекс-силуэт "1 центр + 6 сот вокруг",
 * см. `client-assets/complex_lib/sprites/DefineSprite_149_sota_contur/
 * 1.png`). Переписано как SVG гекс-сцена той же геометрии, что и карта
 * соты (`map/hexGrid.ts`, H/V=24/13 — здесь свой масштаб для крупной
 * отдельной сцены, формула та же, `hexPoints()` ниже — центрированный
 * аналог `hexPolygonPoints()`).
 *
 * Графика — извлечена из `complex_lib.swf` (FFDec, `client-assets/
 * complex_lib/sprites/`) и скопирована в `public/assets/complex/`:
 * гербы рас по `race` (`DefineSprite_73_VORAN_LOGO_DEF`=TECH/race=1,
 * `_71_LIENSU_LOGO_DEF`=BIO/race=2, `_69_PSOLAO_LOGO_DEF`=PSI/race=3 —
 * сверено с `race_str`/`def_pic` switch, `sota.as:1303-1321`), кольца
 * ворот по расе (`GATE_TECH/BIO/PSI`), фон-альянс/нейтральная иконка
 * (`ALLIANCE_LOGO_DEF`/`NEUTRAL_LOGO_DEF` — оригинал показывает первую,
 * если есть тег альянса, вторую иначе, `sota.as:1410-1419`) и тайловая
 * грунтовая текстура сот (`images/156.jpg`).
 *
 * Сознательные отличия от оригинала (не пиксель-в-пиксель):
 * - Оригинал красит ВСЕ 6 гексов (включая пустые слоты) по home/alliance/
 *   жёлтый (`sota.as:1157-1162`, комплексарр всегда 6 элементов, флаги
 *   home/samealliance для пустого слота просто false → жёлтый) — здесь
 *   пустой слот вместо этого сделан явно приглушённым/пунктирным
 *   ("свободно"), чтобы не путать с занятой чужой сотой — это расхождение
 *   ради ясности, не находка о протоколе.
 * - Статусные значки (FREEZE/BAN/CAPTURED/NEWBEE/NPC — мелкие цветные
 *   кружки в оригинале, малоинформативные сами по себе без контекста
 *   композиции) заменены на подписанные эмодзи-бейджи с `title` —
 *   тот же смысл, читаемее в отдельном порте без остальной композиции.
 * - VK-профиль/кнопки (`PROFILE_BUT`, `ComplexProfiles`) сознательно не
 *   портированы — см. memory remaster_vk_integration_rebuild.
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import type { ComplexViewComb, ComplexViewData, ComplexViewNavigateCommand } from './complexViewData';

export interface ComplexViewWindowProps {
  data: ComplexViewData;
  onClose: () => void;
  onNavigate: (cmd: ComplexViewNavigateCommand) => void;
  onWriteMail: (nick: string) => void;
}

/** Масштаб гекс-сцены — своя крупная геометрия, формула как в map/hexGrid.ts (hexPolygonPoints), но центрированная и параметризованная под размер экрана. */
const H = 80;
const V = 46;

/** 6 вершин гекса с центром (cx,cy) — центрированный аналог hexPolygonPoints(). */
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

/** Направления 6 соседних гексов вокруг общего "хаба" (центра комплекса) — sota.as не документирует порядок слотов 1-6 визуально, здесь произвольный, но стабильный обход по часовой стрелке от верха. */
const SLOT_DIRECTIONS: Array<{ x: number; y: number }> = [
  { x: H, y: -1.5 * V }, // 1 — вверх-вправо
  { x: 2 * H, y: 0 }, // 2 — вправо
  { x: H, y: 1.5 * V }, // 3 — вниз-вправо
  { x: -H, y: 1.5 * V }, // 4 — вниз-влево
  { x: -2 * H, y: 0 }, // 5 — влево
  { x: -H, y: -1.5 * V }, // 6 — вверх-влево
];

const RACE_LOGO: Record<string, string> = {
  '1': '/assets/complex/VORAN_LOGO_DEF.png',
  '2': '/assets/complex/LIENSU_LOGO_DEF.png',
  '3': '/assets/complex/PSOLAO_LOGO_DEF.png',
};

const RACE_GATE: Record<string, string> = {
  '1': '/assets/complex/GATE_TECH.png',
  '2': '/assets/complex/GATE_BIO.png',
  '3': '/assets/complex/GATE_PSI.png',
};

function ownerColor(comb: ComplexViewComb): string {
  if (comb.home) return '#8b2fc9';
  if (comb.sameAlliance) return '#33dd55';
  return '#ffcc33';
}

function statusBadges(comb: ComplexViewComb): Array<{ icon: string; title: string }> {
  const badges: Array<{ icon: string; title: string }> = [];
  if (comb.premium) badges.push({ icon: '★', title: 'Премиум' });
  if (comb.frozen) badges.push({ icon: '❄', title: 'Заморожен / отпуск' });
  if (comb.banned) badges.push({ icon: '⛔', title: 'Забанен' });
  if (comb.newbee) badges.push({ icon: '🛡', title: 'Защита новичка' });
  if (comb.npc) badges.push({ icon: '🤖', title: 'NPC' });
  if (comb.captured) badges.push({ icon: '⚔', title: 'Захвачена' });
  return badges;
}

export function ComplexViewWindow({ data, onClose, onNavigate, onWriteMail }: ComplexViewWindowProps) {
  const [ring, setRing] = useState(data.rings.find((r) => r.ln === data.ringName)?.id ?? data.rings[0]?.id ?? '');
  const [complexNum, setComplexNum] = useState(data.complexNumber);

  const slots = Array.from({ length: 6 }, (_, i) => data.combs.find((c) => c.num === i + 1));
  const anyGateOpen = data.gateOpen || slots.some((c) => c?.gateOpen);

  const sceneW = 860;
  const sceneH = 640;
  const originX = sceneW / 2;
  const originY = sceneH / 2;

  return (
    <Window title={`Комплекс ${data.ringName}.${data.complexNumber}`} onClose={onClose}>
      <div class="ds-spying__row">
        <span class="ds-spying__label">Кольцо:</span>
        <select value={ring} onChange={(e) => setRing((e.target as HTMLSelectElement).value)}>
          {data.rings.map((r) => (
            <option key={r.id} value={r.id}>
              {r.ln ?? r.id}
            </option>
          ))}
        </select>
        <span class="ds-spying__label">№ комплекса:</span>
        <input
          type="number"
          class="ds-stepper__input"
          value={complexNum}
          onInput={(e) => setComplexNum(Number((e.target as HTMLInputElement).value))}
        />
        <button type="button" class="ds-popup__btn" onClick={() => onNavigate({ ring, complexnum: complexNum })}>
          Перейти
        </button>
      </div>

      <div class="ds-btn-row">
        <button type="button" class="ds-popup__btn" onClick={() => onNavigate({ direction: 'LEFT' })}>
          ← Пред.
        </button>
        <button type="button" class="ds-popup__btn" onClick={() => onNavigate({ direction: 'HOME' })}>
          Домой
        </button>
        <button type="button" class="ds-popup__btn" onClick={() => onNavigate({ direction: 'RIGHT' })}>
          След. →
        </button>
      </div>

      <div class="ds-complex-scene">
        <svg width={sceneW} height={sceneH} viewBox={`0 0 ${sceneW} ${sceneH}`}>
          <defs>
            <pattern id="ds-complex-ground" patternUnits="userSpaceOnUse" width="72" height="72">
              <image href="/assets/complex/GROUND_TEXTURE.jpg" width="72" height="72" />
            </pattern>
            <radialGradient id="ds-complex-hub-glow" cx="50%" cy="50%" r="50%">
              <stop offset="0%" stop-color="#6fd6ff" stop-opacity={anyGateOpen ? 0.55 : 0.15} />
              <stop offset="100%" stop-color="#6fd6ff" stop-opacity="0" />
            </radialGradient>
          </defs>

          <g transform={`translate(${originX},${originY})`}>
            {/* Хаб-гекс в центре — общий узел ворот комплекса. */}
            <circle cx={0} cy={0} r={H * 1.6} fill="url(#ds-complex-hub-glow)" />
            <polygon points={hexPoints(0, 0)} fill="#111a35" stroke="#3a4d7a" stroke-width={2} />
            <text x={0} y={5} text-anchor="middle" font-size="13" font-weight="bold" fill="#8fa3d6">
              {data.ringName}.{data.complexNumber}
            </text>

            {slots.map((comb, i) => {
              const dir = SLOT_DIRECTIONS[i];
              const cx = dir.x;
              const cy = dir.y;
              const occupied = !!comb?.userName;
              const num = i + 1;

              if (!comb || !occupied) {
                return (
                  <g key={num}>
                    <polygon
                      points={hexPoints(cx, cy)}
                      fill="#1b2547"
                      opacity={0.35}
                      stroke="#3a4d7a"
                      stroke-width={1.5}
                      stroke-dasharray="4 3"
                    />
                    <text x={cx} y={cy - 4} text-anchor="middle" font-size="10" fill="#5c6a9c">
                      {data.ringName}.{data.complexNumber}.{num}
                    </text>
                    <text x={cx} y={cy + 10} text-anchor="middle" font-size="10" fill="#5c6a9c">
                      свободно
                    </text>
                  </g>
                );
              }

              const color = ownerColor(comb);
              const gateMidX = cx * 0.42;
              const gateMidY = cy * 0.42;
              const cardX = cx * 2.0;
              const cardY = cy * 2.0;
              const cardW = 152;
              // Фиксированная высота с запасом под худший случай (название
              // соты + строка бейджей) — без этого flexbox сжимал строки с
              // overflow:hidden (name/combname) почти до нуля при нехватке
              // места: у элементов с overflow≠visible автоматический
              // минимальный размер во flex-контейнере равен 0, а не
              // min-content, так что именно они "проигрывали" сжатие
              // первыми, пока остальные строки оставались полного размера
              // (найдено визуальной проверкой скриншота — "Enzo"/"Enzo_test"
              // рендерились в 1-2px высотой).
              const cardH = 122;
              const badges = statusBadges(comb);

              return (
                <g key={num}>
                  <polygon points={hexPoints(cx, cy)} fill="url(#ds-complex-ground)" opacity={0.85} />
                  <polygon points={hexPoints(cx, cy)} fill={color} opacity={0.4} stroke={color} stroke-width={2} />
                  {comb.gateOpen && comb.race && RACE_GATE[comb.race] && (
                    <image
                      href={RACE_GATE[comb.race]}
                      x={gateMidX - 12}
                      y={gateMidY - 16}
                      width={24}
                      height={32}
                    />
                  )}
                  {comb.race && RACE_LOGO[comb.race] && (
                    <>
                      <circle cx={cx} cy={cy} r={24} fill="#0b1730" opacity={0.55} />
                      <image href={RACE_LOGO[comb.race]} x={cx - 22} y={cy - 22} width={44} height={44} />
                    </>
                  )}

                  <foreignObject x={cardX - cardW / 2} y={cardY - cardH / 2} width={cardW} height={cardH}>
                    <div class="ds-complex-card" style={{ borderColor: color }}>
                      <div class="ds-complex-card__top">
                        <span class="ds-complex-card__addr">
                          {data.ringName}.{data.complexNumber}.{num}
                        </span>
                        <span class="ds-complex-card__league">Лига {comb.league}</span>
                      </div>
                      <div class="ds-complex-card__name">{comb.userName}</div>
                      {comb.combName && <div class="ds-complex-card__combname">{comb.combName}</div>}
                      <div class="ds-complex-card__meta">
                        {comb.allianceTag && <span title="Альянс">{comb.allianceTag}</span>}
                        <span title="Рейтинг">★ {comb.cscore}</span>
                      </div>
                      {badges.length > 0 && (
                        <div class="ds-complex-card__badges">
                          {badges.map((b) => (
                            <span key={b.title} title={b.title}>
                              {b.icon}
                            </span>
                          ))}
                        </div>
                      )}
                      <button type="button" class="ds-complex-card__mail" onClick={() => onWriteMail(comb.userName!)}>
                        ✉ Письмо
                      </button>
                    </div>
                  </foreignObject>
                </g>
              );
            })}
          </g>
        </svg>
      </div>
    </Window>
  );
}
