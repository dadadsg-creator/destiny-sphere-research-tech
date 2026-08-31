<?php $CVS_Version = '$Revision$ ; $Date$';

// закладка военной базы - список битв юзера
class PN_BattleOverview extends UA_GetBuildMenu 
{

	public $days = 1;
	
	private $userInstance;
	
	private $maxBattleLogStoreDate = 14;
	 
	function __construct()
    {
    	UA_GetBuildMenu::UA_GetBuildMenu ();
    }

    function getPanel () 
    {

    	global $params;
        global $common;
				
        $this->userInstance = DE_UserInstance::CreateFromID((int)$params['uiid']);
                
        $this->maxBattleLogStoreDate = DE_WorldSettings::CreateForCurentWorld()->get("BATTLE_FULL_STORE_PERIOD_DAYS");
        
        // корректировка времени
        $delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();
		
        if(empty($this->status))
        {
        	$this->days = $this->maxBattleLogStoreDate;
        	$this->fromDays = DS_Battle::GetLastBattleDayToUserInstance($this->userInstance->getID());
        }
        
        settype($this->days, 'integer');
        settype($this->fromDays, 'integer');
        
        if($this->days < 1)
        {
        	$this->days = 1;
        }elseif($this->days > $this->maxBattleLogStoreDate){
        	$this->days = $this->maxBattleLogStoreDate;
        }
        
        if($this->fromDays < 1)
        {
        	$this->fromDays = 1;
        } elseif($this->fromDays > $this->maxBattleLogStoreDate) {
        	$this->fromDays = $this->maxBattleLogStoreDate;
        }
        
        if($this->fromDays > $this->days)
        {
        	$this->fromDays = $this->days;
        }
        
        if(empty($this->status))
        {
        	$this->status = 'ALL';
        } else {
        	assert($this->status == 'A' || $this->status == 'D' || $this->status == 'ALL');
        }
        
        if(!key_exists('BATTLE_OVERVIEW', $_SESSION))
        {
        	$_SESSION['BATTLE_OVERVIEW'] = array();
        }
        
        //Елси етсь в сесии то берем оттуда
        //РЕсетиться кеш при рефреше
        /*if(key_exists($this->days, $_SESSION['BATTLE_OVERVIEW']))
        {
        	$str = $_SESSION['BATTLE_OVERVIEW'][$this->days];
        	SU_OutputHandler::Singleton()->addString( $str , 'xmldata' );			        
        	return true;
        }*/
        
        // Кнопка отступления
        $allowRetreat = DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_RETREAT_ALLOW');
        
        // Кнопка трофеев
        $allowReward = 0;
        
        $reward = DE_Reward::CreateFromUserInstanceID((int)$_SESSION['uiid']);
        foreach(GB_ResourcesList::$allResources as $k => $resourceName)
		{
			$rewardRes = $reward->getReward($resourceName);

			if ($rewardRes > 0) {
				$allowReward = 1;
				break;
			}
		}
        
        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><battleoverview ra=\"".$allowRetreat."\" reward=\"$allowReward\" />";
        $doc = domxml_open_mem ( $xml );
        $root = $doc->document_element ();                
        
        $ch = $root->new_child("filter");
        
        $ch->set_attribute("fromday", $this->fromDays);
        $ch->set_attribute("showdays", $this->days);
        $ch->set_attribute("status", $this->status);
        $ch->set_attribute("winner", $this->winner);

        
        $chOpp = $ch->new_child('opp');
        $oponents = DS_Battle::GetBattleOppenentsToUserInstance($this->userInstance->getID());   
        $opRes = array();
        foreach($oponents as $k => $oponent)
        {
        	$opRes[] = $oponent['username'];
        }
        $opp = $doc->create_text_node(implode(',', $opRes));
        $chOpp->append_child ( $opp );
        
        $chSot = $ch->new_child ( "sot" );
        $combs = DS_Battle::GetBattleCombsToUserInstance($this->userInstance->getID());
        $combRes = array();
        foreach($combs as $k => $comb)
        {
        	$combRes[] = $comb['coords'];
        }
        $sot = $doc->create_text_node(implode(',', $combRes));
        $chSot->append_child ( $sot );
        
        $chDates = $ch->new_child ( "dates" );
        $chDates->set_attribute("cur", $this->fromDays."-".$this->days);
        //$dates = DS_Battle::GetBattleDatesToUserInstance($this->userInstance->getID());
        $dates = $doc->create_text_node($this->_getBattleDates());        
        
        $chDates->append_child ( $dates );
        
        // список битв
        $battlesArray = $this->_getBattleList();
        
        foreach($battlesArray as $k => $v) 
        {               
        	$battleID = (int)$v['id_battle'];
        	assert($battleID > 0);
        	
        	$roundsCount = (int)$v['roundscount'];
        	
        	$battle = DE_Battle::CreateFromID($battleID);
        	
         	$ch = $root->new_child ( "battle" );                
         	                
          	$ch->set_attribute("id", $battle->getID());          	
            $ch->set_attribute("starttime", $battle->getStartDate()->getFormatedFull($delta));      
            
            if(($battle->getEndDate() instanceof GB_DateNull) && GL_CanRetreatFromBattle::Check($battle, $this->userInstance))
            {
            	$ch->set_attribute ( "ra", 1);            	
            }else{            	
            	if(!($battle->getEndDate() instanceof GB_DateNull))
            	{
            		$ch->set_attribute ( "endtime", $battle->getEndDate()->getFormatedFull($delta));	
            	}            	
            	$ch->set_attribute ( "ra", 0);            	
            }
            
            $ch->set_attribute ( "battlecomb", $battle->getComb()->getName()." ".$battle->getComb()->getFormatedAddress());
                
            $ch->set_attribute ( "rc", $roundsCount-1);
                        
            $userBattleParticipant = $battle->getBattleParticipantBy($this->userInstance);
			if($userBattleParticipant->getParticipantFlag('PG') == 'D')
			{
				$ch->set_attribute ( "opponent", $battle->getAttacker()->getUserInstance()->getUser()->getName()." ".$battle->getFromComb()->getFullName());
            	$ch->set_attribute ( "opponent_race", $battle->getAttacker()->getUserInstance()->getRaceID());
			}else{
				$ch->set_attribute ( "opponent", $battle->getDefender()->getUserInstance()->getUser()->getName());
            	$ch->set_attribute ( "opponent_race", $battle->getDefender()->getUserInstance()->getRaceID());
			}
			
			$ch->set_attribute ( "ranking", round($userBattleParticipant->getBattleXP(), 5));
			$ch->set_attribute ( "battleaction", $userBattleParticipant->getParticipantFlag('PG'));
						
			
			switch($battle->getWinnerCode()->value)
			{
				case 'D':
					$ch->set_attribute ( "winner", $battle->getDefender()->getUserInstance()->getUser()->getName());
					break;
				case 'A':
					$ch->set_attribute ( "winner", $battle->getAttacker()->getUserInstance()->getUser()->getName());
					break;
			}
			                               			
        }    
        
        if(empty($this->attachedbid))
        {
        	$this->bid = 26;
        	$this->getBuildingTabs($root, $this->bid, $params["lang"], $this->tab, true);	
        }else{
        	$this->getBuildingTabs($root, $this->bid, $params["lang"], $this->tab);	
        }
        
        
        $str = $doc->dump_mem();
        
        //Кешируем в сессию
        //$_SESSION['BATTLE_OVERVIEW'][$this->days] = $str;
        
        SU_OutputHandler::Singleton()->addString($str , 'xmldata');			        
        return true;
	}
	
