/**
 * Окно «Наглядное поле боя» — warplateclass.as, см.
 * docs/client-combat-ui.md §4. Оборачивает PixiJS-рендерер
 * `battleRenderer.ts` (уже существовал, но нигде не монтировался) в
 * обычное Preact-окно с навигацией по раундам — `<battlinfo>` присылает
 * ВСЕ раунды одним ответом (не по одному на запрос), поэтому листание
 * раундов здесь чисто клиентское, без новых запросов к серверу.
 */
import { useEffect, useRef, useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { BattleRenderer } from './battleRenderer';
import type { BattleInfo } from './battleData';

export interface BattleWindowProps {
  data: BattleInfo;
  onClose: () => void;
}

export function BattleWindow({ data, onClose }: BattleWindowProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  const rendererRef = useRef<BattleRenderer | null>(null);
  const [round, setRound] = useState(0);
  const [ready, setReady] = useState(false);

  const hasRounds = data.rounds.length > 0;

  useEffect(() => {
    if (!hasRounds) return undefined;
    let cancelled = false;
    void BattleRenderer.create(containerRef.current!).then((renderer) => {
      if (cancelled) {
        renderer.destroy();
        return;
      }
      rendererRef.current = renderer;
      setReady(true);
    });
    return () => {
      cancelled = true;
      rendererRef.current?.destroy();
      rendererRef.current = null;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [hasRounds]);

  useEffect(() => {
    if (ready && data.rounds[round]) void rendererRef.current?.renderRound(data.rounds[round]);
  }, [ready, round, data]);

  return (
    <Window title={`Ход боя${data.idBattle ? ` #${data.idBattle}` : ''}`} onClose={onClose}>
      <p class="ds-train-unit__idle">Участники: {data.nicks.join(', ') || '—'}</p>
      {hasRounds ? (
        <>
          <div class="ds-spying__row">
            <button type="button" class="ds-popup__btn" disabled={round <= 0} onClick={() => setRound((r) => r - 1)}>
              ← Раунд
            </button>
            <span>
              Раунд {round + 1} / {data.rounds.length}
            </span>
            <button
              type="button"
              class="ds-popup__btn"
              disabled={round >= data.rounds.length - 1}
              onClick={() => setRound((r) => r + 1)}
            >
              Раунд →
            </button>
          </div>
          <div ref={containerRef} class="ds-battle-window__canvas" />
        </>
      ) : (
        <p class="ds-train-unit__idle">Нет данных о раундах боя.</p>
      )}
    </Window>
  );
}
