<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * –асчет минимального и максимального ценника и следующего значени€ цены на аукционе.
 *
 */
class GL_TradeCountMinAndMaxPrices
{
	static public function Count(DE_TradeType $tradeType, $sellAmount, $currentPrice = null, $haveCurrentCustomer = true)
	{
		
		assert(is_float($sellAmount) && $sellAmount > 0);
		assert(is_null($currentPrice) || (is_float($currentPrice) && $currentPrice > 0));
		
		$result = array
		(
			'MAX_PRICE' => 0,
			'MIN_PRICE' => 0,
			'NEXT_PRICE' => 0,
		);
		
		if(DE_WorldSettings::CreateForCurentWorld()->get('TRADE_USE_TRANDS'))
		{
			$price = ME_TradeGetCourse::Get($tradeType->getFromArticle(), $tradeType->getToArticle());			

			$result['MAX_PRICE'] = (float)ceil(($price + ($tradeType->getMaxPriceDelta()/100) * $price) * $sellAmount);
			$result['MIN_PRICE'] = (float)floor(($price + ($tradeType->getMinPriceDelta()/100) * $price) * $sellAmount);			
		}else{
			$result['MAX_PRICE'] = 2000000000;
			$result['MIN_PRICE'] = 1;	
		}
		
		if(!is_null($currentPrice))
		{
			if(!empty($haveCurrentCustomer))
			{
				$minBidStep = DE_WorldSettings::CreateForCurentWorld()->get('MIN_BID_STEP');
				$result['NEXT_PRICE'] = (float)ceil($currentPrice + $currentPrice * $minBidStep);	
				
				/*
				// ”брано, чтобы не возникали ошибки Buy error: TO_SMALL_BID_STEP
				//из-за того, что шаг меньше минимального
				if($result['NEXT_PRICE'] > $result['MAX_PRICE'])
				{
					$result['NEXT_PRICE'] = $result['MAX_PRICE'];
				}*/
			}
			else 
			{
				$result['NEXT_PRICE'] = $currentPrice;
			}
			
		}		
		
		return $result;
	}
}

?>