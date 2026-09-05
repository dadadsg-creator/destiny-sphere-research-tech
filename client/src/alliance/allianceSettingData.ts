/**
 * Разбор mark `alliancesettings` — настройки альянса (Фаза 3.6,
 * `alliance_setting_class.as`, источник: docs/client-economy-social-ui.md
 * §2.3), портировано 1:1 из `xmlparsers.as:590-605`
 * (`alliancesetting_menu_parser`).
 *
 * <alliancesettings leader="0|1">
 *   <user id="" name_=""/>   (список участников для передачи лидерства)
 *   ...
 * </alliancesettings>
 *
 * ПОДТВЕРЖДЁННЫЙ БАГ ОРИГИНАЛА (не наш): поле пароля в форме
 * предзаполняется буквальным плейсхолдером `"********"`, и если лидер
 * нажмёт «Сменить пароль», не тронув поле, отправится именно эта строка
 * как новый пароль. Здесь это НЕ воспроизведено — поле пароля в
 * AllianceSettingWindow начинается пустым, отправка возможна только при
 * непустом вводе (сознательное исправление, а не 1:1 перенос бага —
 * задокументировано явно, как того требует правило проекта: копировать
 * баги протокола/сервера, но не обязательно баги конкретно UI-формы,
 * которые тривиально не приводят к иным числам на сервере).
 */
import { parseXmlFragment } from '../protocol/response';

export interface AllianceSettingMember {
  id: string | null;
  ln: string | null;
}

export interface AllianceSettingData {
  /** "1" — текущий игрок лидер (кнопки смены пароля/лидера доступны только лидеру). */
  leader: string | null;
  members: AllianceSettingMember[];
}

export function parseAllianceSettings(xml: string): AllianceSettingData {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root || root.nodeName !== 'alliancesettings') {
    throw new Error(`Ожидался корневой тег <alliancesettings>, получено: ${root?.nodeName}`);
  }

  const members: AllianceSettingMember[] = [];
  for (const el of Array.from(root.children)) {
    if (el.nodeName !== 'user') continue;
    members.push({ id: el.getAttribute('id'), ln: el.getAttribute('name_') });
  }

  return { leader: root.getAttribute('leader'), members };
}

/** reqvestdata.as:592-603 — <changepassword>текст</changepassword>. */
export function buildChangePasswordCommand(newPassword: string): { changePassword: string } {
  return { changePassword: newPassword };
}

/** reqvestdata.as:592-603 — <changeleader newleader=""/>. */
export function buildChangeLeaderCommand(newLeaderUserId: string): { newLeader: string } {
  return { newLeader: newLeaderUserId };
}
