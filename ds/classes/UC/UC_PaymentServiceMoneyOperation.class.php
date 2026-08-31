<?php $CVS_Version = '$Revision: 1.14 $ ; $Date: 2010/07/07 09:00:42 $';

/**
 * Класс осуществляет снятие кредитов со счета игрока за платные услуги 
 *
 */
class UC_PaymentServiceMoneyOperation
{	
	/**
	 * Проверка, есть ли требуемая для данной операции сумма на счете
	 *
	 * @param DE_User $user
	 * @param string $operation
	 * @return bool
	 */
	static public function CheckMoneyForOperation(DE_User $user, $operation, $count = 1)
	{
		assert(!empty($operation));
		assert(is_int($count) && $count >0 );

		//Получаем цену
		$actionType = DE_MoneyActionType::CreateFromName($operation);
		$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
		$operationCost = $MoneyActionType->getAmount()*$count;
		
		if($operationCost == 0)
		{
			return true;
		}
		
		$operationCredits = $MoneyActionType->getCreditName();
		assert($operationCredits == 'SC' || $operationCredits == 'GC');

		//смотрим, есть ли достаточное количество кредитов
		if (!self::CheckMoney($user, $operationCost, $operationCredits, 'UC'))
		{
			/* ДУРАТСКАЯ ЗАТЫКА!! ПЕРЕПИСАТЬ ВЕСЬ МЕТОД!! */
			// Если мы смотрели серебро, но у нас есть еще цена в золоте
			if ($operationCredits == 'SC' && $MoneyActionType->getPriceInGolg() != 0) {
				// Смотрим, есть ли необходимое золото на соте игрока
				if (self::CheckMoney($user, (int)$MoneyActionType->getPriceInGolg(), 'GC', 'UC')) {
					return true;
				}
			}
			return false;
		}
		
		return true;
	}
	
	/**
	 * Снятие кредитов со счета 
	 *
	 * @param DE_User $user
	 * @param string $operation
	 */
	static public function GetMoneyForOperation(DE_User $user, $operation, $multiplier = 1, $WI = '', $credit = 'SC')
	{
		assert(!empty($operation));
		assert($multiplier > 0);
		assert($credit == 'SC' || $credit == 'GC');
		
		//Получаем цену
		$actionType = DE_MoneyActionType::CreateFromName($operation);
		$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
		
		// Если мы хотим заплатить в серебре
		if ($credit == 'SC') {
			$operationCost = $MoneyActionType->getAmount();
			$operationCredits = 'SC';
		} else {
			// Если мы хотим заплатить в золоте
			$operationCost = $MoneyActionType->getPriceInGolg();	
			$operationCredits = 'GC';
		}
		// Проверяем, чтобы стоимость не была равна 0
		assert($operationCost > 0);
		
		$operationCost = $operationCost*$multiplier;	
			
		assert(self::CheckMoney($user, $operationCost, $operationCredits, 'UC'));
		
		// перевод денег
		$world = DE_WorldFromCollection::CreateFromID('UC');
		$memberFrom = new MU_EntityOperationMember($user, $world);
		$memberTo = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $world);
		$summ = new MU_EntityMoneySumm($operationCost, $operationCredits);

