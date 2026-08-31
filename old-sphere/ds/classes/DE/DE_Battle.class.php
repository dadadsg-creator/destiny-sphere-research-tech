<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Класс сущности боя. Создаеться на сонование ID боя.
 */
class DE_Battle extends DE_Base
{
    
    /**
     * ID соты на которой происходит бой
     *
     * @var integer
     */
    private $battleCombID;

    /**
     * ID соты ск оторой произошла атака
     *
     * @var integer
     */
    private $fromCombID;
    
    /**
     * Дата начала боя
     *
     * @var GB_Date
     */
    private $battleStartDate;
    
    
    /**
     * Дата окнчания боя
     *
     * @var GB_Date or GB_DateNull
     */
    private $battleEndDate;         
    
    
    /**
     * ID участника атакующего
     *
     * @var integer
     */
    private $attackerParticipantID;
    
    /**
     * ID участника обороняющегося
     *
     * @var integer
     */
    private $defenderParticipantID;
    
    private $battleParticipant;
    
    /**
     * Enter description here...
     *
     * @var GB_BattleParticipantTypeCode
     */
    private $winnerCode;
    
    private $battleEndReasonID;
    
    /**
     * Enter description here...
     *
     * @var GB_BattleParticipantTypeCode
     */
    private $retreatSideName;
    
    /**
     * Enter description here...
     *
     * @var integer
     */
    private $retreatRoundNumber;
    
    /**
     * Enter description here...
     *
     * @var bool
     */
    private $retreatWithNullDamage;
    
    /**
     * Enter description here...
     *
     * @var DC_CombDefendBattleParticipants
     */
    private $combDefendBattleParticipants;
    
    /**
     * Enter description here...
     *
     * @var DE_BattleParticipant
     */
    private $attackerBattleParticipant;
    
    /**
     * Enter description here...
     *
     * @var DE_BattleParticipant
     */
    private $defenderBattleParticipant;
    
    public function __construct($id)
    {

        assert(is_integer($id) && $id>0);       
        parent::__construct($id);
        
        $data = DS_Battle::GetBattleByID($id);      

        // assert(key_exists('id_battlecomb', $data));     
        if (!key_exists('id_battlecomb', $data)) return;
        $this->battleCombID = (int)$data['id_battlecomb'];
        
        assert(key_exists('battlestartdate', $data));       
        $this->battleStartDate = GB_TypeFactory::CreateConstant('GB_Date', $data['battlestartdate']);
        
        assert(key_exists('battleenddate', $data));
        if ((int)$data['battleenddate'] == 0)
        {
            $this->battleEndDate = new GB_DateNull();
        }else{
            $this->battleEndDate = GB_TypeFactory::CreateConstant('GB_Date', $data['battleenddate']);
        }
        
        assert(key_exists('id_attacker', $data));       
        $this->attackerParticipantID = (int)$data['id_attacker'];       
        
        assert(key_exists('id_defender', $data));       
        $this->defenderParticipantID = (int)$data['id_defender'];       
        
        assert(key_exists('id_battleendreason', $data));        
        $this->battleEndReasonID = (int)$data['id_battleendreason'];
        
        assert(key_exists('winner', $data));        
        $this->winnerCode = GB_TypeFactory::CreateMaster('GB_BattleParticipantTypeCode', (string)$data['winner']);      
        
        assert(key_exists('id_attackercomb', $data));
        $this->fromCombID = (int)$data['id_attackercomb'];
    
        assert(key_exists('retreat_side', $data));
        $this->retreatSideName = GB_TypeFactory::CreateMaster('GB_BattleParticipantTypeCode', (string)$data['retreat_side']);       
        
        assert(key_exists('retreat_round_number', $data));
        $this->retreatRoundNumber = (int)$data['retreat_round_number'];
        
        assert(key_exists('retreat_with_empty_damage', $data));
        if($data['retreat_with_empty_damage'] == 't')
        {
            $this->retreatWithNullDamage = true;
        } else {
            $this->retreatWithNullDamage = false;
        }
        
        
        $this->_invariant('construct');
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $id
     * @return DE_Battle
     */
    static public function CreateFromID($id)
    {
        assert(is_int($id) && $id>0);                   
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);

    }       
    
