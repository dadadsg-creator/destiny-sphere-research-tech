<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс получения вознаграждения
 *
 */
class UA_GetRewards extends UA_Action
{
	/**
	* Инстансе текущего пользователя
	*
	* @var DE_UserInstance
	*/
	private $userInstance;

	function __construct()
	{
		$this->UA_Action();
	}//END OF CONSTRUCTOR

	function checkLocalParams ()
	{
		return true;
	}//END OF FUNCTION
	
	function checkAction ()
	{
		return true;
	}//END OF FUNCTION
	
	function performAction ()
	{
		// Получаем трофеи игрока
		$reward = DE_Reward::CreateFromUserInstanceID((int)$_SESSION['uiid']);
		
		$message = '';

		// Вознаграждение
		foreach(GB_ResourcesList::$allResources as $k => $resourceName)
		{
			$rewardRes = $reward->getReward($resourceName);

			if ($rewardRes > 0) {
//SU_DebugTools::LogAll($resourceName.' '.$reward, 'Annita.log');		
				// Вписываем русурсы на текущую соту
				DE_CombAttributes::CreateFromCombID((int)$this->comb)->deltaAttribute('CURRENT_AMOUNT', $resourceName, (float)$rewardRes);
				
				// Уменьшаем значение трофея
				$reward->deltaReward($resourceName, -(float)$rewardRes);
				
				$message .= number_format($rewardRes, 0, ",", "'")." ".DE_Resource::CreateFromName($resourceName)->getLocolizedShortName()."<br>";

				
				// Вписываем историю
				//DS_Reward::InsertRewardHytory();
			}
		}
		
		// Если мы хоть что-то получили
		if (!empty($message)) {
			// Вы получили трофеи в размере:
			$message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('YOU_GET_REWARD', array(), null, null, false).'<br>'.$message;
		} else {
			// У вас нет трофеев для сбора
			$message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NO_REWARD', array(), null, null, false);
		}
		
		// Рефешим соту
		
		if(!$this->refresh(2, "", "", 1))
		{
			return false;
		}
			
			
		/*
		$menu = new PN_BattleOverview();
		$menu = new PN_SpyAction();

		$menu->setBID(33);
		$menu->colony = array();
		$menu->spyActionTypes = array();
		//$menu->setTAB('PN_SpyAction');
		$menu->setTAB('PN_SpyLogs');

		assert($menu->getPanel(true));
		*/
		
		
		// Выводим алерт о вознагражднии 
		$title = ""; 
	
		SU_OutputHandler::Singleton()->addReadyString('outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf" title="'.$title.'"><txt><![CDATA['.$message.']]></txt></swloader>');
		
		return true;
	}//END OF FUNCTION
}

?>