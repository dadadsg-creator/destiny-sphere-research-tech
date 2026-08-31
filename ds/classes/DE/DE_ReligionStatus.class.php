<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Статус религия инстанса
 */
class DE_ReligionStatus extends DE_Base
{
    private $religionID;        
    
    private $religionStatusID;
    
    private $statusName;
    
    
    function __construct($id)
    {
        GB_SingletonObjectStorage::TestSingletonCreation();
        assert(is_int($id));

        parent::__construct($id);

        $data = DS_Religion::GetReligionStatusByUserInstance($id);

        if(is_array($data) && count($data)>0)
        {
            assert(key_exists('id_religion', $data));
            $this->religionID = (int)$data['id_religion'];
        
            assert(key_exists( 'id_status', $data));        
            $this->religionStatusID = (int)$data['id_status'];
    
            assert(key_exists( 'statusname', $data));       
            $this->statusName = $data['statusname'];
        }else{
            $this->religionID = 0;
            $this->religionStatusID = 0;
            $this->statusName = 'NULL';
        }       
        
        $this->_invariant();
    }
    
    static public function CreateFromUserInstanceID($id)
    {               
        assert(is_int($id));
        
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
    }
    
    public function getReligionID()
    {
        return $this->religionID;
    }
    
    public function getReligion()
    {
        if($this->religionID == 0 || $this->statusName != 'RELIGI_STATUS_ACTIVE')
        {
            return DE_ReligionNull::Create();
        }else{
            return DE_Religion::CreateFromID($this->religionID);
        }               
    }
    
    public function getStatusName()
    {
        return $this->statusName;
    }

    public function commit()
    {
        $this->_invariant();
        parent::commit();
    }
    
    public function _invariant($mode = 'public')
    {
        parent::_invariant();
    }
    
    function __destruct()
    {
        $this->_invariant();
        parent::__destruct();
        //$this->commit();
    }
    
}//END OF CLASS




?>
