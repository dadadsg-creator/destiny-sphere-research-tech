<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_StartBattle
{
	static public function Exec(DE_Army $army, DE_Comb $combFrom, DE_Comb $combTo, GB_Date $eventEndTime)
	{

		//—оздали запись о бое
		$battle = DE_Battle::CreateInDB($combTo, $army, $eventEndTime);

		//—оздали нулевой раунд дл€ записи началсьногососото€ни€
		$round0 = DE_BattleRound::CreateInDB($battle, (int)0, $eventEndTime, GB_TypeFactory::CreateConstant('GB_TimePeriod', 0));

		//«аписали в это раунд начальные состо€ни войск
		ME_AddRoundResults::exec($round0, $army, DE_CombDefendArmy::CreateFromCombID($combTo->getID()));

		return $battle;
	}
}



?>