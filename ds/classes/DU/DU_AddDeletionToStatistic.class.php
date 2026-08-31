<?php $CVS_Version = '$Revision$ ; $Date$';

class DU_AddDeletionToStatistic
{
	/**
	 * Подключенный к мастеру класс для работы с базой
	 *
	 * @var DS_Common
	 */
	private $common;
	
	/**
	 * ID баы мастера
	 *
	 * @param unknown_type $masterDBID
	 */
 	public function __construct($masterDBID)
 	{
 		assert(is_int($masterDBID) && $masterDBID >= 0);
 		
 		$this->common = new DS_Common();
 		$this->common->setDbId($masterDBID);
 	}
	
 	public function add ($dbid, $worldname, $id_world, $premium)
	{
		assert(is_int($dbid) && $dbid >= 0);
		assert(!empty($worldname));
		assert(is_int($id_world) &&  $id_world > 0);
		assert(is_int($premium) && ($premium == 0 || $premium == 1));
		
		$statsId = (int)$this->getStatisticID();
		assert($statsId > 0);		
		
		$id_worldstats = (int)$this->getWorldStatsId($dbid, $worldname, $id_world);
		assert($id_worldstats > 0);
		
		$sql = "SELECT
      				iowu.id_inoutworldusers
				FROM
    				ds_inoutworldusers iowu
				WHERE
     				iowu.id_statistic = $statsId
     				AND 
     				iowu.id_worldstats = $id_worldstats";								
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));			
		
		if(sizeof($res) === 0)
  		{
  			$sql = sprintf("INSERT INTO
           								ds_inoutworldusers
           								(id_statistic, id_worldstats,  del_users, delpremiumusers)
           							VALUES
           								(%d, %d, 1, %d)",  									
  									$statsId,
  									$id_worldstats,
  									$premium); 
  									
  		}
  		else 
  		{  			
  			$sql = sprintf("UPDATE
      								ds_inoutworldusers
      							SET
           							del_users = del_users + 1,
           							delpremiumusers = delpremiumusers + %d
      							WHERE
           							id_inoutworldusers = %d",
  								$premium,
  								$res[0]['id_inoutworldusers']);
  		}	
  		
  		$res = $this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));	
		
	}
 	
	function getStatisticId()
	{
			
		$sql = sprintf("SELECT
								id_statistic
							FROM
								ds_statistic
							WHERE
								statsdate < CURRENT_DATE+1
								and statsdate > CURRENT_DATE");
																
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
  		if(sizeof($res) === 0)
  		{
  			$sql = "INSERT INTO
        				ds_statistic	(statsdate)
           			VALUES
           				(now())";  				
  			$res = $this->common->executeQuery($sql);
  			assert(empty($this->common->errormsg));
  				
  			$oid = pg_last_oid($this->common->getRES());  				  			
  			assert($oid > 0);
				
			$sql = 'select id_statistic from ds_statistic where oid = '.$oid;
			$res = $this->common->getResultArray("", $sql, "", "", "");
			assert(empty($this->common->errormsg));
			assert(!empty($res[0]['id_statistic']));
  				
  			$id_stats = $res[0]['id_statistic'];
  			return $id_stats;				
  		} else {
  			$id_stats = $res[0]['id_statistic'];
  			return $id_stats;
  		}												
	}
	
	function getWorldStatsId($dbid, $worldname, $id_world)
	{		
		$sql = sprintf("SELECT
      						ws.id_worldstats
						FROM
    						ds_worldsstatisic ws
						WHERE
     						ws.dbid = %d
     						AND ws.world_id = %d",
							$dbid,
							$id_world);
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
  		if(sizeof($res) === 0)
  		{
  				$sql = sprintf("INSERT INTO
           								ds_worldsstatisic
           								(dbid, world_id, worldname)
           							VALUES
           								(%d,%d,'%s')",
								$dbid,
								$id_world,
								$worldname);
								
				$res = $this->common->executeQuery($sql);
  				assert(empty($this->common->errormsg));
  				
  				$oid = pg_last_oid($this->common->getRES());  				  			
  				assert($oid > 0);
				
				$sql = 'SELECT w.id_worldstats FROM ds_worldsstatisic w WHERE oid = '.$oid;
				$res = $this->common->getResultArray("", $sql, "", "", "");
				assert(empty($this->common->errormsg));
				assert(sizeof($res) != 0);  	  													
  		}	
  		  				
  		return $res[0]['id_worldstats'];		
	}	
}


?>