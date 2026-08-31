<?php $CVS_Version = '$Revision$ ; $Date$';





class GL_CombDefendArmyPower extends GameBase 
{
	/** Класс игровой логики для расчета силы армии */
	public $armyPower = '';
		
	function GL_CombDefendArmyPower () 
	{
		$this->GameBase();		
		$this->armyPower = new GL_ArmyPower();				
	}
	
	function countPower($combID)
	{
		assert ($combID > 0);
		
		$combArmys = DS_Army::getArmysOnComb($combID);
		
		$combPower = 0;
		
		foreach($combArmys as $k=>$v)
		{
			$combPower = $combPower + $this->armyPower->countArmy ( $v['id_unitcontainer'] );
		}	
		
		return $combPower;	
	}
}

?>