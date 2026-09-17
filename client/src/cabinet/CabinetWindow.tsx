/**
 * Главный экран личного кабинета — cabinetmenuclass.as, см.
 * docs/client-economy-social-ui.md §5.3-5.4. Список услуг (HEAD/SPLITER/
 * SUB) рисуется как пришло с сервера — клиент НЕ фильтрует пункты по
 * IP/uid (это делает сервер, см. докстринг cabinetMainData.ts).
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
      <div class="ds-window__content" style={{ display: 'flex', gap: '16px' }}>
        <ul style={{ flex: 1 }}>
          {data.serviceList.map((item, i) => {
            if (item.type === 'HEAD') return <li key={i} style={{ fontWeight: 'bold', marginTop: '8px' }}>{item.name}</li>;
            if (item.type === 'SPLITER') return <li key={i}><hr /></li>;
            return (
              <li key={i} class="ds-army-row" onClick={() => onSelectService(item.id)}>
                {item.name}
              </li>
            );
          })}
        </ul>

        <div style={{ flex: 1 }}>
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
