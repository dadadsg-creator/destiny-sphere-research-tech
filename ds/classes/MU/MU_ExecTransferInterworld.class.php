<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_ExecTransferInterworld
{
	static public function Exec(MU_EntityOperationMember $memberFrom, MU_EntityOperationMember $memberTo, MU_EntityMoneySumm $summ)
	{
		assert($memberFrom->getWorldShortName() != $memberTo->getWorldShortName());
		assert($memberFrom->getUserID() == $memberTo->getUserID());
		
		$systemMemberTo = MU_EntityOperationMember::CreateSystemAccount($memberFrom->getWorldShortName());
		$systemMemberFrom = MU_EntityOperationMember::CreateSystemAccount($memberTo->getWorldShortName());
		
		$key = GB_RandomPassword::Generate(25);
		
		//Сперва в резерв										
		$actionType = DE_MoneyActionType::CreateFromName('SEND_INTERWORLD_TRANSFER');	
		$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $systemMemberTo);
		$operation->setAdditionalID($key);
		$operation->setInterworldTransferData($memberFrom->getCollectionWorld(), $memberTo->getCollectionWorld());
		
		MU_ControlExecOperations::ExecOperation($operation);

		//Потом из резерва
		$actionType = DE_MoneyActionType::CreateFromName('RECIVE_INTERWORLD_TRANSFER');	
		$operation = new MU_EntityOperation($actionType, $summ, $systemMemberFrom, $memberTo);
		$operation->setAdditionalID($key);
		$operation->setInterworldTransferData($memberFrom->getCollectionWorld(), $memberTo->getCollectionWorld());
		
		MU_ControlExecOperations::ExecOperation($operation);		
	}
}


?>