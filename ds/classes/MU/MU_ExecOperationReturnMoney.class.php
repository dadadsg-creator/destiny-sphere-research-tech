<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_ExecOperationReturnMoney extends MU_ExecOperation
{
	static private $allowedActionTypes = array(
			'PS_RETURN_MONEY_EXCHANGE_COORDS', 
			'PS_RETURN_MONEY_EXCHANGE_COORDS_MELS'
		);
	
	public function Exec()
	{							
		assert($this->operation->getToUser()->getUserID() != 0);
		assert($this->operation->getFromUser()->getUserID() == 0);
		
		assert($this->operation->getFromUser()->getWorldShortName() == $this->operation->getToUser()->getWorldShortName());
		assert(in_array($this->operation->getActionType()->getActionName(), self::$allowedActionTypes));
		
		if($this->operation->getActionType()->isHavePrice())
		{
			$this->operation->setSummFromActionPrice();
		}
		
		$acountData = MU_SearchOperations::GetAccountData($this->operation->getFromUser());
		assert(is_array($acountData) && count($acountData) > 0);
		
		$logData = new MU_EntityOperationLogRecord($this->operation->getActionType(), $this->operation->getFromUser()->getDBID());
		$logData->setField('id_user', $this->operation->getFromUser()->getUserID());
		$logData->setField('world_identifier', $this->operation->getFromUser()->getWorldShortName());
		$logData->setField('gc_amount', $this->operation->getSumm()->getAmountGC());
		$logData->setField('sc_amount', $this->operation->getSumm()->getAmountSC());
		$logData->setField('id_action', $this->operation->getActionType()->getActionID());
		$logData->setField('id_account', $acountData['ID_ACCOUNT']);
		$logData->setField('id_user_to', $this->operation->getToUser()->getUserID());
				
		$listID = MU_SearchOperations::InsertOperationLog($logData);
		assert($listID > 0);
		
		MU_ExecTransfer::Exec($listID, $this->operation);		
	}	
}

?>