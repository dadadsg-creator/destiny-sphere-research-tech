<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_ExecOperationPVPTransfer extends MU_ExecOperation
{	
	public function Exec()
	{
		assert($this->operation->getSumm()->getAmountGC() > 0 || 
				$this->operation->getSumm()->getAmountSC() > 0);
				
		assert($this->operation->getFromUser()->getUserID() >= 0);
		assert($this->operation->getToUser()->getUserID() >= 0);
		
		assert($this->operation->getFromUser()->getWorldShortName() == 'UC');
		assert($this->operation->getToUser()->getWorldShortName() == 'UC');	
		
		assert($this->operation->getActionType()->getActionName() == 'PVP_TRANSFER');
		
		$acountDataFrom = MU_SearchOperations::GetAccountData($this->operation->getFromUser());
		assert(is_array($acountDataFrom) && count($acountDataFrom) > 0 && $acountDataFrom['IS_ACTIVE'] === true);
		
		$acountDataTo = MU_SearchOperations::GetAccountData($this->operation->getToUser());
		assert(is_array($acountDataTo) && count($acountDataTo) > 0 && $acountDataTo['IS_ACTIVE'] === true);
		
		$logData = new MU_EntityOperationLogRecord($this->operation->getActionType(), $this->operation->getFromUser()->getDBID());
		$logData->setField('id_user', $this->operation->getFromUser()->getUserID());
		$logData->setField('world_identifier', $this->operation->getFromUser()->getWorldShortName());
		$logData->setField('gc_amount', $this->operation->getSumm()->getAmountGC());
		$logData->setField('sc_amount', $this->operation->getSumm()->getAmountSC());
		$logData->setField('id_action', $this->operation->getActionType()->getActionID());
		$logData->setField('id_account', $acountDataFrom['ID_ACCOUNT']);		
		$logData->setField('id_user_to', $this->operation->getToUser()->getUserID());
		//$logData->setField('id_trade', $this->operation->getAdditionalID());
		
		$listID = MU_SearchOperations::InsertOperationLog($logData);
		assert($listID > 0);
		
		MU_ExecTransfer::Exec($listID, $this->operation);				
	}
}

?>