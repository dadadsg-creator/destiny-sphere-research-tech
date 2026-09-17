/**
 * Окно «Аукцион» — auctionmenuclass.as, см.
 * docs/client-economy-social-ui.md §1.2. Данные — auctionData.ts
 * (parseAuction, computeLotDisabled). Форма фильтров (дерево по
 * категориям) сознательно не портирована в этой версии — см. докстринг
 * auctionData.ts.
 *
 * **Исправление (2026-09-11)**: лот-ресурс (`idCatOn === '1'`) и лот-юнит
 * (`idCatOn === '2'`) показывали сырое машинное имя (`NANO_CRYSTAL`/`TANK`)
 * вместо названия — исправлено через `resourceDisplayName()`/
 * `unitDisplayName()` (живая локализация из `loaddata`, см. `resourceIcons.ts`),
 * не выдуманные строки.
 *
 * **Исправление (2026-09-18)**: артефакты (3) тоже теперь локализованы —
 * `artefactDisplayName()` (`resourceIcons.ts`, та же каталожная
 * локализация — `LocalData.artefacts`, раньше считалась отдельной
 * неподключённой таблицей). Сам оригинал (`xmlparsers.as: auction_menu_parser`)
 * ни разу не резолвит `.ln` для лотов ВООБЩЕ (ни для одной категории —
 * только сырой `libname` в протоколе аукциона) — уже существующее
 * улучшение для категорий 1/2 читаемости ради, категория 3 теперь
 * получает то же самое, тем же приёмом.
 */
import { Window } from '../ui/Window';
import type { TabDef } from '../ui/tabs';
import { resourceDisplayName, unitDisplayName, artefactDisplayName, unitIconUrl } from '../data/resourceIcons';
import { computeLotDisabled, type AuctionLot } from './auctionData';

function lotLabel(lot: AuctionLot): string {
  if (lot.idCatOn === '1' && lot.libname) return resourceDisplayName(lot.libname);
  if (lot.idCatOn === '2' && lot.libname) return unitDisplayName(lot.libname);
  if (lot.idCatOn === '3' && lot.libname) return artefactDisplayName(lot.libname);
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
