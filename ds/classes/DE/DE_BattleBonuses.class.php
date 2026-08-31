<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Военные бонусы
 */
class DE_BattleBonuses extends DE_Base
{
    private $attackBonus = null;
    
    private $defendBonus = null;
    
    private $demageBonus = null;
    
    private $hitpointBonus = null;
    
    private $isComplit = false;
    
    private $userInstanceID;
    
    private $countWithArts = 1;
    
    private $combID;
    
    public function __construct($id)
    {
        GB_SingletonObjectStorage::TestSingletonCreation();
        assert(!empty($id));        
        parent::__construct($id);
        
        $idParts = explode('_',$id);
        
        $this->countWithArts = (int)$idParts[2];
        $this->userInstanceID = (int)$idParts[1];
        $this->combID = (int)$idParts[0];
        
        assert($this->combID > 0);
        assert($this->userInstanceID >= 0);
    }
    
    /**
     * Enter description here...
     *
     * @param string $id [ид соты]_[ид инстанса]_[учитывать артефакты]
     * @return DE_BattleBonuses
     */
    static public function CreateFromCombID($id)
    {
        assert(!empty($id));                
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
    }       
    
    public function setIsComplit()
    {
        $this->_invariant();
        
        $this->isComplit = true;
    }
    
    public function getIsComplit()
    {
        $this->_invariant();
        
        return $this->isComplit;
    }
    
    public function setAttackBonus(DE_Bonus $value)
    {
        $this->attackBonus = $value;
    }
    
    public function setDefendBonus(DE_Bonus $value)
    {
        $this->defendBonus = $value;
    }
    
    public function setDemageBonus(DE_Bonus $value)
    {
        $this->demageBonus = $value;
    }
    
    public function setHitpointBonus(DE_Bonus $value)
    {
        $this->hitpointBonus = $value;
    }
    
    /**
     * Enter description here...
     *
     * @return DE_Bonus
     */
    public function getAttackBonus()
    {
        $this->_invariant();
        return $this->attackBonus;
    }
    
    /**
     * Enter description here...
     *
     * @return DE_Bonus
     */
    public function getDefendBonus()
    {
        $this->_invariant();
        return $this->defendBonus;
    }
    
    /**
     * Enter description here...
     *
     * @return DE_Bonus
     */
    public function getDemageBonus()
    {
        $this->_invariant();
        return $this->demageBonus;
    }
    
    /**
     * Enter description here...
     *
     * @return DE_Bonus
     */
    public function getHitpointBonus()
    {
        $this->_invariant();
        return $this->hitpointBonus;
    }
    
    public function validate()
    {
        $this->_invariant();
    }
    
    protected function _invariant($mode = 'public')
    {
        if(BS_Base::_exitInvariant($mode)) return;
        
        assert($this->attackBonus instanceof DE_Bonus);
        assert($this->defendBonus instanceof DE_Bonus);
        assert($this->demageBonus instanceof DE_Bonus);
        assert($this->hitpointBonus instanceof DE_Bonus);       
    }
    
    public function __destruct()
    {
        parent::__destruct();
    }
}

?>