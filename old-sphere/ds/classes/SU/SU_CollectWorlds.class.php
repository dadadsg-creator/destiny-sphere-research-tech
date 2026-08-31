<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Класс для сбора коллекции миров, юзверей и пользователй допущеных в мир
 *
 */
class SU_CollectWorlds
{
	/**
	 * ИНтерфейсный объект сипользуемый для раоты с БД текущей
	 *
	 * @var DS_Common
	 */
	private $commonCurrent;
	
	/**
	 * ИНтерфейсный объект сипользуемый для раоты с БД master
	 *
	 * @var DS_Common
	 */
	private $commonMaster = '';
		
	/**
	 * Список DBDID по которым идем
	 *
	 * @var array
	 */
	public $allowBases = array();

	/**
	 * Режим вывода
	 *
	 * @var string
	 */
	private $outPutMode = 'html';
		
	function __construct()
	{
		$this->commonMaster = new DS_Common;
		assert($this->commonMaster->setDBID(0));
		
		$this->commonCurrent = new DS_Common;		
				
		$sapi = php_sapi_name();
		if( strpos($sapi, "apache" )!==false)
		{
			$this->outPutMode = 'html';			
		}else{
			$this->outPutMode = 'text';
		}
		
		SU_DebugTools::ResetLog('CollectWorlds.log');
	}//END OF METHOD

	function collect()
	{		
		$this->out('START SCANING BASES', true);
		
		foreach ($GLOBALS['dbWorldAccess'] as $DBID=>$serverData)
		{
			settype($DBID, "integer");
			assert($DBID >= 0);
			
			assert(is_array($serverData));
			
			if(!in_array ($DBID, $this->allowBases))
			{
				continue;
			}
			
			$this->out("======== WORK WITH DB '".$serverData['server']."' ===========", true);
			$this->out(' ', true);
			
			assert($this->commonCurrent->setDbId($DBID));			
			
			$this->commonMaster->executeQuery('BEGIN');
			assert(empty($this->commonMaster->errormsg));
			
			$this->clearMaster($DBID, $serverData);			
			
			$this->collectWorlds($DBID, $serverData);

			$this->collectInstances($DBID, $serverData);

			$this->collectAllowed($DBID, $serverData);

			$this->commonMaster->executeQuery ('COMMIT');
			assert(empty($this->commonMaster->errormsg));
						
			$this->out(' ', true);
			$this->out("================= DB COMPLIT ===========", true);
		}		
  }//END OF METHOD

	function clearMaster($DBID, $serverData)
	{
		assert(is_array($serverData));			
		assert(is_int($DBID) && $DBID >= 0);
						
		$sql = "DELETE FROM ds_userinstancecollection  WHERE dbname = '".$serverData['server']."'";
		$this->commonMaster->getResultArray("", $sql, "", "", "");
		assert(empty($this->commonMaster->errormsg));
		
		$sql = "DELETE FROM ds_usersallowedcollection  WHERE dbname = '".$serverData['server']."'";
		$this->commonMaster->getResultArray("", $sql, "", "", "");
		assert(empty($this->commonMaster->errormsg));		
		
		/*$sql = "DELETE FROM ds_worldcollection WHERE dbname = '".$serverData['server']."'";
		$this->commonMaster->getResultArray("", $sql, "", "", "");
		assert(empty($this->commonMaster->errormsg));*/	
						
	}//END OF FUNCTION

