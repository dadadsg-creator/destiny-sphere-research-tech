/**
 * Окно «Мир между альянсами» — peace_aliance.as, см.
 * docs/client-economy-social-ui.md §2.4. Данные — alliancePeaceData.ts
 * (parseAlliancePeaceList). Пять групп альянсов (allow/ready/offer/wait/
 * dismiss, см. докстринг alliancePeaceData.ts) — строки кликабельны,
 * только если сервер прислал `viewdata="1"` (единственное, что делает их
 * кликабельными в оригинале — не защита, просто UI-флаг).
 */
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import type { AlliancePeaceEntry, AlliancePeaceListData } from './alliancePeaceData';

export interface AlliancePeaceWindowProps {
  data: AlliancePeaceListData;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onOpenDetail: (allianceId: string) => void;
}

function Group({ title, entries, clickable, onOpen }: { title: string; entries: AlliancePeaceEntry[]; clickable: boolean; onOpen: (id: string) => void }) {
  if (entries.length === 0) return null;
  return (
    <>
      <h4>{title}</h4>
      <table>
        <thead>
          <tr>
            <th>Альянс</th>
            <th>Лидер</th>
            <th>Срок мира</th>
          </tr>
        </thead>
        <tbody>
          {entries.map((e) => (
            <tr
              key={e.id}
              class={clickable && e.id ? 'ds-army-row' : undefined}
              onClick={clickable && e.id ? () => onOpen(e.id!) : undefined}
            >
              <td>
                {e.name} [{e.short}]
              </td>
              <td>{e.leader}</td>
              <td>{e.peaceEndDate ?? e.peaceDuration ?? '—'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </>
  );
}

export function AlliancePeaceWindow({ data, tabs, onTabClick, onClose, onOpenDetail }: AlliancePeaceWindowProps) {
  const clickable = data.viewData === '1';

  return (
    <Window title={`Мир — ${data.alliName ?? ''}`} tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <Group title="Можно предложить мир" entries={data.allow} clickable={clickable} onOpen={onOpenDetail} />
      <Group title="Ждём подтверждения от нас" entries={data.wait} clickable={clickable} onOpen={onOpenDetail} />
      <Group title="Наши предложения (ждут ответа)" entries={data.offer} clickable={clickable} onOpen={onOpenDetail} />
      <Group
        title={data.blinkDismiss ? 'Заявки на расторжение (есть новые!)' : 'Заявки на расторжение'}
        entries={data.dismiss}
        clickable={clickable}
        onOpen={onOpenDetail}
      />
      <Group title="Действующий мир" entries={data.ready} clickable={clickable} onOpen={onOpenDetail} />
      {!data.allow.length && !data.wait.length && !data.offer.length && !data.dismiss.length && !data.ready.length && (
        <p class="ds-train-unit__idle">Нет альянсов, с которыми можно заключить мир.</p>
      )}
    </Window>
  );
}
