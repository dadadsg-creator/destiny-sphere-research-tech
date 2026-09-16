/**
 * Круговое меню (docs/main_screen.md §11) — второй заход, полная
 * переделка по детальному разбору владельца (2026-09-16): "текущее
 * меню выглядит как временная схема из пяти обычных кругов… это не
 * единое круговое меню… потеряна металлическая конструкция". Прошлая
 * версия (отдельные круглые кнопки, разложенные абсолютным
 * позиционированием) была сознательным упрощением "форма не финальна"
 * — здесь она заменена на честную геометрию: единый SVG, клиновидные
 * секторы (donut-сегменты, не отдельные круги), металлическое кольцо
 * вокруг центра, центральная "сфера" с градиентом и свечением.
 *
 * Разделов теперь 5 (было 4 — "Нейтрал" отсутствовал): Дом/Центр/
 * Комплекс/Колонии/Нейтрал. Все пять уже реально подключены —
 * `<navigate cell="1" dest="HOME|CENTRAL|NEUTRAL"/>` (то же семейство
 * команд, что и "Мои колонии", `world/colonyNavigateData.ts`) плюс уже
 * существовавшие Комплекс/Колонии — ни один сектор не заглушка, в
 * отличие от MainNav.tsx/BottomBar.tsx (там протокол для остальных
 * разделов не исследован).
 *
 * Геометрия (центр 110,110 в 220×220 viewBox):
 * - центральная сфера r=50 (диаметр 100px, точно по ТЗ);
 * - металлическое кольцо 50→74 (~24px, "около 30" по ТЗ);
 * - секторы (донат-сегменты) 74→105 (диаметр меню 210px, точно по ТЗ).
 * Секторы — SVG `<path>` дуга-линия-дуга-линия, не CSS-трюки с
 * border-radius (для КЛИНЬЕВ, а не кругов, это единственный практичный
 * способ без готовой библиотеки).
 */
import { useState } from 'preact/hooks';
import { hudActions } from './hudActionsStore';

const CX = 110;
const CY = 110;
const R_OUTER = 105;
const R_SECTOR_INNER = 74;
const R_CENTER = 50;

function toRad(deg: number): number {
  return (deg * Math.PI) / 180;
}

/** Угол 0 = "12 часов" (верх), положительное направление — по часовой стрелке (совпадает с визуальным порядком секторов). */
function polar(r: number, deg: number): { x: number; y: number } {
  const rad = toRad(deg);
  return { x: CX + r * Math.sin(rad), y: CY - r * Math.cos(rad) };
}

/** Донат-сегмент (клин с "дыркой" в середине) между двумя углами — путь дуга/линия/дуга/линия, замкнутый. */
function sectorPath(startDeg: number, endDeg: number, rInner: number, rOuter: number): string {
  const p1 = polar(rOuter, startDeg);
  const p2 = polar(rOuter, endDeg);
  const p3 = polar(rInner, endDeg);
  const p4 = polar(rInner, startDeg);
  const large = endDeg - startDeg > 180 ? 1 : 0;
  return `M ${p1.x} ${p1.y} A ${rOuter} ${rOuter} 0 ${large} 1 ${p2.x} ${p2.y} L ${p3.x} ${p3.y} A ${rInner} ${rInner} 0 ${large} 0 ${p4.x} ${p4.y} Z`;
}

interface Sector {
  key: string;
  label: string;
  mid: number;
  onClick?: () => void;
}

