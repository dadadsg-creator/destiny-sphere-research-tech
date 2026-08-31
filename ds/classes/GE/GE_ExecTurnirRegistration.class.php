<?php $CVS_Version = '$Revision$ ; $Date$';

class GE_ExecTurnirRegistration extends GE_SearchBase
{
	static public function Add($UID, $UIID, $worldId, $serverName)
	{
		assert(is_int($UID) && $UID > 0);
		assert(is_int($UIID) && $UIID > 0);
		assert(is_int($worldId) && $worldId > 0);
		
		assert(!empty($serverName));
	
		// чтобы ничего не выводилось
		ob_start();
		// добавляем инстанс в коллекцию
		$collectWorlds = new SU_CollectWorlds();
		$userData['id_user'] = $UID;
		$userData['id_userinstance'] = $UIID;
		$userData['id_world'] = $worldId;
		$userData['admin'] = 0;		
		
		$collectWorlds->collectUserInstance($userData, $serverName);
		// чистим буфер вывода
		ob_clean();

		// выставляем защиту новых игроков на год
		$newbDefend = GB_TypeFactory::CreateConstant('GB_Date', time() + 31622400);
		
		$sql = "
			UPDATE
				ds_userinstance
			SET
				newb_defend_enddate = '".$newbDefend->getPgFormat()."'
			WHERE
				id_userinstance = $UIID
			";
		self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		// изменяем число свободных мест в мире
		// если есть запись в ds_usersallowedcollection то не уменьшаем кол-во мест
		if(!GE_SearchStart::GetUserAllowed($UID, $worldId, $serverName))
		{
			$sql = "
				UPDATE 
					ds_worldcollection
  				SET 
					max_users = max_users - 1
		  		WHERE
		  			id_world = ".$worldId." AND
	  				dbname = '".$serverName."'
	  			";
			self::$common->getResultArray("", $sql, "", "", "");
			assert(empty(self::$common->errormsg));
		}
	}
}

?>