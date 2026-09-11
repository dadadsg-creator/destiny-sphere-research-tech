/**
 * Окно «Аукцион» — auctionmenuclass.as, см.
 * docs/client-economy-social-ui.md §1.2. Данные — auctionData.ts
 * (parseAuction, computeLotDisabled). Форма фильтров (дерево по
 * категориям) сознательно не портирована в этой версии — см. докстринг
 * auctionData.ts.
 *
 * **Исправление (2026-09-11)**: лот-ресурс (`idCatOn === '1'`) показывал
 * сырое машинное имя (`NANO_CRYSTAL`) вместо русского названия — тот же
 * баг и то же исправление, что в `SellWindow.tsx`
 * (`RESOURCE_NAME_RU[libname]`); для юнитов/артефактов `libname` остаётся
 * как есть.
 */
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import { RESOURCE_NAME_RU, unitIconUrl } from '../data/resourceIcons';
import { computeLotDisabled, type AuctionLot } from './auctionData';

function lotLabel(lot: AuctionLot): string {
  if (lot.idCatOn === '1' && lot.libname) return RESOURCE_NAME_RU[lot.libname] ?? lot.libname;
  return lot.libname ?? '';
}

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
                <td class="ds-artefact-row">
                  {lot.idCatOn === '2' && lot.libname && <img class="ds-unit-icon" src={unitIconUrl(lot.libname)} alt="" />}
                  <span>{lotLabel(lot)}</span>
                </td>
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
