<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_TradeCountMinSellAmount
{
	static public function Count(DE_TradeType $tradeType)
	{
		$sellStazis = $tradeType->getFromArticle()->getStazis();	
		$priceStazis = $tradeType->getToArticle()->getStazis();	
	}
}


?>