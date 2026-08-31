<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_RepairUnitsAfterBattle
{
	static public function Exec(DE_Army $attackerArmy, DE_CombDefendArmy $defendArmy)
	{
		if($attackerArmy->getArmyPower() != 0)
		{
			//„иним юниты атакующего
			$attackerArmy->repair();
						
		}

		//„иним юниты оборон€ющегос€.
		$defendArmy->repair();
	}
}


?>