<?php $CVS_Version = '$Revision$ ; $Date$';

class TI_SpyTrainUnit extends TI_BaseMenu 
{
	/**
	 * units = array(
	 * 		id => array('ATTR' => 'VAL')
	 * 	)
	 *
	 * @var array
	 */
	public $units = array();
	
	public $battleXP;
	
	static private $allowedParams = array('AMOUNT_TRAINING', 'MAX_AMOUNT', 'CURRENT_AMOUNT', 'PRODUCTION_COST', 'PRICE_DELTA', 'AWAIL');
	
	function __construct($buildingID, $tabName)
	{
		parent::__construct($buildingID, $tabName);
	}
	
	public function addUnit($unitID)
	{
		assert(!empty($unitID) && !key_exists($unitID, $this->units));
		$this->units[$unitID] = array();
	}
	
	public function addParam($unitID, $key, $value)
	{
		assert(!empty($unitID) && key_exists($unitID, $this->units));
		assert(in_array($key, self::$allowedParams));
		
		$this->units[$unitID][$key] = $value;
	}
	
	public function setBattleXP($battleXP)
	{
		assert(is_numeric($battleXP) && $battleXP >= 0);
		
		$this->battleXP = $battleXP;
	}
}

?>