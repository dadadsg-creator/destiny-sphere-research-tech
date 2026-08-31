<?php $CVS_Version = '$Revision$ ; $Date$';

class EH_ArmyMovementTo extends EH_Event 
{
    public $eqid;
    
    protected $checktime;
    
    private $canAttack;
    
    private $combFrom;
    
    private $combTo;
    
    private $army;
        
    function __construct()
    {
        $this->EH_Event();
        $this->canAttack = new GL_CanAttackComb();          
    }
    
    public function handleEvent ( $eqid )
    {
        assert($eqid>0);
        $this->eqid = $eqid;
                        
        // получаем параметры
        if(!$this->getEventParams()) 
        {
            return false;
        }       

        $combToID = (int)$this->eventparams["ID_CombTo"];
        $combFromID = (int)$this->eventparams["ID_CombFrom"];
        $armyID = (int)$this->eventparams["ID_Army"];
        
        assert($combToID > 0);
        assert($combFromID > 0);
        assert($armyID > 0);
        
        $this->combTo = DE_Comb::CreateFromID($combToID);       
        $this->combFrom = DE_Comb::CreateFromID($combFromID);
        $this->army = DE_Army::CreateFromID($armyID);       
            
        if($this->_isTimeToHandle())
        {
            $this->_handle();
            $this->setTrash ();
        }else{
            $this->_preHandle();
        }
                
        return true;
    }
    
    private function _handle()
    {
        if($this->canAttack->canAttack($this->combFrom, $this->combTo))
        {
            
            $this->_startBattle();
        }else{

            if ($this->canAttack->getErrorID())
            {
                switch ($this->canAttack->getErrorID()) {
                    case 'CLOSED_CENTRAL_GATES':
                            $messparams[0] = $this->army->getName();
                            $messparams[1] = $this->combFrom->getFormatedAddress();
                            $messparams[2] = $this->combFrom->getName();
                            $messparams[3] = $this->combTo->getFormatedAddress();
                            $messparams[4] = $this->combTo->getName();
                        break;
                    case 'CLOSED_SELF_CENTRAL_GATES':
                            $messparams[0] = $this->army->getName();
                            $messparams[1] = $this->combFrom->getFormatedAddress();
                            $messparams[2] = $this->combFrom->getName();
                            $messparams[3] = $this->combFrom->getFormatedAddress();
                            $messparams[4] = $this->combFrom->getName();
                        break;
                    case 'IN_SOME_ALLIANCE':
                            $messparams[0] = $this->army->getName();
                            $messparams[1] = $this->combFrom->getFormatedAddress();
                            $messparams[2] = $this->combFrom->getName();
                        break;
                    case 'DENIED_ATTACK_BANNED_USER_MESS':
                            $messparams[0] = $this->army->getName();
                            $messparams[1] = $this->combFrom->getFormatedAddress();
                            $messparams[2] = $this->combFrom->getName();
                            $messparams[3] = $this->combTo->getUserInstance->getUser()->getName();
                        break;
                    case 'TEMPORARY_DEFEND_COLONY':
                            $messparams[0] = $this->army->getName();
                            $messparams[1] = $this->combFrom->getFormatedAddress();
                            $messparams[3] = $this->combTo->getFormatedAddress();
                            $messparams[4] = $this->combTo->getName();
                        break;

                }
                
                if (!$this->sysmsg->addMessage ( 'ARMY_MOVE_BACK_'.$this->canAttack->getErrorID(), $messparams, 1 ) )
                {
                    $this->errormsg = $this->sysmsg->errormsg;
                    return false;
                }
            }

            $this->_createComeBackEvent();          
        }                   
    }//END OF METHOD
    