	private function _getBattleDates()
	{
		$startDate = time() - $this->maxBattleLogStoreDate * 86400;
		$endDate = time();
		
		$startMonthNum = date('n', $startDate) + 1;
		$endMonthNum = date('n', $endDate) + 1;
		
		$dates = array();
		
		if($startMonthNum > $endMonthNum)
		{
			$startMonthNum -= 12;
		}
		
		for($i=$startMonthNum; $i<=$endMonthNum; $i++)
		{
			if($startMonthNum == $i)
			{
				$startDay = date('j', $startDate) + 1;
			} else {
				$startDay = 1;
			}
			
			if($endMonthNum == $i) {
				$endDay = date('j', $endDate);
			} else {
				$endDay = date('t', mktime(0, 0, 0, $i, 0, 0));
			}
			
			$dates[] = strtoupper(date("M", mktime(0, 0, 0, $i, 0, 0)))."A:".$startDay."-".$endDay;
		}
		
		$dates = implode($dates, ',');
		
		return $dates;
	}
	
	private function _getBattleList()
	{
		$backDelta = ($this->maxBattleLogStoreDate - $this->fromDays) * 86400;
        $daysDelta = ($this->maxBattleLogStoreDate - $this->days) * 86400;
        
        // корректировка времени
        $delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();
        
        $startDate = GB_TypeFactory::CreateBase('GB_Date', mktime(-$delta, 0, 0, date('n'), date('j'), date('Y')));
        $startDate->value -= $backDelta;
        
        if($daysDelta == 0)
        {
        	$time = time();
        } else {
        	$time = mktime(23-$delta, 59, 59, date('n'), date('j'), date('Y'));
        }
       	$endDate = GB_TypeFactory::CreateBase('GB_Date', $time);
        $endDate->value -= $daysDelta;
        
//SU_DebugTools::LogAll("DAYS DELTA : $daysDelta\nDELTA: $delta\nSTART : ".$startDate->getPGFormat()."\nEND : ".$endDate->getPGFormat(), __CLASS__, 0);

        if($this->status == 'ALL' || $this->combs == 'MY')
        {
        	$ownComb = null;
        } elseif($this->status == 'A') {
        	$ownComb = false;
        } else {
        	$ownComb = true;
        }
        
        if(empty($this->oponents) || $this->oponents == 'ALL')
        {
        	$oponents = null;
        } else {
        	$oponents = explode(',', $this->oponents);
        }
        
        if((empty($this->winner) && $this->winner !== '0') || $this->winner == 'ALL')
        {
        	$winner = null;
        } else {
        	$winner = (int)$this->winner;
        }

/*
        $ownComb = true;
        $oponents = array('40Min','Anna');
        $this->combs = '2.5.1,2.1.2,2.1.1';
        $winner = true;
*/
        
        if(empty($this->combs) || $this->combs == 'ALL' || $this->combs == 'MY')
        {
        	$battleCombs = null;
        } else {
        	$battleCombsCoords = explode(',', $this->combs);
        	$battleCombs = array();
        	foreach ($battleCombsCoords as $k => $coords) 
        	{
        		list($ring, $complex, $comb) = explode('.', $coords);
        		settype($ring, 'integer');
        		settype($complex, 'integer');
        		settype($comb, 'integer');
        		assert($ring > 0);
        		assert($complex > 0);
        		assert($comb > 0);
        		
        		$comb = DE_Comb::CreateFromCoordinates($_SESSION['world'], $ring, $complex, $comb);
        		$battleCombs[] = $comb->getID();
        	}
        	
        	if(count($battleCombs) == 0)
        	{
        		$battleCombs = null;
        	}
        }
        
        $battleList = DS_Battle::GetBattlesToUserInstanceForBattleOverview($this->userInstance->getID(), $startDate->getPgFormat(), $endDate->getPgFormat(), $winner, $ownComb, $oponents, $battleCombs);
        
        return $battleList;
	}
}

?>
