<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Обработчик ивента боя.
 *
 */
class EH_BattleRound extends EH_Army
{   
    private $roundNumber;

    private $round;
    
    function __construct()
    {
        parent::__construct();  
    }

    public function handleEvent($eqid)
    {
        if(!parent::handleEvent($eqid))
        {
            return true;
        }

        $battleID = (int)$this->eventparams["ID_Battle"];
        $this->roundNumber = (int)$this->eventparams["Round"];

        assert($battleID > 0);
        assert($this->roundNumber > 0);
        
        $this->battle = DE_Battle::CreateFromID($battleID);
        $this->round = DE_BattleRound::CreateFromBattleAndNumber($this->battle, $this->roundNumber);
        if ($this->round==NULL) {
            $this->setTrash();
            return;
        }

        //Если рефреш проводит не хозяин соты на которой бой то внешний рефреш делаем
        if($GLOBALS['params']['refreshedUiid'] != $this->combTo->getUserInstance()->getID())
        {           
            assert($this->refreshUserinstance($this->combTo->getUserInstance()->getID(), 1, $this->eqid));      
        }                               
                
        //создаем контроллер боя
        $this->battleController = CR_Battle::CreateFromBattle($this->battle, $this->army, $this->combFrom, $this->combTo, $this->eventEndTimeObject);
        
        //Сомтрим сколько сил в стазисе приползло в качестве подкрепления
        $delta = DE_ReinforcementMarks::CreateFromID($this->combTo->getID())->get($this->eventStartTimeObject, $this->eventStartTimeObject);
        if($delta > 0)
        {
            $this->round->deltaAdds($delta);
        }
        
        //Логирование системное стартуем для боя
        DE_BattleSystemLog::StartWork($this->battle);
        
        $strTmp  = "\n======== BATTLE_ROUND ID = ".$this->eqid."\n";
        $strTmp .= "START_DATE = ".$this->eventStartTimeObject->getPgFormat()."\n";
        $strTmp .= "END_DATE = ".$this->eventEndTimeObject->getPgFormat()."\n";
        $strTmp .= "ARMY = ".$this->army->getName()." (".$this->army->getID().") of user ".$this->army->getUserInstance()->getUser()->getName()."\n";
        $strTmp .= "COMB_FROM = ".$this->combFrom->getFullName()." (".$this->combFrom->getID().") of user ".$this->combFrom->getUserInstance()->getUser()->getName()."\n";
        $strTmp .= "COMB_TO = ".$this->combTo->getFullName()." (".$this->combTo->getID().") of user ".$this->combTo->getUserInstance()->getUser()->getName()."\n";      
        $strTmp .= "ROUND_NUMBER = ".$this->roundNumber."\n";       
        $strTmp .= "BATTLE_ID = ".$this->battle->getID()."\n";      
        DE_BattleSystemLog::Insert($strTmp);        
        
        if($this->_isTimeToHandle())
        {
            $this->_handle();           
        }else{
            $this->_preHandle();
        }       
        
        //Закрыли сиситемное логирование
        DE_BattleSystemLog::EndWork();
                
        return true;
    }

