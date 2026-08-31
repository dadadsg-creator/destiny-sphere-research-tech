<?php $CVS_Version = '$Revision$ ; $Date$';

//Вычисление демэджа Который получила группа в результате удара
class GL_CountRecivedDamage
{
	static public function Count(DE_UnitsGroupWarPower $hitedWarPower, DE_UnitsGroupInterface $unitsGroup, &$externalLogString)
	{		
		$hitAttack = $hitedWarPower->getAttack();
		$hitDamage = $hitedWarPower->getDemage();
		$unitVsUnitBonus = $hitedWarPower->getUnitsGroup()->getUnit()->getUnitVsUnitBonus($unitsGroup->getUnit());
		$reciveDefend = $unitsGroup->getGroupWarPower()->getDefend();
		$defenceField = $unitsGroup->getGroupWarPower()->getDefenceField();
		$recivedSeporator = GL_SeparateDemageBetweenUnitGroups::Separate($unitsGroup);
	
		$externalLogString .= "HIT PARAMS: hitAttack = $hitAttack; hitDamage = $hitDamage; unitVsUnitBonus = $unitVsUnitBonus; reciveDefend = $reciveDefend; recivedSeporator = $recivedSeporator; defenceField = $defenceField;\n";
		// при использовании защитного поля защита учитывается в хит-поинтах
		if(DE_GlobalSettings::Get('BATTLE_CALC_USE_DEFENCE_FIELD'))
		{
			$reciveDefend = 0.0;
		}
		
		/*
			//$result = $hitAttack * $hitDamage * $recivedSeporator * $unitVsUnitBonus;
		} else {
			//$result = ($hitAttack - $hitAttack * $reciveDefend) * $hitDamage * $recivedSeporator * $unitVsUnitBonus;
		}
		*/
		
		$result = GL_CountBattleHit::CountDamage($hitAttack, $hitDamage, $reciveDefend) * $recivedSeporator * $unitVsUnitBonus;
		
		assert($result >= 0);
		
		return $result;		
	}
}
?>						
