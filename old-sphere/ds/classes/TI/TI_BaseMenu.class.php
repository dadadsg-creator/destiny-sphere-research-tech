<?php $CVS_Version = '$Revision$ ; $Date$';

class TI_BaseMenu extends BS_Base
{	
	private $buildingID;
	
	private $tabName;
	
	private $isComplit = false;
	
	public function __construct($buildingID, $tabName)
	{
		assert($buildingID > 0);				
		$this->buildingID = $buildingID;
		
		assert(!empty($tabName));
		$this->tabName = $tabName;
		
		parent::__construct('construct');
	}
	
	public function getBuildingID()
	{
		$this->_invariant();
		
		return $this->buildingID;
	}
	
	public function getTabName()
	{
		$this->_invariant();
		
		return $this->tabName;
	}
	
	public function setIsComplit()
	{
		$this->isComplit = true;
	}
	
	public function isComplit()
	{
		return $this->isComplit;
	}
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		assert($this->buildingID > 0);
		assert(!empty($this->tabName));
	}
}

?>