<?php  $CVS_Version = '$Revision$ ; $Date$';

class VD_AllianceTag extends VD_Base 
{
	static public function Validate($allianceTag, $worldId, DS_Common $common)
	{
		assert(is_int($worldId) && $worldId >= 0);
		
		if(!preg_match('/^[a-zA-Z0-9_\#\$\*\+\!\@]+$/', $allianceTag)) 
		{
			self::$errorCode = 'ALLIANCE_TAG_HAS_WRONG_SYMBOLS';
			return false;
		}

		if(strlen($allianceTag) > 8) 
		{
			self::$errorCode = 'ALLIANCE_TAG_TO_LONG';
			return false;
		}
		
		if(count(DS_Alliance::GetAllianceByTag($allianceTag, $worldId, $common)) > 0)
		{
			self::$errorCode = 'ALLIANCE_TAG_NOT_UNIQUE';
			return false;
		}
		
		return true;
	}
}

?>