	function collectWorlds($DBID, $serverData)
	{
		assert(is_array($serverData));			
		assert(is_int($DBID) && $DBID >= 0);
		
		$this->out(" ----- COLLECT WORLDS ----- ", true);

		$sql = "
				SELECT 
       				w.id_world,
  	   				wht.id_worldtype,
  	   				whl.id_language,
	   				w.worldname,
	   				w.description,
            w.short_desc,
	   				w.profi,
	   				w.world_identifier,
	   				w.sort_order,
	   				w.for_new_players, 	
	   				gm1.value AS users_max,
	   				gm2.value AS disable_one_world_reg,
  	   				(
        				SELECT count(*)
						FROM ds_userinstance ui
						WHERE ui.id_world = w.id_world
      				) as users_count,
      				w.world_status	 				
				FROM
					ds_world w
					INNER JOIN ds_worldhastype AS wht ON (wht.id_world = w.id_world)
					INNER JOIN ds_gamemodifier AS gm1 ON (gm1.id_world = w.id_world)	
					INNER JOIN ds_gamemodifier AS gm2 ON (gm2.id_world = w.id_world)
					INNER JOIN ds_worldhaslanguage AS whl On (whl.id_world = w.id_world)	
				WHERE
  					gm1.modifiername = 'MAX_COUNT_WORLD_USER_INSTANCE'
  					AND
  					gm2.modifiername = 'DISABLED_ONE_WORLD_REGISTRATION'";
		$rs = $this->commonCurrent->getResultArray("", $sql, "", "", "");
		assert(empty($this->commonCurrent->errormsg));
		  			
  		$this->out('Selected '.count($rs).' worlds', true);

  		if(count($rs) == 0) 
  		{
  			return;
  		}

  		foreach($rs as $k=>$worldData)
  		{
  			$this->out("[".$worldData['worldname']."]");  				
  			
  			$freePlaces = $worldData['users_max'] - $worldData['users_count'];
  			
  			if($freePlaces < 0)
  			{
  				$freePlaces = 0;
  			}

  			$sql = "
  				SELECT
  					COALESCE(id_worldcollection, 0) AS id_worldcollection
  				FROM
  					ds_worldcollection
  				WHERE
  					world_identifier = '".$worldData['world_identifier']."'
  				";
  			$res = $this->commonMaster->getResultArray("", $sql, "", "", "");
			assert(empty($this->commonMaster->errormsg));
  			
			if($res[0]['id_worldcollection'] == 0)
			{
  				$sql = "INSERT INTO ds_worldcollection
  					(id_world, dbname, max_users, worldname, profi, description, short_desc, id_language, id_worldtype, multy_access, world_identifier, sort_order, for_new_players, world_status)
  					VALUES
  					(
  					  '".$worldData['id_world']."',
  					  '".$serverData['server']."',
  			          '".$freePlaces."',
  			          '".$worldData['worldname']."',
  			          '".$worldData['profi']."',
  			          '".$worldData['description']."',
                  '".$worldData['short_desc']."',
  					  ".$worldData['id_language'].",
  					  ".$worldData['id_worldtype'].",
  					  '".$worldData['disable_one_world_reg']."',
  					  '".$worldData['world_identifier']."',
  					  ".$worldData['sort_order'].",
  					  '".$worldData['for_new_players']."',
  					  ".$worldData['world_status']."
  					)";
			} else {
				$sql = "
					UPDATE
						ds_worldcollection
					SET
						dbname = '".$serverData['server']."',
						max_users = '".$freePlaces."',
						profi = '".$worldData['profi']."',
						description = '".$worldData['description']."',
						short_desc = '".$worldData['short_desc']."',
            id_language = ".$worldData['id_language'].",
						id_worldtype = ".$worldData['id_worldtype'].",
						multy_access = '".$worldData['disable_one_world_reg']."',
						sort_order = ".$worldData['sort_order'].",
						for_new_players = '".$worldData['for_new_players']."',
						world_status = ".$worldData['world_status']."
					WHERE
						id_worldcollection = ".$res[0]['id_worldcollection']."
					";

          ///echo "$sql<br>";
			}
			$this->commonMaster->getResultArray("", $sql, "", "", "");
/*echo $this->commonMaster->errormsg."<br><br>";
echo "<pre>$sql<br>";*/			
			assert(empty($this->commonMaster->errormsg));  			
  		}//END OF foreach()
  		$this->out(' ');  		
  	}//END OF FUNCTION


