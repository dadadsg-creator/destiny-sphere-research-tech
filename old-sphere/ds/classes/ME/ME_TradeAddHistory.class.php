<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_TradeAddHistory
{
	static public function Exec(DE_TradeLot $tradeLot, GB_Date $bayDate)
	{			
		$ip = SU_DebugTools::GetIP(0);
		$ip =  $ip['IP'];
		
		if(!preg_match('/^([0-9]{1,3}\.?){4}$/', $ip))
		{
			$ip = '1.1.1.1';
		}
		
		// Записываем в базу продажу		
		DS_Trade::InsertTradeHistory($tradeLot, $bayDate, $ip);
		
		// Если торгуем артами то прилепляем инстансы
		if($tradeLot->getTradeType()->getTradeFromCategoryID() == 3)
		{
			ME_TradeArtefactInstancesOnTradeLotWork::AddArtfactsToHistoryFromTradeLot($tradeLot->getID());
		}
	}
}

?>