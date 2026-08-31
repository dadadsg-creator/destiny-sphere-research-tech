<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_EntityOperation
{
	/**
	 * Сумма  операции 
	 *
	 * @var MU_MoneySumm
	 */
	private $summ;
	
	/**
	 * Тип действия
	 *
	 * @var DE_MoneyActionType
	 */
	private $actionType;
	
	/**
	 * ДОполнительный ID
	 *
	 * @var string
	 */
	private $additionalID = null;
	
	/**
	 * Enter description here...
	 *
	 * @var MU_EntityOperationMember	 
	 */
	private $fromUser;
	
	/**
	 * Enter description here...
	 *
	 * @var MU_EntityOperationMember	 
	 */
	private $toUser;
	
	/**
	 * Enter description here...
	 *
	 * @var MU_EntityOperationMember
	 */
	private $systemUsers = array();
	
	/**
	 * Ключ операции через гейт биллинга
	 *
	 * @var string
	 */
	private $gateKey;
	
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
	
	
	/**
	 * ID записи в журнале
	 *
	 * @var integer
	 */
	private $listID;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_WorldFromCollection
	 */
	private $transferFromWorld;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_WorldFromCollection
	 */
	private $transferToWorld;
	
	public function __construct(DE_MoneyActionType $actionType, MU_EntityMoneySummInterface $summ, MU_EntityOperationMember $fromUser, MU_EntityOperationMember $toUser)
	{							
		$this->actionType = $actionType;
		$this->summ = $summ;		
		$this->fromUser = $fromUser;
		$this->toUser = $toUser;

		/*if($actionType->getNeedLockSystemAccount())
		{
			$this->systemUser[] = MU_EntityOperationMember::CreateSystemAccount($this->fromUser->getWorldShortName());
			$this->systemUser[] = MU_EntityOperationMember::CreateSystemAccount($this->toUser->getWorldShortName());
		}*/
		
	}	
	
	public function setInterworldTransferData(DE_WorldFromCollection $fromWorld, DE_WorldFromCollection $toWorld)
	{
		$this->transferFromWorld = $fromWorld;
		$this->transferToWorld = $toWorld;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_WorldFromCollection
	 */
	public function getTransferFromWorld()
	{
		return $this->transferFromWorld;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_WorldFromCollection
	 */
	public function getTransferToWorld()
	{
		return $this->transferToWorld;
	}
	
	public function setAdditionalID($additionalID)
	{
		assert(is_int($additionalID) || is_string($additionalID));
		
		$this->additionalID = $additionalID;
	}
	
	public function setExpireDateAndReason(GB_Date $expireDate, $reasonName)
	{
		assert(!empty($reasonName));
		
		$this->expireDate = $expireDate;
		$this->reservReasonName = $reasonName;
	}
	
	public function setGateKey($gateKey)
	{
		assert(!empty($gateKey));
		
		$this->gateKey = $gateKey;
	}
	
	public function getGateKey()
	{			
		return $this->gateKey;
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
	 * @return DE_MoneyActionType
	 */
	public function getActionType()
	{
		return $this->actionType;
	}	
	
	public function getAdditionalID()
	{
		return $this->additionalID;
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
	
	/**
	 * Enter description here...
	 *
	 * @return MU_EntityOperationMember
	 */
	public function getFromUser()
	{
		return $this->fromUser;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return MU_EntityOperationMember
	 */
	public function getToUser()
	{
		return $this->toUser;
	}
	
	public function getAcountsListToLock()
	{
		$out = array(
			$this->fromUser,
			$this->toUser			
		);
		
		if(count($this->systemUsers) > 0)
		{
			foreach($this->systemUsers as $k=>$member)
			{
				$out[] = $member;	
			}
		}
		
		return $out;
	}
	
	/**
	 * Если у операции есть фиксированная цена, то выставляем ее
	 *
	 */
	public function setSummFromActionPrice()
	{
		assert($this->actionType->isHavePrice());
		assert($this->summ instanceof MU_EntityMoneyNullSumm);
		
		$this->summ = MU_EntityMoneySumm::CreateFromMoneyActionType($this->actionType);
	}
	
	public function validate()	
	{								
		assert($this->actionType instanceof DE_MoneyActionType);				
		assert($this->summ instanceof MU_EntityMoneySummInterface);			
		assert($this->fromUser instanceof MU_EntityOperationMember);			
		assert($this->toUser instanceof MU_EntityOperationMember);			
		
		if($this->actionType->isNeedAdditionalID())
		{
			assert(is_int($this->additionalID) || is_string($this->additionalID));
		}else{
			assert(is_null($this->additionalID));		
		}
		
		if($this->actionType->getNeedGateKey())
		{
			assert(is_string($this->gateKey) && !empty($this->gateKey));
		}else{
			assert(is_null($this->gateKey));
		}
				
		if($this->actionType->getActionName() == 'MOVE_TO_RESERV')
		{
			assert($this->expireDate instanceof GB_Date);
			assert(!empty($this->reservReasonName));
		}					

		if($this->actionType->getActionName() == 'RECIVE_INTERWORLD_TRANSFER' || $this->actionType->getActionName() == 'SEND_INTERWORLD_TRANSFER')
		{
			assert($this->transferFromWorld instanceof DE_WorldFromCollection);
			assert($this->transferToWorld instanceof DE_WorldFromCollection);
		}else{
			assert(is_null($this->transferFromWorld));
			assert(is_null($this->transferToWorld));
		}
		
		if($this->actionType->getFromUserID() >= 0)
		{
			assert($this->actionType->getFromUserID() == $this->fromUser->getUserID());
		}
		
		if($this->actionType->getToUserID() >= 0)
		{
			assert($this->actionType->getToUserID() == $this->toUser->getUserID());
		}
		
		$toWorldIdentifier = $this->actionType->getToWorldIdetifier();
		if(!empty($toWorldIdentifier))
		{
			assert($toWorldIdentifier == $this->toUser->getWorldShortName());
		}
		
		$fromWorldIdentifier = $this->actionType->getFromWorldIdentifier();
		if(!empty($fromWorldIdentifier))
		{
			assert($fromWorldIdentifier == $this->fromUser->getWorldShortName());
		}
		
		if($this->actionType->isHavePrice())
		{
			assert($this->summ instanceof MU_EntityMoneyNullSumm);
		} else {
			assert($this->summ instanceof MU_EntityMoneySumm);
		}
	}
}

?>