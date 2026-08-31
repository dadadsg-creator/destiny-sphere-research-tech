<?php $CVS_Version = '$Revision$ ; $Date$';

class GB_MoneySumm
{
	private $amount;
	
	private $creditName;
	
	static public $allowCreditName = array('GC' => "gc_amount", 'SC' => 'sc_amount');
	
	public function __construct($amount, $creditName)
	{
		$amount = round($amount, 2);
		
		assert(is_float($amount) && $amount > 0);
		assert(key_exists($creditName, self::$allowCreditName));
		
		$this->amount = $amount;
		$this->creditName = $creditName;		
	}
	
	public function getAmount()
	{
		return $this->amount;
	}
	
	public function getCreditName()
	{
		return $this->creditName;
	}
	
	public function getFieldName()
	{
		return self::$allowCreditName[$this->creditName];
	}
}

?>