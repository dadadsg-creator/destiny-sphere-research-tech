/**
 * Разбор марки верхнего уровня `outside` — серверные алерт-попапы,
 * `parser.as:248-321` (`outside_action`). В оригинале это загрузка
 * ВНЕШНЕГО SWF (`flash/alert.swf`/`flash/dialog_alert.swf`) — не
 * портируется буквально (нет смысла грузить Flash внутри HTML5-порта),
 * вместо этого — обычное информационное окно с тем же текстом, чтобы
 * сообщение не терялось молча (реальная находка аудита панелей
 * 2026-09-16, CLIENT_PORTING_PLAN.md: марка не обрабатывалась вообще).
 *
 * <swfloader url="" override="0|1" title="" action_id=""?>
 *   <txt><![CDATA[...]]></txt>
 *   <answer><![CDATA[...]]></answer>? (нестандартный текст кнопки "Да")
 * </swfloader>
 *
 * `action_id` присутствует у "actionable"-алертов (`parser.as:254-279`,
 * `game.create_confirm_window`) — сервер ожидает, что при согласии
 * клиент повторно отправит ИСХОДНУЮ команду с доп. параметром,
 * специфичным для конкретного домена (пример уже реализован вручную —
 * `attack`→`attack_confirm`/`UA_ComfirmAttack`, см. CLIENT_PORTING_PLAN.
 * md). Универсального повторного действия по одному `action_id` здесь
 * НЕТ (у каждого домена свой набор полей для повтора, как у attack) —
 * `actionId` сохранён в разборе на будущее, но окно показывается как
 * простое информационное сообщение, а не интерактивный confirm/resend.
 */
import { parseXmlFragment } from '../protocol/response';

export interface OutsideAlert {
  title: string;
  text: string;
  actionId: string | null;
}

export function parseOutsideAlert(xml: string): OutsideAlert {
  const doc = parseXmlFragment(xml);
  const root = doc.documentElement;
  if (!root) {
    throw new Error('Пустой ответ в марке outside');
  }

  const txtEl = Array.from(root.children).find((el) => el.nodeName === 'txt');

  return {
    title: root.getAttribute('title') ?? '',
    text: txtEl?.textContent?.trim() ?? '',
    actionId: root.getAttribute('action_id'),
  };
}
