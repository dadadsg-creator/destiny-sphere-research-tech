<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Удаление всех инстансов из мира
 *
 */
class DU_ClearWorld
{
	static private $dbId;
	
	static private $worldId;
	
	static private $common;
	
	static public function Clear($dbId, $worldId)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		
		self::$dbId = $dbId;
		self::$worldId = $worldId;
		
		self::$common = DS_Common::CreateForCurentDB(self::$dbId);

		//self::_deleteUsersAllowed();
		
		$instances = self::_getWorldInstances();
		
		$controlDeleteUser = new DU_ControllerDeleteUserInstance(self::$common);
		echo "SELECTED ".count($instances)." TO DELETE \n";
		//ob_start();
		echo "<xmp>";
		foreach($instances as $k => $instanceId)
		{
			echo "($k) DELETE UIID : ".$instanceId;
			ob_start();
			self::$common->startTrans();
			$controlDeleteUser->drop($instanceId);
			self::$common->closeTrans("COMMIT");
			ob_end_clean();
			echo " .... OK\n";
		}
		echo "</xmp>";
		//ob_end_clean();
		
		$collectWorlds = new SU_CollectWorlds();
		
		ob_start();
		$collectWorlds->allowBases = array(self::$dbId);
		$collectWorlds->collect();
		ob_clean();
		
		/*self::$common->startTrans();
		
		self::$common->closeTrans("ROLLBACK");*/
		
		echo "<h1>WORLD CLEARED</h1>";
	}
	
	/**
	 * Получение списка всех инстансов в мире
	 *
	 * @return unknown
	 */
	static private function _getWorldInstances()
	{
		$sql = "
			SELECT
				id_userinstance
			FROM
				ds_userinstance AS ui
			WHERE
				ui.id_world = ".self::$worldId."
			-- LIMIT
			--	10
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		$instances = array();
		foreach($res as $k => $instance)
		{
			if($instance['id_userinstance'] == 0)
			{
				continue;
			}
			$instances[] = (int)$instance['id_userinstance'];
		}
		
		return $instances;
	}
	
	static private function _deleteUsers()
	{
		$sql = "
			DELETE FROM ds_user WHERE id_user > 0
			";
	}
	
	static private function _deleteUsersAllowed()
	{
		$sql = "
			UPDATE 
				ds_usersallowed
			SET 
				count = 
				CASE 
					WHEN count = 0 THEN 0
					ELSE count - 1
				END
			WHERE 
				EXISTS (
					SELECT
						ui.id_user
					FROM
						ds_userinstance as ui
					WHERE
						ui.id_user = ds_usersallowed.id_user
						AND
						ui.id_world = ".self::$worldId."
				)";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
		
		$sql = "
			DELETE FROM 
				ds_usersallowed 
			WHERE 
				count = 0
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}
}
?>