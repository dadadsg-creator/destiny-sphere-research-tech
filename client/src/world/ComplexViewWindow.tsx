/**
 * Экран «Обзор комплекса» — `fillComplex()` (`sota.as:1013-1300+`).
 * Данные — complexViewData.ts.
 *
 * ИСТОРИЯ ПЕРЕДЕЛОК (обе — по прямой критике владельца, не мои догадки):
 * 1) Первая версия (до 2026-09-16) была текстовой HTML-таблицей —
 *    "это не игра, а CRM". Переписана как SVG гекс-сцена.
 * 2) Вторая версия (гекс-сцена, но маленькая, в стандартном `Window` с
 *    крестиком, герб расы на самом гексе, статусы — общие эмодзи) —
 *    владелец снова указал на конкретные расхождения с оригиналом:
 *    "иконки ворот имеют разные состояния, открыты/закрыты", "соты
 *    имеют СВОИ ворота" (не только хаб), "по-прежнему попап, а не
 *    полный экран", "соты мелкие и нефункциональные", "попапчик
 *    примитивный". Эта версия — ответ на каждый пункт по коду
 *    оригинала (`sota.as:1486-1533`, построение врат), не косметика:
 *
 *    - `sota.as:1491-1499`: `gate=0` → `gate_type="_WRECK"` (разбитые
 *      врата), `gate=1` → `gate_type=""` (целые, лит портал). ВРАТА
 *      СТРОЯТСЯ ДЛЯ КАЖДОЙ ИЗ 6 СОТ ВСЕГДА (`gate_mov_pos =
 *      complexMov["GATE_" + complexarr[i].num]`, вне зависимости от
 *      занятости — даже для пустого слота `race` не задан сервером,
 *      оригинал берёт `default: race_str="_TECH"` тем же switch'ем,
 *      что и герб, `sota.as:1317-1320`) — здесь так же: gate есть на
 *      ВСЕХ 6 гексах, раса по умолчанию TECH, если сервер её не прислал
 *      (незанятый слот, `UA_ComplexView.class.php:349-353` — атрибут
 *      `race` в ответе просто отсутствует).
 *    - `sota.as:1506-1513`: портал-оверлей (BAN/FREEZE/GODPRO/CAPTURED/
 *      NPC, приоритет — каждая следующая проверка молча перекрывает
 *      предыдущую, порядок важен) рисуется ПОВЕРХ врат, и ТОЛЬКО когда
 *      врата открыты (`if (gate_type=="")`) — закрытые врата никакой
 *      статус не показывают вообще. NEWBEE — отдельная, независимая от
 *      портала иконка на вратах (`sota.as:1535-1540`).
 *    - Герб расы/альянс-лого в оригинале — НЕ на самом гексе, а на
 *      отдельной плавающей карточке `DATA_SOTA_i`
 *      (`DefineSprite_147_BLOCK_COMPLEX/1.png` — растровое превью:
 *      лига-бейдж, 2 слота картинок, кнопки, 3 строки текста, PREMIUM-
 *      звезда) — герб перенесён с гекса в карточку, гекс теперь несёт
 *      ТОЛЬКО врата + грунт + цвет владения, карточка стала полноценной
 *      "визиткой" вместо примитивной текстовой плашки.
 *    - Полноэкранный режим: вместо стандартного `Window` (маленький
 *      диалог с титлбаром) — свой полноэкранный каркас
 *      (`.ds-complex-screen`, ~96vw×92vh) с тонкой шапкой; SVG-сцена
 *      растягивается на всё доступное место (`width/height=100%` +
 *      `viewBox` + `preserveAspectRatio`), поэтому гексы КРУПНЕЕ на
 *      любом экране, а не фиксированы в пикселях.
 *    - Гексы кликабельны (курсор, подсветка при наведении, клик —
 *      подсвечивает карточку) — в пределах доступного интерфейса
 *      колбэков (`onNavigate`/`onWriteMail`/`onClose`) это осмысленная
 *      "функциональность", а не просто визуальная поверхность.
 *
 * Графика — извлечена из `complex_lib.swf` (FFDec, `client-assets/
 * complex_lib/sprites/`) и скопирована в `public/assets/complex/`:
 * гербы рас (`VORAN/LIENSU/PSOLAO_LOGO_DEF`, race=1/2/3, сверено с
 * `race_str`/`def_pic`, `sota.as:1303-1321`), врата по расе, целые и
 * `_WRECK` (разбитые), портал-оверлеи (OPEN/BAN/FREEZE/GODPRO/CAPTURED/
 * NPC), NEWBEE, альянс/нейтральное лого по умолчанию, грунтовая
 * текстура (`images/156.jpg`).
 *
 * Сознательные отличия от оригинала (не пиксель-в-пиксель):
 * - VK-профиль/кнопки (`PROFILE_BUT`, `ComplexProfiles`) сознательно не
 *   портированы — см. memory remaster_vk_integration_rebuild.
 * - Визуальная разница целых/разбитых врат в исходных ассетах тоньше,
 *   чем хотелось бы (мелкие растровые превью FFDec) — усилена явным
 *   затемнением/desaturate разбитых врат через CSS-фильтр, чтобы
 *   состояние читалось однозначно на любом масштабе.
 */
