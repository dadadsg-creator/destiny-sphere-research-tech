<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2008/12/22 13:04:17 $';

class TL_AuctionTrades extends TL_BaseMenu
{
	static public function FormMenu(TI_AuctionTrades $incoming)
	{
		$out = '<?xml version="1.0" encoding="utf-8"?>'."\n";
		$out .= '<auction curpage="'.$incoming->currentPage.'" pages="'.$incoming->totalPages.'" onpage="'.$incoming->tradesPerPage.'" >';
		
		foreach ($incoming->trades as $k=>$v)
		{		
			$price = GB_Integer::Format($v['PRICE']);
			$amount = GB_Integer::Format($v['AMOUNT']);
			$nextPrice = GB_Integer::Format($v['NEXT_PRICE']);
			
			if((float)$v['BEST_PRICE'] > (float)1.0)
			{
				$bestPrice = number_format($v['BEST_PRICE'], 5, '.', '\'');//GB_Integer::Format($v['BEST_PRICE'], '\'');
			} else {
				$bestPrice = number_format($v['BEST_PRICE'], 10, '.', '');
			}
			
			$out .= '<au id="'.$v['TRADE_ID'].'" enddate="'.$v['END_DATE'].'" amount="'.$amount.'" getprice="'.$v['GET_PRICE'].'" ';
			$out .= 'price="'.$price.'" customer="'.$v['CUSTOMER'].'" seller="'.$v['SELLER_NAME'].'"  nextprice="'.$nextPrice.'" best_price="'.$bestPrice.'"';
			$out .= 'maxprice="'.$v['MAX_PRICE'].'" tradeby="'.$v['TRADE_BY'].'" tradescope="'.$v['TRADE_SCOPE'].'" immediate="'.$v['IMIDIATE'].'" ';
			$out .= 'libname="'.$v['LIBNAME'].'" id_cat_by="'.$v['ID_CAT_BY'].'" id_cat_on="'.$v['ID_CAT_ON'].'" disable="'.$v['DISABLED'].'" '.($v['DEATH_MESSAGE'] != '' ? 'death_message="'.$v['DEATH_MESSAGE'].'"' : '').($v['LIFETIME'] != '' ? 'lifetime="'.$v['LIFETIME'].'"' : '').'/>';			
		}
		
		$out .= '</auction>';
		
		return $out;
	}
}

?>