		$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);

		MU_ControlExecOperations::ExecOperation($operation, $WI);
	}
	
	
	/**
	 * Проверка, есть ли требуемая для данной операции сумма на счете в конкретном мире
	 *
	 * @param DE_User $user
	 * @param int $operationCost
	 * @param string $operationCredits
	 * @param string $worldIdentifier
	 * @return bool
	 */
	static public function CheckMoney(DE_User $user, $operationCost, $operationCredits, $worldIdentifier)
	{		
		if($operationCost == 0)
		{
			return true;
		}
				
		assert($operationCredits == 'SC' || $operationCredits == 'GC');
		
		$world = DE_WorldFromCollection::CreateFromID($worldIdentifier);
		$member = new MU_EntityOperationMember($user, $world);
		$account = MU_SearchOperations::GetAccountData($member);
		
		if(!$account['IS_ACTIVE'])
		{
			return false;
		}
	
		if($account[$operationCredits] < $operationCost)
		{
			return false;
		}
		
		return true;
	}
		
	/**
	 *  Возврат денег на счет
	 *
	 * @param DE_User $user
	 * @param string $operation
	 */
	static public function ReturnMoney(DE_User $user, $operation, $operationCost, $operationCredits)
	{
		assert(!empty($operation));

		if($operationCost == 0)
		{
			return;
		}
		assert($operationCredits == 'SC' || $operationCredits == 'GC');
		
		// перевод денег
		$world = DE_WorldFromCollection::CreateFromID('UC');
		
		$memberFrom = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $world);
		$memberTo = new MU_EntityOperationMember($user, $world);
		
		$summ = new MU_EntityMoneySumm($operationCost, $operationCredits);
		$actionType = DE_MoneyActionType::CreateFromName($operation);

		$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);

		MU_ControlExecOperations::ExecOperation($operation);
	}
	
	/**
	 * Проверка, есть ли требуемая для данной операции сумма на счете в мирах игрока
	 *
	 * @param DE_User $user
	 * @param int $operationCost
	 * @param string $operationCredits
	 * @param string $worldIdentifier
	 * @return bool
	 */
	static public function CheckMoneyInWorlds(DE_User $user, $operation)
	{		
		assert(!empty($operation));
			
		//Получаем список миров, в которых у игрока есть счет
		$common = DS_Common::CreateForCurentDB(0);
		GE_SearchStart::SetCommon($common);
		$userWorlds = GE_SearchStart::GetWorldsWhereUserIsRegistered($user->getID());
		
		foreach($userWorlds as $k => $instance) 
		{
			if (self::CheckEnoughtMoney($user, $operation, $instance['world_identifier'])) 
			{
				return $instance['worldname'];
			}
		}

		return false;
	}

	static public function CheckEnoughtMoney(DE_User $user, $operation, $world_identifier)
	{
		assert(!empty($operation));
		assert(!empty($world_identifier));
		
		//Получаем цену
		$actionType = DE_MoneyActionType::CreateFromName($operation);
		$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
		$operationCost = $MoneyActionType->getAmount();
		
		if($operationCost == 0)
		{
			return true;
		}
		
		$operationCredits = $MoneyActionType->getCreditName();
		assert($operationCredits == 'SC' || $operationCredits == 'GC');	
		
		$world = DE_WorldFromCollection::CreateFromID($world_identifier);
		$member = new MU_EntityOperationMember($user, $world);
		$account = MU_SearchOperations::GetAccountData($member);

		//Если у нас цена в серебряных кредитах
		if ($operationCredits == 'SC') {
			//Смотрим, сколько у игрока есть денег в серебре в этом мире
			$money  = $account['SC'] + $account['GC']*1000;
		} else {
			//Если цена а золоте
			$money  = $account['GC'];
		}
		//Смотрим, есть ли достаточное количество денег
		if ($money >= $operationCost){
			return true;
		}
		return false;
	}

	static public function EnterMoney($userID, $sum, $credits='SC', $op_title='ENTER_MONEY', $worldID='UC')
	{
		$world = DE_WorldFromCollection::CreateFromID($worldID);
		$memberTo = new MU_EntityOperationMember(DE_User::CreateFromID((int)$userID), $world);
		$memberFrom = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $world);
		$summ = new MU_EntityMoneySumm((int)$sum, $credits);
		$actionType = DE_MoneyActionType::CreateFromName($op_title);
		$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
		$operation->setAdditionalID(GB_RandomPassword::Generate(25));
		$operation->setGateKey(GB_RandomPassword::Generate(25));

		MU_ControlExecOperations::ExecOperation($operation);
	}

	/**
	 * Снятие кредитов со счета за платные операции БЕЗ ФИКСИРОВАННОЙ ЦЕНЫ в интерфейсе игры, 
	 *
	 * @param DE_User $user
	 * @param string $operation
	 */
	static public function GetMoneyForPaymentOperation(DE_User $user, $operation, $operationCost, $operationCredits, $worldIdentifier)
	{
		assert(!empty($operation));
		assert($operationCredits == 'SC' || $operationCredits == 'GC');
		assert($operationCost > 0);
		assert(!empty($worldIdentifier));
		
		assert(self::CheckMoney($user, $operationCost, $operationCredits, $worldIdentifier));

		// перевод денег
		$world = DE_WorldFromCollection::CreateFromID($worldIdentifier);
		$memberFrom = new MU_EntityOperationMember($user, $world);
		$memberTo = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $world);
		$summ = new MU_EntityMoneySumm($operationCost, $operationCredits);
		$actionType = DE_MoneyActionType::CreateFromName($operation);

		$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);

		MU_ControlExecOperations::ExecOperation($operation);
	}
	
	/**
	 * Снятие кредитов со счета за платные операции С ФИКСИРОВАННОЙ ЦЕНОЙ в интерфейсе игры
	 *
	 * @param DE_User $user
	 * @param string $operation
	 */
	static public function GetMoneyForPaymentOperationWitPrice(DE_User $user, $operation, $worldIdentifier, $multiplier = 1)
	{
		assert(!empty($operation));
		assert($multiplier > 0);
		assert(!empty($worldIdentifier));
		
		//Получаем цену
		$actionType = DE_MoneyActionType::CreateFromName($operation);
		$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
		$operationCost = $MoneyActionType->getAmount() ;
		$operationCredits = $MoneyActionType->getCreditName();
		
		assert($operationCredits == 'SC' || $operationCredits == 'GC');
		
		if($operationCost == 0)
		{
			return;
		}
		
		$operationCost = $operationCost*$multiplier;	
			
		assert(self::CheckMoney($user, $operationCost, $operationCredits, $worldIdentifier));
		
		// перевод денег
		$world = DE_WorldFromCollection::CreateFromID($worldIdentifier);
		$memberFrom = new MU_EntityOperationMember($user, $world);
		$memberTo = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $world);
		$summ = new MU_EntityMoneySumm($operationCost, $operationCredits);
		$actionType = DE_MoneyActionType::CreateFromName($operation);

		$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);

		MU_ControlExecOperations::ExecOperation($operation);
	}
	
	
	/**
	 * Получение стоимости услуги. Надо учитывать, что цены для всех миров одинаковы, и табличка с ценами должна должна реплицироваться!
	 *
	 * @param str $operation
	 * @return int 
	 */
	static public function GetServicePrice($operation)
	{
		// ВСЕ ЦЕНЫ ТОЛЬКО В СЕРЕБРЕ. ЕСЛИ ПОСТАВИТЬ В ЗОЛОТЕ, ТО БУДЕТ ОШИБКА!!!
		$actionType = DE_MoneyActionType::CreateFromName($operation);
		$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
		
		$price = (int)$MoneyActionType->getAmount();
		assert($price > 0);
		
		$credit = $MoneyActionType->getCreditName();
		assert($credit == 'SC');
		
		return $price;
	}
}

?>