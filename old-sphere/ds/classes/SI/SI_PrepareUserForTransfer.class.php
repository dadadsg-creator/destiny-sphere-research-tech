<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2010/03/04 08:55:59 $';

/**
 * Подготовка пользователя для переезда в мир иной
 * 	
 *	- снимаем все лоты и ставки
 * 	- завершаем все ивенты доставки оплаты и товаров с торгов
 * 	- возвращаем все армии на соту
 */
class SI_PrepareUserForTransfer
{
	static private $userInstanceStore;
	
	/**
	 * DE_UserInstance
	 *
	 * @var unknown_type
	 */
	static private $userInstance;
	
	static public function Exec($UIID)
	{
		settype($UIID, 'integer');
		assert($UIID > 0);
		
		$common = DS_Common::CreateForCurentDB();
		
		SI_Base::SetCommon($common);
				
		self::$userInstanceStore = new SI_ResultUserInstance($UIID);
		
		self::$userInstance = DE_UserInstance::CreateFromID($UIID);
		
		// Возвращаем зонды
		self::_endSpyEvents();
		
		// если переезд между новыми мирами
		//if(GL_DetectNewTradeVersion::Detect())
		//{
		// отменяем все торги
		self::_endTrades();
				
		// возвращаем кредиты в кабинет
		self::_returnMoneyToCabinet();
			
		//} else {
			// отменяем торги (в старой версии торговли)
		//	self::_endTradesOld();
		//}
		
		// завершаем евенты доставки товаров и оплаты
		self::_clearTradeDeliveryEvents();
		
		// завершаем евенты доставки ресурсов, иначе с артефактами проблемы
		self::_endTransfers();
		
		// завершаем бои
		self::_endBattles();
		
		
	}
	
	static private function _endTrades()
	{		
		ME_CancelAllUserTrades::Cancel(self::$userInstance, false);
	}
	
	static private function _returnMoneyToCabinet()
	{
		//return true;
		$world = DE_WorldFromCollection::CreateFromID(self::$userInstance->getWorld()->getShortName());
		$user = DE_User::CreateFromID(self::$userInstance->getUser()->getID());
		$memberFrom = new MU_EntityOperationMember($user, $world);
		$instanceMoney = MU_SearchOperations::GetAccountData($memberFrom);
		
		$memberTo = new MU_EntityOperationMember($user, DE_WorldFromCollection::CreateFromID('UC'));
			
		if($instanceMoney['GC'] > 0)
		{
			$summ = new MU_EntityMoneySumm($instanceMoney['GC'], 'GC');
			MU_ExecTransferInterworld::Exec($memberFrom, $memberTo, $summ);
		}
			
		if($instanceMoney['SC'] > 0)
		{
			$summ = new MU_EntityMoneySumm($instanceMoney['SC'], 'SC');
			MU_ExecTransferInterworld::Exec($memberFrom, $memberTo, $summ);
		}
	}
	
	static private function _endBattles()
	{
		SI_SearchUserInstance::EndBattles(self::$userInstance->getID());
	}
	
	static private function _endSpyEvents()
	{
		// Получаем все ивенты полетов зондов
		$res = SI_SearchComb::GetSpyEvents(self::$userInstance->getID());
		
		foreach ($res as $key => $data)
		{
			$eqid = (int)$data['id_eventqueue'];
			assert($eqid > 0);
			
			// Получаем атрибуты ивента
			$event = DE_EventQueue::CreateFromID($eqid);
			$eventAttributes = $event->getAttributes();
			$combFrom = DE_Comb::CreateFromID((int)$eventAttributes->getAttribute('ID_CombFrom'));
			$unit = DE_Unit::CreateFromID((int)$eventAttributes->getAttribute('ID_Unit'));
			$amount = (int)$eventAttributes->getAttribute('Amount');
			
			// Меняем количество зондов
			$combAttributes = $combFrom->getAttributes();
			$combAttributes->deltaAttribute('AWAY_AMOUNT', $unit->getName(), -$amount);
			$combAttributes->deltaAttribute('CURRENT_AMOUNT', $unit->getName(), $amount);
			
			$event->setIsTrash(true);		
			$event->commit();	
		}
		
	}
	
	static private function _clearTradeDeliveryEvents()
	{
		SI_SearchEvents::EndTradeDeliveryEvents(self::$userInstance->getID());	
	}
	
	static private function _endTransfers()
	{
		SI_SearchEvents::EndTransferEvents(self::$userInstance->getID());	
	}
	
	// Старая торговля
	static private function _endTradesOld()
	{
		// 1. cancel current customer
		$bids = SI_SearchEndOldTrades::GetUserBids(self::$userInstance->getID());
	
		foreach ($bids as $k => $bid)
		{
			$attributeName =  ST_Attributes::Create()->getAttributeNameByAttributeNameID((int)$bid["id_tradeby"]);
			DE_CombAttributes::CreateFromCombID((int)$bid["id_customercomb"])->deltaAttribute('CURRENT_AMOUNT', $attributeName, (float)$bid["currentprice"]);
			
			SI_SearchEndOldTrades::ClearTradeCurrentCustomer((int)$bid['id_trade']);
			
			SI_SearchEndOldTrades::DeleteFromEventParticipant((int)$bid['id_eventqueue'], self::$userInstance->getID());
		}

		// 2. cancel auctions
		$res = SI_SearchEndOldTrades::GetUserTrades(self::$userInstance->getID());
		
		foreach ($res as $k => $trade)
		{
			if($trade['id_currentcustomer'])
			{
				// return resources to current customes
				$attributeName = ST_Attributes::Create()->getAttributeNameByAttributeNameID((int)$trade["id_tradeby"]);
				DE_CombAttributes::CreateFromCombID((int)$trade["id_customercomb"])->deltaAttribute('CURRENT_AMOUNT', $attributeName, (float)$trade["currentprice"]);
			}
			
			// get back goods
			switch ( $trade['categoryname'] )
			{
				case "RESOURCE":
					$attributeName = ST_Attributes::Create()->getAttributeNameByAttributeNameID((int)$trade["id_article"]);
					DE_CombAttributes::CreateFromCombID((int)$trade["id_sellercomb"])->deltaAttribute('CURRENT_AMOUNT', $attributeName, (float)$trade["amount"]);
					break;

				case "ARTEFACT":
					$id = $trade["id_article"]."_".$trade["id_sellercomb"];
					$combArtefact = DE_CombHasArtefact::CreateInDB($id);
					$combArtefact->commit();
					break;

				case "UNIT":
					$comb = DE_Comb::CreateFromID((int)$trade["id_sellercomb"]);
					$unit = DE_Unit::CreateFromID((int)$trade["id_article"]);

					ME_AddUnitsToHomeArmy::AddUnit($comb, $unit, (float)$trade['amount'], GB_TypeFactory::CreateBase('GB_Date', time()));
					break;
			}
			
			SI_SearchEndOldTrades::DeleteTrade((int)$trade['id_eventqueue'], (int)$trade['id_trade']);
			
			$event = DE_EventQueue::CreateFromID((int)$trade['id_eventqueue']);
			$event->setIsTrash(true);
			$event->commit();
		}
	}
}

?>