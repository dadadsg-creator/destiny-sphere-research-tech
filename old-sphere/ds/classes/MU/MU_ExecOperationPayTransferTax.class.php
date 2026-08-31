<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_ExecOperationPayTransferTax extends MU_ExecOperation
{
	public function Exec()
	{
		assert($this->operation->getToUser()->getUserID() == 0);
		
		assert($this->operation->getActionType()->getActionName() == 'PAY_TRANSFER_TAX');
		
		$acountData = MU_SearchOperations::GetAccountData($this->operation->getFromUser());
		assert(is_array($acountData) && count($acountData) > 0);
		
		$key = GB_RandomPassword::Generate(25);
		$this->operation->setAdditionalID($key);
		
		$logData = new MU_EntityOperationLogRecord($this->operation->getActionType(), $this->operation->getFromUser()->getDBID());
		$logData->setField('id_user', $this->operation->getFromUser()->getUserID());
		$logData->setField('world_identifier', $this->operation->getToUser()->getWorldShortName());
		$logData->setField('gc_amount', $this->operation->getSumm()->getAmountGC());
		$logData->setField('sc_amount', $this->operation->getSumm()->getAmountSC());
		$logData->setField('id_action', $this->operation->getActionType()->getActionID());
		$logData->setField('id_account', $acountData['ID_ACCOUNT']);
		$logData->setField('enter_key', $key);
		
		
		$listID = MU_SearchOperations::InsertOperationLog($logData);
		assert($listID > 0);
		
		MU_ExecTransfer::Exec($listID, $this->operation);		
		
		
		/*$actionType = DE_MoneyActionType::CreateFromName('PAY_TRANSFER_TAX');
		$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberSystem);
		
		$key = GB_RandomPassword::Generate(25);
		$operation->setAdditionalID($key);
		
		$worldFromCollection = DE_WorldFromCollection::CreateFromID(DE_World::CreateForCurrentWorld()->getShortName());
		$memberSystem = MU_EntityOperationMember::CreateSystemAccount($worldFromCollection->getShortName());
		
		MU_ControlExecOperations::ExecOperation($operation);		
			
		assert($this->operation->getToUser()->getUserID() == 0);
		
		assert($this->operation->getActionType()->getActionName() == 'PAY_TRANSFER_TAX');
		
		$acountData = MU_SearchOperations::GetAccountData($this->operation->getFromUser());
		assert(is_array($acountData) && count($acountData) > 0);
		
		$logData = new MU_EntityOperationLogRecord($this->operation->getActionType(), $this->operation->getFromUser()->getDBID());
		$logData->setField('id_user', $this->operation->getFromUser()->getUserID());
		$logData->setField('world_identifier', $this->operation->getFromUser()->getWorldShortName());
		$logData->setField('gc_amount', $this->operation->getSumm()->getAmountGC());
		$logData->setField('sc_amount', $this->operation->getSumm()->getAmountSC());
		$logData->setField('id_action', $this->operation->getActionType()->getActionID());
		$logData->setField('id_account', $acountData['ID_ACCOUNT']);
		$logData->setField('id_trade', $this->operation->getAdditionalID());
		
		$listID = MU_SearchOperations::InsertOperationLog($logData);
		assert($listID > 0);
		
		MU_ExecTransfer::Exec($listID, $this->operation);*/		
		
		/*assert($memberFrom->getWorldShortName() != 'UC');
		assert($memberFrom->getUserID() == $memberTo->getUserID());
		
		$systemMemberTo = MU_EntityOperationMember::CreateSystemAccount($memberFrom->getWorldShortName());
		$systemMemberFrom = MU_EntityOperationMember::CreateSystemAccount($memberTo->getWorldShortName());
		
		$key = GB_RandomPassword::Generate(25);
		
		//Сперва в резерв										
		$actionType = DE_MoneyActionType::CreateFromName('PAY_TRANSFER_TAX');	
		$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $systemMemberTo);
		$operation->setAdditionalID($key);
		$operation->setInterworldTransferData($memberFrom->getCollectionWorld(), $memberTo->getCollectionWorld());
		
		MU_ControlExecOperations::ExecOperation($operation);*/
	}
}


?>