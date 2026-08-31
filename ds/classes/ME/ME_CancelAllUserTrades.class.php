<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_CancelAllUserTrades
{
	/**
	 * Отмена всех торгов пользователя
	 *
	 * @param DE_UserInstance $userInstance
	 * @param bool $withComission снимать или нет налог за некпленные ресурсы
	 * @param array $excludeScopes лоты и ставки данного скопа не будут трогаться
	 */
	static public function Cancel(DE_UserInstance $userInstance, $withComission = true, array $excludeScopes = array(), $onlyWithoutBinds = false)
	{
		assert(is_bool($withComission));
		assert(is_bool($onlyWithoutBinds));
		
		$checkDate = GB_TypeFactory::CreateBase('GB_Date', time());
		
		$userTradesCollection = DC_TradeUserLots::CreateFromUIID($userInstance->getID());
		$userTradesCollection->resetNext();
		while($tradeLot = $userTradesCollection->next())
		{
			$key = $userTradesCollection->getCurrentPosition();	
			if(!in_array($tradeLot->getTradeScopeID(), $excludeScopes))
			{
				// Если настроено, смотрим, есть ли ставки на данный лот
				if ($onlyWithoutBinds === false || $tradeLot->haveCurrentCustomer() === false) {
					ME_TradeExecLotReturn::Exec($tradeLot, $checkDate, $withComission);
				}
			}
			$userTradesCollection->setPointerToPosition($key);
		}
				
		$userBidsCollection = DC_TradeUserBids::CreateFromUIID($userInstance->getID());
		$userBidsCollection->resetNext();
		while($tradeLot = $userBidsCollection->next())
		{
			$key = $userBidsCollection->getCurrentPosition();
			if(!in_array($tradeLot->getTradeScopeID(), $excludeScopes))
			{		
				// Смотрим, можно ли отменять торки со ставками
				if ($onlyWithoutBinds === false)
				{
					ME_TradeExecReturnUserBid::Exec($tradeLot);
				}
			}
			$userBidsCollection->setPointerToPosition($key);
		}
	}
}

?>