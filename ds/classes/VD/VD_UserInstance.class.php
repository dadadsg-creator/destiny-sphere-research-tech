<?php $CVS_Version = '$Revision$ ; $Date$';

class VD_UserInstance extends VD_Base
{
	
	static public function Validate($UIID, $UID, DS_Common $common)	
	{
		assert(is_int($UIID) && $UIID>0);		
		assert(is_int($UID) && $UID>0);		
		
		if(!DS_Validate::GetUIID($UIID, $UID, $common))
		{
			self::$errorCode = 'MISMATCH_UIID_AND_UID';
			return false;
		}
		
		return true;
	}
	
}

?>