<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_ExecOperationEnterMoney extends MU_ExecOperation
{
	public function Exec()
	{		
		
		assert($this->operation->getSumm()->getAmountGC() >= 0);
		assert($this->operation->getSumm()->getAmountSC() >= 0);
		
		assert($this->operation->getFromUser()->getUserID() == 0);
		assert($this->operation->getToUser()->getUserID() != 0);
		/*
		assert($this->operation->getFromUser()->getWorldShortName() == 'UC');
		assert($this->operation->getToUser()->getWorldShortName() == 'UC');
		*/
/*	К чёрту излишнюю паранойю	
		assert($this->operation->getActionType()->getActionName() == 'ENTER_MONEY' || 
		$this->operation->getActionType()->getActionName() == 'TURRNIR_PRIZE'
		 || $this->operation->getActionType()->getActionName() == 'REFERRAL_PAYMENT');
*/		
		$acountData = MU_SearchOperations::GetAccountData($this->operation->getToUser());
		assert(is_array($acountData) && count($acountData) > 0);
		
		$logData = new MU_EntityOperationLogRecord($this->operation->getActionType(), $this->operation->getFromUser()->getDBID());
		$logData->setField('id_user', $this->operation->getToUser()->getUserID());
		$logData->setField('world_identifier', $this->operation->getToUser()->getWorldShortName());
		$logData->setField('gc_amount', $this->operation->getSumm()->getAmountGC());
		$logData->setField('sc_amount', $this->operation->getSumm()->getAmountSC());
		$logData->setField('id_action', $this->operation->getActionType()->getActionID());
		$logData->setField('id_account', $acountData['ID_ACCOUNT']);
		$logData->setField('enter_key', $this->operation->getGateKey());		
		
		$listID = MU_SearchOperations::InsertOperationLog($logData);
		assert($listID > 0);
		
		MU_ExecTransfer::Exec($listID, $this->operation);		
	}	
}

?>