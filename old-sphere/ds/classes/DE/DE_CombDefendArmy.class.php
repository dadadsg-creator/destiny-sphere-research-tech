<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Объект, который представляет армии, стоящии на защите соты, в виде единой сущности
 *
 */
class DE_CombDefendArmy extends DE_Base implements DE_ArmyInterface
{
	
	private $combID;
	
	private $hitedWarPowers = array();
	
	private $armyBonus;
	
	/**
	 * Enter description here...
	 *
	 * @var DC_CombDefendArmys
	 */
	private $combDefendArmys;
	
	/**
	 * Enter description here...
	 *
	 * @var DC_CombDefendUnitGroups
	 */
	private $combDefendUnitGroupsCollection;
	
	function __construct($combID)
	{		
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($combID) && $combID>0);		
		
		parent::__construct($combID);		
		
		$this->combID = (int)$combID;

		assert(is_int($this->combID) && $this->combID>0);
		assert(is_array($this->hitedWarPowers));
		assert(is_null($this->armyBonus) || $this->armyBonus instanceof DE_BattleBonusesStatic);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $combID
	 * @return DE_CombDefendArmy
	 */
	static public function CreateFromCombID($combID)
	{		
		assert(is_int($combID) && $combID>0);				
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $combID);						
	}
	
	public function setLoses($loses)
    {
    	assert(is_float($loses) && $loses >= 0);
    	
   
    	    	       
    	$this->loses = $loses;
    	
    	if($this->loses > $this->getAmount())
    	{
    		$this->loses = $this->getAmount();
    	}
    }
	
    public function getLoses()
    {    	
    	return $this->loses;
    }
	
	public function getInAction()
	{

		return false;
	}
	
	public function getBaseComb()
	{

		return DE_Comb::CreateFromID($this->getID());
	}
	
	public function getUserInstance()
	{	
		return DE_Comb::CreateFromID($this->getID())->getUserInstance();
	}
	
	public function reciveHit(DE_UnitsGroupWarPower $warPower)
	{

		
		$this->hitedWarPowers[] = $warPower;
	}
	
	public function fetchHits()
	{

		
		$out = $this->hitedWarPowers;
		
		$this->hitedWarPowers = array();
		
		return $out;
	}
	
	
	public function haveHits()
	{

		
		if(count($this->hitedWarPowers)>0)
		{
			return true;
		}else{
			return false;
		}
	}
	
	public function getBonus()
	{
	
		if(is_null($this->armyBonus))
		{
			$this->armyBonus = GL_BattleBonuses::GetStaticBonus($this); 
		}
		
		return $this->armyBonus;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DC_CombDefendUnitGroups
	 */
	public function getUnitsGroupCollection()
	{
		return $this->getCombDefendUnitGroupsCollection();
	}
	
	/**
	 * Возвращает ссылку на коолекцию колекцитю оборонных групп юнитов
	 *
	 * @return DC_CombDefendUnitGroups
	 */
	public function getCombDefendUnitGroupsCollection()
	{
		if(!($this->combDefendUnitGroupsCollection instanceof DC_CombDefendUnitGroups))
		{
			$this->combDefendUnitGroupsCollection = DC_CombDefendUnitGroups::CreateFromCombID($this->combID);
		}
		
		return $this->combDefendUnitGroupsCollection;
	}
	
	/**
	 * Возвращает ссылку на коолекцию армийц стоящих на обороне
	 *
	 * @return DC_CombDefendArmys
	 */
	public function getCombDefendArmysCollection()
	{
		if(!($this->combDefendArmys instanceof DC_CombDefendArmys))
		{
			$this->combDefendArmys = DC_CombDefendArmys::CreateFromCombID($this->combID);
		}
		
		return $this->combDefendArmys;
	}
	
	public function repair()
	{

		
		$this->getCombDefendArmysCollection()->resetNext();
		while($army = $this->getCombDefendArmysCollection()->next())
		{
			$army->repair();
		}
	}
	
	public function getName()
	{

		return 'DEFEND ARMY OF '.DE_Comb::CreateFromID($this->getID())->getName();
	}
	
	/**
	 * Армия имеет сейчас нулевой демедж
	 *
	 * @return bool
	 */
	public function haveNullDamage()
	{
		$armysCollection = $this->getCombDefendArmysCollection();
		
		$armysCollection->resetNext();		
		while ($army = $armysCollection->next())
		{
			if(!$army->haveNullDamage())
			{
				return false;
			}
		}
		
		return true;
	}

	
	/**
	 * Вохзвращает Силу защитной армии в стазисе
	 *
	 * @return integer
	 */
	public function getArmyPower($calcUnitsWithNullDamage = true)
	{
		assert(is_bool($calcUnitsWithNullDamage));
		
		$combDefendPower = (float)0;
		
		if($this->getCombDefendArmysCollection()->countCollection() == 0)
		{
			return (float)0;
		}
		
		$this->getCombDefendArmysCollection()->resetNext();
		while($army = $this->getCombDefendArmysCollection()->next())
		{		
			$combDefendPower = $combDefendPower + $army->getArmyPower($calcUnitsWithNullDamage);
		}
				
		return (float)$combDefendPower;
	}	
	
	public function getArmyStars() 
	{		
		return GL_ArmyPowerToStars::Count($this->getArmyPower());
	}
	
	public function commit()
	{

		parent::commit();
		
		if($this->isModifyed == true)
		{
			if(DS_Comb::UpdateCombData($this))
			{
				$this->isModifyed = false;
			}			
		}
	}
	
	function __destruct()
	{
		$this->_invariant();
		parent::__destruct();
		//$this->commit();
	}

}//END OF CLASS


?>