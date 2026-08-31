<?php

class World
{
	private $wdb;
	private $dbh;
	
	private $id;
	private $worldName;
	private $description;
	
	private $notradePeriods;
	private $peacePeriods;
	
	public function __construct($wdb, $id)
	{
		assert( $wdb >= 0);
		assert(!empty($id) || 0 === $wdb);

		$this->wdb = $wdb;
		$this->dbh = DS_Common::CreateForCurentDB($wdb);
		
		$this->id = $id;
		$this->checkId();
		
		$sql = '
			select
				id_world, worldname, description, world_identifier
			from
				ds_world
			where
				id_world = ' . $id . '
		';
		repareDbId($this->dbh, $this->wdb);
		$res = $this->dbh->getResultArray('', $sql, '', '', '');
		
		assert(empty($this->dbh->errormsg));
		
		$this->worldName = $res[0]['worldname'];
	}

	public function deletePeriod($periodId, $periodType)
	{
		assert(is_numeric($periodId));
		assert('peace' === $periodType || 'notrade' === $periodType);
		
		if ('peace' === $periodType) {
			$sql = 'delete from ds_peacepreiods where id_peaceperiod = ' . $periodId;
		} elseif ('notrade' === $periodType) {
			$sql = 'delete from ds_notradeperiods where id_notradeperiod = ' . $periodId;
		} else {
			return false;
		}
		
		$res = $this->dbh->executeQuery($sql);
		
		if (!empty($res->errormsg)) {
			return false;
		}
		return 1;
	}
	
	public function addPeriod($type, $startDate, $endDate)
	{
		if ('notrade' === $type) {
			$sql = 'insert into ds_notradeperiods (start_date, end_date, id_world) values (\'' . $startDate . '\', \'' . $endDate . '\', ' . $this->id . ')';
		} elseif ('peace' === $type) {
			$sql = 'insert into ds_peacepreiods (start_date, end_date, id_world) values (\'' . $startDate . '\', \'' . $endDate . '\', ' . $this->id . ')';
		} else {
			return false;
		}
/*echo "<pre>";
print_r($sql);
exit;*/
		repareDbId($this->dbh, $this->wdb);
		$res = $this->dbh->executeQuery($sql);
		assert(empty($res->errormsg));
	}
	
	public function updatePeriod($periodId, $periodType, $startDate, $endDate)
	{
		//assert();
		if ('peace' === $periodType) {
			$tableName = 'ds_peacepreiods';
			$idName = 'id_peaceperiod';
		} elseif ('notrade' === $periodType) {
			$tableName = 'ds_notradeperiods';
			$idName = 'id_notradeperiod';
		} else {
			return false;
		}
		
		$sql = 'update ' . $tableName . ' set start_date = \'' . $startDate . '\', end_date = \'' . $endDate . '\' where ' . $idName . ' = ' . $periodId . ' and id_world = ' . $this->id;

		repareDbId($this->dbh, $this->wdb);
		$res = $this->dbh->executeQuery($sql);
		assert(empty($this->dbh->errormsg));				
			
		return 1;
	}
	
	public function getDbId()
	{
		return $this->wdb;
	}
	
	private function checkId()
	{
		//print_r($this);
		$sql = 'select count(*) as cnt from ds_world where id_world = ' . $this->id;
		repareDbId($this->dbh, $this->wdb);
		$res = $this->dbh->getResultArray('', $sql, '', '', '');
		
		assert(empty($this->dbh->errormsg));
		
		if (0 === intval($res[0]['cnt'])) {
			return false;
		} else {
			return $this->id;
		}
	}
	
	// static functions
	public static function getAllWorlds()
	{
		$allwdb = WorldDB::getAllWorldDatabases();

		$allWorlds = array();

		foreach ($allwdb as $wdb) {
			$worlds = $wdb->getWorlds();
			
			if (is_array($worlds)) {
				foreach ($worlds as $w) {
					$allWorlds[] = $w;
				}
			}
		}
		
		return $allWorlds;
	}
	
