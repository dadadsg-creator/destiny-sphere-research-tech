<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_ExecOperationReserv extends MU_ExecOperation
{
	public function Exec()
	{
		
		assert($this->operation->getToUser()->getUserID() == 0);
		
		assert($this->operation->getActionType()->getActionName() == 'MOVE_TO_RESERV');
		
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
		$logData->setField('id_reserv_reason', 1);
		$logData->setField('expire_date', $this->operation->getExpireDate()->getPgFormat());		
		
		$listID = MU_SearchOperations::InsertOperationLog($logData);
		assert($listID > 0);
		
		MU_ExecTransfer::Exec($listID, $this->operation);		
	}	
}



?>