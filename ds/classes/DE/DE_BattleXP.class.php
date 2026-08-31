<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * 
 * Класс с боевым рейтингом
 */
class DE_BattleXP extends DE_Base 
{   
    // Боевые Очки          
    private $battleXP;
    
    // Боевой Рейтинг
    private $battleXPAbsolute;

    // Опыт от NPC
    private $npcXP;
    
    /** Флаг того что у члеовека небыо XP до этого */
    private $startWithEmptyFlag = false;

    function __construct($id) 
    {       
        GB_SingletonObjectStorage::TestSingletonCreation();
        assert(is_int($id));
        
        parent::__construct($id);
                        
        $data = DS_UserInstance::GetBattleXPByUIID($id, false);

        if(count($data) == 0)
        {
            $this->startWithEmptyFlag = true;
            $this->battleXP = (float)0; 
            $this->npcXP = (float) 0;
        }else{
            $this->startWithEmptyFlag = false;
            $this->battleXP = (float) $data[0]['value'];
            $this->battleXPAbsolute = (float) $data[0]['absolute_value'];
            $this->npcXP = (float) $data[0]['npc_xp'];
        }
                                    
        $this->_invariant('construct');
        
    }//END OF METHOD
        
    /**
     * Enter description here...
     *
     * @param int $id UIID
     * @return DE_BattleXP
     */
    static public function CreateFromID($id)
    {
        assert(is_int($id));
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
    }       

    public function getXP()
    {
        return $this->battleXP;
    }       

    public function getNpcXP()
    {
        return $this->npcXP;
    }       


    public function getXPAbsolute()
    {
        return $this->battleXPAbsolute;
    }
    
    public function getStartWithEmptyFlag()
    {
        return $this->startWithEmptyFlag;
    }
    
    public function deltaXP($delta)
    {
        assert(is_float($delta) && $delta >= 0);
        $this->battleXP += $delta;
        $this->isModifyed = true;
    }

    public function deltaNpcXP($delta)
    {
        assert(is_float($delta) && $delta >= 0);
        $this->npcXP += $delta;
        $this->isModifyed = true;
    }
    
    public function deltaXPAbsolute($delta)
    {
        assert(is_float($delta) && $delta >= 0);
        $this->battleXPAbsolute += $delta;
        $this->isModifyed = true;
    }
    
    public function minusXP($delta)
    {
        assert(is_float($delta) && $delta >= 0);
        assert($delta <= $this->battleXP);
        $this->battleXP -= $delta;
        $this->isModifyed = true;
    }
    
    protected function _invariant($mode = 'public')
    {
        if(BS_Base::_exitInvariant($mode)) return;
        parent::_invariant($mode);
        assert(is_float($this->battleXP) && $this->battleXP>=0);        
        assert(is_bool($this->startWithEmptyFlag));
    }
    
    public function commit()
    {
        parent::commit();
        
        if($this->isModifyed == true)
        {
            if(DS_UserInstance::UpdateBattleXP($this))
            {
                $this->isModifyed = false;
            }           
            $ranking = DE_Ranking::CreateFromID($this->id);
            $ranking->_checkLevelCahge();
        }
    }
    
    function __destruct()
    {
        $this->_invariant();        
    }

}//END OF CLASS


?>