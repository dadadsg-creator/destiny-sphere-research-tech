<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Менеджер выполняющий необходимые дествия по началу отступления боя.
 *
 */
class ME_RetreatBattle
{
	static public function Exec(DE_BattleRound $round, GB_BattleParticipantTypeCode $retreatSide, $nullDamage = false)
	{
		assert(is_bool($nullDamage));
		
		$battle = $round->getBattle();
		
		if($battle->isBattleRetreated() && 
			(!$battle->isRetreatWithEmptyDamage() || $retreatSide->value == $battle->getRetreatSideName()->value))
		{
			return;
		}

		DE_BattleSystemLog::Insert("\n== START RETREAT FROM BATTLE: round = ".$round->getRoundNumber()." side = ".$retreatSide->value."\n");	
		
		$battle->setRetreatSideName($retreatSide->value);
		$battle->setRetreatRoundNumber($round->getRoundNumber());
		$battle->setRetreatWithEmptyDamage($nullDamage); 
		
		$round->setRetreatData($retreatSide, true);
	}
	
	/**
	 * Отмена отступления.
	 * Используется для отступления транспортов
	 *
	 * @param DE_Battle $battle
	 */
	static public function Cancel(DE_BattleRound $round)
	{
		$battle = $round->getBattle();
		
		// Отеняем только если вообще есть отспуление и это отсупление транспортов
		if(!$battle->isBattleRetreated() || !$battle->isRetreatWithEmptyDamage())
		{
			return;
		}
		
		DE_BattleSystemLog::Insert("\n== CANCEL RETREAT FROM BATTLE: round = ".$round->getRoundNumber()."\n");	
		
		$round->setRetreatData($battle->getRetreatSideName(), false);
		$battle->setRetreatRoundNumber(0);
	}
}

?>