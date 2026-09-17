/**
 * Главный экран личного кабинета — cabinetmenuclass.as, см.
 * docs/client-economy-social-ui.md §5.3-5.4. Список услуг (HEAD/SPLITER/
 * SUB) рисуется как пришло с сервера — клиент НЕ фильтрует пункты по
 * IP/uid (это делает сервер, см. докстринг cabinetMainData.ts).
 *
 * Вёрстка (2026-09-18) — первая версия была совсем не стилизована (голый
 * `<ul>` без класса, кнопки без единого `class`) — владелец: "что за
 * уродский список? что за налипшие друг на друга кнопки без стиля?".
 * Список услуг — свои классы `.ds-cabinet-services*` (не переиспользован
 * `.ds-army-row` — та таблично-строчная подсветка целится в `tbody tr`,
 * на `<li>` не срабатывала вовсе, была мёртвым классом здесь).
 */
import { InfoPopup } from '../ui/Popup';
import type { CabinetMainData, OperationStatisticData } from './cabinetMainData';
import type { SubmenuFormData } from './submenuData';
import { SubmenuForm } from './SubmenuForm';
import { RightBlock, type RightBlockProps } from './RightBlock';
import { CabinetStatisticPanel } from './CabinetStatisticPanel';

export interface CabinetWindowProps {
  data: CabinetMainData;
  /** Открытая подформа (после клика по пункту услуги), если уже запрошена с сервера. */
  openSubmenu: SubmenuFormData | null;
  /** "Вся статистика" (см. докстринг CabinetStatisticPanel.tsx), если уже запрошена с сервера. */
  statistic: OperationStatisticData | null;
  onClose: () => void;
  onSelectService: (id: string) => void;
  onCloseSubmenu: () => void;
  onSubmitSubmenu: (buttonId: string, values: Record<string, string>) => void;
  onDismissPopup: () => void;
  onFilterStatistic: (typeId: string | undefined) => void;
  onCloseStatistic: () => void;
  rightBlock: Omit<RightBlockProps, 'user' | 'currentStatus' | 'lastOperation' | 'billingUrl'>;
}

export function CabinetWindow({
  data,
  openSubmenu,
  statistic,
  onClose,
  onSelectService,
  onCloseSubmenu,
  onSubmitSubmenu,
  onDismissPopup,
  onFilterStatistic,
  onCloseStatistic,
  rightBlock,
}: CabinetWindowProps) {
  return (
    <div class="ds-window">
      <div class="ds-window__titlebar">
        <span class="ds-window__title">Личный кабинет</span>
        <button type="button" class="ds-window__close" onClick={onClose} aria-label="Закрыть">
          ×
        </button>
      </div>
      <div class="ds-window__content ds-cabinet">
        <ul class="ds-cabinet-services">
          {data.serviceList.map((item, i) => {
            if (item.type === 'HEAD') return <li key={i} class="ds-cabinet-services__head">{item.name}</li>;
            if (item.type === 'SPLITER') return <li key={i}><hr class="ds-cabinet-services__spliter" /></li>;
            return (
              <li key={i} class="ds-cabinet-services__item" onClick={() => onSelectService(item.id)}>
                {item.name}
              </li>
            );
          })}
        </ul>

        <div class="ds-cabinet-right">
          <RightBlock
            user={data.user}
            currentStatus={data.currentStatus}
            lastOperation={data.lastOperation}
            billingUrl={data.billingUrl}
            {...rightBlock}
          />
        </div>
      </div>

      {openSubmenu && <SubmenuForm form={openSubmenu} onClose={onCloseSubmenu} onSubmit={onSubmitSubmenu} />}
      {statistic && <CabinetStatisticPanel data={statistic} onFilterSelect={onFilterStatistic} onClose={onCloseStatistic} />}
      {data.popupMessage && <InfoPopup message={data.popupMessage} onClose={onDismissPopup} />}
    </div>
  );
}
