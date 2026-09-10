/**
 * Окно почты — mailmenu.as, см. docs/client-economy-social-ui.md §4.5.
 * Список + фильтр по типу скоупа + панель чтения. Выбор писем (чекбоксы)
 * используется и для «Удалить», и для «Прочитать выбранные» — как в
 * оригинале (`del_flag` — общее свойство выборки, не только для удаления).
 */
import { useState } from 'preact/hooks';
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import { computeMailTypeFilters, selectActiveMailIndices, type MailListData } from './mailData';

export interface MailWindowProps {
  data: MailListData;
  /** Тело текущего открытого письма, если уже запрошено с сервера. */
  openBody: string | null;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onSwitchType: () => void;
  onOpenMail: (id: string) => void;
  onDeleteSelected: (ids: string[]) => void;
  onReadSelected: (ids: string[]) => void;
  onNewMail: () => void;
  onReply: (id: string) => void;
  onReplyQuote: (id: string) => void;
}

export function MailWindow({
  data,
  openBody,
  tabs,
  onTabClick,
  onClose,
  onSwitchType,
  onOpenMail,
  onDeleteSelected,
  onReadSelected,
  onNewMail,
  onReply,
  onReplyQuote,
}: MailWindowProps) {
  const [typeFilters, setTypeFilters] = useState(() => computeMailTypeFilters(data.mails));
  const [selected, setSelected] = useState<Set<string>>(new Set());
  const [currentId, setCurrentId] = useState<string | null>(null);

  const activeIndices = selectActiveMailIndices(data.mails, typeFilters);
  const activeMails = activeIndices.map((i) => data.mails[i]);

  const toggleType = (name: string) => {
    const next = new Map(typeFilters);
    next.set(name, !next.get(name));
    setTypeFilters(next);
  };

  const toggleSelected = (id: string) => {
    const next = new Set(selected);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    setSelected(next);
  };

  return (
    <Window title={data.type === 'in' ? 'Входящие' : 'Исходящие'} tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <div class="ds-popup__buttons ds-mail-toolbar">
        <button type="button" class="ds-popup__btn" onClick={onSwitchType}>
          {data.type === 'in' ? 'Показать исходящие' : 'Показать входящие'}
        </button>
        <button type="button" class="ds-popup__btn" onClick={onNewMail}>
          Новое письмо
        </button>
        <button type="button" class="ds-popup__btn" disabled={!currentId} onClick={() => currentId && onReply(currentId)}>
          Ответить
        </button>
        <button type="button" class="ds-popup__btn" disabled={!currentId} onClick={() => currentId && onReplyQuote(currentId)}>
          Ответить с цитатой
        </button>
        <button type="button" class="ds-popup__btn" disabled={selected.size === 0} onClick={() => onDeleteSelected([...selected])}>
          Удалить выбранные
        </button>
        <button type="button" class="ds-popup__btn" disabled={selected.size === 0} onClick={() => onReadSelected([...selected])}>
          Прочитать выбранные
        </button>
      </div>

      <div class="ds-mail-filters">
        {[...typeFilters.entries()].map(([name, sel]) => (
          <label key={name}>
            <input type="checkbox" checked={sel} onChange={() => toggleType(name)} /> {name}
          </label>
        ))}
      </div>

      <table>
        <thead>
          <tr>
            <th />
            <th>{data.type === 'in' ? 'От' : 'Кому'}</th>
            <th>Тема</th>
            <th>Тип</th>
            <th>Отправлено</th>
            <th>Прочитано</th>
          </tr>
        </thead>
        <tbody>
          {activeMails.map((mail) => (
            <tr
              key={mail.id}
              class="ds-army-row"
              style={{ fontWeight: mail.id === currentId ? 'bold' : undefined }}
              onClick={() => {
                setCurrentId(mail.id);
                onOpenMail(mail.id);
              }}
            >
              <td onClick={(e) => e.stopPropagation()}>
                <input type="checkbox" checked={selected.has(mail.id)} onChange={() => toggleSelected(mail.id)} />
              </td>
              <td>{mail.name}</td>
              <td dangerouslySetInnerHTML={{ __html: mail.subj }} />
              <td>{mail.lscopename}</td>
              <td>{mail.datecreate}</td>
              <td>{mail.dateread}</td>
            </tr>
          ))}
        </tbody>
      </table>

      {currentId && (
        <div class="ds-window__content" dangerouslySetInnerHTML={{ __html: openBody ?? 'Загрузка…' }} />
      )}
    </Window>
  );
}
