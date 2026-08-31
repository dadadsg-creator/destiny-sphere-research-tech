<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2009/02/18 09:54:26 $';

/**
 * Меню просмотра логов шпионажа
 *
 */
class PN_SpyLogs extends UA_GetBuildMenu
{
	/**
	 * Enter description here...
	 *
	 * @var TI_SpyLogs
	 */
	private $transportInput;
	
	/**
	 * Enter description here...
	 *
	 * @var GB_Date
	 */
	private $startDate = null;
	
	/**
	 * Enter description here...
	 *
	 * @var GB_Date
	 */
	private $endDate = null;
	
	private $searchUserID = null;
	
	function __construct()
	{
		parent::UA_GetBuildMenu();
	}

	function getPanel($hideTabs = false)
	{
		$this->transportInput = new TI_SpyLogs((int)$this->bid, $this->tab);
		
		$this->_checkFilters();
		
		$this->_getLogs();
		
		$this->_getActions();
		
		$this->_getCombs();
		
		$this->_getTimes();
		
		SU_OutputHandler::Singleton()->addString(TL_SpyLogs::FormMenu($this->transportInput, $hideTabs), 'xmldata');
		
		return true;
	}
	
	private function _checkFilters()
	{
		foreach ($this->colony as $k => $comb)
		{
			settype($this->colony[$k], 'integer');
			assert($this->colony[$k] > 0);
			$this->transportInput->setSelectedColony($this->colony[$k]);
		}
		
		foreach ($this->spyActionTypes as $k => $actionType)
		{
			$action = DE_SpyAction::CreateFromName($actionType);
			$this->transportInput->setSelectedAction($action->getID());
		}
		
		if(VD_UserName::Validate($this->name))
		{
			$uid = DS_User::GetUserIDByName($this->name);
			if($uid > 0)
			{
				$user = DE_User::CreateFromID($uid);
				$this->searchUserID = $uid;
				$this->transportInput->setUserName($user);
			}
		}
		
		if($this->dateTo > $this->dateFrom)
		{
			$this->startDate = GB_TypeFactory::CreateBase('GB_Date', time() - $this->dateTo);
			$this->endDate = GB_TypeFactory::CreateBase('GB_Date', time() - $this->dateFrom);
			$period = GB_TypeFactory::CreateBase('GB_TimePeriod', $this->dateTo - $this->dateFrom);
			$this->transportInput->setTime($period);
		}
	}
		
	private function _getLogs()
	{
		$logs = DS_SpyBattle::GetUserLogs(DE_UserInstance::CreateFromID((int)$this->uiid), $this->spyActionTypes, $this->colony, $this->searchUserID, $this->startDate, $this->endDate);
		foreach ($logs as $k => $log)
		{
			$this->transportInput->addLog(DE_SpyLog::CreateFromID((int)$log['id_spy_log']));
		}
	}
	
	private function _getActions()
	{
		$this->transportInput->addAction(DE_SpyAction::CreateFromName('RESEARCH'));
		$this->transportInput->addAction(DE_SpyAction::CreateFromName('DEFEND'));
	}
	
	private function _getCombs()
	{
		$combs = DC_UserInstanceCombs::CreateFromUserInstanceID((int)$this->uiid);
		$combs->resetNext();
		while($comb = $combs->next())
		{
			$this->transportInput->addComb($comb);
		}
	}
	
	private function _getTimes()
	{
		$this->transportInput->setTimeMin('0:00:00:00');
		$this->transportInput->setTimeMax('5:23:59:59');
	}
}


?>