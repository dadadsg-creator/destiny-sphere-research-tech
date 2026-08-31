<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_ExecOperationExchange extends MU_ExecOperation
{	
	static private $exchangeCourse;
	
	public function Exec()
	{
		
		assert($this->operation->getSumm()->getAmountGC() > 0);
		assert($this->operation->getSumm()->getAmountSC() == 0);
		
		assert($this->operation->getFromUser()->getUserID() != 0);
		assert($this->operation->getToUser()->getUserID() == 0);		
		
		assert($this->operation->getActionType()->getActionName() == 'EXCHANGE_CREDITS');
		
		self::$exchangeCourse = (int)constant('GC_COURSE');
		assert(self::$exchangeCourse > 0);
		
		$acountData = MU_SearchOperations::GetAccountData($this->operation->getFromUser());
		assert(is_array($acountData) && count($acountData) > 0  && $acountData['IS_ACTIVE'] === true);
		
		
		$logData = new MU_EntityOperationLogRecord($this->operation->getActionType(), $this->operation->getFromUser()->getDBID());
		$logData->setField('id_user', $this->operation->getFromUser()->getUserID());
		$logData->setField('world_identifier', $this->operation->getFromUser()->getWorldShortName());
		$logData->setField('gc_amount', $this->operation->getSumm()->getAmountGC());
		$logData->setField('sc_amount', $this->operation->getSumm()->getAmountSC());
		$logData->setField('id_action', $this->operation->getActionType()->getActionID());
		$logData->setField('id_account', $acountData['ID_ACCOUNT']);
		$logData->setField('exchenge_course', self::$exchangeCourse);
		
		$listID = MU_SearchOperations::InsertOperationLog($logData);
		assert($listID > 0);
		
		MU_ExecTransfer::Exec($listID, $this->operation);		
		
		$backAmount = self::$exchangeCourse * $this->operation->getSumm()->getAmountGC();
		$backSumm = new MU_EntityMoneySumm($backAmount, 'SC');
		
		$newOperation = new MU_EntityOperation($this->operation->getActionType(), $backSumm, $this->operation->getToUser(), $this->operation->getFromUser());
		
		MU_ExecTransfer::Exec($listID, $newOperation);		
	}	
}
?>