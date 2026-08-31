<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс показа красной кнопки
 *
 */
class UA_GetRedBatton extends UA_Action 
{
	
	/**
	 * Данные для транспортного слоя
	 *
	 * @var TI_RedButton
	 */
	private $transportInput;
	
	/**
	 * Инстансе текущего пользователя
	 *
	 * @var DE_UserInstance
	 */
	private $userInstance;
	
	/**
	 * Максимальная дата окончания действия армии
	 *
	 * @var GB_Date
	 */
	private $requestMaxDate;
	
	/**
	 * Инстанс пользователя свазаные с которым действия мы извлекаем.
	 *
	 * @var DE_UserInstance
	 */
	private $requestUserInstance;
	
	/**
	 * Пользовател свазаные с которым действия мы извлекаем.
	 *
	 * @var DE_User
	 */
	private $requestUser;
	
	/**
	 * Имя того человека для кого фильтруем
	 *
	 * @var string
	 */
	private $requestUserName = '';
	
	/**
	 * Массив с ID соты для которых осужествляем выборку
	 *
	 * @var array
	 */
	private $requestCombsArray = array();
	
	/**
	 * Тип дейтсвий который нас интересует
	 *
	 * @var array
	 */
	private $requestTypes = array();
	
	/**
	 * Тот период который передал клиент, кторый надо вернуть обратно
	 *
	 * @var GB_timePeriod
	 */
	private $requestMaxDelta;
	
	function __construct()
	{
		$this->UA_Action();
		
	}//END OF CONSTRUCTOR
	
	function checkLocalParams ()
	{
		$this->requestUserName = pg_escape_string($this->root->get_attribute("name"));
		if(!empty($this->requestUserName))
		{
			//Проверка на существование имени пользователя который передан в зарпосе. 
			$userID = DS_User::GetUserIDByName($this->requestUserName);
			if($userID > 0)
			{
				$this->requestUser = DE_User::CreateFromID($userID);
			
				$requestUIID = DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID($this->requestUser->getID(), DE_World::CreateForCurrentWorld()->getID());
				assert($requestUIID > 0);
			
				$this->requestUserInstance = DE_UserInstance::CreateFromID($requestUIID); 
			}
		}
		
		$colonysString = $this->root->get_attribute("colony");
		if(!empty($colonysString))
		{
			$this->requestCombsArray = explode(',', $colonysString);
			
			foreach ($this->requestCombsArray as $k=>$v)
			{
				assert($v > 0);
			}
		}
		
		$types = pg_escape_string($this->root->get_attribute("types"));
		if(!empty($types))
		{
			$this->requestTypes = explode(',', $types);
			
			foreach ($this->requestTypes as $k=>$v)
			{
				assert(!empty($v));
			}
		}
		
		$maxDelta = (int)$this->root->get_attribute("date");
		if(!empty($maxDelta))
		{
			$this->requestMaxDate = GB_TypeFactory::CreateBase('GB_Date', time() + $maxDelta);
			$this->requestMaxDelta = GB_TypeFactory::CreateBase('GB_TimePeriod', $maxDelta);
		}
		
		return true;
	}//END OF FUNCTION
	
	function checkAction ()
	{
		return true;
	}//END OF FUNCTION
	
	function performAction ()
	{
		//Объект текущего инстанса
		$this->userInstance = DE_UserInstance::CreateFromID($this->uiid);
		
		//Создаем класс с данными для трнаспотрного слоя
		$this->transportInput = new TI_RedButton();
		
		//Имя пользователя для которого фильтруем
		$this->transportInput->setUserName($this->requestUserName);
		
		if($this->requestMaxDate instanceof GB_TimePeriod)
		{
			$periodParts = $this->requestMaxDelta->getWithParts();
			$this->transportInput->setCurrentTime(implode('.', $periodParts));
		}else{
			$this->transportInput->setCurrentTime('3:23:59:59');	
		}
		
		//Списко сот игрока для филтра
		$this->_formCombsList();
		
		//Списко действия для филтров
		$this->_formActionsList();
		
		//Список армий
		$this->_formArmysList();

		//Вывод протокола 
		SU_OutputHandler::Singleton()->addString(TL_RedButton::FormMenu($this->transportInput), 'xmldata');
		
		return true;
	}
		
