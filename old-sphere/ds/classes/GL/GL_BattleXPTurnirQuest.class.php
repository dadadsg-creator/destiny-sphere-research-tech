<?php

/**
 * Расчет боевого опыта для квестового турнира
 * опыта дается только за убийство определенных юнитов
 *	з.ы. на данный момент это только девушка с пистолетом
 */
class GL_BattleXPTurnirQuest
{

	/**
	 * Объект с инвормацией по бою
	 *
	 * @var DE_Battle
	 */
	static private $battle;
			
	/**
	 * Строка с тестовыми выводами
	 *
	 * @var string
	 */	
	static public $debugInfo;
	
	/**
	 * Убийство каких юнитов дает рейтинг
	 *
	 * @var unknown_type
	 */
	static private $calcUnits = array(
		68, // девушка с пистолетом
	);
	
	static private $currentRound;
	
	static public function Count(DE_Battle $battle)
	{
		self::_init();
		
		self::$battle = $battle;
						
		$finalXP = self::_countXP();
		
		self::_debugMsg('======== END BATTLE XP');
		
		DE_BattleSystemLog::Insert(self::$debugInfo);
		
		return $finalXP;
	}
	
	static private function _init()
	{	
		self::$battle = null;
		
		self::$currentRound = null;

		self::$debugInfo = '======== START BATTLE XP';			
	}

	/**
	 * Расчет боевого опыта во всех раундах
	 *
	 */
	static private function _countXP()
	{
		$result = array(
			'ATTACKER' => 0,
			'DEFENDER' => 0,
		);
		
		$roundsCollection =  self::$battle->getBattleRoundCollection();
		$roundsCollection->resetNext();	
		$roundsCount = $roundsCollection->countCollection();
		
		while(self::$currentRound = $roundsCollection->next())
		{
			self::_debugMsg('');
			self::_debugMsg('START CALC ROUND '.self::$currentRound->getRoundNumber());
			
			if(self::$currentRound->getRoundNumber() == 0)
			{
				continue;
			}
				
			$roundResultCollection = self::$currentRound->getBattleResultCollection();
			$roundResultCollection->resetNext();	

			while($result = $roundResultCollection->next())
			{
				$key = $roundResultCollection->getCurrentPosition();
			
				$unit = $result->getUnit();
				
				if(!in_array($unit->getID(), self::$calcUnits))
				{
					continue;
				}
						
				$participant = $result->getPattleParticipant();
			
				if($participant->getParticipantFlag() == 'ATTACKER')
				{
					$result['DEFENDER'] += $result->getLoses();
					if($roundsCount == self::$currentRound->getRoundNumber())
					{
						// если в бою был юнит за который дается опыт и он выжил, то даем опыт за него
						$result['ATTACKER'] += ceil($result->getAmount());
					}
				} else {
					$result['ATTACKER'] += $result->getLoses();
					if($roundsCount == self::$currentRound->getRoundNumber())
					{
						// если в бою был юнит за который дается опыт и он выжил, то даем опыт за него
						$result['DEFENDER'] += ceil($result->getAmount());
					}
				}
			
				$roundResultCollection->setPointerToPosition($key);
			}
		}
		
		$result['ATTACKER'] = floor($result['ATTACKER']);
		// в обороне опыт дается только хозяину соты
		$result['DEFENDER'][self::$battle->getDefender()->getUIID()] = floor($result['DEFENDER']);
		
		$defendersCollection = self::$battle->getDefendersCollection();
		$defendersCollection->resetNext();
		while($defender = $defendersCollection->next())
		{
			if(!key_exists($defender->getUIID, $result['DEFENDER']))
			{
				$result['DEFENDER'][$defender->getUIID] = 0;
			}
		}
		
		self::_debugMsg(print_r($result, true));
		
		return $result;
	}
	
	static private function _debugMsg($msg)
	{				
		self::$debugInfo .= $msg."\n";
	}
	
}

?>