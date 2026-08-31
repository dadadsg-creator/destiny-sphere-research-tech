<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Коллекция армий принадлежащих соте.
 */
class DC_UserInstanceCombs extends DC_Base 
{
    function __construct($userInstanceID)
    {
        GB_SingletonObjectStorage::TestSingletonCreation();
        assert(is_int($userInstanceID));
        
        parent::__construct($userInstanceID, 'DE_Comb');
        
        $data = DS_Comb::GetUserInstanceCombs($userInstanceID);     
        assert(is_array($data) && count($data)>0);
                
        foreach($data as $k=>$v)
        {
            assert(key_exists('id_comb', $v));
            $combID = (int)$v['id_comb'];
            assert($combID>0);
                        
            $this->_addNewElement(DE_Comb::CreateFromID($combID));
        }
        
        $this->_invariant('construct');
    }   
            
    static public function CreateFromUserInstanceID($userInstanceID)
    {
        assert(is_int($userInstanceID));
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $userInstanceID);             
    }
    
    protected function _invariant($mode = 'construct')
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