    /**
     * Enter description here...
     *
     * @param DE_Comb $combTo
     * @param DE_Army $attackArmy
     * @param GB_Date $startBattleDate
     * @return DE_Battle
     */
    static public function CreateInDB(DE_Comb $combTo, DE_Army $attackArmy, GB_Date $startBattleDate)
    {
        $attaker = $attackArmy->getUserInstance();
        $combFrom = $attackArmy->getBaseComb();
        
        $battleID = DS_Battle::InsertBattle($combTo, $combFrom, $startBattleDate);
                
        DE_BattleParticipant::CreateInDB($battleID, $combTo, $attaker, 'A', 'f', $attackArmy);
        
        $insertedDefenders = array();
        
        $combDefendArmysCollection = DC_CombDefendArmys::CreateFromCombID($combTo->getID());
        $combDefendArmysCollection->resetNext();                
        while($army = $combDefendArmysCollection->next())
        {
            if($army->getUserInstance()->getID() == $combTo->getUserInstance()->getID())
            {
                $combOwnerFlag = 't';
            }else{
                $combOwnerFlag = 'f';
            }           

            if(in_array($army->getUserInstance()->getID(), $insertedDefenders))
            {
                continue;
            } else {
                $insertedDefenders[] = $army->getUserInstance()->getID();
            }
            
            DE_BattleParticipant::CreateInDB($battleID, $combTo, $army->getUserInstance(), 'D', $combOwnerFlag);    
        }
                
        $battle = self::CreateFromID($battleID);
        
        //DE_BattleReport::CreateInDB($battle, 'A');
        //DE_BattleReport::CreateInDB($battle, 'D');
        
        return $battle;
    }
            
    /**
     * Возаращет соту на которой бой идет
     *
     * @return DE_Comb
     */
    public function getComb()
    {
            
        return DE_Comb::CreateFromID($this->battleCombID);      
    }
    
    /**
     * Enter description here...
     *
     * @return unknown
     */
    public function getCombID()
    {
        
        return $this->battleCombID;     
    }
    
    /**
     * Возаращет соту ск оторой произошла атака
     *
     * @return DE_Comb
     */
    public function getFromComb()
    {
        
        return DE_Comb::CreateFromID($this->fromCombID);        
    }
    
    
    /**
     * Enter description here...
     *
     * @return DC_BattleRounds
     */
    public function getBattleRoundCollection()
    {
 
        
        return DC_BattleRounds::CreateFromBattleID($this->getID());
    }
    
    /**
     * Получем дату начал боя
     *
     * @return GB_Date
     */
    public function getStartDate()
    {
        
        return $this->battleStartDate;      
    }
    
    /**
     * Получаем дату окончания боя
     *
     * @return GB_Date OR GB_DateNull
     */
    public function getEndDate()
    {
        
        return $this->battleEndDate;        
    }
    
    /**
     * Участник ааткующий
     *
     * @return DE_BattleParticipant
     */
    public function getAttacker()
    {
        if(!($this->attackerBattleParticipant instanceof DE_BattleParticipant))
        {
            $this->attackerBattleParticipant = DE_BattleParticipant::CreateFromID($this->attackerParticipantID);
        }
        
        return  $this->attackerBattleParticipant;
    }
    
    /**
     * Enter description here...
     *
     * @param DE_UserInstance $userInstance
     * @return DE_BattleParticipant
     */
    public function getBattleParticipantBy(DE_UserInstance $userInstance)
    {       
        
        if($this->getAttacker()->getUserInstance()->equals($userInstance))
        {
            return $this->getAttacker();
        }
                            
        $this->getDefendersCollection()->resetNext();
        while($defender = $this->getDefendersCollection()->next())
        {
            if($defender->getUserInstance()->equals($userInstance))
            {
                return $defender;
            }
        }
        
        return false;
    }
    
    /**
     * Участник обороняющейся и являющийся владельцем соты
     *
     * @return DE_BattleParticipant
     */
    public function getDefender()
    {
        return  GB_SingletonObjectStorage::GetSingleton('DE_BattleParticipant', $this->defenderParticipantID);
    }
            

