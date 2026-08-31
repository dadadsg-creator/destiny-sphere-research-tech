<?php $CVS_Version = '$Revision$ ; $Date$';

class DE_UnitsGroupLoses extends BS_Base
{
	private $unitGroup;
	private $countLoses;
	
	public function __construct(DE_UnitsGroup $unitGroup, GB_Float $countLoses)
	{
		parent::__construct();
		
		$this->unitGroup = $unitGroup;
		
		if($countLoses->value > $this->unitGroup->getAmount())
		{
			$countLoses->value = $this->unitGroup->getAmount();
		}
		
		$this->countLoses = $countLoses;
	}
	
	public function getUnitGroup()
	{
		return $this->unitGroup;
	}
	
	public function getCountLoses()
	{
		return $this->countLoses;
	}
	
	public function __destruct()
	{
		parent::__destruct();
	}
}

?>