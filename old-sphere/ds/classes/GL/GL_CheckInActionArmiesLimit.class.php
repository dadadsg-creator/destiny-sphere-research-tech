<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_CheckInActionArmiesLimit
{
	static private $errorCode = '';
	
	static private $messparams = array();
	
	static public function Check($uiid, DE_Comb $combFrom, DE_Comb $combTo)
	{
		assert(is_int($uiid) && $uiid > 0);
		
		$actions = array('MOVE_TO_ATTACK', 'IN_BATTLE');

		$timeInterval = (int)round(DE_WorldSettings::CreateForCurentWorld()->get('MOVING_TO_COMB_USER_ARMIES_CHECK_INTERVAL') * 3600);
		if(DS_Army::GetCountUserArmiesInActionToComb($uiid, $combTo, $actions, $timeInterval) >= DE_WorldSettings::CreateForCurentWorld()->get('MAX_USER_ARMIES_MOVING_TO_COMB'))
		{
			// Смотрим, через сколько мы сможем отправить следующую армию на данную соту
			$actionstarttime = DS_Army::GetTimeNextArmiesInActionToComb($uiid, $combTo, $actions, $timeInterval);
			$nextTime = GB_TypeFactory::CreateConstant('GB_TimePeriod', (int)((int)round(DE_WorldSettings::CreateForCurentWorld()->get('MOVING_TO_COMB_USER_ARMIES_CHECK_INTERVAL') * 3600) - time() + strtotime($actionstarttime)));
			$st = GB_TypeFactory::CreateConstant('GB_Date', (int)(strtotime($actionstarttime)));
			self::$messparams[0] = "{b}".$nextTime->getDSFormat()."{/b}";
			self::$errorCode = 'TOTAL_MOVING_TO_COMB_USER_ARMY_LIMIT';
			return false;
		}
		
		$timeInterval = (int)round(DE_WorldSettings::CreateForCurentWorld()->get('MOVING_TO_COMB_ARMIES_CHECK_INTERVAL') * 3600);
		if(DS_Army::GetCountArmiesInActionToComb($combTo, $actions, $timeInterval) >= DE_WorldSettings::CreateForCurentWorld()->get('MAX_ARMIES_MOVING_TO_COMB'))
		{
			self::$errorCode = 'TOTAL_MOVING_TO_COMB_ARMY_LIMIT';
			return false;
		}
		
	
		
		if(DE_WorldSettings::CreateForCurentWorld()->get('MAX_USER_ARMIES_CALC_ARMY_IN_DEFENCE'))
		{
			$actions[] = 'MOVE_TO_DEFEND';
			$actions[] = 'IN_DEFEND';
		}
		
		if(DS_Army::GetCountUserArmiesInAction($uiid, $actions) >= DE_WorldSettings::CreateForCurentWorld()->get('MAX_USER_ARMIES_IN_ACTION_TOTAL'))
		{
			self::$errorCode = 'TOTAL_IN_ACTION_ARMY_LIMIT';
			return false;
		}
		
		if(DS_Army::GetCountUserArmiesInActionOnComb($combFrom, $actions) >= DE_WorldSettings::CreateForCurentWorld()->get('MAX_USER_ARMIES_IN_ACTION_PER_COMB'))
		{
			self::$errorCode = 'PER_COMB_IN_ACTION_ARMY_LIMIT';
			return false;
		}
		
		return true;
	}
	
	static public function GetErrorCode()
	{
		return self::$errorCode;
	}
	
	static public function GetMessparams()
	{
		return self::$messparams;
	}
}

?>