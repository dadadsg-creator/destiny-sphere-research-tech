/**
 * Окно настроек — settingmenu.as, см. docs/client-economy-social-ui.md
 * §0.3. В отличие от прочих экранов этой сессии — реально функционален
 * (не демо-заглушка): чтение/запись через настоящий localStorage
 * (setting.ts), т.к. это чисто клиентская, не протокольная фича.
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import {
  DEFAULT_WINDOW_COLOR,
  QUALITY_IDS,
  QUALITY_LABELS,
  SETTINGS_CHECKBOXES,
  loadCheckbox,
  loadQuality,
  saveCheckbox,
  saveQuality,
  saveWindowColor,
  type QualityId,
} from './settingsMenuData';

export interface SettingsWindowProps {
  onClose: () => void;
}

export function SettingsWindow({ onClose }: SettingsWindowProps) {
  const [checks, setChecks] = useState<Record<string, boolean>>(() =>
    Object.fromEntries(SETTINGS_CHECKBOXES.map((c) => [c.key, loadCheckbox(c.key)])),
  );
  const [quality, setQuality] = useState<QualityId>(() => loadQuality());

  const toggleCheck = (key: string) => {
    const next = !checks[key];
    saveCheckbox(key, next);
    setChecks({ ...checks, [key]: next });
  };

  const changeQuality = (id: QualityId) => {
    saveQuality(id);
    setQuality(id);
  };

  const resetColor = () => {
    saveWindowColor(DEFAULT_WINDOW_COLOR.red, DEFAULT_WINDOW_COLOR.green, DEFAULT_WINDOW_COLOR.blue);
  };

  return (
    <Window title="Настройки" onClose={onClose}>
      {SETTINGS_CHECKBOXES.map((c) => (
        <div key={c.key}>
          <label>
            <input type="checkbox" checked={checks[c.key]} onChange={() => toggleCheck(c.key)} /> {c.label}
          </label>
        </div>
      ))}

      <div>
        Качество:{' '}
        {QUALITY_IDS.map((id) => (
          <label key={id} style={{ marginRight: '8px' }}>
            <input type="radio" name="quality" checked={quality === id} onChange={() => changeQuality(id)} /> {QUALITY_LABELS[id]}
          </label>
        ))}
      </div>

      <button type="button" class="ds-popup__btn" onClick={resetColor}>
        Восстановить тон окон по умолчанию
      </button>
    </Window>
  );
}
