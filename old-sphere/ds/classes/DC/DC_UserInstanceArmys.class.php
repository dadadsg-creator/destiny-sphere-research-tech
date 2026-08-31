<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Коллекция армий инстанса.
 *
 */
class DC_UserInstanceArmys extends DC_Base 
{
    
    private $uiid;
    
    function __construct($UIID)
    {
        GB_SingletonObjectStorage::TestSingletonCreation();
        
        parent::__construct($UIID, 'DE_Army');
        
        $this->uiid = $UIID;
        
        $data = DS_Army::GetUserInstanceArmys($UIID);       
        assert(is_array($data) && count($data)>0);
                
        foreach($data as $k=>$v)
        {
            assert(key_exists('id_unitcontainer', $v));
            $unitContainerID = (int)$v['id_unitcontainer'];
            assert($unitContainerID>0);
            
            $this->_addNewElement(DE_Army::CreateFromID($unitContainerID, $v));
        }
        
        $this->_invariant('construct');
    }   
            
    static public function CreateFromUserInstanceID($UIID)
    {
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $UIID);                       
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