    private function _startBattle()
    {
        
        //Создаем начало боя
        $battle = ME_StartBattle::Exec($this->army, $this->combFrom, $this->combTo, GB_TypeFactory::CreateConstant('GB_Date', $this->eventendtime));
                
        //Проверяем мож но ли начинать бой или нет
        if(GL_CanStartBattle::CanStart($this->army, DE_CombDefendArmy::CreateFromCombID($this->combTo->getID())))
        {                               
            //РАсчитали время рануда для этих восйк
            $roundDuration = GL_BattleRoundDuration::Count($this->army, $this->combTo);
            
            //Ударили армии дру о друга
            ME_ArmysHit::Exec($this->army, DE_CombDefendArmy::CreateFromCombID($this->combTo->getID()));

            //Создали первый раунд
            $round1 = DE_BattleRound::CreateInDB($battle, GB_TypeFactory::CreateConstant('GB_Integer', 1), GB_TypeFactory::CreateConstant('GB_Date', $this->eventendtime), $roundDuration);

            //Даобавли в него армии после этого
            ME_AddRoundResults::exec($round1, $this->army, DE_CombDefendArmy::CreateFromCombID($this->combTo->getID()));

            //Вычистили потери у армий
            ME_CleanArmyLooses::exec($this->army, DE_CombDefendArmy::CreateFromCombID($this->combTo->getID()));

            $this->_createRoundEvent($battle, $roundDuration);          
        }else{
            
            //Совершаем действия необходимые для завершения боя     
            ME_EndBattle::Exec($battle, GL_CanStartBattle::$testResult, GB_TypeFactory::CreateConstant('GB_Date', (int)strtotime($this->eventendtime)));
                    
            ME_FormBattleLog::Exec($battle);
            ME_FormBattleLatters::Exec($battle);
            
            //Армя вышла из боя, не воюя.
            $this->_createComeBackEvent();
        }       
    }
    
    private function _createRoundEvent(DE_Battle $battle, GB_TimePeriod $roundDuration)
    {
        $roundStartTime = GB_TypeFactory::CreateConstant('GB_Date', $this->eventendtime);       
        $roundEndTime = GB_TypeFactory::CreateMaster('GB_Date', $this->eventendtime);               
        $roundEndTime->value = $roundEndTime->value + $roundDuration->value;        
        $times = new GB_DatePeriod($roundStartTime, $roundEndTime);
        
        $participants[] = (int)$this->combFrom->getUserInstance()->getID();
        $participants[] = (int)$this->combTo->getUserInstance()->getID();
            
        $options["ID_Army"] = $this->army->getID();     
        $options["ID_CombFrom"] = $this->combFrom->getID();
        $options["ID_CombTo"] = $this->combTo->getID(); 
        $options["ID_Battle"] = $battle->getID();               
        $options["Round"] = 1;      
        
        $eventQueueID = EH_EventCreate::Create('BATTLE_ROUND', $participants, $options, $times);            
        
        DE_ArmyAction::DeleteAction($this->army->getArmyAction());
        $armyAction = DE_ArmyAction::CreateInDB($this->army, $this->combFrom, $this->combTo, $times, 'IN_BATTLE');  
        
        $this->new = 1;
        $this->newevents[0]['id_eventqueue'] = $eventQueueID;
    }
    
    private function _createComeBackEvent()
    {           
        //Убратьтекущую комбу у армии
        $this->army->setCurentComb(new DE_CombNull('Unknown'));
        
        $endTime = strtotime($this->eventendtime) + GL_TravelTime::Count($this->combFrom, $this->combTo)->value;        
        
        $times = new GB_DatePeriod(GB_TypeFactory::CreateConstant('GB_Date', (int)strtotime($this->eventendtime)), GB_TypeFactory::CreateConstant('GB_Date', $endTime));

        $participants[] = (int)$this->army->getUserInstance()->getID();     

        $options["ID_Army"] = $this->army->getID();
        $options["ID_CombFrom"] = $this->combTo->getID();
        $options["ID_CombTo"] = $this->combFrom->getID();       
        
        $eventQueueID = EH_EventCreate::Create('ARMY_MOVEMENT_FROM', $participants, $options, $times);          
        
        
        DE_ArmyAction::DeleteAction($this->army->getArmyAction());
        $armyAction = DE_ArmyAction::CreateInDB($this->army, $this->combTo, $this->combFrom, $times, 'MOVE_FROM');
        
        $this->new = 1;
        $this->newevents[0]['id_eventqueue'] = $eventQueueID;
    }
    
    private function _preHandle()
    {   
        $period = $this->_getTimePeriodToEnd()->getWithParts(); 
        $messparams[0] = $this->army->getName();
        $messparams[1] = $this->combFrom->getUserInstance()->getUser()->getName();      
        $messparams[2] = $this->combFrom->getFormatedAddress();
        $messparams[3] = $this->combTo->getName();      
        $messparams[4] = (string)$period['DAYS'];
        $messparams[5] = (string)$period['HOURS'];
        $messparams[6] = (string)$period['MINUTES'];
        $messparams[7] = (string)$period['SECONDS'];
        
        // системное сообщение
        if ( !$this->sysmsg->addMessage ( 'ATTACK_MESS1', $messparams, 1 ) ) 
        {
            $this->errormsg = $this->sysmsg->errormsg;
            return false;
        }
    }//END OF METHOD
        
}//END OF CLASS


?>