    private function _handle()
    {               
        //Проверяем можем ли атаковать все еще эту соту
        if($this->canAttack->canAttack($this->combFrom, $this->combTo, $this->eventEndTimeObject, true, false))
        {
            //Стукнули армии
            $this->battleController->armysHit($this->round);

            // Убить артефакт
            if ($this->army->useArtefacts && (int)DE_WorldSettings::CreateForCurentWorld()->get('OFFENSIVE_ARTS_BURN_CHANCE') > mt_rand(0, 99)) {
                $common = DS_Common::CreateForCurentDB();
                $sql = "select ai.id_artefact, ai.id_artefactinstance
                    from ds_artefactattachedbuilding aab, ds_attachedbuilding ab, ds_artefactinstance ai
                    where ab.id_attachedbuilding=aab.id_attachedbuilding
                    and ai.id_artefactinstance=aab.id_artefact
                    and aab.collection=2
                    and ab.id_comb=".$this->combFrom->getID();
                $res = $common->getResultArray("", $sql);
                $row = $res[mt_rand(0, count($res)-1)];
                $common->executeQuery("delete from ds_artefactinstance where id_artefactinstance=".$row['id_artefactinstance']);
                $common->executeQuery("insert into ds_battleround_burned_arts (id_battleround, id_artefact)
                    values (".$this->round->getID().", ".$row['id_artefact'].")");
            }
            
        
            DE_BattleSystemLog::Insert("\n======== I STILL CAN ATTACK TRY CONTINUE BATTLE\n");
            $this->_startNewRound();
        }else{
            DE_BattleSystemLog::Insert("\n======== I CANT CONTINUE ATTACK REASON:".$this->canAttack->getErrorID()."\n");
            $this->_endRound();
        }       
    }//END OF METHOD

    
    private function _endRound()
    {
        $messparams = array();

        $errorCode = $this->canAttack->getErrorID();
        // assert(!empty($errorCode));
        
        switch ($errorCode)
        {
            case 'CLOSED_CENTRAL_GATES':
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getFormatedAddress()."{/b}";
                $messparams[2] = "{b} ".$this->combFrom->getName()."{/b}";
                $messparams[3] = "{b} ".$this->combTo->getFormatedAddress()."{/b}";
                $messparams[4] = "{b} ".$this->combTo->getName()."{/b}";
                break;
            case 'CLOSED_SELF_CENTRAL_GATES':
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getFormatedAddress()."{/b}";
                $messparams[2] = "{b} ".$this->combFrom->getName()."{/b}";
                $messparams[3] = "{b} ".$this->combFrom->getFormatedAddress()."{/b}";
                $messparams[4] = "{b} ".$this->combFrom->getName()."{/b}";
                break;
            case 'IN_SOME_ALLIANCE':
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getFormatedAddress()."{/b}";
                $messparams[2] = "{b} ".$this->combFrom->getName()."{/b}";
                break;
            case 'DENIED_ATTACK_BANNED_USER_MESS':
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getFormatedAddress()."{/b}";
                $messparams[2] = "{b} ".$this->combFrom->getName()."{/b}";
                $messparams[3] = "{b} ".$this->combTo->getUserInstance()->getUser()->getName()."{/b}";
                break;
            case 'CANT_ATTACK_USER_IN_VOCATION_MODE':
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getFullName()."{/b}";  
                $messparams[2] = "{b} ".$this->combFrom->getUserInstance()->getUser()->getName()."{/b}";                
                $messparams[3] = "{b} ".$this->combTo->getFullName()."{/b}";                
                break;
            case 'PEACE_PERIOD':
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getUserInstance()->getUser()->getName()."{/b}";
                $messparams[2] = "{b} ".$this->combFrom->getName()."{/b}";  
                break;  
            case 'TEMPORARY_DEFEND_COLONY':
                // Армия % возвращается на соту %, потому что соте % игрока % включена защита.
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getFullName()."{/b}";
                $messparams[2] = "{b} ".$this->combTo->getFullName()."{/b}";
                $messparams[3] = "{b} ".$this->combTo->getUserInstance()->getUser()->getName()."{/b}";
                break;
            case 'ATTACK_YOURSELF':
                // Ваша армия % возвращается на соту %, так как нельзя нападать на самого себя.
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getFullName()."{/b}";
                break;
            case 'ATTACK_YOURSELF':
                // Ваша армия % возвращается на соту %, так как нельзя нападать на самого себя.
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getFullName()."{/b}";
                break;
            case 'COLONY_PEACEFUL_TIME':            
                // Армия % игрока % возвращается на соту, так как на соте % начался нерест ихтиандров.
                $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                $messparams[1] = "{b} ".$this->combFrom->getUserInstance()->getUser()->getName()."{/b}";
                $messparams[2] = "{b} ".$this->combTo->getFullName()."{/b}";
                break;
            case 'DENIED_ATTACK_USER_WITH_SMALLER_LEAGUE':
                    $errorCode = 'ATTACK_USER_WITH_ENOTHER_LEAGUE';
                    // Армия % возвращается на соту %, потому что игрок % перешл в лигу %.
                    $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                    $messparams[1] = "{b} ".$this->combFrom->getFullName()."{/b}";
                    $messparams[2] = "{b} ".$this->combFrom->getUserInstance()->getUser()->getName()."{/b}";
                    $messparams[3] = "{b} ".DE_League::CreateFromID((int)$this->combFrom->getUserInstance()->getLeagueId())->getLeagueName()."{/b}";
                    break;
                    
            case 'DENIED_ATTACK_USER_WITH_BIGGER_LEAGUE':
                    $errorCode = 'ATTACK_USER_WITH_ENOTHER_LEAGUE';
                    // Армия % возвращается на соту %, потому что игрок % перешл в лигу %.
                    $messparams[0] = "{b} ".$this->army->getName()."{/b}";
                    $messparams[1] = "{b} ".$this->combFrom->getFullName()."{/b}";
                    $messparams[2] = "{b} ".$this->combTo->getUserInstance()->getUser()->getName()."{/b}";
                    $messparams[3] = "{b} ".DE_League::CreateFromID((int)$this->combTo->getUserInstance()->getLeagueId())->getLeagueName()."{/b}";
                    break;
            default:
                //Временная затыка.
                $errorCode = 'IN_SOME_ALLIANCE';                
        }
        
        DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'ARMY_MOVE_BACK_'.$errorCode, $messparams, 1, "GREEN");
                
        
        //Ставим пока затыку на завершение боя !!!!!! ЗАТЫКА НАДО ВСТАВИТЬ НОРМАЛЬНЕ МЕССАГИ!!!!        
        $battleEndResult =  new DE_EndBattleTestResult (GB_TypeFactory::CreateConstant('GB_BattleParticipantTypeCode', 'D'), 'BIG_LOSES');
                        
        //Завершаем бой     
        $this->battleController->battleEnd($battleEndResult);
                        
        //поехали обратно
        $this->_createComeBackEvent();  
        $this->_sendNotification();
    }

