<?php  $CVS_Version = '$Revision$ ; $Date$';

class VD_AlliancePassword extends VD_Base 
{
	static public function Validate($alliancePassword)
	{
		if(preg_match('/[\'\"\`\!\@\#\$\%\^\*\(\)\;\:\{\}\[\]\<\>\?]/', $alliancePassword)) 
		{
			self::$errorCode = 'JOINPASSWORD_HAS_WRONG_SYMBOLS';
			return false;
		}

		if(strlen($alliancePassword) < 3) 
		{
			self::$errorCode = 'JOINPASSWORD_TO_SHORT';
			return false;
		}

		if(strlen($alliancePassword) > 30) 
		{
			self::$errorCode = 'JOINPASSWORD_TO_LONG';
			return false;
		}
		
		return true;
	}
}

?>