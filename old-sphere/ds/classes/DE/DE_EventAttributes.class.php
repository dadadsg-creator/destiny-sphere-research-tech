<?php $CVS_Version = '$Revision$ ; $Date$';


class DE_EventAttributes extends DE_Base
{
	private $attributes = array();
		
	function __construct($eventID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($eventID) && $eventID>0);
		parent::__construct($eventID);
		
		$res = DS_Event::GetEventAtrributesByEventID($eventID);		
		assert(is_array($res) && count($res)>0);
		
		$this->_importAttributes($res);
	}
	 
	static public function CreateFromEventID($eventID)
	{				
		assert(is_int($eventID) && $eventID>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $eventID);						
	}

	public function getAttribute($attributeName)
	{
		$this->_invariant();
		assert(key_exists($attributeName, $this->attributes));
		
		return $this->attributes[$attributeName];
	}
	
	public function setAttribute($attributeName, $value)
	{		
		$this->_invariant();
		
		assert(key_exists($attributeName, $this->attributes));
		
		$this->attributes[$attributeName] = $value;		
	}	
	
	private function _importAttributes($res)
	{
		assert(is_array($res) && count($res)>0);
		
		foreach($res as $k=>$v)
		{			
			$this->attributes[$v['optionname']] = $v['value'];
		}
	}	
	
	protected function _invariant()
	{
		parent::_invariant();
		
		assert(is_array($this->attributes) && count($this->attributes)>0);
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

}//END OF CLASS

?>