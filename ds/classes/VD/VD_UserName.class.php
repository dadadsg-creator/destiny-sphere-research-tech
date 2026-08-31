<?php $CVS_Version = '$Revision$ ; $Date$';

class VD_UserName extends VD_Base
{
	static public function Validate($userName, $checkUnique = false, $common = false)
	{
		if($common !== false)
		{
			assert($common instanceof DS_Common);
		}
		
		if(!preg_match('/^[a-zA-Z0-9_]+$/', $userName))
		{
			self::$errorCode = 'WRONG_SYMBOLS_IN_USER_NAME';
			return false;
		}
		
		if(strlen($userName) < 3)
		{
			self::$errorCode = 'USER_NAME_TOO_SHORT';
			return false;
		}
		
		if(strlen($userName) > 20)
		{
			self::$errorCode = 'USER_NAME_TOO_LONG';
			return false;
		}
				
		if($checkUnique)
		{
			if(self::CheckIfExists($userName, $common))
			{
				self::$errorCode = 'USER_NAME_NOT_UNIQUE';
				return false;
			}
		}
		
		return true;
	}
	
	static public function CheckIfExists($userName, DS_Common $common)
	{
		return DS_Validate::GetUserIdByUserName($userName, $common);
	}
}

?>