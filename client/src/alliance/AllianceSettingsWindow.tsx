/**
 * Окно «Настройки альянса» — alliance_setting_class.as, см.
 * docs/client-economy-social-ui.md §2.3. Данные — allianceSettingData.ts
 * (parseAllianceSettings). Смена пароля/лидера доступна только лидеру
 * (`data.leader === '1'`) — не участникам.
 *
 * Поле пароля НЕ предзаполняется плейсхолдером `"********"`, в отличие
 * от оригинала (см. докстринг allianceSettingData.ts — подтверждённый
 * баг UI-формы оригинала, сознательно не воспроизведён).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import type { AllianceSettingData } from './allianceSettingData';

export interface AllianceSettingsWindowProps {
  data: AllianceSettingData;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onChangePassword: (newPassword: string) => void;
  onChangeLeader: (newLeaderId: string) => void;
}

export function AllianceSettingsWindow({ data, tabs, onTabClick, onClose, onChangePassword, onChangeLeader }: AllianceSettingsWindowProps) {
  const [newPassword, setNewPassword] = useState('');
  const [newLeaderId, setNewLeaderId] = useState(data.members[0]?.id ?? '');

  if (data.leader !== '1') {
    return (
      <Window title="Настройки альянса" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
        <p class="ds-train-unit__idle">Эти настройки доступны только лидеру альянса.</p>
      </Window>
    );
  }

  return (
    <Window title="Настройки альянса" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <div class="ds-spying__row">
        <span class="ds-spying__label">Новый пароль:</span>
        <input
          type="password"
          class="ds-text-input"
          value={newPassword}
          onInput={(e) => setNewPassword((e.target as HTMLInputElement).value)}
        />
        <button
          type="button"
          class="ds-popup__btn"
          disabled={newPassword.length === 0}
          onClick={() => {
            onChangePassword(newPassword);
            setNewPassword('');
          }}
        >
          Сменить пароль
        </button>
      </div>

      <div class="ds-spying__row">
        <span class="ds-spying__label">Передать лидерство:</span>
        <select value={newLeaderId ?? ''} onChange={(e) => setNewLeaderId((e.target as HTMLSelectElement).value)}>
          {data.members.map((m) => (
            <option key={m.id} value={m.id ?? ''}>
              {m.ln}
            </option>
          ))}
        </select>
        <button type="button" class="ds-popup__btn" disabled={!newLeaderId} onClick={() => onChangeLeader(newLeaderId)}>
          Передать
        </button>
      </div>
    </Window>
  );
}
