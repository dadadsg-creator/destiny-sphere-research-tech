<?php $CVS_Version = '$Revision$ ; $Date$';

class GB_MoneyUserInstanceOperation
{
	/**
	 * Сумма  операции 
	 *
	 * @var MU_MoneySumm
	 */
	private $summ;
	
	/**
	 * От какого инстанса проводим операцию
	 *
	 * @var DE_UserInstance
	 */
	private $fromUserInstance;
	
	/**
	 * К какому инстансу 
	 *
	 * @var DE_UserInstance
	 */
	private $toUserInstance;
	
	/**
	 * Тип действия
	 *
	 * @var string
	 */
	private $actionTypeName;
	
	/**
	 * ДОполнительный ID
	 *
	 * @var integer
	 */
	private $additionalID = 0;
	
	/**
	 * Дата устаревания. Нужна только для резервирования бабла
	 *
	 * @var GB_Date
	 */
	private $expireDate;
	
	/**
	 * Причина резервирования денег
	 *
	 * @var string
	 */
	private $reservReasonName;
	
	public function __construct($actionTypeName, MU_MoneySumm $summ, DE_UserInstance $fromInstance, $toInstance = null)
	{
		assert(is_null($toInstance) || $toInstance instanceof DE_UserInstance);
		assert(!empty($actionTypeName) && is_string($actionTypeName));
		
		$this->actionTypeName = $actionTypeName;
		$this->summ = $summ;
		$this->fromUserInstance = $fromInstance;
		$this->toUserInstance = $toInstance;
	}		
	
	public function setAdditionalID($additionalID)
	{
		assert(is_int($additionalID) && $additionalID > 0);
		
		$this->additionalID = $additionalID;
	}
	
	public function setExpireDateAndReason(GB_Date $expireDate, $reasonName)
	{
		assert(!empty($reasonName));
		
		$this->expireDate = $expireDate;
		$this->reservReasonName = $reasonName;
	}
	
	public function getActionTypeName()
	{
		return $this->actionTypeName;
	}	
	
	/**
	 * Enter description here...
	 *
	 * @return DE_UserInstance
	 */
	public function getFromUserInstance()
	{
		return $this->fromUserInstance;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_UserInstance
	 */
	public function getToUserInstance()
	{
		return $this->toUserInstance;
	}
	
	public function havePartner()
	{
		return $this->toUserInstance instanceof DE_UserInstance;
	}
	
	public function getAdditionalID()
	{
		return $this->additionalID;
	}
	
	
	public function getResrvReasonName()
	{
		return $this->reservReasonName;
	}
		
	/**
	 * Enter description here...
	 *
	 * @return GB_Date
	 */
	public function getExpireDate()
	{
		return $this->expireDate;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return MU_MoneySumm
	 */
	public function getSumm()
	{
		return $this->summ;
	}
	
	public function validate()
	{
		assert(!empty($this->actionTypeName));
		$actionTypeData = MU_SearchUserInstanceOperations::GetActionTypeDataByName($this->actionTypeName);
		assert(is_array($actionTypeData) && count($actionTypeData) > 0);
		
		//Проверяем это действие доступно ли это нам
		assert(key_exists('scopename', $actionTypeData) && $actionTypeData['scopename'] == 'USER_INSTANCE');
		
		assert($this->summ instanceof MU_MoneySumm);
		assert($this->fromUserInstance instanceof DE_UserInstance);
		
		assert(key_exists('need_partner', $actionTypeData));
		if($actionTypeData['need_partner'] == 't')
		{
			assert($this->toUserInstance instanceof DE_UserInstance);
		}else{
			assert(is_null($this->toUserInstance));
		}
		
		assert(key_exists('need_additional_id', $actionTypeData));
		if($actionTypeData['need_additional_id'] == 't')
		{
			assert(is_int($this->additionalID) && $this->additionalID > 0);
		}else{
			assert(is_null($this->additionalID));		
		}
		
		assert(key_exists('action_name', $actionTypeData));
		if($actionTypeData['action_name'] == 'MOVE_TO_RESERV')
		{
			assert($this->expireDate instanceof GB_Date);
			assert(!empty($this->reservReasonName));
		}	
	}
}

?>