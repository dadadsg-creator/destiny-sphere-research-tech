<?php $CVS_Version = '$Revision: 1.8 $ ; $Date: 2008/10/29 11:40:24 $';

/**
 * ¬ходжные данные дл€ сло€ транспортного менюшки выставлени€ на торги
 *
 */
class TI_TradePanel extends TI_BaseMenu 
{	
	public $courses = array();
	
	public $matrixUpTitles = array();
	
	public $matrixLeftTitles = array();
	
	public $matrixData = array();
	
	public $matrixDataRowTmp = array();
	
	public $tradeFrom = array();

	// —тартовое количество ресурсов, которое можно продавать
	public $minquntity = 1;
	
	// ћаксимально количество турнов, на которые можно выставл€ть лоты
	public $time2trade = 99;
	
	public $turnTime;
	
	public $combAllowedTradeScopes = array();
	
	public $tradeFlags = array(
		'TRADE_USE_PERSONAL_TRADES' => 1,
		'TRADE_USE_TAX' => 1,
		'TRADE_USE_TRANDS' => 1,
		'TRADE_ANONYMOUS_SELL' => 1,
	);
	
	public function __construct($buildingID, $tabName)
	{
		parent::__construct($buildingID, $tabName);								
	}	
	
	public function setTradeFlag($flagName, $value)
	{
	 	assert(key_exists($flagName, $this->tradeFlags));
	 	assert($value === 1 || $value === 0);
	 	
	 	$this->tradeFlags[$flagName] = $value;
	}
	
	public function setCourse($resourceShortName, $value)
	{
		assert(!empty($resourceShortName) && !array_key_exists($resourceShortName, $this->courses));
		
		$this->courses[$resourceShortName] = $value;
	}
	
	public function setMatrixUpperTitle($tradeCategoryID, $typeCategoryID, $articleID, $libName)	
	{
		assert(is_int($tradeCategoryID) && $tradeCategoryID > 0);
		assert(is_int($typeCategoryID) && $typeCategoryID > 0);
		assert(is_int($articleID) && $articleID > 0);
		assert(!empty($libName));
		
		$this->matrixUpTitles[] = array($tradeCategoryID, $typeCategoryID, $articleID, $libName);
	}
	
	public function setMatrixLeftTitle($tradeCategoryID, $typeCategoryID)	
	{
		assert(is_int($tradeCategoryID) && $tradeCategoryID > 0);
		assert(is_int($typeCategoryID) && $typeCategoryID > 0);		
		
		$this->matrixLeftTitles[] = array($tradeCategoryID, $typeCategoryID);
	}
	
	public function setMatrixDataRow($isAllowed, $minPriceDelta, $maxPriceDelta, $tax)
	{
		assert($isAllowed == 0 || $isAllowed == 1);
		assert((is_float($minPriceDelta) && $minPriceDelta <= 0) || $minPriceDelta == 'X');
		assert((is_float($maxPriceDelta) && $maxPriceDelta >= 0) || $maxPriceDelta == 'X');		
		assert((is_float($tax) && $tax >= 0) || $tax == 'X');
		$this->matrixDataRowTmp[] = array($isAllowed, $minPriceDelta, $maxPriceDelta, $tax);			
	}
	
	public function setTurnTime($turnTime)
	{
		assert(is_float($turnTime) && $turnTime > 0);			
		
		$this->turnTime = $turnTime;
	}	
	
	public function setMinquntity($minquntity)
	{
		assert(is_int($minquntity) && $minquntity >= 0);			
		
		$this->minquntity = $minquntity;
	}
	
	public function setTime2trade($time2trade)
	{
		assert(is_int($time2trade) && $time2trade >= 1);			
		
		$this->time2trade = $time2trade;
	}
	
	public function attachMatrixRow()
	{
		assert(count($this->matrixDataRowTmp) == count($this->matrixUpTitles));	

		$this->matrixData[] = $this->matrixDataRowTmp;
		
		$this->matrixDataRowTmp = array();
		
		assert(count($this->matrixData) <= $this->matrixLeftTitles);
	}
	
	public function setTradeFrom($tradeCategoryID, $typeCategoryID, $articleID, $amount, $power, $libName = '', $upkeepData = null, $datum = '')
	{
		assert(is_int($tradeCategoryID) && $tradeCategoryID > 0);
		assert(is_int($typeCategoryID) && $typeCategoryID > 0);
		assert(is_int($articleID) && $articleID > 0);		
		assert(is_float($amount) && $amount >= 0);
		assert(!empty($power));		
		assert(is_array($upkeepData) || is_null($upkeepData));
		
		$this->tradeFrom[] = array($tradeCategoryID, $typeCategoryID, $articleID, $amount, $power, $libName, $upkeepData, $datum);		
	}	

	public function setAllowedTradeScope($tradeScopeID, $tradeScopeName)
	{
		assert(is_int($tradeScopeID) && $tradeScopeID > 0);
		assert(!empty($tradeScopeName));		
		
		$this->combAllowedTradeScopes[] = array($tradeScopeID, $tradeScopeName);
	}
}

?>