import { useState } from 'preact/hooks';
import type { ComplexViewComb, ComplexViewData, ComplexViewNavigateCommand } from './complexViewData';

export interface ComplexViewWindowProps {
  data: ComplexViewData;
  onClose: () => void;
  onNavigate: (cmd: ComplexViewNavigateCommand) => void;
  onWriteMail: (nick: string) => void;
}

/** Масштаб гекс-сцены — формула как в map/hexGrid.ts (hexPolygonPoints), центрированная и параметризованная под крупную отдельную сцену (SVG всё равно растягивается на весь экран — это лишь внутренняя система координат). */
const H = 112;
const V = 64;

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

/** Направления 6 соседних гексов вокруг общего "хаба" — sota.as не документирует порядок слотов 1-6 визуально, здесь произвольный, но стабильный обход по часовой стрелке от верха. */
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

/** race_str switch, sota.as:1303-1321 — "TECH"/"BIO"/"PSI", default TECH. */
const RACE_GATE_KEY: Record<string, 'TECH' | 'BIO' | 'PSI'> = { '1': 'TECH', '2': 'BIO', '3': 'PSI' };

function gateAssetUrl(race: string | null, open: boolean): string {
  const key = RACE_GATE_KEY[race ?? '1'] ?? 'TECH';
  return `/assets/complex/GATE_${key}${open ? '' : '_WRECK'}.png`;
}

/** Приоритет портал-оверлея — sota.as:1491-1513, каждая проверка перекрывает предыдущую, порядок важен (npc "побеждает" всех). Показывается только при открытых воротах. */
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

function ownerColor(comb: ComplexViewComb | null): string {
  if (comb?.home) return '#a44bf0';
  if (comb?.sameAlliance) return '#33dd55';
  return '#ffcc33';
}