    public function getDefendersCollection()    
    {
        if(!($this->combDefendBattleParticipants instanceof DC_CombDefendBattleParticipants))
        {
            $this->combDefendBattleParticipants = DC_CombDefendBattleParticipants::CreateFromBattleID($this->getID());
        }
        
        return $this->combDefendBattleParticipants;
    }
    
    /**
     * Установка даты окончания боя
     *
     * @param Gb_Date $endDate - дата окнчания боя
     */
    public function setEndDate(Gb_Date $endDate)
    {       
 
        $this->battleEndDate = $endDate;
        $this->isModifyed = true;       
    }
    
    public function getReport()
    {
 
        return DE_BattleReport::CreateFromBattleID($this->getID());
    }
    
    public function getBattleEndReason()
    {
 
        return DE_BattleEndReason::CreateFromID($this->battleEndReasonID);
        
    }
    
    public function getWinnerCode()
    {
 
        
        return $this->winnerCode;
    }
            
    public function setWinner($result)
    {               
        assert($result instanceof DE_EndBattleTestResult);
                
 
        
        $this->winnerCode = $result->getWinnerCode();
        
        switch ($this->winnerCode->value)
        {
            case 'A':
                $this->getAttacker()->setIsWinner(true);
                break;
            case 'D':
                $this->getDefender()->setIsWinner(true);
                break;
        }
        
        $this->battleEndReasonID = (int)DE_BattleEndReason::CreateFromWinnerAndReasonName($result->getWinnerCode(), $result->getReasonCode())->getID();
        
        $this->isModifyed = true;
    }
    
    public function isBattleEnded()
    {
 
        
        return $this->battleEndDate instanceof GB_Date;
    }
    
    /**
     * Enter description here...
     *
     * @return DE_BattleAttributes
     */
    public function getAttributes()
    {
                
        return DE_BattleAttributes::CreateFromBattleID($this->getID());
    }
    
    
    /**
     * Идет ли отступление из-за того что не осталось атакующих армий
     *
     * @return bool
     */
    public function isRetreatWithEmptyDamage()
    {
        return $this->retreatWithNullDamage;
    }
    
    public function setRetreatWithEmptyDamage($retreatWithEmptyDamage)
    {
        assert(is_bool($retreatWithEmptyDamage));
        $this->retreatWithNullDamage = $retreatWithEmptyDamage;
        $this->isModifyed = true;
    }
        
    /**
     * Возвращаем код отступившей стороны
     *
     * @return GB_BattleParticipantTypeCode
     */
    public function getRetreatSideName()
    {
        return $this->retreatSideName;
    }
    
    public function setRetreatSideName($sideName)
    {
        assert(empty($this->retreatSideName->value));
        
        $this->retreatSideName->value = $sideName;
        $this->isModifyed = true;
    }

    
    /**
     * Говарит в бою уже началось отступление или нет.
     *
     * @return boolean
     */
    public function isBattleRetreated()
    {
        return ($this->retreatRoundNumber > 0);
    }
    
    
    /**
     * Возвращаем норме райнда в котором было начато отсутление одной из сторон
     *
     * @return integer
     */
    public function getRetreatRoundNumber()
    {
        return $this->retreatRoundNumber;
    }   
    
    public function setRetreatRoundNumber($roundNumber)
    {
        assert(is_int($roundNumber) && $roundNumber >= 0);
        
        $this->retreatRoundNumber = $roundNumber;   

        $this->isModifyed = true;   

    }
        
    protected  function _invariant($mode = 'public')
    {
        parent::_invariant($mode);
        
        //assert(is_int($this->battleCombID) && $this->battleCombID>0);       
        //assert($this->battleStartDate instanceof GB_Date);
        //assert($this->battleEndDate instanceof GB_Date || $this->battleEndDate instanceof GB_DateNull);
        
        //assert(is_int($this->attackerParticipantID) && $this->attackerParticipantID>0);     
        //assert(is_int($this->defenderParticipantID) && $this->defenderParticipantID>0);
        //assert(is_int($this->fromCombID) && $this->fromCombID>0);     
        //assert($this->winnerCode instanceof GB_BattleParticipantTypeCode );
    }
    
    public function commit()
    {
        parent::commit();

        if($this->isModifyed)
        {
            DS_Battle::UpdateBattle($this);     
            $this->isModifyed = false;          
        }
    }
    
     
}//END OF CLASS
?>