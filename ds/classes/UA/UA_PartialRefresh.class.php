<?php

/**
 * Частичниый рефреш, вызывается ТОЛЬКО из командной строки демоном обновления
 *
 */
class UA_PartialRefresh extends UA_Action 
{
	/**
	 * Время до которого рефрешим игрока
	 *
	 * @var GB_Date
	 */
	private $checkTime;
	
	function __construct()
	{
		parent::__construct();
	}
	
	function checkLocalParams()
	{
		$checkTime = $this->root->get_attribute("checkTime");
		$this->checkTime = GB_TypeFactory::CreateBase('GB_Date', $checkTime);
		
		assert($this->checkTime->value < time());
	}
	
	function checkAction()
	{
		assert(strpos(php_sapi_name(), 'apache') === false);
	}
	
	function performAction()
	{ 	
		$userInstanceObject = DE_UserInstance::CreateFromID((int)$this->uiid);

		$refreshCommand = new CR_RefreshUserInstanceCommand($userInstanceObject);
		$refreshCommand->setRefreshType(true, true);	
		$refreshCommand->setPartialRefreshMode($this->checkTime);
		
		$refreshController = new CR_RefreshUserInstance($refreshCommand);
		$refreshController->exec();	
	}
}

?>