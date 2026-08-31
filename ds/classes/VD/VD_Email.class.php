<?php $CVS_Version = '$Revision$ ; $Date$';

class VD_Email extends VD_Base
{
	static public function Validate($email, $common = null)
	{
		$checkUnique = false;
		
		if(!is_null($common))
		{
			assert($common instanceof DS_Common);
			$checkUnique = true;
		}
		
		if(!preg_match('/^([a-zA-Z0-9])+([\.a-zA-Z0-9_-])*@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)+/', $email))
		{
			self::$errorCode = 'WRONG_SYMBOLS_IN_EMAIL';
			return false;
		}
		
		if(strlen($email) < 3)
		{
			self::$errorCode = 'EMAIL_TOO_SHORT';
			return false;
		}
		
		if(strlen($email) > 100)
		{
			self::$errorCode = 'EMAIL_TOO_LONG';
			return false;
		}
		
		if(function_exists('checkdnsrr'))
		{
			list(, $domain) = explode('@', $email);
			if(checkdnsrr($domain, 'A') === false && checkdnsrr($domain, 'MX') === false)
			{
				self::$errorCode =  'WRONG_SYMBOLS_IN_EMAIL';
				return false;
			}
		}
		
		if($checkUnique)
		{
			if(DS_Validate::GetEmail($email, $common))
			{
				self::$errorCode = 'EMAIL_NOT_UNIQUE';
				return false;
			}
		}
		
		return true;
	}
}

?>