	function collectInstances($DBID, $serverData)
	{
		assert(is_array($serverData));			
		assert(is_int($DBID) && $DBID >= 0);
		
		$this->out(" ----- COLLECT USERS ----- ", true);
		
		$sql = "SELECT
						ui.ID_UserInstance,
						w.id_world,
						ui.id_user,
  						w.worldname,
  						u.username,
  						u.admin
				FROM
						DS_UserInstance ui,
						DS_User u,
						DS_World w
					WHERE
						ui.ID_User = u.ID_User AND
						w.ID_World = ui.ID_World";
		$rs = $this->commonCurrent->getResultArray("", $sql, "", "", "");
  		assert(empty($this->commonCurrent->errormsg));
				
		$this->out('Selected '.count($rs).' users', true);

		if(count($rs) == 0)
		{
			return;
		}

		foreach($rs as  $k=>$userData)
		{
			$this->out("[".$userData['username'].']	=> ['.$userData['worldname'].']');			
			$this->collectUserInstance($userData, $serverData['server']);
		}
	}//END OF METHOD
	
	public function collectUserInstance($userData, $server)
	{
		assert(is_array($userData));
		assert(key_exists('id_user', $userData));
		assert(key_exists('id_userinstance', $userData));
		assert(key_exists('id_world', $userData));
		assert(key_exists('admin', $userData));
		
		$sql = "INSERT INTO ds_userinstancecollection
  					(id_user, id_userinstance, id_world, dbname, admin, id_worldcollection)
  					SELECT 
  						".$userData['id_user'].",
  					  	".$userData['id_userinstance'].",
  					  	".$userData['id_world'].",
  					  	'".$server."',
  					  	'".$userData['admin']."',
  					  	id_worldcollection
  					FROM 
  						ds_worldcollection  						
  					WHERE 
  						dbname = '".$server."'
  						AND
  						id_world = ".$userData['id_world']."";
		
		$this->commonMaster->getResultArray("", $sql, "", "", "");
		assert(empty($this->commonMaster->errormsg));
	}//END OF METHOD

	function collectAllowed($DBID, $serverData)
	{
		assert(is_array($serverData));			
		assert(is_int($DBID) && $DBID >= 0);

		$this->out(" ----- COLLECT USERS ALLOWED----- ", true);	
		
		$sql = "SELECT
						id_user,
						id_world
					FROM
						ds_usersallowed";
		$rs = $this->commonCurrent->getResultArray("", $sql, "", "", "");
  		assert(empty($this->commonCurrent->errormsg));

    $this->out('Selected '.count($rs).' users', true);

		if(count($rs) == 0)
		{
			return;
		}

		foreach($rs as  $k=>$userData)
		{
			$this->out("UserID = ".$userData['id_user'].' WorldID = '.$userData['id_world']);			
			$sql = "INSERT INTO ds_usersallowedcollection
  					(dbname, id_world, id_user, id_worldcollection)
  					SELECT 
  						'".$serverData['server']."',
  						".$userData['id_world'].",
  						".$userData['id_user'].",  					  	
  					  	id_worldcollection
  					FROM 
  						ds_worldcollection  						
  					WHERE 
  						dbname = '".$serverData['server']."'
  						AND
  						id_world = ".$userData['id_world']."";
		
			$this->commonMaster->getResultArray("", $sql, "", "", "");
			assert(empty($this->commonMaster->errormsg));
		}
					
	}//END OF METHOD
	
	public function deleteFromUserInstanceCollection($UIID)
	{
		assert(is_int($UIID) && $UIID > 0);
		$sql = "DELETE FROM ds_userinstancecollection WHERE id_userinstance = $UIID";
		$this->commonMaster->executeQuery($sql);
		assert(empty($this->commonMaster->errormsg));	
	}//END OF METHOD

	function out($msg, $toLog = false)
	{	
		$str = date('Y-m-d H:i:s')." ".$msg."\n";
		
		if($this->outPutMode == 'html')
		{
			echo $str;
			flush();		
		}
		
		if($toLog)
		{	
			SU_DebugTools::WriteToLog($str, 'CollectWorlds.log');
		}		
	}//END OF METHOD
}//END OF CLASS

?>