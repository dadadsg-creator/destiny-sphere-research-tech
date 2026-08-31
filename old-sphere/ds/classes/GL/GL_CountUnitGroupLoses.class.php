<?php $CVS_Version = '$Revision$ ; $Date$';

//Расчет потерь
class GL_CountUnitGroupLoses
{
	static public function Count($recivedDamage, DE_UnitsGroupInterface $unitsGroup)
	{
		assert(is_float($recivedDamage));
			
		$defenceField = 0.0;
		
		if(DE_GlobalSettings::Get('BATTLE_CALC_USE_DEFENCE_FIELD'))
		{
			$defenceField = $unitsGroup->getGroupWarPower()->getDefenceField();
		}
		
		/*	
			// защитное поле = увеличение кол-ва хит-поитнтов
			return $recivedDamage / ($unitsGroup->getGroupWarPower()->getOneHitpoint() * (1 + $unitsGroup->getGroupWarPower()->getDefenceField()));	
		} else {
			return $recivedDamage / $unitsGroup->getGroupWarPower()->getOneHitpoint();
		}
		*/
		
		return GL_CountBattleHit::CountLoses($recivedDamage, $unitsGroup->getGroupWarPower()->getOneHitpoint(), $defenceField);
	}
}


?>