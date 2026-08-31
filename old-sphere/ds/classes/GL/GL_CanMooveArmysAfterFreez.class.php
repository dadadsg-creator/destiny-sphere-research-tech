<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Клас слогики определящий можно ли двигаться армиями поле опкончания заморозки
 *
 */

class GL_CanMooveArmysAfterFreez
{
	static private $delta;
	
	static public function Test(DE_UserInstance $userInstance, GB_Date $checkDate)
	{
		$powerShild = DE_PowerShild::CreateFromUserInstance($userInstance->getID());
		
		$lastWorkDate = $powerShild->getLastWorkDate();
		
		$minArmyMoveDelay = DE_WorldSettings::CreateForCurentWorld()->get('PS_MIN_ARMY_MOVE_DELAY_AFTER_FREEZ') *
							DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
		
		self::$delta = ($lastWorkDate->value + $minArmyMoveDelay) - $checkDate->value;
			
									
		if(self::$delta > 0)
		{
			return true;
		}else{
			return false;
		}
	}
	
	static public function GetDelta()
	{
		return self::$delta;
	}
}


?>