<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Расчет лучшей цены
 * (цена за единицу товара)
 *
 */
class GL_TradeCalcBestPrice
{
	static public function Calc(DE_TradeLot $tradeLot)
	{
		$price = 0;
		
		// для аукциона берем текущую цену
		if($tradeLot->getIsAuction())
		{
			$price = $tradeLot->getCurrentPrice();
		}
		
		// для лотов без торга (или аукциона без ставки) берем начальную цену
		if($price == 0)
		{
			$price = $tradeLot->getPrice();
		}
		
		// CategoryID = 3 - артефакты
		if($tradeLot->getTradeType()->getTradeFromCategoryID() != 3)
		{
			// в случае юнитов и русурсов все просто - берем их кол-во
			$count = $tradeLot->getAmount();
		} else {
			// в случае артефактов берем бонус
			$artAttributes = DS_Artefact::GetArtefactAtrributes($tradeLot->getArticleID());
			if(count($artAttributes) == 0)
			{
				// если нету бонуса, то берем кол-во артефактов
				$count = $tradeLot->getAmount();
			} else {
				$count = $artAttributes[0]['value'];
				if($artAttributes[0]['typename'] == 'BONUS_RELATIVE')
				{
					$count *= 100;
				}
				
				$count *= $tradeLot->getAmount();
				
				if($count == 0)
				{
					$count = $tradeLot->getAmount();
				}
			}
		}
		
		$bestPrice = $price / $count;
		
		$tradeLot->setBestPrice($bestPrice);
	}
}

?>