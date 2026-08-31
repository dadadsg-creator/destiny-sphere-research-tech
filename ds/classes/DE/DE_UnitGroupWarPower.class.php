<?php $CVS_Version = '$Revision$ ; $Date$';


/**
 * Класс описывает боевые характеристики группы юнитов.
 */
class DE_UnitGroupWarPower extends BS_Base
{
	/** Чистое значение юнита из которого состоит данная группа без бонусов */
	private $attack = null;
	private $defend = null;
	private $demageMin = null;
	private $demageMax = null;	
	private $hitpoint = null;
	
	/** Сюда переданный для добавления DE_BattleBonusesStatic */
	private $bonusesStatic = null;
	
	/** Сюда переданный для добавления DE_BattleBonusesDinamic */
	private $bonusesDinamic = null;

	/** Юнит группа для которой все расчитываем */
	private $unitsGroupID;		   
	
	
	function __construct(DE_UnitsGroup $unitsGroup)
	{				
		parent::__construct();
		$this->unitsGroupID = $unitsGroup->getID();
		$this->_formClear();
		$this->_invariant();		
	}
	
	private function _formClear()
	{		
		$unitAtributes = $this->getUnitsGroup()->getUnit()->getAttributes();
		$this->attack = $unitAtributes->getAttribute('FIGHT_ATTRIBUTE', 'ATTACK');
		$this->defend = $unitAtributes->getAttribute('FIGHT_ATTRIBUTE', 'DEFENCE');
		$this->demageMin = $unitAtributes->getAttribute('FIGHT_ATTRIBUTE', 'DAMAGE_MIN');
		$this->demageMax = $unitAtributes->getAttribute('FIGHT_ATTRIBUTE', 'DAMAGE_MAX');
		$this->hitpoint = $unitAtributes->getAttribute('FIGHT_ATTRIBUTE', 'HITPOINTS');	
	}
	
	public function getUnitsGroup()
	{
		return GB_SingletonObjectStorage::GetSingleton('DE_UnitsGroup', $this->unitsGroupID);
	}
	
	/**
	 * Добавлояет к вычсилению статические бонусы
	 *
	 * @param DE_BattleBonusesStatic $bonus - класс со статическими бонусами
	 * @return unknown
	 */
	public function addStaticBonuses(DE_BattleBonusesStatic $bonus)
	{
		$this->_invariant();
		
		if($this->bonusesStatic instanceof DE_BattleBonusesStatic)
		{
			return false;
		}else{
			$this->bonusStatic = $bonus;		
			return true;
		}			
	}
	
	/**
	 * Убирает из вычисления статические бонусы
	 *
	 */	
	public function removeStaticBonuses()
	{
		$this->_invariant();
		$this->bonusStatic = null;		
	}
	
	
	/**
	 * Добоавляет к вычисдению ещзе и динамические бонусы.
	 *
	 * @param DE_BattleBonusesDinamic $bonus - Класс с динамическими бонусами к этой группе юнитов
	 */
	public function addDinamicBonuses(DE_BattleBonusesDinamic $bonus)
	{
		$this->_invariant();
		$this->bonusesDinamic = $bonus;
	}
	
	
	/**
	 * Убирает из вычисления динамические бонусы
	 *
	 */
	public function removeDinamicBonuses()
	{
		$this->_invariant();
		$this->bonusesDinamic = null;
	}
	
	/**
	 * Говарит о том добавлены ли сейчас для вычсиления статьические б онусы
	 *
	 * @return bool
	 */
	public function haveStaticBonuses()
	{
		$this->_invariant();
		return !is_null($this->bonusesStatic);		
	}
	
	/**
	 * Говарит о том добавлены ли сейчас для вычсиления динамические б онусы
	 *
	 * @return bool
	 */
	public function haveDinamicBonuses()
	{
		$this->_invariant();
		return !is_null($this->bonusesDinamic);		
	}

		
	public function getAttack()
	{		
		$this->_invariant();				
		
		$attack = $this->attack;
		
		if($this->haveStaticBonuses())
		{
			$relativBonusArray = $this->bonusesStatic->getAttackBonus()->getRelativeBonus();						
			$relativBonusArray->sortByValsInvers();
			$relativBonusArray->resetPointer();
			while($bonus = $relativBonusArray->getNextElement())
			{
				assert($bonus < 1);
				$attack = (1 - $attack)*$bonus + $attack;	
			}					
		}				
		
		return $attack;		
	}
	
	public function getDefend()
	{
		$this->_invariant();				
		
		$defend = $this->defend;
		
		if($this->haveStaticBonuses())
		{
			$relativBonusArray = $this->bonusesStatic->getDefendBonus()->getRelativeBonus();			
			$relativBonusArray->sortByValsInvers();
			$relativBonusArray->resetPointer();
			while($bonus = $relativBonusArray->getNextElement())
			{
				assert($bonus->value < 1);
				$defend = (1 - $defend) * $bonuse + $defend;	
			}					
		}									
		
		return $defend;
	}
	
		
	public function getDemage()
	{
		$this->_invariant();				
		
		$damage = (float)GL_CountUnitAverageDemage::Count($this->demageMin, $this->demageMax);
		
		if($this->haveStaticBonuses())
		{
			$damage = $damage * $this->bonusesStatic->getDemageBonus()->getSummRelativeBonus()->value + $damage;
			$damage = $damage + $this->bonusesStatic->getDemageBonus()->getAbsoluteBonus()->value;
		}
		
		$damage = $damage * $this->getUnitsGroup()->getAmount();		
		
		return $damage;
	}
	
	public function getHitpoint ()
	{
		$this->_invariant();
		
		$hitpoint = $this->hitpoint;
		
		if($this->haveStaticBonuses())
		{
			$hitpoint = $hitpoint * $this->bonusesStatic->getHitpointBonus()->getSummRelativeBonus()->value + $hitpoint;
			$hitpoint = $hitpoint + $this->bonusesStatic->getHitpointBonus()->getAbsoluteBonus()->value;
		}
				
		$hitpoint->value = $hitpoint->value * $this->getUnitsGroup()->getAmount();
		return $hitpoint;
	}	
	
	public function getOneHitpoint ()
	{
		$this->_invariant();
		
		$hitpoint = $this->hitpoint;
		
		if($this->haveStaticBonuses())
		{
			$hitpoint = $hitpoint * $this->bonusesStatic->getHitpointBonus()->getSummRelativeBonus()->value + $hitpoint;
			$hitpoint = $hitpoint + $this->bonusesStatic->getHitpointBonus()->getAbsoluteBonus()->value;
		}
		
		return $hitpoint;
	}					
	
	private function _invariant()
	{		
		
		assert(is_float($this->attack));
		assert(is_float($this->defend));
		assert(is_float($this->demageMin));
		assert(is_float($this->demageMax));
		assert(is_float($this->hitpoint));		
		
		assert(is_null($this->bonusesDinamic) || $this->bonusesDinamic instanceof DE_BattleBonusesDinamic);	
		assert(is_null($this->bonusesStatic) || $this->bonusesStatic instanceof DE_BattleBonusesStatic);	
		
		assert(!empty($this->unitsGroupID));
	}
	
	public function __destruct()
	{
		parent::__destruct();
	}
}
?>