<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2007/06/07 08:36:01 $';

class MU_ExecLogUserInstanceOperation
{
	static public function Log(GB_MoneyUserInstanceOperation $operation)
	{
		$actionID = self::_logToBase($operation);
		
		self::_logToFile($operation, $actionID);
	}
	
	static private function _logToBase(GB_MoneyUserInstanceOperation $operation)
	{
		//Записали действие
		$actionID = MU_SearchUserInstanceOperations::InsertAction($operation->getActionTypeName(), $operation->getAdditionalID());
		assert(is_int($actionID) && $actionID > 0);
		
		//Снимаем деньги 
		MU_SearchUserInstanceOperations::InsertBalance($actionID, -$operation->getSumm()->getAmount(), $operation->getSumm()->getCreditName(), $operation->getFromUserInstance());
		
		//Приписываем деньги
		if($operation->havePartner())
		{
			MU_SearchUserInstanceOperations::InsertBalance($actionID, $operation->getSumm()->getAmount(), $operation->getSumm()->getCreditName(), $operation->getToUserInstance());			
		}		
		
		return $actionID;
	}
	
	static private function _logToFile(GB_MoneyUserInstanceOperation $operation, $actionID)
	{		
		self::_writeString($operation, $operation->getFromUserInstance(), -$operation->getSumm()->getAmount(), $actionID);		
		
		if($operation->havePartner())
		{
			self::_writeString($operation, $operation->getToUserInstance(), $operation->getSumm()->getAmount(), $actionID);
		}
	}
	
	static private function _writeString(GB_MoneyUserInstanceOperation $operation, DE_UserInstance $userInstance, $summ, $actionID)
	{
		$nowDate = GB_TypeFactory::CreateBase('GB_Date', time());

		$world = DE_World::CreateForCurrentWorld();
			
		$str = $nowDate->getPgFormat()." User: ".$userInstance->getUser()->getName()."(".$world->getShortName().") Action: ".$operation->getActionTypeName()."(".$actionID.") OptionID: ".$operation->getAdditionalID()." Summ: $summ".$operation->getSumm()->getCreditName()."\n";
		
		SU_DebugTools::WriteToLog($str, 'MoneyOperationsUserInstance.log');
	}
		
	
}

?>