<?php $CVS_Version = '$Revision$ ; $Date$';

class VD_Base
{
	static protected $errorCode = '';
	
	static public function FetchErrorCode()
	{
		$errorCode = self::$errorCode;
		self::$errorCode = null; 
		
		return $errorCode;
	}
	
}

?>