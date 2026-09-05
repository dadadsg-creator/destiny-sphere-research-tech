/**
 * Окно написания письма — create_new_mail.as, см.
 * docs/client-economy-social-ui.md §4.5. Rich-text-кнопки упрощены до
 * вставки HTML-тегов вокруг всего текста (оригинал вставлял по индексам
 * выделения через Selection.getBeginIndex/getEndIndex — в браузере для
 * простого <textarea> это не так прямолинейно; полноценный rich-text
 * редактор сознательно не делается, это визуальная полировка вне рамок
 * протокол-ориентированного переноса).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import { validateNewMail, type CreateNewMailData } from './createNewMailData';

export interface CreateNewMailWindowProps {
  data: CreateNewMailData;
  /** true — сборка ВКонтакте: поле «Кому» ограничено цифрами (VK ID вместо ника). */
  isVk: boolean;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onSend: (to: string, subject: string, body: string, typeScope: number) => void;
}

export function CreateNewMailWindow({ data, isVk, tabs, onTabClick, onClose, onSend }: CreateNewMailWindowProps) {
  const [to, setTo] = useState(data.recipientName);
  const [subject, setSubject] = useState(data.quoteSubject);
  const [body, setBody] = useState(
    data.quoteBody.length > 0 ? `<font color="#992222"><b>${data.quoteBody}</b></font>` : '',
  );
  const [typeScope, setTypeScope] = useState(data.scopes[0] ? Number(data.scopes[0].id) : 1);
  const [invalid, setInvalid] = useState<{ to: boolean; subject: boolean; body: boolean }>({
    to: false,
    subject: false,
    body: false,
  });

  const toDisabled = typeScope !== 1;

  const handleSend = () => {
    const v = validateNewMail(to, subject, body, typeScope);
    setInvalid({ to: !v.toValid, subject: !v.subjectValid, body: !v.bodyValid });
    if (v.allValid) onSend(to, subject, body, typeScope);
  };

  return (
    <Window title="Новое письмо" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <div>
        Кому:{' '}
        <select value={String(typeScope)} onChange={(e) => setTypeScope(Number((e.target as HTMLSelectElement).value))}>
          {data.scopes.map((s) => (
            <option key={s.id} value={s.id}>
              {s.ln}
            </option>
          ))}
        </select>{' '}
        <input
          type="text"
          value={to}
          disabled={toDisabled}
          pattern={isVk ? '[0-9.]*' : undefined}
          style={{ borderColor: invalid.to ? 'red' : undefined }}
          onInput={(e) => setTo((e.target as HTMLInputElement).value)}
        />
      </div>
      <div>
        Тема:{' '}
        <input
          type="text"
          value={subject}
          style={{ borderColor: invalid.subject ? 'red' : undefined }}
          onInput={(e) => setSubject((e.target as HTMLInputElement).value)}
        />
      </div>
      <div>
        <textarea
          rows={8}
          cols={60}
          value={body}
          style={{ borderColor: invalid.body ? 'red' : undefined }}
          onInput={(e) => setBody((e.target as HTMLTextAreaElement).value)}
        />
      </div>
      <button onClick={() => setBody((b) => `<b>${b}</b>`)}>Ж</button>
      <button onClick={() => setBody((b) => `<i>${b}</i>`)}>К</button>
      <button onClick={() => setBody((b) => `<a href="" target="blank">${b}</a>`)}>Ссылка</button>
      <button onClick={handleSend}>Отправить</button>
    </Window>
  );
}
