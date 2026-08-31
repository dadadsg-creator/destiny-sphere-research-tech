<?php $CVS_Version = '$Revision$ ; $Date$';


class GB_DatePeriod extends BS_Base
{
	private $startDate;
	private $endDate;
	
	function __construct($startDate, $endDate)
	{
		assert($startDate instanceof GB_Date);
		assert($endDate instanceof GB_Date);
		
		parent::__construct();
		
		$this->startDate = $startDate;
		$this->endDate = $endDate;
		$this->_invariant();
	}
	
	public function getStartDate()
	{
		$this->_invariant();
		return $this->startDate;
	}
	
	public function getEndDate()
	{
		$this->_invariant();
		return $this->endDate;
	}
	
	public function dateInPeriod(GB_Date $date)
	{
		if($date->value >= $this->startDate->value && $date->value <= $this->endDate->value)
		{
			return true;
		}
		
		return false;
	}
	
	protected function _invariant()
	{	
		assert($this->startDate instanceof GB_Date);
		assert($this->endDate instanceof GB_Date);
	}	
	
	
}
?>