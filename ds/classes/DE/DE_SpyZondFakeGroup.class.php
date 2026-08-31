<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Группа зондов
 *
 * псевдо-группа для работы калькулятора боя зондов
 * 
 */
class DE_SpyZondFakeGroup extends DE_Base implements DE_SpyZondGroupInterface
{
	/**
	 * кол-во зондов в группе
	 *
	 * @var float
	 */
	private $zondsAmount;
	
	/**
	 * с какой соты 
	 *
	 * @var DE_Comb
	 */
	private $combFrom;
	
	/**
	 * тип юнита
	 *
	 * @var DE_Unit
	 */
	private $unit;
	
	/**
	 * Кол-во потерь
	 *
	 * @var float
	 */
	private $loses = 0;
	
	public function __construct($id, array $data)
	{
		assert(is_int($id) && $id < 0);
		assert(is_array($data));
		
		parent::__construct($id);
			
		$this->combFrom = new DE_CombNull;
		
		assert(key_exists('id_unit', $data));
		$this->unit = DE_Unit::CreateFromID((int)$data['id_unit']);	
		
		assert(key_exists('zonds_amount', $data));
		$this->zondsAmount = (float)$data['zonds_amount'];
		assert($this->zondsAmount > 0);
	}
	
	/**
	 * Создание по ИД 
	 *
	 * @param int $id
	 * @return DE_SpyZondGroup
	 */
	static public function CreateFromID($id, array $data)
	{
		assert(is_int($id) && $id < 0);
		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, $data);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_SpyAction
	 */
	public function getAction()
	{
		return null;
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
		return null;
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
		return null;
	}
	
	/**
	 * Получила ли группа какой-нибудь лог
	 *
	 * @return bool
	 */
	public function haveLog()
	{
		return false;
	}
	
	public function getLogID()
	{
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
		$this->isModifyed = true;
	}
	
	public function getLoses()
	{
		return $this->loses;
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
		return false;
	}
	
	public function delete()
	{
		return;
	}
	
	public function commit()
	{
		return;
	}
}

?>