<?php $CVS_Version = '$Revision$ ; $Date$';


/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Класс сущности для участника боя
 *
 */
class DE_BattleParticipant extends DE_Base
{
    private $battleID;
        
    private $userInstanceID;
        
    private $participantFlag;   
    
    private static $allowedParticipantFlags = array('ATTACKER'=>'A', 'DEFENDER'=>'D');
    
    private $isCombOwner;
    
    private $isStartRound;
    
    private $isWinner;
            
    private $battleXP;
    
    /**
     * Enter description here...
     *
     * @var DC_BattleParticipantArmys
     */
    private $armysCollection;
    
    public function __construct($id)
    {
        assert(is_integer($id));
        parent::__construct($id);
        
        $data = DS_Battle::GetBattleParticipantByID($id);
        assert(is_array($data) && count($data)>0);

        assert(key_exists('id_battle', $data));     
        $this->battleID = (int)$data['id_battle'];              
        
        assert(key_exists('id_userinstance', $data));       
        $this->userInstanceID = (int)$data['id_userinstance'];              
        
        assert(key_exists('participantflag', $data));
        $participant = array_flip(self::$allowedParticipantFlags);
        $this->participantFlag = $participant[(string)$data['participantflag']];

        assert(key_exists('iswinner', $data));  
        $this->isWinner = $data['iswinner'];    
        
        assert(key_exists('isstartround', $data));  
        $this->isStartRound = (int)$data['isstartround'];   
        
        assert(key_exists('iscombowner', $data));   
        $this->isCombOwner = $data['iscombowner'];
        
        assert(key_exists('battlexp', $data));  
        $this->battleXP = (float)$data['battlexp'];
        
        $this->_invariant('construct');
    }
    
    /**
     * Enter description here...
     *
     * @param int $id
     * @return DE_BattleParticipant
     */
    public static function CreateFromID($id)
    {
        assert(is_int($id) && $id>0);           
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
    }
    
    /**
     * Enter description here...
     *
     * @return DC_BattleParticipantArmys
     */
    public function getArmiesColletion()
    {
        if(!($this->armysCollection instanceof DC_BattleParticipantArmys))
        {
            $this->armysCollection = DC_BattleParticipantArmys::CreateFromParticipantID($this->getID());
        }
                        
        return  $this->armysCollection;
    }
    
    /**
     * Enter description here...
     *
     * @param int $battleID
     * @param DE_Comb $comb
     * @param int $userInstance
     * @param string $participantFlag
     * @param pg_bool $isCombOwner
     * @param DE_Army $attackerArmy
     * @return DE_BattleParticipant
     */
    public static function CreateInDB($battleID, DE_Comb $comb, $userInstance, $participantFlag, $isCombOwner, $attackerArmy = null)
    {
        assert($userInstance instanceof DE_UserInstance);
        assert(is_null($attackerArmy) || $attackerArmy instanceof DE_Army);
        
        assert(is_int($battleID) && $battleID>0);   
        assert(in_array($participantFlag, self::$allowedParticipantFlags));
        assert($isCombOwner=='t' || $isCombOwner=='f');
                
        $participantID = DS_Battle::InsertBattleParticipant($battleID, $userInstance, $participantFlag, $isCombOwner);
        DC_BattleParticipantArmys::CreateInDB($participantID, $comb, $attackerArmy);
            
        return self::CreateFromID($participantID);
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
     * @return int
     */
    public function getBattleID()
    {
        $this->_invariant();                
        return $this->battleID;
    }
    
    /**
     * Enter description here...
     *
     * @return DE_UserInstance
     */
    public function getUserInstance()
    {
        $this->_invariant();        
        return DE_UserInstance::CreateFromID($this->userInstanceID);
    }
    
    /**
     * Enter description here...
     *
     * @return int
     */
    public function getUIID()
    {
        return $this->userInstanceID;
    }
    
    /**
     * Enter description here...
     *
     * @param string $mode PHP(PG)
     * @return string
     */
    public function getParticipantFlag($mode='PHP')
    {
        $this->_invariant();
        switch ($mode)
        {
            case 'PHP':
                return $this->participantFlag;
                break;
            case 'PG':
                return self::$allowedParticipantFlags[$this->participantFlag];
                break;
            default:
                ds_error('Unknown $mode = '.$mode);
        }
                
    }
    
    /**
     * Enter description here...
     *
     * @param string $mode PHP(PG)
     * @return bool(pg_bool)
     */
    public function getIsWinner($mode='PHP')
    {
        $this->_invariant();
        
        assert($mode=='PHP' || $mode=='PG');
        
        if($mode=='PHP')
        {
            if($this->isWinner == 'f')
            {
                return false;
            }else{
                return true;
            }                   
        }else{
            return $this->isWinner;
        }       
    }
        
    /**
     * Enter description here...
     *
     * @param bool $val
     */
    public function setIsWinner($val)
    {
        $this->_invariant();
        assert(is_bool($val));
        
        if($val == true)
        {
            $this->isWinner = 't';
        }else{
            $this->isWinner = 'f';
        }
        $this->isModifyed = true;
    }
    
    /**
     * Enter description here...
     *
     * @param float $val
     */
    public function setBattleXP($val)
    {
        $this->_invariant();
        assert(is_float($val));
        
        $this->battleXP = $val;
        
        $this->isModifyed = true;
    }
    
    /**
     * Enter description here...
     *
     * @return float
     */
    public function getBattleXP()
    {
        $this->_invariant();                
        return $this->battleXP;             
    }
            
    
    public function isCombOwner()
    {
        return $this->isCombOwner;
    }
    
    protected function _invariant($mode = 'public')
    {
        if(BS_Base::_exitInvariant($mode)) return;
        
        parent::_invariant($mode);
        
        assert(is_int($this->battleID) && $this->battleID>0);
        assert(is_int($this->userInstanceID) && $this->userInstanceID>0);
        assert(array_key_exists($this->participantFlag, self::$allowedParticipantFlags));               
                
        assert($this->isCombOwner == 't' || $this->isCombOwner == 'f');
        assert(is_int($this->isStartRound));
                
        assert($this->isWinner == 't' || $this->isWinner == 'f');
        
        assert($this->battleXP >=0 );
    }
    
    public function commit()
    {
        parent::commit();
        
        if($this->isModifyed == true)
        {
            if(DS_Battle::UpdateBattleParticipant($this))
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