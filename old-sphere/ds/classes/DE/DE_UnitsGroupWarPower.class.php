<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Класс описывает боевые характеристики группы юнитов.
 */
class DE_UnitsGroupWarPower extends BS_Base
{
	/** Чистое значение юнита из которого состоит данная группа без бонусов */
	private $attack = null;

	private $defend = null;

	private $demageMin = null;

	private $demageMax = null;

	private $hitpoint = null;

	/** Сюда переданный для добавления DE_BattleBonusesStatic */
	private $bonusesStatic = null;

	/** Юнит группа для которой все расчитываем */
	private $unitsGroup;


	function __construct(DE_UnitsGroupInterface $unitsGroup, $bonus = null)
	{
		assert(is_null($bonus) || ($bonus instanceof DE_BattleBonuses));

		parent::__construct();
		$this->unitsGroup = $unitsGroup;
		$this->_formClear();

		if(is_null($bonus))
		{
			$this->_addStaticBonuses($unitsGroup->getArmy()->getBonus());
		} else {
			$this->_addStaticBonuses($bonus);
		}

		$this->_invariant('construct');
	}

	private function _formClear()
	{
		$unitAtributes = $this->unitsGroup->getUnit()->getAttributes();
		$this->attack = $unitAtributes->getAttribute('FIGHT_ATTRIBUTE', 'ATTACK');
		$this->defend = $unitAtributes->getAttribute('FIGHT_ATTRIBUTE', 'DEFENCE');
		$this->demageMin = $unitAtributes->getAttribute('FIGHT_ATTRIBUTE', 'DAMAGE_MIN');
		$this->demageMax = $unitAtributes->getAttribute('FIGHT_ATTRIBUTE', 'DAMAGE_MAX');
		$this->hitpoint = $unitAtributes->getAttribute('FIGHT_ATTRIBUTE', 'HITPOINTS');
	}

	public function getUnitsGroup()
	{
		return $this->unitsGroup;
	}

	/**
	 * Добавлояет к вычсилению статические бонусы
	 *
	 * @param DE_BattleBonusesStatic $bonus - класс со статическими бонусами
	 * @return unknown
	 */
	private function _addStaticBonuses(DE_BattleBonuses $bonus)
	{
		$this->_invariant();

		$this->bonusesStatic = $bonus;

		return true;
	}

	public function getAttack()
	{
		$this->_invariant();

		$attack = $this->attack;

		$attack = $attack * $this->bonusesStatic->getAttackBonus()->getSummRelativeBonus() + $attack;
		$attack = $attack + $this->bonusesStatic->getAttackBonus()->getAbsoluteBonus();

		/*Старый варинт начисления бонуса
		$relativBonusArray = $this->bonusesStatic->getAttackBonus()->getRelativeBonus();
		$relativBonusArray->resetPointer();
		while($bonus = $relativBonusArray->getNextElement())
		{
		assert($bonus < 1);
		$attack = (1 - $attack)*$bonus + $attack;
		}*/

		return $attack;
	}

	/**
	 * При использовании защитного поля защита и, соответственно, регрессия, не используется
	 *
	 * @return unknown
	 */
	public function getDefend()
	{
		$this->_invariant();

		$defend = $this->defend;


		$relativBonusArray = $this->bonusesStatic->getDefendBonus()->getRelativeBonus();
		$relativBonusArray->resetPointer();
		while($bonus = $relativBonusArray->getNextElement())
		{
			assert($bonus < 1);
			$defend = (1 - $defend) * $bonus + $defend;
		}

		/*$defend = $defend * $this->bonusesStatic->getDefendBonus()->getSummRelativeBonus() + $defend;
		$defend = $defend + $this->bonusesStatic->getDefendBonus()->getAbsoluteBonus();	*/

		return $defend;
	}


	public function getDemage()
	{
		$this->_invariant();

		$damage = (float)GL_CountUnitAverageDemage::Count($this->demageMin, $this->demageMax);

		$damage = $damage * $this->bonusesStatic->getDemageBonus()->getSummRelativeBonus() + $damage;
		$damage = $damage + $this->bonusesStatic->getDemageBonus()->getAbsoluteBonus();


		$damage = $damage * $this->getUnitsGroup()->getAmount();

		return $damage;
	}

	/**
	 * NEW!! Защитное поле, используется вместо старой защиты
	 *
	 * @return float
	 */
	public function getDefenceField()
	{
		$this->_invariant();

		// пересчет из защиты в защитное поле
		$defenceField = 1/(1 - $this->defend) - 1;

		// бонусы считаются просто
		$defenceField = $defenceField * $this->bonusesStatic->getDefendBonus()->getSummRelativeBonus() + $defenceField;
		$defenceField = $defenceField + $this->bonusesStatic->getDefendBonus()->getAbsoluteBonus();

		return $defenceField;
	}

	public function getHitpoint ()
	{
		$this->_invariant();

		$hitpoint = $this->hitpoint;


		$hitpoint = $hitpoint * $this->bonusesStatic->getHitpointBonus()->getSummRelativeBonus() + $hitpoint;
		$hitpoint = $hitpoint + $this->bonusesStatic->getHitpointBonus()->getAbsoluteBonus();


		$hitpoint = $hitpoint * $this->getUnitsGroup()->getAmount();
		return $hitpoint;
	}

	public function getOneHitpoint ()
	{
		$this->_invariant();

		$hitpoint = $this->hitpoint;

		$hitpoint = $hitpoint * $this->bonusesStatic->getHitpointBonus()->getSummRelativeBonus() + $hitpoint;
		$hitpoint = $hitpoint + $this->bonusesStatic->getHitpointBonus()->getAbsoluteBonus();

		return $hitpoint;
	}

	private function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;

		assert(is_float($this->attack));
		assert(is_float($this->defend));
		assert(is_float($this->demageMin));
		assert(is_float($this->demageMax));
		assert(is_float($this->hitpoint));

		assert(is_null($this->bonusesStatic) || $this->bonusesStatic instanceof DE_BattleBonusesStatic);

		assert($this->unitsGroup instanceof DE_UnitsGroupInterface);
	}

	public function __destruct()
	{
		parent::__destruct();
	}
}
?>