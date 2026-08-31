<?php $CVS_Version = '$Revision$ ; $Date$';

class UA_PowerShildActivate extends UA_Action
{
	/**
	 * Объект инстанса игрока
	 *
	 * @var DE_UserINstance
	 */	
	private $userInstance;
	
	/**
	 * Время акьтвации щита в секундах
	 *
	 * @var integre
	 */
	private $activationTime;
	
	public function __construct()
	{
		UA_Action::UA_Action ();
		
		$this->userInstance = DE_UserInstance::CreateFromID((int)$this->uiid);
		
		$this->activationTime = DE_WorldSettings::CreateForCurentWorld()->get('PS_ACTIVATE_TIME') * DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
	}
	
	public function checkLocalParams() 
	{		
		return true;
	}
	
	public function checkAction ()
	{		
		//Проверяем есть ли вобще здание
		$buildingData = DS_Building::GetAttachedBuildingByCombIDAndBuildingID((int)$this->comb, 66);
		assert(is_array($buildingData) && count($buildingData) > 0);
		
		//Проверяем можно ли сейчас жать активацию
		$nextStatusData = DS_Freez::GetPowerShildNextStatus($this->userInstance->getID(), false);
		assert(is_array($nextStatusData) && count($nextStatusData) > 0);

		assert(key_exists('tostatusname', $nextStatusData[0]));
		$nextStatusName = $nextStatusData[0]['tostatusname'];
		
		assert($nextStatusName == 'ACTIVATING');
		
		return true;
	}
	
	public function performAction()
	{				
		
		$startDate = GB_TypeFactory::CreateBase('GB_Date', time());
		$endDate = GB_TypeFactory::CreateBase('GB_Date', time() + $this->activationTime);
		
		DS_Freez::ChangePowerShildStatusToNext ($this->userInstance->getID(), $startDate, $endDate, false);	
		
		//Пишем мессагу о том что стартонули		
		$messparams = array();
		DE_SystemMessages::AddMessageFromAction(__CLASS__, 'PS_START_ACTIVATION', $messparams, 0);	
		
		return true;	
	}
}

?>