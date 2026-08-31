<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_GetCurrentPowerShildStatus
{	
	static private $cache = array();	
	
	static public function Get(DE_UserInstance $userInstance)	
	{
		if(!key_exists($userInstance->getID(), self::$cache))
		{
			self::$cache[$userInstance->getID()] = 	'';
		}
				
		return self::$cache[$userInstance->getID()];
	}
	
	
	static private function _formStatus(DE_UserInstance $userInstance)
	{
		
	}
	
	static public function ResetCache(DE_UserInstance $userInstance)
	{
		unset(self::$cache[$userInstance->getID()]);
	}
}

?>