    private function _sendNotification()
    {
      //if ($_SERVER['REMOTE_ADDR'] !='195.182.156.98')
        //return;
        if ((constant('DEFAULT_LANGUAGE_SHORT') == 'L') /* && (stripos(php_sapi_name(), 'apache')===false) */)
        {
          $date12 = $this->eventEndTimeObject->getPgFormat(-12); 

           //Пошлём оповещение жертве
           $token = DS_User::GetOAuthInfoByUserId($this->combTo->getUserInstance()->getUser()->getID());
           //SU_DebugTools::LogAll('token='.var_export($token, true), 'oauth.log' );
           if ($token)
           {
             //Проверим, первое ли это нападение на данную соту за 12 часов

             //SU_DebugTools::LogAll('combTo='.$this->combTo->getID(),'oauth.log');
             $bcnt = DS_Battle::GetCountOfBattlesOnComb(intval($this->combTo->getID()), $date12);
             //SU_DebugTools::LogAll("1 bcnt = $bcnt",'oauth.log');
             //if ($bcnt <= 1)
             //{
             $params = array('type' => 'attack-victim-attacked', 'attacker' => $this->combFrom->getUserInstance()->getUser()->getName(),
             'time'=> $this->eventEndTimeObject->getTimeStamp());
             $odata = MakeOAuthNotification($token['open_key'], $token['secret_key'], $params, $bcnt );    
             //}  
           }
           
           //пошлём оповещение атакующему
           $token = DS_User::GetOAuthInfoByUserId($this->combFrom->getUserInstance()->getUser()->getID());
           if ($token)
           {
              $bcnt = DS_Battle::GetCountOfAttacks(intval($this->combFrom->getUserInstanceID()), $date12);
              //SU_DebugTools::LogAll("2 bcnt = $bcnt",'oauth.log'); 
              if ($bcnt <= 1)
              {
                $params = array('type' => 'attack-attacker', 'victim' => $this->combTo->getUserInstance()->getUser()->getName(),
                'time'=> $this->eventEndTimeObject->getTimeStamp());
                $odata = MakeOAuthNotification($token['open_key'], $token['secret_key'], $params );    
              }  
           }           
           //SU_DebugTools::LogAll('END sendNotification', 'oauth.log' );
           
        }       
    }
    