	// attribute functions
	public function getId()
	{
		return $this->id;
	}
	public function getWorldName()
	{
		return $this->worldName;
	}
	
	public function setWorldName($worldName)
	{
		$this->worldName = $worldName;
	}
	
	public function getDescription()
	{
		return $this->description;
	}
	
	public function setDescription($description)
	{
		$this->description = $description;
	}
	
	public function getNotradePeriods()
	{
		$periods = array();
		
		$sql = 'select * from ds_notradeperiods where id_world = ' . $this->id . ' order by start_date';
		repareDbId($this->dbh, $this->wdb);
		$res = $this->dbh->getResultArray('', $sql, '', '', '');
		assert(empty($res->errormsg));
		
		foreach ($res as $p) {
			$periods[] = new NoTradePeriod($this->wdb, $this->id, $p['id_notradeperiod']);
		}
		
		return $periods;
	}
	
	public function getPeacePeriods()
	{
		$periods = array();
		
		$sql = 'select * from ds_peacepreiods where id_world = ' . $this->id . ' order by start_date';
		
		repareDbId($this->dbh, $this->wdb);
		$res = $this->dbh->getResultArray('', $sql, '', '', '');
		assert(empty($res->errormsg));
		
		foreach ($res as $p) {
			$periods[] = new PeacePeriod($this->wdb, $this->id, $p['id_peaceperiod']);
		}
		return $periods;
	}
	
	public function getStartByPeriodID($periodId)
	{
		$periods = array();
		
		$sql = "SELECT start_date from ds_peacepreiods
				WHERE id_world =".$this->id."
						AND
						id_peaceperiod = $periodId";
		repareDbId($this->dbh, $this->wdb);
		$res = $this->dbh->getResultArray('', $sql, '', '', '');
		assert(empty($res->errormsg));
		
		return $res[0]['start_date'];
	}
	
	public function getEndByPeriodID($periodId)
	{
		$periods = array();
		
		$sql = "SELECT end_date from ds_peacepreiods
				WHERE id_world =".$this->id."
						AND
						id_peaceperiod = $periodId";
		repareDbId($this->dbh, $this->wdb);
		$res = $this->dbh->getResultArray('', $sql, '', '', '');
		assert(empty($res->errormsg));
		
		return $res[0]['end_date'];
	}
	
	public static function getAllWorldsInDb($dbId)
	{
		assert(is_int($dbId) && $dbId >= 0);
		$common = DS_Common::CreateForCurentDB($dbId);
		
		$sql = '
			select
				id_world, worldname
			from
				ds_world
			';
		$res = $common->getResultArray('', $sql, '', '', '');
		assert(empty($res->errormsg));

		$worlds = array();
		
		foreach ($res as $w) {
			$world = new World($dbId, $w['id_world']);
			$worlds[] = $world;
		}
		
		return $worlds;
	}
}

class WorldDB
{
	private $wdbId;
	private $dbh;
	
	public function __construct($wdbId)
	{
		$this->wdbId = $wdbId;
		
		$this->dbh = DS_Common::CreateForCurentDB($wdbId);
	}
	
	public function getId()
	{
		return $this->wdbId;
	}
	
	public function  getWorlds()
	{
		repareDbId($this->dbh, $this->wdbId);
		$sql = '
			select
				id_world, worldname
			from
				ds_world
			';
		$res = $this->dbh->getResultArray('', $sql, '', '', '');
		
		assert(empty($res->errormsg));
		
		$worlds = array();
		
		foreach ($res as $w) {
			$world = new World($this->wdbId, $w['id_world']);
			$worlds[] = $world;
		}
		
		return $worlds;
	}
	
	// static
	private static $allWorldDatabases;
	
	public static function getAllWorldDatabases()
	{
		$wdbs = $GLOBALS['dbWorldAccess'];
		
		foreach ($wdbs as $wdbId => $wdbConfig) {
			$result[$wdbId] = new WorldDB($wdbId);
		}
		
		return $result;
	}
	
	
}

?>