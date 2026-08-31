<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 *  ласс игровой логики определющий можно ли отступать из данного бо€ или нет.
 *
 */
class GL_CanRetreatFromBattle
{
	/**
	 * Ѕой который рассматриваем
	 *
	 * @var DE_Battle
	 */
	static private $battle;
	
	/**
	 *  од ошибки обнаруженной при проверке
	 *
	 * @var string
	 */
	static public $errorCode = '';
	
	/**
	 * Enter description here...
	 *
	 * @var DE_UserInstance
	 */
	static private $userInstance;
	
	/**
	 * ќсновной метод
	 *
	 * @param DE_Battle $battle
	 * @param DE_UserInstance $userInstance
	 * @return boolean
	 */
	static public function Check(DE_Battle $battle, DE_UserInstance $userInstance)
	{
		self::$battle = $battle;
		self::$userInstance = $userInstance;
		
		//ћожно ли в мире днном отступать
		if(!self::_isRetreatAllowInWorld())
		{
			self::$errorCode = 'RETREAT_NOT_ALOWED_IN_THIS_WORLD';
			return false;
		}
		
		//Ѕой в работе или уже завершен
		if(self::_isBattleEnded())
		{
			self::$errorCode = 'CANT_RETREAT_IN_ENDED_BATTLE';
			return false;
		}
		
		//ѕрвоерка того имеет ли право данный участник нажать отступление
		if(!self::_haveGrantToRetreat())
		{
			self::$errorCode = 'DONT_HAVE_GRANT_TO_RETREAT_FROM_BATTLE';
			return false;
		}
		
		//Ќе оттупил ли уже данный бой
		if(self::_isBattleRetreated())
		{
			self::$errorCode = 'BATTLE_IS_ALLREADY_RETREATED';
			return false;
		}
					
		return true;	
	}
	
	static private function _isRetreatAllowInWorld()
	{		
	 	if(DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_RETREAT_ALLOW') == 1)
	 	{
	 		return true;	
	 	}else{
	 		return false;
	 	}				
	}
	
	static private function _isBattleEnded()
	{						
		return self::$battle->isBattleEnded();
	}
	
	static private function _isBattleRetreated()
	{
		if(self::$battle->isBattleRetreated())
		{
			// если уже идет нормальное отсупление, то нельз€ больше отступать
			if(!self::$battle->isRetreatWithEmptyDamage())
			{
				return true;
			}
			
			if(self::$battle->getAttacker()->getUIID() == self::$userInstance->getID())
			{
				$side = 'A';
			} else {
				$side = 'D';
			}
		
			// если уже идет опступление траспортов у этого участника, то нельз€ отступать
			if(self::$battle->getRetreatSideName()->value == $side)
			{
				return true;
			}
			
			return false;
		}
		
		return false;
	}
	
	static private function _haveGrantToRetreat()
	{
		$retreaterUIID = self::$userInstance->getID();
		$attackerUIID = self::$battle->getAttacker()->getUIID();
		$defenderUIID = self::$battle->getComb()->getUserInstanceID();

		if($retreaterUIID == $attackerUIID || $retreaterUIID == $defenderUIID)
		{
			return true;
		}else{
			return false;
		}
	}
}

?>