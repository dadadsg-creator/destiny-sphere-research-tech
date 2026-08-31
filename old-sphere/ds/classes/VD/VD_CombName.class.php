<?php $CVS_Version = '$Revision$ ; $Date$';

class VD_CombName extends VD_Base
{
	static public function Validate($combName, $checkUnique = false, $common = false)
	{	
		if($common)
		{
			assert($common instanceof DS_Common);
		}
	
		if(!preg_match('/^[a-zA-Z0-9_-]+$/', $combName))
		{
			self::$errorCode = 'WRONG_SYMBOLS_IN_COMB_NAME';
			return false;
		}
		
		if(strlen($combName) < 3)
		{
			self::$errorCode = 'COMB_NAME_TOO_SHORT';
			return false;
		}
		
		if(strlen($combName) > 20)
		{
			self::$errorCode = 'COMB_NAME_TOO_LONG';
			return false;
		}
		
		if($checkUnique)
		{
			if(DS_Validate::GetCombName($combName, $common))
			{
				self::$errorCode = 'COMB_NOT_UNIQUE';
				return false;
			}
		}
		
		return true;
	}
}


?>