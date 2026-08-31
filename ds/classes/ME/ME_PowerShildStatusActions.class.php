<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Действия над статусом Щита
 *
 */

class ME_PowerShildStatusActions
{
	/**
	 * Здесь проверям есть ли вобще запись о щите.
	 * Используеться при каждом открытие менюшки
	 *
	 * @param DE_UserInstance $userInstance
	 */
	static public function ControlShildRecordExists(DE_UserInstance $userInstance)
	{
		$powerShildData = DS_Freez::GetPowerShildRecord($userInstance->getID());
		
		if(!$powerShildData)
		{
			DS_Freez::InsertFirstPowerShildRecord($userInstance->getID());
		}
		
	}
	
	/**
	 * Првоеряем не поменялся ли статус щита на основание того что время вышло
	 * Используеться для меню
	 *
	 * @param DE_UserInstance $userInstance
	 */
	static public function ControlStatusAutoChange(DE_UserInstance $userInstance)
	{
		$powerShildData = DS_Freez::GetPowerShildRecord($userInstance->getID());		
		assert(is_array($powerShildData));		

		assert(key_exists('end_date', $powerShildData));
		$endDate = GB_TypeFactory::CreateBase('GB_Date', $powerShildData['end_date']);
		$currentDate = GB_TypeFactory::CreateBase('GB_Date', time());

		if($endDate->value > $currentDate->value)
		{
			return;
		}
		
		$nexAutoStatus = DS_Freez::GetPowerShildNextStatus($userInstance->getID());
		assert(count($nexAutoStatus) == 0 || count($nexAutoStatus) == 1);
		
		if(count($nexAutoStatus) == 0)
		{
			return;
		}
		
		assert(key_exists('time_count_class', $nexAutoStatus[0]));
		$timeLogicClassName = $nexAutoStatus[0]['time_count_class'];
		
		$timeLogic = new $timeLogicClassName;
		$timeDelta = $timeLogic->Count($userInstance);
		assert($timeDelta > 0);
		
		$newEndDate = GB_TypeFactory::CreateBase('GB_Date', $endDate->value + $timeDelta);
		
		DS_Freez::ChangePowerShildStatusToNext ($userInstance->getID(), $endDate, $newEndDate, true);		
	}
	
	/**
	 * Изменяем статус на слудлующий
	 *
	 * @param DE_UserInstance $userInstance
	 */
	static public function ChangeStatusManual(DE_UserInstance $userInstance, GB_Date $startDate, GB_Date $endDate)
	{
		DS_Freez::ChangePowerShildStatusToNext ($userInstance->getID(), $endDate, $newEndDate, false);
	}
	
}

?>