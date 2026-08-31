<?php

class Period
{
	private $id;
	private $idName;
	private $tableName;
	
	private $dbh;
	private $dbId;
	private $worldId;
	
	public function __construct($dbId, $worldId, $periodId, $tableName, $idName = '')
	{
		$this->id = $periodId;
		$this->tableName = $tableName;
		
		// this fixes current wrong name of table 'ds_peacepreiods' (which should be renamed to 'ds_peaceperiods')
		if ('' === $idName) {
			list($ds, $name) = explode('_', $tableName);
			
			assert(!empty($name));
			
			$this->idName = 'id_' . substr($name, 0, strlen($name) - 1); // trim ending 's'
		} else {
			$this->idName = $idName;
		}
		
		$this->dbId = $dbId;
		$this->worldId = $worldId;
		
		// DB initialization
		$this->dbh = DS_Common::CreateForCurentDB($dbId);
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	public function getTableName()
	{
		return $this->tableName;
	}
	
	public function getStartDate()
	{
		$this->dbh->setDbId($this->dbId);
		
		$sql = 'select start_date from ' . $this->tableName . ' where ' . $this->idName . ' = ' . $this->id . ' and id_world = ' . $this->worldId;
		$res = $this->dbh->getResultArray('', $sql, '', '', '', '');
		
		assert(empty($res->errormsg));
		
		return $res[0]['start_date'];
	}
	
	public function getEndDate()
	{
		$this->dbh->setDbId($this->dbId);
		
		$sql = 'select end_date from ' . $this->tableName . ' where ' . $this->idName . ' = ' . $this->id . ' and id_world = ' . $this->worldId;
		$res = $this->dbh->getResultArray('', $sql, '', '', '', '');
		
		assert(empty($res->errormsg));
		
		return $res[0]['end_date'];
	}
	
	public function updatePeriod($startDate, $endDate)
	{
		$this->dbh->setDbId($this->dbId);
		
		$pgStartDate = GB_TypeFactory::CreateBase('GB_Date', $startDate);
		$pgEndDate = GB_TypeFactory::CreateBase('GB_Date', $endDate);
		$sql = 'update ' . $this->tableName . ' set start_date = \'' . $pgStartDate .'\' and end_date = \'' . $pgEndDate . '\' where ' . $this->idName . ' =  ' . $this->id;
		$res = $this->dbh->executeQuery($sql);
		
		assert(empty($res->errormsg));
	}
	
	// static methods
	public static function deletePeriod()
	{
		//
	}
}

class NoTradePeriod extends Period
{
	public function __construct($dbId, $worldId, $periodId)
	{
		parent::__construct($dbId, $worldId, $periodId, 'ds_notradeperiods');
	}
}

class PeacePeriod extends Period
{
	public function __construct($dbId, $worldId, $periodId)
	{
		parent::__construct($dbId, $worldId, $periodId, 'ds_peacepreiods', 'id_peaceperiod');
	}
}

?>