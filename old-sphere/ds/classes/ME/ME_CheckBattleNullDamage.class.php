<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_CheckBattleNullDamage
{
	static public function Check(DE_Army $attackArmy,  DE_CombDefendArmy $defendArmy, DE_BattleRound $round)
	{
		if($round->getBattle()->isBattleRetreated() && !$round->getBattle()->isRetreatWithEmptyDamage())
		{
			return;
		}
		
		/*
		Нафиг тут это -непонятно
		Фактически отрубаем модифаером BATTLE_TRANSPORT_RETREAT_ADDITIONAL_ROUNDS другой модифаер - BATTLE_NULL_DAMAGE_UNITS_CAN_WIN
		if((int)DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_TRANSPORT_RETREAT_ADDITIONAL_ROUNDS') == 0)
		{
			return;
		}*/
		
		if($attackArmy->haveNullDamage())
		{
			$retreatParticipantTypeCode = GB_TypeFactory::CreateBase('GB_BattleParticipantTypeCode', 'A');
		} elseif($defendArmy->haveNullDamage())	{
			$retreatParticipantTypeCode = GB_TypeFactory::CreateBase('GB_BattleParticipantTypeCode', 'D');
		} else {
			ME_RetreatBattle::Cancel($round);
			return;
		}
		
		ME_RetreatBattle::Exec($round, $retreatParticipantTypeCode, true);
	}
}

?>