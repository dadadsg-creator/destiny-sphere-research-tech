<?php $CVS_Version = '$Revision$ ; $Date$';

class VD_Password extends VD_Base
{
	static public function Validate($password)
	{
		if(!preg_match('/^[a-zA-Z0-9_\!\@\#\$\%\^\&\*\(\)\;\:\.\,\?]+$/', $password))
		{
			self::$errorCode = 'WRONG_SYMBOLS_IN_PASSWORD';
			return false;
		}
		
		if(strlen($password) < 3)
		{
			self::$errorCode = '5_PASSWORD_TOO_SHORT';
			return false;
		}
		
		if(strlen($password) > 16)
		{
			self::$errorCode = '5_PASSWORD_TOO_LONG';
			return false;
		}
		
		return true;
	}
}

?>