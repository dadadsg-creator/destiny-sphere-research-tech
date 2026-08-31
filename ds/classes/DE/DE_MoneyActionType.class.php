<?php $CVS_Version = '$Revision$ ; $Date$';
class DE_MoneyActionType extends DE_Base
{	
	/**
	 * Имя побработчкика платежа
	 *
	 * @var string
	 */
	private $handlerName;	
	
	/**
	 * Есть  ли необходимость в дополнительном ID для записи действия с наружи
	 *
	 * @var boolean
	 */
	private $needAdditionalID;
	
	/**
	 * Абровиатура из какого мира обезательно должна бть направленна операция
	 *
	 * @var string
	 */
	private $fromWorld;
		
	/**
	 * Абровиатура мира куд адолжна быть обезательно направллнеа операция
	 *
	 * @var string
	 */
	private $toWorld;
	
	/**
	 * От какого ID пользователя обезательно должен идти платеж
	 *
	 * @var integer
	 */
	private $userFromID;
	
	/**
	 * Какому ID пользователя должне идти платеж
	 *
	 * @var integer
	 */
	private $userToID;
	
	/**
	 * Надо ли дополнительно лочить сиситемынй счет
	 *
	 * @var boolean
	 */
	private $needGateKey;
	
	/**
	 * Активна ли услуга
	 *
	 * @var boolean
	 */
	private $active;
	
	/**
	 * Таблица для логирования операции
	 *
	 * @var string
	 */
	private $logActionTable;
	
	/**
	 * ID действия в БД.
	 *
	 * @var integer	 
	 */
	private $actionID;
	
	/**
	 * Есть ли жестко заданная цена
	 *
	 * @var unknown_type
	 */
	private $havePrice;
	
	public function __construct($id)
	{	
		assert(is_string($id) && !empty($id));	
		parent::__construct($id);
		
		$data = MU_SearchOperations::GetActionTypeDataByName($id);		
		assert(is_array($data) && count($data)>0);
		
		assert(key_exists('handler_class_name', $data));		
		$this->handlerName = $data['handler_class_name'];
		assert(!empty($this->handlerName));		
		
		assert(key_exists('need_additionl_id', $data));		

		if($data['need_additionl_id'] == 'f')
		{
			$this->needAdditionalID = false;	
		}else{
			$this->needAdditionalID = true;	
		}
		assert(is_bool($this->needAdditionalID));	
		
		assert(key_exists('from_world', $data));	
		$this->fromWorld = (string)$data['from_world'];
		
		assert(key_exists('to_world', $data));		
		$this->toWorld = (string)$data['to_world'];
		
		assert(key_exists('id_user_from', $data));		
		$this->userFromID = (integer)$data['id_user_from'];
		
		assert(key_exists('id_user_to', $data));	
		$this->userToID = (integer)$data['id_user_to'];	
						
		assert(key_exists('need_gate_key', $data));	
		if('f' == $data['need_gate_key'])
		{
			$this->needGateKey = false;	
		}else{
			$this->needGateKey = true;	
		}
		
		assert(key_exists('is_active', $data));	
		if('f' == $data['is_active'])
		{
			$this->active = false;	
		}else{
			$this->active = true;	
		}
			
		assert(key_exists('log_action_table', $data));	
		$this->logActionTable = $data['log_action_table']; 
		assert(!empty($this->logActionTable));
		
		assert(key_exists('id_money_action', $data));	
		$this->actionID = (int)$data['id_money_action'];
		assert($this->actionID > 0);
		
/*		assert(key_exists('have_price', $data));
		if('t' == $data['have_price'])
		{
			$this->havePrice = true;
		}else{
			$this->havePrice = false;	
		}*/
	}
	
	static public function CreateFromID($id)
	{
		assert(is_string($id) && !empty($id));					
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
	}	
		
	static public function CreateFromName($id)
	{
		assert(is_string($id) && !empty($id));					
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
	}	
	
	public function getHandlerName()
	{
		return $this->handlerName;
	}

	public function getActionID()
	{
		return $this->actionID;
	}
	
	public function isNeedAdditionalID()
	{
		return $this->needAdditionalID;
	}
	
	
	public function getFromWorldIdentifier()
	{
		return $this->fromWorld;
	}
	
	public function getToWorldIdetifier()
	{
	 	return $this->toWorld;			
	}
	
	public function getFromUserID()
	{
		return $this->userFromID;
	}
	
	public function getToUserID()
	{
		return $this->userToID;
	}

	public function getNeedGateKey()
	{
		return $this->needGateKey;
	}
	
	public function isActive()
	{
		return $this->active;
	}
	
	public function getActionName()
	{
		return $this->getID();
	}
	
	public function getLogActionTable()
	{
		return $this->logActionTable;
	}
	
	public function isHavePrice()
	{
		return $this->havePrice;
	}
}



?>