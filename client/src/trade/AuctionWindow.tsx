/**
 * Окно «Аукцион» — auctionmenuclass.as, см.
 * docs/client-economy-social-ui.md §1.2. Данные — auctionData.ts
 * (parseAuction, computeLotDisabled). Форма фильтров (дерево по
 * категориям) сознательно не портирована в этой версии — см. докстринг
 * auctionData.ts.
 */
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import { computeLotDisabled, type AuctionLot } from './auctionData';

export interface AuctionWindowProps {
  lots: AuctionLot[];
  /** Текущий запас игрока по ресурсу lot.tradeBy (для клиентской проверки, см. computeLotDisabled). */
  playerResourceByName: Map<string, number>;
  tabs?: TabDef[];
  onTabClick?: (tabId: string) => void;
  onClose: () => void;
  onBuy: (lotId: string, price: number) => void;
}

export function AuctionWindow({ lots, playerResourceByName, tabs, onTabClick, onClose, onBuy }: AuctionWindowProps) {
  return (
    <Window title="Аукцион" tabs={tabs} onTabClick={onTabClick} onClose={onClose}>
      <table>
        <thead>
          <tr>
            <th>Лот</th>
            <th>Кол-во</th>
            <th>Цена</th>
            <th>След. ставка</th>
            <th>Продавец</th>
            <th />
          </tr>
        </thead>
        <tbody>
          {lots.map((lot) => {
            const resourceCurrent = playerResourceByName.get(lot.tradeBy ?? '') ?? Number.MAX_SAFE_INTEGER;
            const disabled = computeLotDisabled(lot, resourceCurrent);
            const priceToBuy = lot.immediate === '1' ? lot.currentPrice : lot.nextPrice;
            return (
              <tr key={lot.id}>
                <td>{lot.libname}</td>
                <td>{lot.amount}</td>
                <td>{lot.currentPrice}</td>
                <td>{lot.nextPrice}</td>
                <td>{lot.seller}</td>
                <td>
                  <button
                    type="button"
                    class="ds-popup__btn"
                    disabled={disabled}
                    onClick={() => onBuy(lot.id!, priceToBuy)}
                  >
                    {lot.immediate === '1' ? 'Купить' : 'Сделать ставку'}
                  </button>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </Window>
  );
}
