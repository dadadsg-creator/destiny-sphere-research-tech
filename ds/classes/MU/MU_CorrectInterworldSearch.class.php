<?php

/**
 * Класс работы с базой для контроля межмировых операций
 *
 */
class MU_CorrectInterworldSearch
{
	/**
	 * Выборка всех отправленых переводов которые еще не проверенны
	 *
	 * @param string $serverName
	 * @param string $worldShortName
	 * @return array
	 */
	static public function GetAllSendedNotChecked($serverName, $worldShortName)
	{
		assert(is_string($serverName) && !empty($serverName));
		assert(is_string($worldShortName) && !empty($worldShortName));
		
		$common = DS_Common::CreateForCurentDB(0);
		$common->setDBID($common->getDBIDByName($serverName));
		
		$sql = "SELECT *
				FROM
    				ds_money_list AS ml
    				INNER JOIN ds_money_list_interworld_send AS iws ON (iws.id_list = ml.id_list)
				WHERE
    				iws.check_date IS NULL
    				AND
    				ml.world_identifier = '".$worldShortName."'";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	/**
	 * Выборка всех принятых преводов но не валидированных
	 *
	 * @param string $serverName
	 * @param string $worldShortName
	 * @return array
	 */
	static public function GetAllRecivedNotChecked($serverName, $worldShortName)
	{
		assert(is_string($serverName) && !empty($serverName));
		assert(is_string($worldShortName) && !empty($worldShortName));
		
		$common = DS_Common::CreateForCurentDB(0);
		$common->setDBID($common->getDBIDByName($serverName));
		
		$sql = "SELECT *
				FROM
    				ds_money_list AS ml
    				INNER JOIN ds_money_list_interworld_recive AS iws ON (iws.id_list = ml.id_list)
				WHERE
    				iws.check_date IS NULL
    				AND
    				ml.world_identifier = '".$worldShortName."'";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	/**
	 * Пытаемся найти по ключу данные о том что было принято.
	 *
	 * @param string $serverName
	 * @param string $worldShortName
	 * @param string $key
	 * @return array
	 */
	static public function GetRecivedTransfersByKey($serverName, $worldShortName, $key, $gcAmount, $scAmount, $toWorldShortName)
	{
		assert(is_string($serverName) && !empty($serverName));
		assert(is_string($worldShortName) && !empty($worldShortName));
		assert(is_string($key) && !empty($key));
		assert(is_int($gcAmount) && $gcAmount >= 0);
		assert(is_int($scAmount) && $scAmount >= 0);
		assert(is_string($toWorldShortName) && !empty($toWorldShortName));
		
		$common = DS_Common::CreateForCurentDB(0);
		$common->setDBID($common->getDBIDByName($serverName));
		
		$sql = "SELECT *
				FROM
    				ds_money_list AS ml
    				INNER JOIN ds_money_list_interworld_recive AS iws ON (iws.id_list = ml.id_list)
				WHERE
					ml.gc_amount = ".$gcAmount."
					AND
					ml.sc_amount = ".$scAmount."
					AND
					iws.from_world_identifier = '".$toWorldShortName."'
					AND
    				iws.check_date IS NULL
    				AND
    				ml.world_identifier = '".$worldShortName."'
    				AND
    				iws.transfer_key = '".$key."'";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
echo $sql."\n";

		if(count($res) > 1)	
		{
			echo $sql."\n";
			die();
		}
		
		if(count($res) == 0)
		{
			return false;
		}else{
			return $res[0];
		}
		
		return $res;
	}
	
	/**
	 * Пытаемся извлечь сведения о передеча по ключу.
	 *
	 * @param string $serverName
	 * @param string $worldShortName
	 * @param string $key
	 * @return array
	 */
	static public function GetSendedTransfersByKey($serverName, $worldShortName, $key, $gcAmount, $scAmount, $toWorldShortName)
	{
		assert(is_string($serverName) && !empty($serverName));
		assert(is_string($worldShortName) && !empty($worldShortName));
		assert(is_string($key) && !empty($key));
		assert(is_int($gcAmount) && $gcAmount >= 0);
		assert(is_int($scAmount) && $scAmount >= 0);
		assert(is_string($toWorldShortName) && !empty($toWorldShortName));
		
		$common = DS_Common::CreateForCurentDB(0);
		$common->setDBID($common->getDBIDByName($serverName));
		
		$sql = "SELECT *
				FROM
    				ds_money_list AS ml
    				INNER JOIN ds_money_list_interworld_send AS iws ON (iws.id_list = ml.id_list)
				WHERE
					ml.gc_amount = ".$gcAmount."
					AND
					ml.sc_amount = ".$scAmount."
					AND
					iws.to_world_idetifier = '".$toWorldShortName."'
					AND
    				iws.check_date IS NULL
    				AND
    				ml.world_identifier = '".$worldShortName."'
    				AND
    				iws.transfer_key = '".$key."'";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));

		if(count($res) > 1)	
		{
			echo $sql."\n";
			die();
		}
		
		if(count($res) == 0)
		{
			return false;
		}else{
			return $res[0];
		}
		
		return $res;
	}
	
	/**
	 * Почемае сведения об отправке как валидные
	 *
	 * @param string $serverName
	 * @param integer $listID
	 */
	static public function MarkSendAsValid($serverName, $listID)
	{
		assert(is_string($serverName) && !empty($serverName));
		assert(is_int($listID) && !empty($listID));
		
		$common = DS_Common::CreateForCurentDB(0);
		$common->setDBID($common->getDBIDByName($serverName));
		
		$sql = "UPDATE ds_money_list_interworld_send SET check_date = now() WHERE id_list = $listID";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));			
	}
	
	/**
	 * Помечаем сведения о получение как валидные
	 *
	 * @param string $serverName
	 * @param integer $listID
	 */
	static public function MarkReciveAsValid($serverName, $listID)
	{
		assert(is_string($serverName) && !empty($serverName));
		assert(is_int($listID) && !empty($listID));
		
		$common = DS_Common::CreateForCurentDB(0);
		$common->setDBID($common->getDBIDByName($serverName));
		
		$sql = "UPDATE ds_money_list_interworld_recive SET check_date = now() WHERE id_list = $listID";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));			
	}
	
	/**
	 * Выборка всех миров для проверки
	 *
	 * @return array
	 */
	static public function GetWorldsList()
	{		
		$common = DS_Common::CreateForCurentDB(0);
		$common->setDBID(0);
		
		$sql = "SELECT * FROM ds_worldcollection WHERE  ";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg) && count($res) > 0);
				
		return $res;	
	}
	
	/**
	 * Выборка всех миров для проверки
	 *
	 * @return array
	 */
	static public function GetWorldDataBayShortName($worldShortName)
	{		
		assert(is_string($worldShortName) && !empty($worldShortName));
		
		$common = DS_Common::CreateForCurentDB(0);
		$common->setDBID(0);
		
		$sql = "SELECT * FROM ds_worldcollection WHERE world_identifier = '".$worldShortName."'";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg) && count($res) == 1);
				
		return $res[0];	
	}
	
	/**
	 * Выборка пользваотеля по ID 
	 *
	 * @return array
	 */
	static public function GetUserByID($userID)
	{		
		assert(is_int($userID) && $userID > 0);
		
		$common = DS_Common::CreateForCurentDB(0);
		$common->setDBID(0);
		
		$sql = "SELECT * FROM ds_user WHERE id_user = '".$userID."'";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg) && count($res) == 1);
				
		return $res[0];	
	}
}

?>