/**
 * Панель артефактной статистики соты (`artefactstatclass.as`), см.
 * docs/client-economy-social-ui.md §1.4. НЕ модальное окно со списком —
 * владелец (2026-09-18), увидев предыдущую версию
 * (`ArtefactStatWindow.tsx`, таблица "Ячейка/Тип/Слоты", удалена):
 * "Как из этого всплывающего окошка понять, какие здания заправлены
 * артефактами, а какие нет? Никак! Делай нормальный функционал
 * подсветки." Тот же принцип, что и у `WarFactoryPanel.tsx` (см. его
 * докстринг): докнутая сверху полоса-легенда (`ui/windowStore.ts:
 * topPanel`) + подсветка прямо на живой карте
 * (`MapRenderer.setArtefactHighlights()`), не отдельная схематичная
 * гекс-сетка оригинала.
 *
 * Цвет — по типу здания (`getArtCellColor`), бейдж — "занято/всего",
 * толстая белая рамка — у "самого слабого" здания каждого типа
 * (`findWeakestCellsPerType`, `glowMinArt` оригинала), цветной кружок —
 * скоро истекающий артефакт, только для реальных премиум-аккаунтов
 * (`findMinRemainArt`/`getExpiryColor`). Клик по зданию — уже
 * подключённое окно attach/detach (`getbuildmenu tab=BuildingArtefact`,
 * `renderBuildArtefact` в dispatch.tsx).
 */
import { useEffect } from 'preact/hooks';
import type { ArtefactStatData } from './artefactStatData';
import { getArtCellColor, findMinRemainArt, getExpiryColor, findWeakestCellsPerType } from './artefactStatData';
import type { MapRenderer } from '../map/renderer';

export interface ArtefactStatPanelProps {
  data: ArtefactStatData;
  isPremium: boolean;
  renderer: MapRenderer;
  onClose: () => void;
  onCellClick: (cellNum: string) => void;
}

const LEGEND: Array<{ type: string; ln: string }> = [
  { type: 'STASIS_ENERGY', ln: 'Энергостанция' },
  { type: 'KADERIUM', ln: 'Кадериум' },
  { type: 'NANO_CRYSTAL', ln: 'Нано-кристаллы' },
  { type: 'FOOD', ln: 'Еда' },
  { type: 'PRODIUM', ln: 'Продиум' },
  { type: 'WAR', ln: 'Военное' },
];

export function ArtefactStatPanel({ data, isPremium, renderer, onClose, onCellClick }: ArtefactStatPanelProps) {
  useEffect(() => {
    const weakest = findWeakestCellsPerType(data);
    const entries = [...data.entries()].map(([id, cell]) => {
      const minArt = isPremium ? findMinRemainArt(cell) : null;
      const expiryColor = minArt ? getExpiryColor(Number(minArt.remain)) : null;
      return {
        num: Number(id),
        color: getArtCellColor(cell.type),
        label: `${cell.fullSlots}/${cell.maxSlots}`,
        weakest: weakest.has(id),
        expiryColor,
        onClick: () => onCellClick(id),
      };
    });
    renderer.setArtefactHighlights(entries.length > 0 ? entries : null);
    return () => renderer.setArtefactHighlights(null);
  }, [data, isPremium, renderer, onCellClick]);

  return (
    <div class="ds-art-panel">
      <div class="ds-art-panel__legend">
        {LEGEND.map((l) => (
          <span key={l.type} class="ds-art-panel__legend-item">
            <span class="ds-art-panel__swatch" style={{ background: `#${getArtCellColor(l.type).toString(16).padStart(6, '0')}` }} />
            {l.ln}
          </span>
        ))}
        <span class="ds-art-panel__hint">
          Белая рамка — самое слабое здание типа{isPremium ? '; цветной кружок — скоро истекает артефакт' : ''}. Клик по зданию — управление артефактами.
        </span>
      </div>
      <button type="button" class="ds-popup__btn" onClick={onClose}>
        Закрыть
      </button>
    </div>
  );
}
