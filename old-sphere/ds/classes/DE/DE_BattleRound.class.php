<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Раунд боя
 */
class DE_BattleRound extends DE_Base 
{
    private $battleID;
    
    private $roundNumber;
        
    private $startDate;
    
    private $endDate;
    
    private $haveadds;
    
    private $retreatSide;
    
    private $retreatStarted;
        
    public function __construct($id, $data = '')
    {       
        assert(is_integer($id) && $id>0);       
        assert(is_array($data) || $data == '');
        
        parent::__construct($id);
        
        if(!is_array($data))
        {
            $data = DS_Battle::GetBattleRoundByID($id);         
        }       
        assert(is_array($data) && count($data)>0);

        assert(key_exists('id_battle', $data));     
        $this->battleID = (int)$data['id_battle'];

        assert(key_exists('roundnumber', $data));       
        $this->roundNumber = (int)$data['roundnumber'];
        
        assert(key_exists('roundstarttime', $data));        
        $this->startDate = GB_TypeFactory::CreateConstant('GB_Date', $data['roundstarttime']);
        
        assert(key_exists('roundendtime', $data));      
        $this->endDate = GB_TypeFactory::CreateConstant('GB_Date', $data['roundendtime']);
            
        assert(key_exists('haveadds', $data));      
        $this->haveadds = (float)$data['haveadds'];
        
        assert(key_exists('retreat_side', $data));      
        $retreatSide = $data['retreat_side'];
        assert(empty($retreatSide) || $retreatSide == 'A' || $retreatSide == 'D');
        if(!empty($retreatSide))
        {
            $this->retreatSide = GB_TypeFactory::CreateBase('GB_BattleParticipantTypeCode', $retreatSide);
        }
        
        assert(key_exists('retreat_started', $data));       
        $this->retreatStarted = $data['retreat_started'] == 't' ? true : false;
        
        $this->_invariant('construct');
    }
    
    /**  
     * @param integer $id
     * @return DE_BattleRound
     */
    static public function CreateFromID($id, $data = '')
    {
        assert(is_int($id) && $id>0);   
        assert(is_array($data) || $data == '');
            
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, $data);      
    }
    
    /** 
     * @param DE_Battle $battle
     * @param integer $number
     * @return DE_BattleRound
     */
    static public function CreateFromBattleAndNumber(DE_Battle $battle, $number)
    {
        assert(is_int($number) && $number>=0);
        
        $id = (int)DS_Battle::GetRoundIDByBattleAndNumber($battle,$number);     
        if ($id > 0)
            return self::CreateFromID($id);
        else
            return NULL;
    }
    
    /**
     * Enter description here...
     *
     * @param DE_Battle $battle
     * @param int $roundNumber
     * @param GB_Date $startDate
     * @param GB_TimePeriod $duration
     * @return DE_BattleRound
     */
    static public function CreateInDB(DE_Battle $battle, $roundNumber, GB_Date $startDate, GB_TimePeriod $duration)
    {
        assert(is_int($roundNumber) && $roundNumber>=0);
        
        $roundID = DS_Battle::InsertBattleRound($battle, $roundNumber, $startDate, $duration);
        return  self::CreateFromID($roundID);
    }
    
    /**
     * Enter description here...
     *
     * @return GB_Date
     */
    public function getStartDate()
    {
        $this->_invariant();
        return $this->startDate;        
    }
    
    /**
     * Enter description here...
     *
     * @return GB_Date
     */
    public function getEndDate()
    {
        $this->_invariant();
        return $this->endDate;      
    }
    
    /**
     * Enter description here...
     *
     * @return DE_Battle
     */
    public function getBattle()
    {
        $this->_invariant();
        return DE_Battle::CreateFromID($this->battleID);
    }
    
    /**
     * Enter description here...
     *
     * @return DC_BattleRoundResultsCollection
     */
    public function getBattleResultCollection()
    {
        $this->_invariant();
        return DC_BattleRoundResultsCollection::CreateFromRoundID($this->getID());      
    }
    
    /**
     * Enter description here...
     *
     * @return int
     */
    public function getRoundNumber()
    {
        $this->_invariant();
        return $this->roundNumber;
    }
    
    /**
     * Enter description here...
     *
     * @return float
     */
    public function getAdds()
    {
        $this->_invariant();
        return $this->haveadds;
    }
    
    /**
     * Enter description here...
     *
     * @param float $addArmyPower
     */
    public function deltaAdds($addArmyPower)
    {       
        $this->_invariant();
        
        assert($addArmyPower >= 0);
        
        $this->haveadds += $addArmyPower; 
        
        $this->isModifyed = true;
    }
    
    public function getRetreatSide()
    {
        return $this->retreatSide;
    }
    
    public function isRetreatStarted()
    {
        return $this->retreatStarted;
    }
    
    public function setRetreatData(GB_BattleParticipantTypeCode $retreatSide, $started)
    {
        assert(is_bool($started));
        
        $this->retreatSide = $retreatSide;
        
        $this->retreatStarted = $started;
        
        $this->isModifyed = true;
    }
    
    protected  function _invariant($mode = 'public')
    {
        if(BS_Base::_exitInvariant($mode)) return;
        
        parent::_invariant($mode);              
        
        assert($this->battleID>0);
        assert($this->roundNumber>=0);
        assert($this->startDate instanceof GB_Date);
        assert($this->endDate instanceof GB_Date);
        assert($this->haveadds >= 0);
    }
    
    public function commit()
    {
        parent::commit();
        
        if($this->isModifyed == true)
        {
            if(DS_Battle::UpdateBattleRound($this))
            {
                $this->isModifyed = false;
            }           
        }
    }
    
    function __destruct()
    {
        parent::__destruct();
        //$this->commit();
    }

}

?>