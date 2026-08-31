<?php $CVS_Version = '$Revision$ ; $Date$';

class GB_TravelPath extends BS_Base
{
	private $combFrom;
	
	private $combTo;
	
	public function __construct(DE_Comb $combFrom, DE_Comb $combTo)
	{
		parent::__construct();
			
		$this->combFrom = $combFrom;
		$this->combTo = $combTo;		
		
		$this->_invariant();
	}
	
	public function getCombFrom()
	{
		return $this->combFrom;
	}
	
	public function getCombTo()
	{
		return $this->combTo;
	}
	
	private function _invariant()
	{
		assert($this->combTo instanceof DE_Comb);
		assert($this->combFrom instanceof DE_Comb);
		//assert($this->combFrom->getID() != $this->combTo->getID());		
	}
	
	public function __destruct()
	{
		parent::__destruct();
	}
}


?>