export function RadialMenu() {
  const actions = hudActions.value;
  // "Дом" — активный сектор по умолчанию: это и есть главный экран
  // (владелец: "если это главный экран, в круговом меню нужно выделить
  // Домой" — на верхней навигации выделять нечего, ни один из 11
  // разделов не представляет сам главный экран).
  const [active, setActive] = useState<string | null>('home');
  const [pressed, setPressed] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  if (!actions) return null;

  const sectors: Sector[] = [
    { key: 'home', label: 'Дом', mid: 0, onClick: actions.navigateHome },
    { key: 'central', label: 'Центр', mid: 72, onClick: actions.navigateCentral },
    { key: 'complex', label: 'Комплекс', mid: 144, onClick: actions.openComplex },
    { key: 'neutral', label: 'Нейтрал', mid: 216, onClick: actions.navigateNeutral },
    { key: 'colonies', label: 'Колонии', mid: 288, onClick: actions.openColonies },
  ];
  const step = 360 / sectors.length;

  async function handleCenterClick() {
    setLoading(true);
    try {
      await actions!.refresh();
    } finally {
      setLoading(false);
    }
  }

  return (
    <div class="ds-hud-radial2">
      <svg width="220" height="220" viewBox="0 0 220 220">
        {/* Корпус-подложка — визуально соединяет меню с рамкой интерфейса (не "плавающий" кружок сам по себе). */}
        <rect x="0" y="0" width="220" height="220" rx="0" class="ds-hud-radial2__housing" />

        <defs>
          <radialGradient id="ds-radial-center-grad" cx="35%" cy="30%" r="75%">
            <stop offset="0%" stop-color="#7fd0ff" />
            <stop offset="55%" stop-color="#2a6fd6" />
            <stop offset="100%" stop-color="#0e2c5c" />
          </radialGradient>
          <linearGradient id="ds-radial-ring-grad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#8b96ab" />
            <stop offset="50%" stop-color="#3a4256" />
            <stop offset="100%" stop-color="#181d29" />
          </linearGradient>
          <filter id="ds-radial-glow" x="-60%" y="-60%" width="220%" height="220%">
            <feGaussianBlur stdDeviation="6" result="blur" />
            <feMerge>
              <feMergeNode in="blur" />
              <feMergeNode in="SourceGraphic" />
            </feMerge>
          </filter>
        </defs>

        {/* Металлическое кольцо вокруг центра. */}
        <circle
          cx={CX}
          cy={CY}
          r={(R_CENTER + R_SECTOR_INNER) / 2}
          fill="none"
          stroke="url(#ds-radial-ring-grad)"
          stroke-width={R_SECTOR_INNER - R_CENTER}
        />

        {/* Секторы. */}
        {sectors.map((s) => {
          const start = s.mid - step / 2;
          const end = s.mid + step / 2;
          const isActive = active === s.key;
          const isPressed = pressed === s.key;
          const isLocked = !s.onClick;
          const labelPos = polar((R_SECTOR_INNER + R_OUTER) / 2, s.mid);
          return (
            <g
              key={s.key}
              class={[
                'ds-hud-radial2__sector',
                isActive && 'ds-hud-radial2__sector--active',
                isPressed && 'ds-hud-radial2__sector--pressed',
                isLocked && 'ds-hud-radial2__sector--locked',
              ]
                .filter(Boolean)
                .join(' ')}
              onPointerDown={() => !isLocked && setPressed(s.key)}
              onPointerUp={() => setPressed(null)}
              onPointerLeave={() => setPressed(null)}
              onClick={() => {
                if (isLocked) return;
                setActive(s.key);
                s.onClick!();
              }}
            >
              <path d={sectorPath(start, end, R_SECTOR_INNER, R_OUTER)} />
              <text x={labelPos.x} y={labelPos.y} text-anchor="middle" dominant-baseline="middle">
                {s.label}
              </text>
              {isLocked && (
                <text x={labelPos.x} y={labelPos.y + 12} text-anchor="middle" class="ds-hud-radial2__lock">
                  🔒
                </text>
              )}
            </g>
          );
        })}

        {/* Центральная сфера — "Обновить" / индикатор загрузки. */}
        <g class="ds-hud-radial2__center" onClick={() => void handleCenterClick()}>
          <circle cx={CX} cy={CY} r={R_CENTER} fill="url(#ds-radial-center-grad)" filter="url(#ds-radial-glow)" />
          {loading ? (
            <circle
              cx={CX}
              cy={CY}
              r={R_CENTER - 14}
              fill="none"
              stroke="#eaf6ff"
              stroke-width="4"
              stroke-linecap="round"
              stroke-dasharray="60 200"
              class="ds-hud-radial2__spinner"
            />
          ) : (
            <text x={CX} y={CY} text-anchor="middle" dominant-baseline="middle" class="ds-hud-radial2__center-label">
              Обновить
            </text>
          )}
        </g>
      </svg>
    </div>
  );
}
