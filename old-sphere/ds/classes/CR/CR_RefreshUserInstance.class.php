<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Контроллер обновления инстанса
 */
class CR_RefreshUserInstance extends BS_Base
{	
	/**
	 * Объект с данными для обновления
	 *
	 * @var CR_RefreshUserInstanceCommand
	 */
	private $command;
	
	/**
	 * UIID уже залоченные на рефреш
	 *
	 * @var unknown_type
	 */
	static private $lockUIIDs = array();			
	
	public function __construct(CR_RefreshUserInstanceCommand $command)
	{
		$this->command = $command;
	}	
	
	/**
	 * Основной метод 
	 *
	 * @return boolean
	 */
	public function Exec()
	{			
		
		if($this->command->getWithEvents() == false && $this->command->getWithTurnCalc() == false)
		{
			return true;
		}		
		
		if(GL_IsUserInVocationMode::Test($this->command->getUserInstance()) || DE_PowerShild::CreateFromUserInstance($this->command->getUserInstance()->getID())->isWorks())
		{	
			return true;
		}
		
		$this->_lock();
		
		if (key_exists('refreshedUiid', $GLOBALS['params'])) {
			$currentUid = $GLOBALS['params']['refreshedUiid'];
		} else {
			$currentUid = 0;
		}
		
		
		// Устанавливаем в глобальные параметры id игрока, которого рефрешим
		$GLOBALS['params']['refreshedUiid'] = $this->command->getUserInstance()->getID();
		
		$this->_initUnitsDeathLog();
					
		if($this->command->getWithEvents())
		{
			$this->_events();
		}
		
		if($this->command->getWithTurnCalc())
		{
			$this->_resources();
		}		
		
		// Восстанавливаем на место старого игрока
		$GLOBALS['params']['refreshedUiid'] = $currentUid;
		
		return true;
	}
	
	/**
	 * Инициализация объектов для логирования помирания юнитов на сотах
	 *
	 */
	private function _initUnitsDeathLog()
	{
		if($this->command->getIsExternalRefresh())
		{			
			$checkDate = $this->command->getCheckDate();
		}else{
			$checkDate = GB_TypeFactory::CreateBase('GB_Date', time());
		}
		
		$collection = DC_UserInstanceCombs::CreateFromUserInstanceID($this->command->getUserInstance()->getID());
		$collection->resetNext();	
		while($comb = $collection->next())
		{						
			$deathLog = DE_LogUnitsDeathOnComb::CreateFromID($comb->getID());
			$deathLog->refreshData($checkDate);
		}
	}
	
	/**
	 * Локинг инстанса
	 *
	 */
	private function _lock()
	{					
		if(in_array($this->command->getUserInstance()->getID(), self::$lockUIIDs))
		{
			return;
		}				
		
		if(!SU_Locker::CerateForCurrentDB('USERACTION')->lockWithTest($this->command->getUserInstance()->getID()))
		{
			DS_ClientLockHandler('USER_LOCK_DELAY', constant('USER_LOCK_DELAY'), $this->command->getUserInstance()->getID(), 'USERACTION');
		}
		
		self::$lockUIIDs[] = $this->command->getUserInstance()->getID();		
	}
	
	/**
	 * Обработка евентов
	 *
	 */
	private function _events()
	{
		$eventMonitor = ME_EventMonitor::CreateFromUserInstance($this->command->getUserInstance());
				
		if($this->command->getIsExternalRefresh())
		{
			$eventMonitor->setExternalRefreshMode($this->command->getCheckDate(), $this->command->getExternalEvent());	
		}
		
		if($this->command->getIsPartialRefresh())
		{
			$eventMonitor->setPartialRefreshMode($this->command->getCheckDate());
		}
		
		$eventMonitor->scan();
	}
	
	/**
	 * Обработка ресурсов
	 *
	 */
	private function _resources()
	{
		if($this->command->getIsExternalRefresh())
		{			
			$checkDate = $this->command->getCheckDate();
		}else{
			$checkDate = GB_TypeFactory::CreateBase('GB_Date', time());
		}
		
		$collection = DC_UserInstanceCombs::CreateFromUserInstanceID($this->command->getUserInstance()->getID());
		$collection->resetNext();	
		while($comb = $collection->next())
		{						
			ME_TurnCalc::Count($comb, $checkDate);							
		}
		
		$this->command->getUserInstance()->setLastRefresh($checkDate);
	}
	
}//END OF CLASS
?>