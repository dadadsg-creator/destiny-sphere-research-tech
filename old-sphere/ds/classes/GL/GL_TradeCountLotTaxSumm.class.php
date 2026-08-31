<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Определяем размер налога в абсолютных единицах
 *
 */
class GL_TradeCountLotTaxSumm
{	
	static public function Count(DE_TradeLot $tradeLot)
	{		
		assert($tradeLot->haveCurrentCustomer()); //Это значит что лот готов к покупке
		
		if(DE_WorldSettings::CreateForCurentWorld()->get('TRADE_USE_TAX') == 0)
		{
			return (float)0;
		}
		
		$taxBase = $tradeLot->getTradeType()->getTax() / 100;
		$premiumTaxDelta = DE_WorldSettings::CreateForCurentWorld()->get('TRADE_PREMIUM_TAX_DELTA') / 100; 
		
		if($tradeLot->getSeller()->getUser()->isProfy())
		{
			$tax = $taxBase + $premiumTaxDelta;
		}else{
			$tax = $taxBase;
		}
		
		if($tax < 0)
		{
			$tax = 0;
		}
		
		$price = $tradeLot->getCurrentPrice();
		
		$tradeToCategoryName = DS_Trade::GetTradeCategoryNameByID($tradeLot->getTradeType()->getTradeToCategoryID());	
		if($tradeToCategoryName == 'MONEY')
		{
			$money = DE_Money::CreateFromID($tradeLot->getTradeType()->getToArticle()->getArticleID());
			if($money->getShortName() == 'GC')
			{
				$price *= constant('GC_COURSE');
			}
		}
		
		$taxSumm = (float)ceil($price * $tax);	
		
		assert($taxSumm >= 0);
		
		return $taxSumm;
	}
}

?>