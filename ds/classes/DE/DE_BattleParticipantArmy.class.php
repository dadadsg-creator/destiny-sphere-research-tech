<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Армия участника боя
 * это не DE_Army!
 */
class DE_BattleParticipantArmy extends DE_Base
{   
    private $armyID;
    
    private $participantID;
    
    private $armyName;
    
    private $dieRoundNumber;
    
    function __construct($id)
    {
        
        GB_SingletonObjectStorage::TestSingletonCreation();
        assert(is_integer($id) && $id>0);       
        parent::__construct($id);
        
        $data = DS_Battle::GetBattleParticipantArmyByID($id);       
        assert(is_array($data) && count($data)>0);
        assert(key_exists('id_battleparticipant', $data));      
        $this->participantID = (int)$data['id_battleparticipant'];
        
        assert(key_exists('id_unitcontainer', $data));      
        $this->armyID = (int)$data['id_unitcontainer'];
        
        assert(key_exists('unitcontainername', $data));     
        $this->armyName = (string)$data['unitcontainername'];   
        
        assert(key_exists('dieafterround', $data));     
        $this->dieRoundNumber = (int)$data['dieafterround'];

        $this->_invariant('construct');
    }
    
    /**
     * Enter description here...
     *
     * @param int $id
     * @return DE_BattleParticipantArmy
     */
    static public function CreateFromID($id)
    {
        assert(is_int($id) && $id>0);           
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
    }
    
    /**
     * Enter description here...
     *
     * @param int $participantID
     * @param int $unitContainerID
     * @param string $unitContainerName
     * @return DE_BattleParticipantArmy
     */
    static public function CreateInDB($participantID, $unitContainerID, $unitContainerName)
    {
        assert(is_int($participantID) && $participantID >0);
        assert(is_int($unitContainerID) && $unitContainerID > 0 );
        // assert(is_string($unitContainerName) && !empty($unitContainerName));
        
        $id = DS_Battle::InsertParticipantArmy($participantID, $unitContainerID, $unitContainerName);
                
        return self::CreateFromID($id);
    }
    
    /**
     * Enter description here...
     *
     * @return DE_Army
     */
    public function getArmy()
    {
        $this->_invariant();
        return DE_Army::CreateFromID($this->armyID);
    }
    
    /**
     * Enter description here...
     *
     * @return int
     */
    public function getArmyID()
    {
        $this->_invariant();
        return $this->armyID;
    }
    
    /**
     * Enter description here...
     *
     * @return string
     */
    public function getArmyName()
    {
        $this->_invariant();
        return $this->armyName;
    }
    
    /**
     * Enter description here...
     *
     * @return DE_BattleParticipant
     */
    public function getBattleParticipant()
    {
        $this->_invariant();
        return DE_BattleParticipant::CreateFromID($this->participantID);
    }
    
    /**
     * Enter description here...
     *
     * @return int
     */
    public function getBattleParticipantID()
    {
        $this->_invariant();
        return $this->participantID;
    }
    
    /**
     * Enter description here...
     *
     * @return int
     */
    public function getDieRoundNumber()
    {
        $this->_invariant();
        return $this->dieRoundNumber;       
    }
    
    /**
     * Enter description here...
     *
     * @param int $roundNumber
     */
    public function setDieRoundNumber($roundNumber)
    {
        $this->_invariant();
        
        assert(is_int($roundNumber) && $roundNumber > 0);
        
        $this->dieRoundNumber = $roundNumber;       
        
        $this->isModifyed = true;
    }
    
    protected function _invariant($mode = 'public')
    {
        if(BS_Base::_exitInvariant($mode)) return;
        
        parent::_invariant($mode);
        
        assert(is_int($this->armyID) && $this->armyID>0);
        assert(is_int($this->participantID) && $this->participantID>0);     
        assert(is_string($this->armyName) && !empty($this->armyName));
        assert(is_int($this->dieRoundNumber));                      
    }
    
    public function commit()
    {
        $this->_invariant();
        parent::commit();
        
        if($this->isModifyed == true)
        {
            if(DS_Battle::UpdateParticipantArmy($this))
            {
                $this->isModifyed = false;
            }           
        }
    }
    
    function __destruct()
    {
        $this->_invariant();
        parent::__destruct();
    }
}

?>
