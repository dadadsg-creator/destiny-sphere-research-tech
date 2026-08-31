<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Коллекция армий принадлежащих соте.
 *
 */
class DC_CombArmys extends DC_Base 
{
    
    private $combID;
    
    function __construct($combID)
    {
        GB_SingletonObjectStorage::TestSingletonCreation();
        assert(is_int($combID) && $combID>0);
        
        parent::__construct($combID, 'DE_Army');
        
        $this->combID = $combID;
        
        $data = DS_Army::GetCombArmys($combID);     
        assert(is_array($data) && count($data)>0);
                
        foreach($data as $k=>$v)
        {
            assert(key_exists('id_unitcontainer', $v));
            $unitContainerID = (int)$v['id_unitcontainer'];
            assert($unitContainerID>0);
            
            $this->_addNewElement(DE_Army::CreateFromID($unitContainerID));
        }
        
        $this->_invariant('construct');
    }   
            
    /**
     * Enter description here...
     *
     * @param int $combID
     * @return DC_CombArmys
     */
    static public function CreateFromCombID($combID)
    {
        assert(is_int($combID) && $combID>0);       
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $combID);                     
    }
    
    protected function _collectionCondition($element)
    {
        if(!($element instanceof $this->storedClassName))
        {
            return false;
        }
        
        if($element->getBaseCombID() != $this->armyID)
        {
            return false;
        }
        
        return true;
    }
    
    protected function _invariant($mode = 'public')
    {
        if(BS_Base::_exitInvariant($mode)) return;
        parent::_invariant($mode);              
    }
    
    public function commit()
    {
        $this->_invariant();
        parent::commit();           
    }
    
    function __destruct()
    {
        $this->_invariant();
        parent::__destruct();
        //$this->commit();
    }

}

?>