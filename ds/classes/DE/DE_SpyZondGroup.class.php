<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Группа зондов
 *
 * 
 * DE_SpyZondGroupInterface 
 * DE_SpyZondGroup 
 * DE_SpyZondDefenceGroup 
 * 
 */
class DE_SpyZondGroup extends DE_Base implements DE_SpyZondGroupInterface
{
	/**
	 * кол-во зондов в группе
	 *
	 * @var float
	 */
	private $zondsAmount;
	
	/**
	 * тип действия
	 *
	 * @var DE_SpyAction
	 */
	private $action;
	
	/**
	 * с какой соты 
	 *
	 * @var DE_Comb
	 */
	private $combFrom;
	
	/**
	 * на какую соту
	 *
	 * @var DE_Comb
	 */
	private $combTo = null;
	
	/**
	 * время окончания действия
	 *
	 * @var GB_Date
	 */
	private $endDate;
	
	/**
	 * тип юнита
	 *
	 * @var DE_Unit
	 */
	private $unit;
	
	/**
	 * Лог разведки если есть
	 *
	 * @var DE_SpyLog
	 */
	private $log = null;
	
	/**
	 * ID евента который привязан к группе
	 *
	 * @var DE_EventQueue
	 */
	private $eventQueue;
	
	/**
	 * Кол-во потерь
	 *
	 * @var float
	 */
	private $loses = 0;
	
	/**
	 * Отправлены на один раунд
	 *
	 * @var bool
	 */
	private $oneRound = false;
	
	public function __construct($id)
	{
		assert(is_int($id) && $id > 0);
		parent::__construct($id);
		
		$data = DS_SpyAction::GetSpyGroupActionByID($id);
		
		assert(is_array($data));
		
		assert(key_exists('id_spy_action', $data));
		$this->action = DE_SpyAction::CreateFromID((int)$data['id_spy_action']);
		
		assert(key_exists('zonds_amount', $data));
		$this->zondsAmount = (float)$data['zonds_amount'];
		assert($this->zondsAmount > 0);
		
		assert(key_exists('id_comb_from', $data));
		$this->combFrom = DE_Comb::CreateFromID((int)$data['id_comb_from']);
		
		assert(key_exists('id_comb_to', $data));
		if(!empty($data['id_comb_to']))
		{
			$this->combTo = DE_Comb::CreateFromID((int)$data['id_comb_to']);
		}
		
		assert(key_exists('end_time', $data));
		$this->endDate = GB_TypeFactory::CreateBase('GB_Date', $data['end_time']);
		
		assert(key_exists('id_unit', $data));
		$this->unit = DE_Unit::CreateFromID((int)$data['id_unit']);
		
		assert(key_exists('id_spy_log', $data));
		if(!empty($data['id_spy_log']))
		{
			$this->log = DE_SpyLog::CreateFromID((int)$data['id_spy_log']);
		}
		
		assert(key_exists('id_eventqueue', $data));
		$this->eventQueue = DE_EventQueue::CreateFromID((int)$data['id_eventqueue']);
		
		assert(key_exists('one_round', $data));
		if($data['one_round'] == 't')
		{
			$this->oneRound = true;
		}
	}
	
