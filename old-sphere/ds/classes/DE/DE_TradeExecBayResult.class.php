<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Резулттат выболнения операции покупки
 *
 */
class DE_TradeExecBayResult
{
	/**
	 * Enter description here...
	 *
	 * @var DE_TradeLot
	 */
	private $tradeLot;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_EventQueue
	 */
	private $deliveryGoodEvent;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_EventQueue
	 */
	private $deliveryPaymentEvent;
	
	/**
	 * Ресурсы возвращенные продавцу если купили юниты раньше конца
	 *
	 * @var array
	 */
	private $returnUpkeepResource = array();
	
	/**
	 * Ресурсы которые сняли с покупателя за жисть купленных юнитов
	 *
	 * @var array
	 */
	private $upkeepResources = array();
	
	/**
	 * Конструктор создаем на основание самого обекта торга
	 *
	 * @param DE_TradeLot $tradeLot
	 */
	public function __construct(DE_TradeLot $tradeLot)
	{
		$this->tradeLot = $tradeLot;
	}
	
	/**
	 * Ивент доставки товара
	 *
	 * @param DE_EventQueue $event
	 */
	public function setDeliveryGoodEvent(DE_EventQueue $event)
	{
		$this->deliveryGoodEvent = $event;
	}
	
	/**
	 * Ивент доставки оплаты задан только если оплата была не дньгами
	 *
	 * @param DE_EventQueue $event
	 */
	public function setDeliveryPaymentEvent(DE_EventQueue $event)
	{
		$this->deliveryPaymentEvent = $event;
	}
	
	public function setReturnUpkeepResource($resourceName, $value)
	{
		assert(in_array($resourceName, GB_ResourcesList::$unitsLiveUpkeepResources));
		
		$this->returnUpkeepResource[$resourceName] = $value;
	}
	
	public function setUpkeepResource($resourceName, $value)
	{
		assert(in_array($resourceName, GB_ResourcesList::$unitsLiveUpkeepResources));
		
		$this->upkeepResource[$resourceName] = $value;
	}
	
	public function getDeliveryGoodEvent()
	{
		return $this->deliveryGoodEvent;
	}
	
	public function getDeliveryPaymentEvent()
	{
		return $this->deliveryPaymentEvent;
	}			
}

?>