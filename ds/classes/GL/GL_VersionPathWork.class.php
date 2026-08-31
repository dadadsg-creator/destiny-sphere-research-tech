<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_VersionPathWork
{
	static public function GetVersionPath($dbID)
	{
		settype($dbID, 'integer');
		
		assert(isset($GLOBALS['dbWorldAccess']) && is_array($GLOBALS['dbWorldAccess']));
		assert(key_exists($dbID, $GLOBALS['dbWorldAccess']));		
		assert(key_exists('dir', $GLOBALS['dbWorldAccess'][$dbID]));		
		
		return $GLOBALS['dbWorldAccess'][$dbID]['dir'];		
	}

	static public function ValidateVersionPath($dbID)
	{
		settype($dbID, 'integer');
		
		if(RD_AdeptUserActionToConsole::$commandLineFlag)
		{
			return true;
		}
		
		$scriptPath = str_replace(basename($_SERVER['PHP_SELF']), "", $_SERVER['SCRIPT_NAME']);
		$etalonPath = GL_VersionPathWork::GetVersionPath($dbID);
		
		if (strcmp($scriptPath, $etalonPath) === 0)
		{
			return true;
		} else {
			return false;
		}
	}
}




?>