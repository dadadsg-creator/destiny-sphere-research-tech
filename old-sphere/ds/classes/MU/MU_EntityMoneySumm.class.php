<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_EntityMoneySumm implements MU_EntityMoneySummInterface
{
	private $amount;
	
	private $creditName;
	
	private $priceInGold; // ¬от така€ затыка дл€ возможности установки разной цены и в сере и взолоте
	
	static public $allowCreditName = array('GC' => "gc_amount", 'SC' => 'sc_amount');
	
	public function __construct($amount, $creditName, $priceInGold = 0)
	{
		//$amount = round($amount, 2);
		
		assert(is_int($amount) && $amount > 0);
		assert(is_int($priceInGold) && $priceInGold >= 0);
		assert(key_exists($creditName, self::$allowCreditName));
		
		$this->amount = $amount;
		$this->creditName = $creditName;		
		$this->priceInGold = $priceInGold;		
	}
	
	static public function CreateFromMoneyActionType(DE_MoneyActionType $moneyAction)
	{
		$actionPrice = MU_SearchOperations::GetActionPrice($moneyAction);
		assert(is_array($actionPrice));
		assert(key_exists('gc_price', $actionPrice));
		assert(key_exists('sc_price', $actionPrice));
		assert($actionPrice['gc_price'] != 0 || $actionPrice['sc_price'] != 0);
		
		if($actionPrice['sc_price'] > 0)
		{
			$creditName = 'SC';
			$amount = (int)$actionPrice['sc_price'];
		} else {
			$creditName = 'GC';
			$amount = (int)$actionPrice['gc_price'];
		}
		
		// «атыка
		$priceInGold = (int)$actionPrice['gc_price'];
		
		return new self($amount, $creditName, $priceInGold);
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
	
	public function getAmountGC()
	{
		if('GC' == $this->creditName)
		{
			return $this->amount;
		}else{
			return 0;
		}
	}
	
	public function getAmountSC()
	{
		if('SC' == $this->creditName)
		{
			return $this->amount;
		}else{
			return 0;
		}
	}
	
	public function getPriceInGolg()
	{
		return $this->priceInGold;
	}
}

?>