    private function _startNewRound()
    {                               
        //Проверили кончен ли бой или нет
        if(GL_TestEndBattle::isBattleEnded($this->army, $this->combDefendArmy, $this->round))
        {           
            DE_BattleSystemLog::Insert("\n======== CANT CONTINUE BATTLE AND TRY TO END BATTLE REASON CODE:".GL_TestEndBattle::$testResult->getReasonCode()." WINNER:".GL_TestEndBattle::$testResult->getWinnerCode()->value."\n");
            $this->battleController->battleEnd(GL_TestEndBattle::$testResult);
            DE_BattleSystemLog::Insert("\n======== TRY CREATE COME BACK EVENT\n");
                        
            //поехали домой
            $this->_createComeBackEvent();  
            $this->_sendNotification(); 
        }else{                          
            //РАссчитали новый номер райнда
            $newRoundNumber = $this->roundNumber + 1;                                   
            DE_BattleSystemLog::Insert("\n======== TRY CREATE NEXT ROUND EVENT $newRoundNumber\n");
            
            //Создаем новый раунд
            $roundDuration = $this->battleController->startRound($newRoundNumber);
            
            //Создаем ивент следующего райнда
            $this->_createRoundEvent($roundDuration, $newRoundNumber);

            if (DE_WorldSettings::CreateForCurentWorld()->get('ACTIVE_NPC')) {
                // Делаем прослой для NPC
                if ($this->combTo->getUserInstance()->getUser()->getIsUserNpc() == 't') {
                    $npcID = $this->combTo->getUserInstanceID();

                    $sql = "select nas.max_layer_class, nas.fight_layer_prob
                            from ds_npc_army_settings nas, ds_userinstance ui, ds_npchastype nt
                            where ui.id_userinstance=nt.id_userinstance and ui.id_league=nas.league and nt.type=nas.type
                            and ui.id_userinstance=$npcID";
                    $res = $this->common->getResultArray("", $sql);
                    $max_layer_class = $res[0]['max_layer_class'];
                    $fight_layer_prob = (float) $res[0]['fight_layer_prob'];

                    if (mt_rand() / mt_getrandmax() < $fight_layer_prob) {
                        $date = GB_TypeFactory::CreateBase('GB_Date', time());
                        GE_SearchStart::setCommon($this->common);
                        $worldtype = GE_SearchStart::GetWorldType($this->combTo->getUserInstance()->getWorldID());

                        $sql = "select u.id_unit from ds_unit u, ds_unitcanbebuildbyrace ur, ds_unithasclass uc
                            where u.id_unit=ur.id_unit and u.id_unitcontainertype=1
                            and u.id_unit=uc.id_unit and uc.id_unitclass <= $max_layer_class
                            and u.id_unit not in (93,98,99)";
                        if ($worldtype=='FREE_FOR_ALL') $sql .= ' and ur.id_race<4';
                        else $sql .= " and ur.id_race=".$this->combTo->getUserInstance()->getRaceID();
                        $allUnits = $this->common->getResultArray("", $sql);
                        foreach ($allUnits as $unit) {
                            $unitID = (int)$unit['id_unit'];
                            $unit = DE_Unit::CreateFromID($unitID);
                            ME_AddUnitsToHomeArmy::AddUnit($this->combTo, $unit, 1.0, $date);
                        }
                    }
                }
                // Если это была грабля или рейдер NPC
                if ($this->combFrom->getUserInstance()->getUser()->getIsUserNpc() == 't'
                && substr($this->army->getName(), 0, 9) != 'Collector') {
                    // Были ли потери
                    $haveLosses = FALSE;
                    $rounds = DC_BattleRounds::CreateFromBattleID($this->battle->getID());
                    $rounds->resetNext();
                    for ($i=0; $round = $rounds->next(); $i++)
                    {
                        $results = $round->getBattleResultCollection();
                        $results->resetNext();
                        while ($result = $results->next()) {
                            $participant = $result->getPattleParticipant();
                            if ($participant->getParticipantFlag() == 'ATTACKER' && $result->getLoses() >= 1) {
                                $haveLosses = TRUE;
                                break;
                            }
                        }
                        if ($haveLosses) break;
                    }
                    // Ограбили или нет
                    $haveSpoils = FALSE;
                    $resources = array('KADERIUM', 'NANO_CRYSTAL', 'PRODIUM', 'FOOD', 'STASIS_ENERGY');
                    foreach($resources as $k=>$resourceName)
                    {
                        if ($this->battle->getAttributes()->haveAttribute('CURRENT_AMOUNT', $resourceName))
                        {
                            $haveSpoils = TRUE;
                            break;
                        }
                    }

                    // Победил или нет
                    $winner = $this->battle->getWinnerCode()->value == 'A';
                    // Грабля или рейдер
                    $pillager = substr($this->army->getName(), 0, 8) == 'Pillager';
                    // Первый ли бой
                    $sql = "select b.id_battle from ds_npc_battle_log nbl, ds_battle b
                        where nbl.id_battle=b.id_battle and b.battlestartdate > now() - interval '2 days'
                        and nbl.id_userinstance=".$this->combTo->getUserInstanceID()."
                        and nbl.id_npcinstance=".$this->combFrom->getUserInstanceID()."
                        and b.id_battle!=".$this->battle->getID();
                    $res = $this->common->getResultArray("", $sql);
                    $firstFight = count($res) == 0;

                    // Проверяем нужен ли повтор
                    $sql = "select * from ds_npc_repeat_attack where".
                        ($firstFight?"":" not")." first_fight and ".
                        ($pillager?"":" not")." pillager and ".
                        ($winner?"":" not")." winner".
                        " and have_losses!=".($haveLosses?0:1).
                        " and have_spoils!=".($haveSpoils?0:1);
                    $res = $this->common->getResultArray("", $sql);
                    if (!count($res)) return;
                    $repSettings = $res[0];
                    if (mt_rand() / mt_getrandmax() > $repSettings['repeat_chance']) return;

                    // Выбираем тип повтора
                    $repTypes = array ('big_pillager', 'pillager', 'spam', 'raider', 'big_raider');
                    $probs = array(0);
                    reset($repTypes);
                    for ($i=0; list($key, $type) = each($repTypes); $i++) {
                        $probs[$i+1] = $probs[$i] + (float) $repSettings[$type."_chance"];
                    }
                    if ($probs[$i] <= 0) return;
                    $r = mt_rand() / mt_getrandmax() * $probs[$i];
                    reset($repTypes);
                    for ($i=1; list($key, $type) = each($repTypes); $i++) {
                        if ($r < $probs[$i]) break;
                    }

                    // реализуем повтор
                    $sql = "select nas.*, ui.id_league, ui.id_race from ds_npc_army_settings nas, ds_userinstance ui, ds_npchastype nt
                            where ui.id_userinstance=nt.id_userinstance and ui.id_league=nas.league and nt.type=nas.type
                            and ui.id_userinstance=".$this->combFrom->getUserInstanceID();
                    $res = $this->common->getResultArray("", $sql);
                    $npcSettings = $res[0];
                    $npcSettings['id_comb'] = $this->combFrom->getID();
                    $npcSettings['battles'] = 0;
                    $rand = mt_rand() / mt_getrandmax();
                    switch ($type) {
                        case "pillager":
                            $weight = $rand * ($npcSettings['max_pillage_army'] - $npcSettings['min_pillage_army']) + $npcSettings['min_pillage_army'];
                            $armyType = "Pillager";
                            break;
                        case "big_pillager":
                            $weight = ($rand * ($npcSettings['max_pillage_army'] - $npcSettings['min_pillage_army']) + $npcSettings['min_pillage_army']) * $npcSettings['big_army_multiplier'];
                            $armyType = "Pillager";
                            break;
                        case "raider":
                            $weight = $rand * ($npcSettings['max_init_army'] - $npcSettings['min_init_army']) + $npcSettings['min_init_army'];
                            $armyType = "Raider";
                        case "big_raider":
                            $weight = ($rand * ($npcSettings['max_init_army'] - $npcSettings['min_init_army']) + $npcSettings['min_init_army']) * $npcSettings['big_army_multiplier'];
                            $armyType = "Raider";
                            break;
                        default:
                            $weight = 0;
                            $armyType = "Raider";
                    }
                    $army = SU_NPC::MakeArmy($npcSettings, $weight, $armyType);
                    $starttime = time();
                    $traveltime = GL_TravelTime::Count($this->combFrom, $this->combTo, GB_TypeFactory::CreateBase('GB_MoveSpeed',1.0))->value;
                    $endtime = $starttime + $traveltime;
                    //Создаем ивент
                    $dateFrom = GB_TypeFactory::CreateBase('GB_Date', $starttime);
                    $dateTo = GB_TypeFactory::CreateBase('GB_Date', $endtime);
                    $datePeriod = new GB_DatePeriod($dateFrom, $dateTo);
                    $eventParticipants = new GB_EventParticipants;
                    $eventParticipants->setParticipant($this->combTo->getUserInstance());
                    $eventParticipants->setParticipant($this->combFrom->getUserInstance());
                    $actionData = new GB_ArmyActionCreationData('MOVE_TO_ATTACK');
                    $actionData->setArmy($army);
                    $actionData->setDatePeriod($datePeriod);
                    $actionData->setParticipants($eventParticipants);
                    $actionData->setPath(new GB_TravelPath($this->combFrom, $this->combTo));
                    ME_CreateArmyAction::CreateAction($actionData);
                }
            }
        }       
    }
    
