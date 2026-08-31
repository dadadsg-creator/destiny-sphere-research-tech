<?php

/**
 * Создание операции приема для исправления ошибки
 *
 */
class MU_InterworldExecCreateRecive extends MU_InterworldBase
{
	
	static public $msg = '';
	
	static public function  Exec(MU_EntityOperationMember $memberTo,  DE_WorldFromCollection $worldFrom, MU_EntityMoneySumm $summ, $key)
	{				
		assert(is_string($key) && !empty($key));
		
		self::$msg = "\n";
		
		$systemMemberFrom = MU_EntityOperationMember::CreateSystemAccount($memberTo->getWorldShortName());
				
		//Проверим есть ли деньги на счету что бы переветис их
		$accountData = MU_SearchOperations::GetAccountData($memberTo);		
		self::$msg .= "    Before ".$memberTo->getUser()->getName()." [".$memberTo->getWorldShortName()."] = SC: ".
						$accountData['SC']."\n"; 
		
		self::$msg .= "    Need transfer ".$systemMemberFrom->getUser()->getName().
							" [".$systemMemberFrom->getWorldShortName()."] => ".$memberTo->getUser()->getName().
							" [".$memberTo->getWorldShortName()."] ".$summ->getCreditName().": ".$summ->getAmount()."\n";		
		
		if(!$accountData['IS_ACTIVE'])
		{			
			MU_InterworldSearch::ChangeAcountActiveStatus($memberTo, true);
			$makeUnactive = true;		
			self::$msg .= "    Account is anactive. Make him active.\n";	
		}else{
			$makeUnactive = false;
		}
				
		//Сосбветнно делаем операцию
		$actionType = DE_MoneyActionType::CreateFromName('RECIVE_INTERWORLD_TRANSFER');	
		$operation = new MU_EntityOperation($actionType, $summ, $systemMemberFrom, $memberTo);
		$operation->setAdditionalID($key);
		$operation->setInterworldTransferData($worldFrom, $memberTo->getCollectionWorld());		
		MU_ControlExecOperations::ExecOperation($operation);			
		
		$accountData = MU_SearchOperations::GetAccountData($memberTo);		
		self::$msg .= "    Before ".$memberTo->getUser()->getName()." [".$memberTo->getWorldShortName()."] = SC: ".$accountData['SC']."\n"; 
		
		if($makeUnactive)
		{
			MU_InterworldSearch::ChangeAcountActiveStatus($memberTo, false);
			self::$msg .= "    Deactivate account.\n";	
		}
		
		self::$msg .= '    FIX';
		return true;
	}
	
}

?>