export function ComplexViewWindow({ data, onClose, onNavigate, onWriteMail }: ComplexViewWindowProps) {
  const [ring, setRing] = useState(data.rings.find((r) => r.ln === data.ringName)?.id ?? data.rings[0]?.id ?? '');
  const [complexNum, setComplexNum] = useState(data.complexNumber);
  const [selected, setSelected] = useState<number | null>(null);

  const slots = Array.from({ length: 6 }, (_, i) => data.combs.find((c) => c.num === i + 1) ?? null);
  const anyGateOpen = data.gateOpen || slots.some((c) => c?.gateOpen);

  const sceneW = 1200;
  const sceneH = 760;
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
        <span class="ds-complex-screen__navsep" />
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
        <svg width="100%" height="100%" viewBox={`0 0 ${sceneW} ${sceneH}`} preserveAspectRatio="xMidYMid meet">
          <defs>
            <pattern id="ds-complex-ground" patternUnits="userSpaceOnUse" width="96" height="96">
              <image href="/assets/complex/GROUND_TEXTURE.jpg" width="96" height="96" />
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
            <text x={0} y={5} text-anchor="middle" font-size="16" font-weight="bold" fill="#8fa3d6">
              {data.ringName}.{data.complexNumber}
            </text>

            {slots.map((comb, i) => {
              const dir = SLOT_DIRECTIONS[i];
              const cx = dir.x;
              const cy = dir.y;
              const num = i + 1;
              const occupied = !!comb?.userName;
              const color = ownerColor(comb);
              const gateOpen = comb?.gateOpen ?? false;
              const gateUrl = gateAssetUrl(comb?.race ?? null, gateOpen);
              const overlay = comb ? portalOverlay(comb) : null;
              const isSelected = selected === num;

              const cardX = cx * 1.85;
              const cardY = cy * 1.85;
              const cardW = 208;
              const cardH = 146;

              return (
                <g key={num}>
                  <g
                    class="ds-complex-hex"
                    onClick={() => setSelected(isSelected ? null : num)}
                    style={{ cursor: 'pointer' }}
                  >
                    <polygon points={hexPoints(cx, cy)} fill="url(#ds-complex-ground)" opacity={0.85} />
                    <polygon
                      points={hexPoints(cx, cy)}
                      fill={color}
                      opacity={occupied ? 0.38 : 0.16}
                      stroke={isSelected ? '#ffffff' : color}
                      stroke-width={isSelected ? 3 : 2}
                    />

                    {/* Врата — на КАЖДОЙ соте, всегда: целые (лит герб расы) или разбитые (_WRECK, приглушены фильтром) — sota.as:1489-1533. */}
                    <g class={gateOpen ? undefined : 'ds-complex-gate--closed'}>
                      <image href={gateUrl} x={cx - 27} y={cy - 40} width={54} height={70} />
                    </g>
                    {overlay && (
                      <g>
                        <circle cx={cx} cy={cy - 30} r={12} fill="#0b1730" opacity={0.65} />
                        <image href={overlay.url} x={cx - 10} y={cy - 40} width={20} height={20} />
                      </g>
                    )}
                    {comb?.newbee && (
                      <image href="/assets/complex/NEWBEE.png" x={cx - 14} y={cy + 14} width={28} height={22} />
                    )}

                    {/* Подпись адреса — только для незанятых слотов (для
                        занятых адрес уже есть на карточке, дублировать
                        рядом с гексом излишне). Вдоль того же луча "от
                        хаба наружу", что и направление слота
                        (SLOT_DIRECTIONS), а не жёстко "под гексом": для
                        среднего ряда (лево/право, cy=0) фиксированное
                        смещение вниз утыкалось прямо в хаб. */}
                    {!occupied && (
                      <>
                        <text x={cx * 1.22} y={cy * 1.22} text-anchor="middle" font-size="11" fill="#8fa3d6">
                          {data.ringName}.{data.complexNumber}.{num}
                        </text>
                        <text x={cx * 1.22} y={cy * 1.22 + 14} text-anchor="middle" font-size="10" fill="#5c6a9c">
                          свободно
                        </text>
                      </>
                    )}
                  </g>

                  {occupied && comb && (
                    <foreignObject x={cardX - cardW / 2} y={cardY - cardH / 2} width={cardW} height={cardH}>
                      <div
                        class={`ds-complex-card${isSelected ? ' ds-complex-card--selected' : ''}`}
                        style={{ borderColor: color }}
                        onClick={() => setSelected(num)}
                      >
                        <div class="ds-complex-card__avatar" style={{ borderColor: color }}>
                          {comb.race && RACE_LOGO[comb.race] && <img src={RACE_LOGO[comb.race]} alt="" />}
                          <img
                            class="ds-complex-card__alliance-badge"
                            src={
                              comb.allianceIconUrl ||
                              (comb.allianceTag ? '/assets/complex/ALLIANCE_LOGO_DEF.png' : '/assets/complex/NEUTRAL_LOGO_DEF.png')
                            }
                            alt=""
                            title={comb.allianceTag ?? 'Без альянса'}
                          />
                        </div>
                        <div class="ds-complex-card__body">
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
                            {comb.premium && (
                              <span title="Премиум" class="ds-complex-card__premium">
                                ✦
                              </span>
                            )}
                          </div>
                          <button
                            type="button"
                            class="ds-complex-card__mail"
                            onClick={(e) => {
                              e.stopPropagation();
                              onWriteMail(comb.userName!);
                            }}
                          >
                            ✉ Письмо
                          </button>
                        </div>
                      </div>
                    </foreignObject>
                  )}
                </g>
              );
            })}
          </g>
        </svg>
      </div>
    </div>
  );
}