    /**
     * Обработка ивента  случае елси время еще не пришло
     *
     */
    private function _preHandle()
    {                       
        $messparams[0] = "{b} ".$this->round->getRoundNumber()."{/b}";
        $messparams[1] = "{b} ".$this->army->getName()."{/b}";
        $messparams[2] = "{b} ".$this->combFrom->getUserInstance()->getUser()->getName()."{/b}";
        $messparams[3] = "{b} ".$this->combTo->getFullName()."{/b}";
        $messparams[4] = "{b} ".$this->combTo->getUserInstance()->getUser()->getName()."{/b}";
        $messparams[5] = "{b} ".$this->_getTimePeriodToEnd()->getDSFormat()."{/b}";

        if($this->params['uiid'] == $this->combFrom->getUserInstance()->getID())
        {
            $color = 'GREEN';
        }else{
            $color = 'RED';
        }

        // системное сообщение
        $colors[$this->combFrom->getUserInstance()->getID()] = 'GREEN';
        $colors[$this->combTo->getUserInstance()->getID()] = 'RED';
        DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'ATTACK_MESS5', $messparams, 1, $colors, 'NOTFORSTORE');
        
    }//END OF METHOD

    public function handleInExternalRefresh(DE_EventQueue $event, DE_UserInstance $refreshedInstance, GB_Date $chekDate)
    {
        if($event->getEndDate()->value > $chekDate->value)
        {
            return false;
        }

        $userInstanceTo = DE_Comb::CreateFromID((int)$event->getAttributes()->getAttribute('ID_CombTo'))->getUserInstance();

        if($refreshedInstance->getID() == $userInstanceTo->getID())
        {
            return true;
        }else{
            return true;
        }
    }
}//END OF CLASS


?>