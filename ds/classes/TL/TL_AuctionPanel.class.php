<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2008/12/22 13:04:17 $';

class TL_AuctionPanel extends TL_BaseMenu
{	
	static public function FormMenu(TI_AuctionPanel $incoming)
	{
		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><auction/>\n";
		$doc = domxml_open_mem($xml);
		$root = $doc->document_element();
		
		$out = "<auction curpage=\"".$incoming->currentPage."\" pages=\"".$incoming->totalPages."\" onpage=\"".$incoming->tradesPerPage."\" order=\"".$incoming->order."\" trader=\"\">";

		$out .= "<sort_other owntrade=\"".$incoming->isOwnTrades."\" ownbet=\"".$incoming->isOwnBets."\" notrade=\"".$incoming->noTrade."\" personal=\"".$incoming->personal."\" trader=\"".$incoming->trader."\" />";
		
		
		foreach($incoming->sortPlace as $k=>$v)
		{
			$out .= "<sort_place sortname=\"".$v[0]."\" cur=\"".$v[1]."\"/>";
		}
		
		foreach($incoming->sortType as $k=>$v)
		{
			$out .= "<sort_type sortname=\"".$v[0]."\" cur=\"".$v[1]."\">";
			foreach($v[2] as $k2=>$v2)
			{
				$out .= "<sort_name sortname=\"".$v2[0]."\" cur=\"".$v2[1]."\"/>";
			}
			$out .= "</sort_type>";
		}
		
		foreach ($incoming->trades as $k=>$v)
		{		
			$price = number_format($v['PRICE'], 0, '.', '');
			$amount = number_format($v['AMOUNT'], 0, '.', '');
			
			$out .= '<au id="'.$v['TRADE_ID'].'" enddate="'.$v['END_DATE'].'" amount="'.$amount.'" getprice="'.$v['GET_PRICE'].'" ';
			$out .= 'price="'.$price.'" customer="'.$v['CUSTOMER'].'" seller="'.$v['SELLER_NAME'].'"  nextprice="'.$v['NEXT_PRICE'].'" ';
			$out .= 'maxprice="'.$v['MAX_PRICE'].'" tradeby="'.$v['TRADE_BY'].'" tradescope="'.$v['TRADE_SCOPE'].'" immediate="'.$v['IMIDIATE'].'" ';
			$out .= 'libname="'.$v['LIBNAME'].'" id_cat_by="'.$v['ID_CAT_BY'].'" id_cat_on="'.$v['ID_CAT_ON'].'" disable="'.$v['DISABLED'].'" '.($v['DEATH_MESSAGE'] != '' ? 'death_message="'.$v['DEATH_MESSAGE'].'"' : '').($v['LIFETIME'] != '' ? 'lifetime="'.$v['LIFETIME'].'"' : '').'/>';			
		}
		
		foreach($incoming->tradeBy as $k=>$v)
		{
			$out .= "<sort_buy sortname=\"".$v[0]."\" cur=\"".$v[1]."\"/>";
		}
		
		$out .= "<listf>";
		foreach ($incoming->filters as $k => $v)
		{
			$out .= '<filter name="'.$v['NAME'].'" id="'.$v['ID'].'" stat="'.$v['VALUE'].'"/>';
		}
		$out .= "</listf>";
		
		$out .= "<sortl>";
		$out .= "<listallow>";
		foreach ($incoming->listSortings as $k => $v)
		{
			$out .= '<field name="'.$v['NAME'].'" id="'.$v['ID'].'"/>';	
		}
		$out .= "</listallow>";
		$out .= "<sortf>";
		foreach ($incoming->currentSortings as $k => $v)
		{
			$out .= '<sort num="'.$v['NUM'].'" id="'.$v['ID'].'" dir="'.$v['DIR'].'"/>';	
		}
		$out .= "</sortf>";
		$out .= "</sortl>";
		
		// закладки
		self::GetBuildingTabs($root, $incoming->getBuildingID(), DE_Language::CreateForCurentLanguage()->getID(), $incoming->getTabName());						
		$forOut = str_replace('<auction>', $out, $doc->dump_mem());		
		
		return $forOut;		
	}	
}


?>