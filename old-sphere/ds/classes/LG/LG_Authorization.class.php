<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Гейт анализатора.
 * Класс проверки валидности сессии и сбора авторизационной информации
 *
 */
class LG_Authorization
{
	static private $uid;
	
	static private $world;
	
	static private $db;
	
	static public function Auth($SID)
	{
		if(!self::_checkSession($SID))
		{
			die('not_valid');
		}
		
		$oldDbId = $_SESSION['dbId'];
		$_SESSION['dbId'] = 0;
		
		$userWorlds = self::_getUserWorlds();
		
		$user = DE_User::CreateFromID(self::$uid);
		
		$str = $user->getName().";";
		
		$wList = array();
		$curWorld = '';
		
		foreach($userWorlds as $k => $world)
		{
			if(empty($curWorld) || (self::$world != 0 && self::$world == $world['id_world'] && self::$db == $world['dbname']))
			{
				$curWorld = $world['world_identifier'];
			}
			
			$wList[] = $world['world_identifier'];
		}
		
		$str .= implode(',', $wList).";".$curWorld;
		
		// премиум
		$premEndDate = self::_getPremEndDate();		
		$str .= ";".$premEndDate.";\n";
		
		$_SESSION['dbId'] = $oldDbId;
		
		echo $str;

	}

	/**
	 * Проверка валидности сессии и получение из нее данных пользователя
	 *
	 * @param string ИД сессии $sido 
	 * @return bool
	 */
	static private function _checkSession($SID)
	{	
		if(!SU_GameSessionWork::SessionContinue($SID))
		{
			return false;
		}	
		
		if(!isset($_SESSION['uid']))
		{
			return false;
		}
			
		self::$uid = (int)$_SESSION['uid'];
			
		if(self::$uid <= 0)
		{
			return false;
		}
			
		if(key_exists('world', $_SESSION))
		{
			self::$world = (int)$_SESSION['world'];
		} else {
			self::$world = 0;
		}
		
		if(key_exists('dbId', $_SESSION))
		{
			self::$db = $GLOBALS['dbWorldAccess'][(int)$_SESSION['dbId']]['server'];
		} else {
			self::$db = 'master';
		}
		
		return true;
	}
	
	/**
	 * Получения списка миров, в которых зарегестрирован пользователь
	 *
	 * @return array
	 */
	static private function _getUserWorlds()
	{
		if(GameDown())
		{
			die('GameDown');
		}
		
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
				wc.world_identifier,
				wc.id_world,
				wc.dbname
			FROM
				ds_userinstancecollection AS uic
				INNER JOIN ds_worldcollection AS wc USING (id_worldcollection)
			WHERE
				uic.id_user = ".self::$uid."
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	/**
	 * Получение даты окончания премиума в анализаторе
	 *
	 * @return timestamp
	 */
	static private function _getPremEndDate()
	{
		$dates = DS_PremiumAnalizer::getUserPremiumDates(self::$uid);
		
		if(count($dates) == 1)
		{
			$endDate = GB_TypeFactory::CreateConstant('GB_Date', $dates[0]['enddate_pg']);
			return $endDate->value;
		} 
		
		return '0';
	}
}

?>