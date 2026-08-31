<?php $CVS_Version = '$Revision$ ; $Date$';

class GB_EventParticipants extends BS_Base
{   
    private $participants = array();
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function setParticipant(DE_UserInstance $instance)
    {
        $this->_invariant();
        
        if (in_array($instance->getID(), $this->participants)) return;
        
        $this->participants[] = $instance->getID();
    }
    
    public function getParticipantsArray()
    {
        $this->_invariant();
        
        assert(count($this->participants>=1));
        return $this->participants;
    }
    
    private function _invariant()
    {
        assert(is_array($this->participants));
    }
    
    public function __destruct()
    {
        parent::__destruct();
    }
}


?>