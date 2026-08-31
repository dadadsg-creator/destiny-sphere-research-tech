<?php  $CVS_Version = '$Revision$ ; $Date$';

class VD_AllianceName extends VD_Base 
{
	static public function Validate($allianceName, $worldId, DS_Common $common)
	{
		assert(is_int($worldId) && $worldId >= 0);
		
		if(preg_match('/[\'\"\`\<\>]/', $allianceName)) 
		{
			self::$errorCode = 'ALLIANCE_NAME_HAS_WRONG_SYMBOLS';
			return false;
		}

		if(strlen($allianceName) < 3) 
		{
			self::$errorCode = 'ALLIANCE_NAME_TO_SHORT';
			return false;
		}

		if(strlen($allianceName) > 50) 
		{
			self::$errorCode = 'ALLIANCE_NAME_TO_LONG';
			return false;
		}
		
		if(count(DS_Alliance::GetAllianceByName($allianceName, $worldId, $common)) > 0)
		{
			self::$errorCode = 'ALLIANCE_NAME_NOT_UNIQUE';
			return false;
		}
		
		return true;
	}
}

?>