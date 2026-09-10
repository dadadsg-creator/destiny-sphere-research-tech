/**
 * Окно журнала заданий — quest_menu_class.as, см.
 * docs/client-economy-social-ui.md §4.3. Подписи вкладок намеренно НЕ
 * совпадают с реально показываемыми данными (см. questData.ts) —
 * воспроизведено как в оригинале. Детальный попап задания намеренно не
 * показывает описание — тоже подтверждённое поведение оригинала.
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import { QUEST_TAB_LABELS, getDefaultQuestTab, getQuestListForTab, type QuestEntry, type QuestMenuData } from './questData';

export interface QuestWindowProps {
  data: QuestMenuData;
  onClose: () => void;
}

function QuestDetailPopup({ quest, onClose }: { quest: QuestEntry; onClose: () => void }) {
  return (
    <div class="ds-window" style={{ position: 'absolute', top: '40px', left: '40px' }}>
      <div class="ds-window__titlebar">
        <span class="ds-window__title">{quest.name}</span>
        <button type="button" class="ds-window__close" onClick={onClose} aria-label="Закрыть">
          ×
        </button>
      </div>
      <div class="ds-window__content" />
    </div>
  );
}

export function QuestWindow({ data, onClose }: QuestWindowProps) {
  const [tabId, setTabId] = useState(() => getDefaultQuestTab(data));
  const [detailQuest, setDetailQuest] = useState<QuestEntry | null>(null);

  const quests = getQuestListForTab(data, tabId);

  return (
    <Window title="Задания" onClose={onClose}>
      <div class="ds-popup__buttons ds-btn-row">
        {QUEST_TAB_LABELS.map((tab) => (
          <button type="button" class="ds-popup__btn" key={tab.id} disabled={tab.id === tabId} onClick={() => setTabId(tab.id)}>
            {tab.ln}
          </button>
        ))}
      </div>

      <table>
        <thead>
          <tr>
            <th>Название</th>
            <th>Награда</th>
          </tr>
        </thead>
        <tbody>
          {quests.map((quest, i) => (
            <tr key={i} class="ds-army-row" onClick={() => setDetailQuest(quest)}>
              <td>{quest.name}</td>
              <td>{quest.bonus.map((b) => `${b.name} ${b.value}`).join(', ')}</td>
            </tr>
          ))}
        </tbody>
      </table>

      {detailQuest && <QuestDetailPopup quest={detailQuest} onClose={() => setDetailQuest(null)} />}
    </Window>
  );
}
