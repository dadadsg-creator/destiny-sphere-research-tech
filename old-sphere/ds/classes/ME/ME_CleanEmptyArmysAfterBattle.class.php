<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_CleanEmptyArmysAfterBattle
{
	static private $forLog;

	static public function Exec($round, DE_Army $attacker, DE_CombDefendArmy $defender)
	{
		assert($round instanceof DE_BattleRound || is_null($round));
		
self::$forLog .= "\n	CLEAN EMPTY ARMY OF ATTACKER\n";

		self::_testAndCleanArmy($attacker, $round);

self::$forLog .= "\n	CLEAN EMPTY ARMY OF DEFENDER\n";

		$defendArmysCollection = $defender->getCombDefendArmysCollection();
		$defendArmysCollection->resetNext();		
		while($defendArmy = $defendArmysCollection->next())
		{
			$key = $defendArmysCollection->getCurrentPosition();	
			
			self::_testAndCleanArmy($defendArmy, $round);
			
			$defendArmysCollection->setPointerToPosition($key);
		}

DE_BattleSystemLog::Insert(self::$forLog);
self::$forLog = '';
	}

	static private function _testAndCleanArmy(DE_Army $army,  $round)
	{
		assert($round instanceof DE_BattleRound || is_null($round));
self::$forLog .= "TEST FOR EMPTY ARMY '".$army->getName()."'\n";

		if($army->getArmyPower() == 0 && !$army->getIsDefault())
		{
			if($round instanceof DE_BattleRound)
			{
				$battleParticipant = $round->getBattle()->getBattleParticipantBy($army->getUserInstance());
				
				if(!($battleParticipant instanceof DE_BattleParticipant))
				{
					$battleParticipant = ME_RefreshBattle::AddDefenderBattleParticipant($round->getBattle(), $army->getUserInstance());
				}
				
				$armysCollection = $battleParticipant->getArmiesColletion();
				$participantArmy = $armysCollection->getParticipantArmyByArmy($army);

				if(!($participantArmy instanceof DE_BattleParticipantArmy))
				{
					$participantArmy = ME_RefreshBattle::AddDefenderArmy($round->getBattle(), $battleParticipant, $army);
				}
			
				$participantArmy->setDieRoundNumber($round->getRoundNumber());	
			}
						
			$army->delete();
self::$forLog .= "ARMY '".$army->getName()."' IS DELETED\n";
		}
	}
}


?>