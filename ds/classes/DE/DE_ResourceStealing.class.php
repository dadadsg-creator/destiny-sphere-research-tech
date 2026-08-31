<?php

class DE_ResourceStealing extends DE_Base 
{
 	function __construct($id)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($id) && $id>0);

		parent::__construct($id);
		
		
		
	}
	
	
	protected function _invariant()
	{
		parent::_invariant();
		
	}
	
	public function commit()
	{
		$this->_invariant();
		parent::commit();
	}

	function __destruct()
	{
		$this->_invariant();
		$this->commit();
	}

} 
?>