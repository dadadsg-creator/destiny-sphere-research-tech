<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_NewbDefendTestArmyPower
{
	static private $currentUserInstanceID;
	
	static private $needCheckCurrentUserInstance = false;
	
	static public function SetNeedCheckCurrentUserInstanceFlag()
	{		
		$userInstance = self::_getCurrentUserInstance();
		
		if($userInstance->haveNewbDefend())
		{
			self::$needCheckCurrentUserInstance = true;
		}
	}
	
	static public function isStillHaveDefend()
	{
		if(self::$needCheckCurrentUserInstance)
		{
			$userInstanceArmys = DC_UserInstanceArmys::CreateFromUserInstanceID(self::$currentUserInstanceID);
			
			$totalArmysPower = 0;
			
			while(false !== ($army = $userInstanceArmys->next()))
			{
				$totalArmysPower += $army->getArmyPower();
			}

			if(GL_ArmyPowerToStars::Count($totalArmysPower) > DE_WorldSettings::CreateForCurentWorld()->Get('MAX_NEWB_ARMY_POWER'))
			{
				return false;
			}
		}
		
		return true;		
	}
	
	static private function _getCurrentUserInstance()
	{
		assert(key_exists('uiid', $_SESSION));
		self::$currentUserInstanceID = (int)$_SESSION['uiid'];
		
		return DE_UserInstance::CreateFromID(self::$currentUserInstanceID);
	}
}
?>