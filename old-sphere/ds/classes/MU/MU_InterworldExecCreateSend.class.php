<?php

/**
 * Создание операции посылки денег для исправлдения ошибки
 *
 */
class MU_InterworldExecCreateSend  extends MU_InterworldBase
{
	
	static public $msg = '';
	
	static public function  Exec(MU_EntityOperationMember $memberFrom, DE_WorldFromCollection $worldTo, MU_EntityMoneySumm $summ, $key)
	{				
		assert(is_string($key) && !empty($key));
		
		self::$msg = "\n";
		
		$memberTo = MU_EntityOperationMember::CreateSystemAccount($memberFrom->getWorldShortName());
		
		//Проверим есть ли деньги на счету что бы переветис их
		$accountData = MU_SearchOperations::GetAccountData($memberFrom);		
		self::$msg .= "Username ".$memberFrom->getUser()->getName()." [".$memberFrom->getWorldShortName()."]  before:  SC= ".
							$accountData['SC']." GC= ".$accountData['GC']." \n";
		
		self::$msg .= "    Need transfer ".$memberFrom->getUser()->getName()." [".$memberFrom->getWorldShortName()."] => ".
							$memberTo->getUser()->getName()." [".$memberTo->getWorldShortName()."] ".$summ->getCreditName().
							": ".$summ->getAmount()."\n";
		
		if($accountData[$summ->getCreditName()] < $summ->getAmount())
		{
			MU_InterworldController::$tmp[$memberFrom->getUser()->getName()][$memberFrom->getWorldShortName()] += $summ->getAmount();
						
			self::$msg .= "    CANT FIX! Not enought money on sorce account\n";
			return false;			
		}
		
		if(!$accountData['IS_ACTIVE'])
		{			
			MU_InterworldSearch::ChangeAcountActiveStatus($memberFrom, true);
			$makeUnactive = true;		
			self::$msg .= "    Account is anactive. Make him active.\n";	
		}else{
			$makeUnactive = false;
		}
				
		//Сосбветнно делаемоперацию
		$actionType = DE_MoneyActionType::CreateFromName('SEND_INTERWORLD_TRANSFER');	
		$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
		$operation->setAdditionalID($key);
		
		$operation->setInterworldTransferData($memberFrom->getCollectionWorld(), $worldTo);		
		MU_ControlExecOperations::ExecOperation($operation);	 		
		
		$accountData = MU_SearchOperations::GetAccountData($memberFrom);		
		self::$msg .= "    After: ".$memberFrom->getUser()->getName()." [".$memberFrom->getWorldShortName()."] :  SC= ".
							$accountData['SC']." GC= ".$accountData['GC']." \n"; 
		
		if($makeUnactive)
		{
			MU_InterworldSearch::ChangeAcountActiveStatus($memberFrom, false); 
			self::$msg .= "    Deactivate account.\n";	
		}
		
		self::$msg .= '    FIXED!';
		return true;
	}
}
 
?>