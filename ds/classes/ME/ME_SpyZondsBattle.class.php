<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_SpyZondsBattle
{
	static private $result = array('WINNER' => '', 'REASON' => '');
	
	/**
	 * Enter description here...
	 *
	 * @var DE_SpyZondGroup
	 */
	static private $attackGroup;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_SpyZondDefenceGroup
	 */
	static private $defenceGroup;
	
	static private $round = 0;
	
	static private $maxRounds = 3;
	
	/**
	 * Бой между зондами
	 *
	 * @param DE_SpyZondGroup $researchGroup прилетевшая на разведку группа зондов 
	 * @param DE_Comb $battleComb разведываемая сота
	 */
	static public function Fight(DE_SpyZondGroup $researchGroup, DE_Comb $battleComb)
	{
		self::_init();
		
		self::$attackGroup = $researchGroup;
		self::$defenceGroup = DE_SpyZondDefenceGroup::CreateFromCombID($battleComb->getCombID());
		
		self::_fight();
	}
	
	/**
	 * Расчет боя для калькулятора
	 *
	 * @param DE_SpyZondFakeGroup $researchGroup
	 * @param DE_SpyZondFakeGroup $defenceGroup
	 */
	static public function CalcFight(DE_SpyZondFakeGroup $researchGroup, DE_SpyZondFakeGroup $defenceGroup)
	{
		self::_init();
		
		self::$attackGroup = $researchGroup;
		self::$defenceGroup = $defenceGroup;
		
		self::_fight();
	}
	
	
	static public function CleanUpLoses()
	{
		self::$attackGroup->preCommit();
		self::$defenceGroup->preCommit();
	}
	
	/**
	 * Расчет боя
	 *
	 */
	static private function _fight()
	{
		if(self::$defenceGroup->getAmount() == 0)
		{
			self::$result['WINNER'] = 'A';
			self::$result['REASON'] = 'NO_DEFENCE';
			self::$result['ROUND'] = 0;
			return;
		}
		
		for(self::$round = 0; self::$round < self::$maxRounds; self::$round++)
		{
			self::_hit();
						
			if(self::_checkEnd())
			{
				self::$round++;
				break;
			}
			
			self::$result['WINNER'] = 'A';
			self::$result['REASON'] = 'ROUNDS';
		}
		
		self::$result['ROUND'] = self::$round;
	}
	
	static private function _init()
	{
		self::$result['WINNER'] = '';
		self::$result['REASON'] = '';
		self::$result['ROUND'] = 0;
	}
	
	static private function _getLoses(DE_SpyZondGroupInterface $attackGroup, DE_SpyZondGroupInterface $defenceGroup)
	{
		$attack = $attackGroup->getAttack();
		$demage = $attackGroup->getRealAmount() * $attackGroup->getDemage();
		$hitPoints = $defenceGroup->getOneHitPoints();
		
		if(DE_GlobalSettings::Get('BATTLE_CALC_USE_DEFENCE_FIELD'))
		{
			$defence = 0.0;
			$defenceField = $defenceGroup->getDefenceField();
		} else {
			$defence = $defenceGroup->getDefence();
			$defenceField = 0.0;
		}

		$recieveDemage = GL_CountBattleHit::CountDamage($attack, $demage, $defence);
		$loses = GL_CountBattleHit::CountLoses($recieveDemage, $hitPoints, $defenceField);

		return $loses;
	}
	
	static private function _hit()
	{
		$defenderLoses = self::_getLoses(self::$attackGroup, self::$defenceGroup);
		$attackerLoses = self::_getLoses(self::$defenceGroup, self::$attackGroup);
		
		self::$attackGroup->setLoses($attackerLoses);
		self::$defenceGroup->setLoses($defenderLoses);
	}
	
	static private function _checkEnd()
	{
		if(self::$attackGroup->isSendForOneRound())
		{
			if(self::$defenceGroup->getRealAmount() > 0 || self::$attackGroup->getRealAmount() == 0)
			{
				self::$result['WINNER'] = 'D';
				self::$result['REASON'] = 'ONE_ROUND';
			} else {
				self::$result['WINNER'] = 'A';
				self::$result['REASON'] = 'NO_DEFENCE';
			}
			
			return true;
		}
				
		if(self::$attackGroup->getRealAmount() == 0)
		{
			self::$result['WINNER'] = 'D';
			if(self::$round == 0 && self::$attackGroup->getStartAmount() < DE_WorldSettings::CreateForCurentWorld()->get('SPY_MIN_ZONDS_COUNT_FOR_GET_LOG'))
			{
				self::$result['REASON'] = 'NO_ATTACK_1';
			} else {
				self::$result['REASON'] = 'NO_ATTACK_2';
			}
			return true;
		}
		
		if(self::$defenceGroup->getRealAmount() == 0)
		{
			self::$result['WINNER'] = 'A';
			self::$result['REASON'] = 'NO_DEFENCE';
			return true;
		}
		
		return false;
	}
	
	static public function GetResult()
	{		
		return self::$result;
	}
}

?>