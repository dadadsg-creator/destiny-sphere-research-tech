<?php $CVS_Version = '$Revision$ ; $Date$';

class GP_CombAttributeChange
{
	static private $accumulator = array();
	
	static public function Add($userName, $string)
	{
		assert(!empty($userName));
		self::$accumulator[$userName] =  $string;
	}
	
	static public function Flush()
	{
		//Должно быть определнно в settings_secondary.php
		assert(isset($GLOBALS['USER_FOR_COMB_ATRIBUTES_CHANGE_LOGING']));
		assert(is_array($GLOBALS['USER_FOR_COMB_ATRIBUTES_CHANGE_LOGING']));
		
		foreach(self::$accumulator as $userName => $stringEH)
		{
			
			if(!self::_logThisUser($userName))
			{
				continue;
			}
			
			$logString = 'XMLDATA: '.SU_DebugTools::GetXmlData()."\n\n";

			$logString .= "EH \n".$stringEH;

			$combsArray = GB_SingletonObjectStorage::GetObjectsOfClass('DE_Comb', true);

			if(count($combsArray) == 0)
			{
				continue;
			}

			foreach($combsArray as $k=>$combTmp)
			{
				if($combTmp->getUserInstanceID() == 0)
				{
					continue;
				}

				$userInstance = $combTmp->getUserInstance();
				$user = $userInstance->getUser();
				$name = $user->getName();

				if($name != $userName)
				{
					continue;
				}
				$logString .= "COMB: ".$combTmp->getName()." ATTRIBUTES MODIFAED\n";
				$logString .= $combTmp->getAttributes()->exportFormatedAttributes(true)."\n";
			}
			
			SU_DebugTools::SetNewLogPath('SLAVE');
			SU_DebugTools::LogAll($logString, "CombAttributesChange_".$userName."_.log", 0, 0);
		}
	}
	
	static private function _logThisUser($userName)
	{		
		if(count($GLOBALS['USER_FOR_COMB_ATRIBUTES_CHANGE_LOGING']) == 1)
		{
			if($GLOBALS['USER_FOR_COMB_ATRIBUTES_CHANGE_LOGING'][0] == 'ALL_USERS')
			{
				return true;	
			}			
		}
		
		return in_array($userName, $GLOBALS['USER_FOR_COMB_ATRIBUTES_CHANGE_LOGING']);
	}
	
}


?>