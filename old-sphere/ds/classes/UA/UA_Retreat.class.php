<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Экшен оступления из боя
 *
 */
class UA_Retreat extends UA_Action 
{
	/**
	 * Массив со спиком ID боев из которых отсупаем
	 *
	 * @var array
	 */
	private $battlesID = array();
	
	private $helperHeader = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<updatebattleoverview ra=\"1\">";
	
	private $helperContent = array();
	
	private $helperFooter = '</updatebattleoverview>';
	
	public function __construct() 
	{
		parent::UA_Action();				
	}
	
	// проверяем пришедшие параметры
	public function checkLocalParams () 
	{		
		// id батлов на отмену
		$this->battlesID = explode(',', $this->root->get_attribute("id_battle"));
		foreach($this->battlesID AS $k=>$battleID)
		{
			settype($battleID, 'integer');
			assert($battleID > 0);
			
			$this->battlesID[$k] = $battleID;
		}
		
		assert(count($this->battlesID) > 0);
		
		if(!$_SESSION['profi_game'])
		{
			assert(count($this->battlesID) == 1);
		}
		
		return true;
	}
	
	// проверяем допустимость действия
	public function checkAction () 
	{						
		return true;
	}

	public function performAction ()
	{
		//Рефрешим игока что бы бои пришли в актуальное состояние
		$this->_refresh();

		foreach($this->battlesID AS $k=>$battleID)
		{
			assert(is_int($battleID) && $battleID>0);
			$battle = DE_Battle::CreateFromID($battleID);
			
			$this->_execRetreat($battle);
			$this->_formHelperRow($battle);
		}
		
		$this->_sendHelper();
		
		return true;
	}
	
	private function _execRetreat(DE_Battle $battle)
	{	
		
		if(!GL_CanRetreatFromBattle::Check($battle, DE_UserInstance::CreateFromID($this->uiid)))
		{
			$messparams = array(GL_CanRetreatFromBattle::$errorCode);		
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'RETREAT_ERROR', $messparams, 0);
			
			return;
		}
				
		//Лочим паралельное отступление
		$lockActionID = __CLASS__."_".$battle->getID();		
		if(!SU_Locker::CerateForCurrentDB('ACTION')->lockWithTest($lockActionID))
		{
			DS_ClientLockHandler('USER_LOCK_DELAY', constant('USER_LOCK_DELAY'), $lockActionID, 'ACTION');
		}
		
		$battleParticipant = $battle->getBattleParticipantBy(DE_UserInstance::CreateFromID((int)$this->uiid));

		$roundsCollection = $battle->getBattleRoundCollection();		
		assert($roundsCollection->haveCurrentRound());		
		$currentRound = $roundsCollection->getCurrentRound();
		
		$retreatParticipantTypeCode = GB_TypeFactory::CreateBase('GB_BattleParticipantTypeCode', $battleParticipant->getParticipantFlag('PG'));
		ME_RetreatBattle::Exec($currentRound, $retreatParticipantTypeCode);
		
		
		$messparams = array();		
		DE_SystemMessages::AddMessageFromAction(__CLASS__, 'RETREAT_OK', $messparams, 0);		
	}
	
	private function _formHelperRow(DE_Battle $battle)
	{
		
		$delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();
		
		$id = $battle->getID();
		
		$startDateLiteral = $battle->getStartDate()->getFormatedFull($delta);
		
		if($battle->getEndDate() instanceof GB_DateNull)
		{
			$endDateLiteral = "";	
		}else{
			$endDateLiteral = $battle->getEndDate()->getFormatedFull($delta);
		}
		
		$battleCombFullName = $battle->getComb()->getName()." ".$battle->getComb()->getFormatedAddress();
		
		if(GL_CanRetreatFromBattle::Check($battle, DE_UserInstance::CreateFromID((int)$this->uiid)))
		{
			$retreatAllowed = 1;
		}else{
			$retreatAllowed = 0;
		}
		
		$userBattleParticipant = $battle->getBattleParticipantBy(DE_UserInstance::CreateFromID((int)$this->uiid));
		if($userBattleParticipant->getParticipantFlag('PG') == 'D')
		{
			$opponent = $battle->getAttacker()->getUserInstance()->getUser()->getName()." ".$battle->getFromComb()->getFullName();
           	$opponentRace = $battle->getAttacker()->getUserInstance()->getRaceID();
		}else{
			$opponent = $battle->getDefender()->getUserInstance()->getUser()->getName();
           	$opponentRace = $battle->getDefender()->getUserInstance()->getRaceID();
		}
			
		$ranking = round($userBattleParticipant->getBattleXP(), 2);
		$battleAction = $userBattleParticipant->getParticipantFlag('PG');
										
		switch($battle->getWinnerCode()->value)
		{
			case 'D':
				$winner = $battle->getDefender()->getUserInstance()->getUser()->getName();
				break;
			case 'A':
				$winner = $battle->getAttacker()->getUserInstance()->getUser()->getName();
				break;
			default:
				$winner = '';
		}
		
		$roundsCount = $battle->getBattleRoundCollection()->collection->countElements() - 1;
						
		$this->helperContent[] = '<battle id="'.$id.'" starttime="'.$startDateLiteral.'" endtime="'.$endDateLiteral.'" battlecomb="'.$battleCombFullName.'" opponent="'.$opponent.'" ra="'.$retreatAllowed.'" opponent_race="'.$opponentRace.'" ranking="'.$ranking.'" battleaction="'.$battleAction.'" rc="'.$roundsCount.'" winner="'.$winner.'" />';
	}
	
	private function _sendHelper()
	{
		if(count($this->helperContent) == 0)
		{
			return;
		}
		
		$helper = $this->helperHeader.implode('', $this->helperContent).$this->helperFooter;
		
		SU_OutputHandler::Singleton()->addString($helper, 'helper');
	}
	
	private function _refresh()
	{
		$refreshCommand = new CR_RefreshUserInstanceCommand(DE_UserInstance::CreateFromID((int)$this->uiid));
		$refreshCommand->setRefreshType(true, true);					
			
		$refreshController = new CR_RefreshUserInstance($refreshCommand);
		$refreshController->exec();									
	}	
}

?>