	/**
	 * Формирвоание списка армий
	 *
	 */
	private function _formArmysList()
	{	
		$redButtonData = DS_Battle::GetRedButton($this->userInstance, $this->requestCombsArray, $this->requestMaxDate, $this->requestTypes, $this->requestUserInstance);
		
		foreach($redButtonData as $k=>$objectData)
		{
			
			$userFrom = DE_UserInstance::CreateFromID((int)$objectData['id_userinstance_from'])->getUser()->getName();
			$userTo = DE_UserInstance::CreateFromID((int)$objectData['id_userinstance_to'])->getUser()->getName();
			
			$startComb = DE_Comb::CreateFromID((int)$objectData['id_startcomb']);
			$targetComb = DE_Comb::CreateFromID((int)$objectData['id_targetcomb']);
			
			$startDate = GB_TypeFactory::CreateBase('GB_Date', $objectData['actionstarttime']);
			$endDate = GB_TypeFactory::CreateBase('GB_Date', $objectData['actionendtime']);
			
			if($this->userInstance->getID() == $objectData['id_userinstance'])
			{
				if($objectData['actiontypename'] == 'MOVE_TO_ATTACK' || $objectData['actiontypename'] == 'IN_BATTLE')	
				{
					$type = '1';
				}else{
					$type = '3';
				}
			}else{
				$type = '2';
			}
			
			if($objectData['actiontypename'] == 'IN_BATTLE')
			{
				$event = DE_EventQueue::CreateFromArmy(DE_Army::CreateFromID($objectData['id_unitcontainer']));
				$info = 'Round: '.$event->getAttributes()->getAttribute('Round');	
			}else{
				$info = '-';
			}
			
			$this->transportInput->setArmyData(
				(int)$objectData['id_unitcontainer'], 
				$objectData['unitcontainername'], 
				$type, 
				$userFrom, 
				$userTo,
				$info, 
				1, 
				DE_Localization::CreateForCurentLang()->get((int)$objectData['id_redbuttonlocalizetion']), 
				$startComb->getFullName(),
				$targetComb->getFullName(), 
				$startDate->getFormatedFull(DE_User::CreateForCurrentUser()->getTimeDelta()),
				$endDate->getFormatedFull(DE_User::CreateForCurrentUser()->getTimeDelta())
			);
		}
		
		$this->transportInput->setMinTime('0:00:00:00');
		$this->transportInput->setMaxTime('3:23:59:59');
	}
	
	/**
	 * Формирвоание списка сот игрока
	 *
	 */
	private function _formCombsList()
	{
		$userCombsCollection = DC_UserInstanceCombs::CreateFromUserInstanceID($this->userInstance->getID());
		$userCombsCollection->resetNext();
		while($userComb = $userCombsCollection->next())
		{
			if(in_array($userComb->getID(), $this->requestCombsArray) || empty($this->requestCombsArray))
			{
				$isCurr = 1;
			}else{
				$isCurr = 0;
			}
			
			$this->transportInput->setColony($userComb->getID(), $userComb->getName(), $userComb->getFormatedAddress(), $isCurr);					
		}	
	}
	
	/**
	 * Списко действий для филтра
	 *
	 */
	private function _formActionsList()
	{
		$armyActionsList = DS_Army::GetArmyActionsListForRedButton();
		
		foreach($armyActionsList as $k=>$actionData)
		{
			if(in_array($actionData['actiontypename'], $this->requestTypes) || empty($this->requestTypes))
			{
				$isCurr = 1;
			}else{
				$isCurr = 0;
			}
			
			$this->transportInput->setArmyTypes($actionData['actiontypename'], DE_Localization::CreateForCurentLang()->get((int)$actionData['id_redbuttonlocalizetion']), $isCurr);
		}
	}
	
}

?>