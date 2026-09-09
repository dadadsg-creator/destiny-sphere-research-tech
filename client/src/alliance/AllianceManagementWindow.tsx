/**
 * Окно «Альянс» — alliance_menu_class.as, см.
 * docs/client-economy-social-ui.md §2.1. Данные — allianceData.ts.
 * Две ветки экрана: своя карточка альянса (с кнопкой «Выйти») ИЛИ форма
 * создания/вступления — определяется по `data.inAlliance`, как и в
 * оригинале (`in_alliance_object.inal`).
 *
 * НЕ реализовано: диалог-предупреждение для новичков (`ALERT_TEXT1`,
 * "создание/вступление снимает щит новичка") — данные для него
 * (`game.getNewbee()`) не разбирались в этой версии.
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import {
  isValidAllianceName,
  isValidAlliancePassword,
  isValidAllianceTag,
  type AllianceManagementData,
  type CreateAllianceCommand,
  type JoinAllianceCommand,
} from './allianceData';

export interface AllianceManagementWindowProps {
  data: AllianceManagementData;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onExit: (idAlliance: string) => void;
  onCreate: (command: CreateAllianceCommand) => void;
  onJoin: (command: JoinAllianceCommand) => void;
}

export function AllianceManagementWindow({
  data,
  tabs,
  onTabClick,
  onClose,
  onExit,
  onCreate,
  onJoin,
}: AllianceManagementWindowProps) {
  if (data.inAlliance) {
    const { own } = data;
    return (
      <Window title="Альянс" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
        <p>
          {own.name} [{own.tag}]
        </p>
        <p class="ds-train-unit__idle">Статус: {own.status}</p>
        <button
          type="button"
          class="ds-popup__btn"
          disabled={own.leave !== '1'}
          onClick={() => onExit(own.id!)}
        >
          Выйти из альянса
        </button>
      </Window>
    );
  }

  return <AllianceJoinCreateForm data={data} tabs={tabs} onTabClick={onTabClick} onClose={onClose} onCreate={onCreate} onJoin={onJoin} />;
}

function AllianceJoinCreateForm({
  data,
  tabs,
  onTabClick,
  onClose,
  onCreate,
  onJoin,
}: {
  data: Extract<AllianceManagementData, { inAlliance: false }>;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onCreate: (command: CreateAllianceCommand) => void;
  onJoin: (command: JoinAllianceCommand) => void;
}) {
  const [name, setName] = useState('');
  const [tag, setTag] = useState('');
  const [pass, setPass] = useState('');

  const [joinTargetId, setJoinTargetId] = useState(data.alliancesToJoin[0]?.id ?? '');
  const [joinPass, setJoinPass] = useState('');

  const canCreate = isValidAllianceName(name) && isValidAllianceTag(tag) && isValidAlliancePassword(pass);
  const canJoin = !!joinTargetId && joinPass.length > 0;

  return (
    <Window title="Альянс" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <h4>Создать альянс</h4>
      <div class="ds-spying__row">
        <input type="text" class="ds-text-input" placeholder="Название (3-50)" value={name} onInput={(e) => setName((e.target as HTMLInputElement).value)} />
        <input type="text" class="ds-text-input" placeholder="Тег (1-8)" value={tag} onInput={(e) => setTag((e.target as HTMLInputElement).value)} />
        <input type="password" class="ds-text-input" placeholder="Пароль (3-30)" value={pass} onInput={(e) => setPass((e.target as HTMLInputElement).value)} />
        <button type="button" class="ds-popup__btn" disabled={!canCreate} onClick={() => onCreate({ name, tag, joinPassword: pass })}>
          Создать
        </button>
      </div>

      <h4>Вступить в альянс</h4>
      <div class="ds-spying__row">
        <select value={joinTargetId ?? ''} onChange={(e) => setJoinTargetId((e.target as HTMLSelectElement).value)}>
          {data.alliancesToJoin.map((a) => (
            <option key={a.id} value={a.id ?? ''}>
              {a.name} [{a.tag}]
            </option>
          ))}
        </select>
        <input
          type="password"
          class="ds-text-input"
          placeholder="Пароль вступления"
          value={joinPass}
          onInput={(e) => setJoinPass((e.target as HTMLInputElement).value)}
        />
        <button
          type="button"
          class="ds-popup__btn"
          disabled={!canJoin}
          onClick={() => onJoin({ idAlliance: joinTargetId, joinPassword: joinPass })}
        >
          Вступить
        </button>
      </div>
    </Window>
  );
}
