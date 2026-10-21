/**
 * Круговое меню (docs/main_screen.md §11) — третий заход (2026-09-16):
 * владелец явно попросил остановиться на голой структуре, без
 * градиентов/металлических рамок/теней/свечения — "графика меню будет
 * сделана отдельно и подключена поверх готовой структуры" позже. Эта
 * версия — только геометрия (5 клиновидных SVG-секторов без промежутков
 * и наложений + отдельная круглая кнопка "Обновить" в центре) и
 * состояния (обычное/наведение/нажатие/активное/заблокированное) на
 * плоских однотонных заливках из CSS-переменных (см. hud.css).
 *
 * Порядок разделов — по ТЗ: Дом сверху, дальше по часовой стрелке
 * Центр (справа), Комплекс (справа снизу), Нейтрал (слева снизу),
 * Колонии (слева). Команды подключены по-настоящему — `<navigate
 * cell="1" dest="HOME|CENTRAL|NEUTRAL"/>` (`world/colonyNavigateData.ts`)
 * плюс уже существовавшие Комплекс/Колонии.
 *
 * Геометрия (центр 105,105 в 210×210 viewBox — ровно диаметр 210px из
 * ТЗ, без служебных полей): секторы — донат-сегменты от R_CENTER до
 * R_OUTER, единое кольцо без зазора между кольцом и центральной кнопкой
 * (R_SECTOR_INNER === R_CENTER). Центральная кнопка r=47.5 (диаметр
 * ~95px, по ТЗ).
 */
import { useState } from 'preact/hooks';
import { hudActions } from './hudActionsStore';

const CX = 105;
const CY = 105;
const R_OUTER = 105;
const R_CENTER = 47.5;

function toRad(deg: number): number {
  return (deg * Math.PI) / 180;
}

/** Угол 0 = "12 часов" (верх), положительное направление — по часовой стрелке (совпадает с визуальным порядком секторов). */
function polar(r: number, deg: number): { x: number; y: number } {
  const rad = toRad(deg);
  return { x: CX + r * Math.sin(rad), y: CY - r * Math.cos(rad) };
}

/** Клиновидный (донат-)сегмент между двумя углами — путь дуга/линия/дуга/линия, замкнутый. Общая граница соседних секторов совпадает, зазоров и наложений нет. */
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
  const [open, setOpen] = useState(false);
  // "Дом" — активный сектор по умолчанию: это и есть главный экран.
  const [active, setActive] = useState<string | null>('home');
  const [pressed, setPressed] = useState<string | null>(null);
  const [centerPressed, setCenterPressed] = useState(false);
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
    <div class={`ds-hud-radial-nav${open ? ' is-open' : ''}`}>
      <div class="ds-hud-radial2" aria-hidden={!open}>
        <svg width="210" height="210" viewBox="0 0 210 210">
        {sectors.map((s) => {
          const start = s.mid - step / 2;
          const end = s.mid + step / 2;
          const isActive = active === s.key;
          const isPressed = pressed === s.key;
          const isLocked = !s.onClick;
          const labelPos = polar((R_CENTER + R_OUTER) / 2, s.mid);
          return (
            <g
              key={s.key}
              role="button"
              tabIndex={isLocked ? -1 : 0}
              aria-label={s.label}
              aria-disabled={isLocked}
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
              <path d={sectorPath(start, end, R_CENTER, R_OUTER)} />
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

        {/* Центральная кнопка "Обновить" — отдельная от секторов, круглая. */}
        <g
          role="button"
          tabIndex={0}
          aria-label="Обновить"
          class={['ds-hud-radial2__center', centerPressed && 'ds-hud-radial2__center--pressed', loading && 'ds-hud-radial2__center--loading']
            .filter(Boolean)
            .join(' ')}
          onPointerDown={() => setCenterPressed(true)}
          onPointerUp={() => setCenterPressed(false)}
          onPointerLeave={() => setCenterPressed(false)}
          onClick={() => void handleCenterClick()}
        >
          <circle cx={CX} cy={CY} r={R_CENTER} />
          <text x={CX} y={CY} text-anchor="middle" dominant-baseline="middle" class="ds-hud-radial2__center-label">
            {loading ? '...' : 'Обновить'}
          </text>
        </g>
        </svg>
      </div>

      <button
        type="button"
        class="ds-hud-radial-nav__toggle"
        aria-expanded={open}
        aria-label={open ? 'Скрыть переходы' : 'Открыть переходы'}
        title={open ? 'Скрыть переходы' : 'Переходы между сотами'}
        onClick={() => setOpen((value) => !value)}
      >
        <svg viewBox="0 0 48 48" aria-hidden="true">
          <circle cx="24" cy="24" r="14.5" />
          <path d="M24 5.5v8M24 34.5v8M5.5 24h8M34.5 24h8" />
          <path class="ds-hud-radial-nav__needle" d="M29.5 18.5 26 26l-7.5 3.5L22 22z" />
        </svg>
      </button>
    </div>
  );
}
