<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 *  ласс осуществл€ет сн€тие кредитов со счета игрока за услуги св€занные с глобальными аль€нсами 
 *
 */
class UC_GlobalAllianceMoneyOperations
{	
	/**
	 * ѕроверка, есть ли требуема€ дл€ данной операции сумма на счете
	 *
	 * @param DE_User $user
	 * @param string $operation
	 * @return bool
	 */
	static public function CheckMoneyForOperation(DE_User $user, $operation)
	{
		assert(!empty($operation));
		
		$operationCost = (int)DE_GlobalSettings::Get('GLOBAL_ALLIANCE_'.$operation.'_COST');
		if($operationCost == 0)
		{
			return true;
		}
		$operationCredits = DE_GlobalSettings::Get('GLOBAL_ALLIANCE_'.$operation.'_COST_CREDITS');
		assert($operationCredits == 'SC' || $operationCredits == 'GC');

		if (!self::CheckMoney($user, $operationCost, $operationCredits))
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * —н€тие кредитов со счета 
	 *
	 * @param DE_User $user
	 * @param string $operation
	 */
	static public function GetMoneyForOperation(DE_User $user, $operation)
	{
		$operationCost = (int)DE_GlobalSettings::Get('GLOBAL_ALLIANCE_'.$operation.'_COST');
		$operationCredits = DE_GlobalSettings::Get('GLOBAL_ALLIANCE_'.$operation.'_COST_CREDITS');
		
		self::GetMoney($user, $operationCost, $operationCredits);
	}
	
	static public function CheckMoney(DE_User $user, $operationCost, $operationCredits)
	{		
		if($operationCost == 0)
		{
			return true;
		}
				
		assert($operationCredits == 'SC' || $operationCredits == 'GC');
		
		$world = DE_WorldFromCollection::CreateFromID('UC');
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
	 * —н€тие кредитов со счета 
	 *
	 * @param DE_User $user
	 * @param string $operation
	 */
	static public function GetMoney(DE_User $user, $operationCost, $operationCredits)
	{
		if($operationCost == 0)
		{
			return;
		}

		assert($operationCredits == 'SC' || $operationCredits == 'GC');
		
		assert(self::CheckMoney($user, $operationCost, $operationCredits));
		
		// перевод денег
		$world = DE_WorldFromCollection::CreateFromID('UC');
		$memberFrom = new MU_EntityOperationMember($user, $world);
		$memberTo = new MU_EntityOperationMember(DE_User::CreateFromName('DestinySphere'), $world);
		$summ = new MU_EntityMoneySumm($operationCost, $operationCredits);
		$actionType = DE_MoneyActionType::CreateFromName('BILLING_PAYMENT');

		$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
		$operation->setAdditionalID(GB_RandomPassword::Generate(25)); 
		$operation->setGateKey(GB_RandomPassword::Generate(25)); 

		MU_ControlExecOperations::ExecOperation($operation);
	}
	
}

?>