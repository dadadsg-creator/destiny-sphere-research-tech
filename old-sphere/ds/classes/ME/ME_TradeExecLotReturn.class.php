<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Менеджер возврата торгов из торгователя после окончания 
 *
 */
class ME_TradeExecLotReturn
{
	static public function Exec(DE_TradeLot $tradeLot, GB_Date $checkDate, $withComission = true)
	{
		assert(is_bool($withComission));

		$eventQueue = $tradeLot->getLotEvent();

		$tradeCategoryName = DS_Trade::GetTradeCategoryNameByID($tradeLot->getTradeType()->getTradeFromCategoryID());

		//Если кто-то уже сдалал ставку на этот лот
		if ($tradeLot->haveCurrentCustomer() === true) {
			$toCategoryName = DS_Trade::GetTradeCategoryNameByID($tradeLot->getTradeType()->getTradeToCategoryID());

			//Возвращение ставки
			if($toCategoryName == 'MONEY')
			{
				$worldFromCollection = DE_WorldFromCollection::CreateFromID(DE_World::CreateForCurrentWorld()->getShortName());

				$memberFrom = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $worldFromCollection);
				$memberTo = new MU_EntityOperationMember($tradeLot->getCurrentCustomer()->getUser(), $worldFromCollection);

				$money = DE_Money::CreateFromID($tradeLot->getTradeType()->getTradeToTypeID());
				$summ = new MU_EntityMoneySumm((int)$tradeLot->getCurrentPrice(), $money->getShortName());
				$actionType = DE_MoneyActionType::CreateFromName('MOVE_FROM_RESERV');

				$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
				$operation->setAdditionalID($tradeLot->getID());

				MU_ControlExecOperations::ExecOperation($operation);
			}else{
				ME_DeltaResourcesOnComb::Exec($toCategoryName, $tradeLot->getCurrentCustomerComb(),
				$tradeLot->getTradeType()->getTradeToTypeID(),
				$tradeLot->getCurrentPrice(),
				GB_TypeFactory::CreateBase('GB_Date', time())
				);
			}
		}

		if($tradeLot->getTradeType()->getTradeFromCategoryID() == 3)
		{
			ME_TradeArtefactInstancesOnTradeLotWork::AddArtfactsFromEventToComb($eventQueue->getID(), $tradeLot->getSellerComb());
		}else{
			//Снимаем штраф за воврат ресурсов после окнчания торга.
			if($tradeCategoryName == 'RESOURCE' && $withComission)
			{
				$amount = ceil($tradeLot->getAmount() - $tradeLot->getAmount() * DE_WorldSettings::CreateForCurentWorld()->get('TRADE_PENALTY'));
			}else{
				$amount = $tradeLot->getAmount();
			}
			ME_DeltaResourcesOnComb::Exec($tradeCategoryName, $tradeLot->getSellerComb(), $tradeLot->getArticleID(), $amount, $eventQueue->getEndDate());
			
			//Пишем сообщение продавцу о том, что его лот отменили
			
			$tradeGood = ME_TradeCreateGoodObjet::Exec($tradeCategoryName, $tradeLot->getArticleID());
			$messparams[0] = $tradeGood->getLocalizedName();
			$messparams[1] = $tradeLot->getAmount();
			$messparams[2] = $tradeLot->getTradeType()->getToArticle()->getLocolizedName();
			$messparams[3] = $tradeLot->getCurrentPrice();
			$messparams[4] = $tradeLot->getSellerComb()->getFullName();
			DE_SystemMessages::AddMessageFromMessageCompiler($checkDate, $tradeLot->getSeller()->getID(), 'CANCEL_TRADE_LOT', $messparams, 0, "GREEN");
		}

		$eventQueue->setIsTrash(true);

		$tradeLot->delete();
	}
}

?>