	/**
	 * Создание по ИД 
	 *
	 * @param int $id
	 * @return DE_SpyZondGroup
	 */
	static public function CreateFromID($id)
	{
		assert(is_int($id) && $id > 0);
		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param DE_EventQueue $eventQueue
	 * @return DE_SpyZondGroup
	 */
	static public function CreateFromEventQueue(DE_EventQueue $eventQueue)
	{
		$groupID = DS_SpyAction::GetSpyGroupIDByEventQueue($eventQueue);
		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $groupID);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_SpyAction
	 */
	public function getAction()
	{
		return $this->action;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_Comb
	 */
	public function getCombFrom()
	{
		return $this->combFrom;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_Comb
	 */
	public function getCombTo()
	{
		return $this->combTo;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_Unit
	 */
	public function getUnit()
	{
		return $this->unit;
	}
	
	/**
	 * Текущее кол-во зондов
	 *
	 * @return integer
	 */
	public function getAmount()
	{
		//return $this->zondsAmount;
		return (int)$this->getRealAmount(true);
	}
	
	/**
	 * Начальное кол-во зондов
	 *
	 * @return integer
	 */
	public function getStartAmount()
	{
		return (int)$this->zondsAmount;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return GB_Date
	 */
	public function getEndDate()
	{
		return $this->endDate;
	}
	
	/**
	 * Получила ли группа какой-нибудь лог
	 *
	 * @return bool
	 */
	public function haveLog()
	{
		if($this->log instanceof DE_SpyLog)
		{
			return true;
		}
		
		return false;
	}
	
	public function getLogID()
	{
		if($this->haveLog())
		{
			return $this->log->getID();
		}
		
		return null;
	}
	
	/**
	 * Лог разведки
	 *
	 * @return DE_SpyLog
	 */
	public function getLog()
	{
		return $this->log;
	}
	
	/**
	 * Увеличить кол-во жертв среди зонтов
	 *
	 * @param float $loses
	 */
	public function setLoses($loses)
	{
		assert(is_float($loses));
		assert($loses > 0);
		
		$this->loses += $loses;
		if($this->loses > $this->zondsAmount)
		{
			$this->loses = $this->zondsAmount;
		}
		$this->isModifyed = true;
	}
	
	public function getLoses()
	{
		return floor($this->loses);
	}
	
	public function getAttack()
	{
		return $this->unit->getAttributes()->getAttribute('FIGHT_ATTRIBUTE', 'ATTACK');
	}
	
	public function getDemage()
	{
		$demageMin = $this->unit->getAttributes()->getAttribute('FIGHT_ATTRIBUTE', 'DAMAGE_MIN');
		$demageMax = $this->unit->getAttributes()->getAttribute('FIGHT_ATTRIBUTE', 'DAMAGE_MAX');
		
		return GL_CountUnitAverageDemage::Count($demageMin, $demageMax);
	}
	
	public function getDefence()
	{
		return $this->unit->getAttributes()->getAttribute('FIGHT_ATTRIBUTE', 'DEFENCE');
	}
	
	public function getWarPower()
	{		
		return $this->getRealAmount() * $this->getAttack() * $this->getDemage();
	}
	
	public function getDefenceField()
	{		
		return 1/(1 - $this->getDefence()) - 1;
	}
	
	public function getOneHitPoints()
	{		
		return $this->unit->getAttributes()->getAttribute('FIGHT_ATTRIBUTE', 'HITPOINTS');
	}
	
	/**
	 * Лог разведки
	 *
	 * @return DE_SpyLog
	 */
	public function getEventQueue()
	{
		return $this->eventQueue;
	}
	
	/**
	 * Реальное кол-во (кол-во минус жертвы)
	 *
	 * @param bool $loses округлять или нет значение
	 * @return float
	 */
	public function getRealAmount($round = false)
	{
		assert(is_bool($round));
		
		$amount = max(0, $this->zondsAmount - $this->loses);
		
		if($round)
		{
			$amount = ceil($amount);
		}
		
		return $amount;
	}

	public function isSendForOneRound()
	{
		return $this->oneRound;
	}
	
	public function delete()
	{
		parent::delete();
		
		// запись в таблице ds_spy_group_action удалится каскадом после удаления евента
		$this->eventQueue->setIsTrash(true);
	}
	
	public function preCommit()
	{
//SU_DebugTools::LogAll('commit start '.$this->id, __METHOD__, 0);
		if($this->isModifyed)
		{
			if($this->getAmount() != $this->getStartAmount())
			{				
				// Чтобы не было отрицательного количества
				//assert($this->getAmount() - $this->getStartAmount() > 0);
				
				// сменить AWAY_AMOUNT на соте $this->combFrom
				$this->combFrom->getAttributes()->deltaAttribute('AWAY_AMOUNT', $this->unit->getName(), $this->getAmount() - $this->getStartAmount());
				// изменить атрибуты евента
				$this->eventQueue->getAttributes()->setAttribute('Amount', $this->getAmount());
				
				$this->zondsAmount = $this->getAmount();
				$this->loses = 0;
			}
			
			if($this->getAmount() == 0 && !$this->isDeleted)
			{
				$this->isDeleted = true;
				$this->delete();
			}
		}
	}
	
	public function commit()
	{
		parent::commit();
		
		if($this->isModifyed && !$this->isDeleted)
		{
			// изменить запись в таблице групп ds_spy_group_action
			DS_SpyAction::UpdateSpyGroupAction($this);
		}
	}
}

?>