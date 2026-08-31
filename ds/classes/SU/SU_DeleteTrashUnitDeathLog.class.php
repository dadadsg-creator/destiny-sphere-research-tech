<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_DeleteTrashUnitDeathLog extends SU_DeleteTrashBase
{
	private $common;
	
	private $timeDelete = 60;
	
	public function __construct(DS_Common $common)
	{
		$this->common = $common;
	}
	
	public function delete()
	{
		$count = $this->_getCount();
		self::_out("START DELETE OLD UNIT DEATH LOG");
		self::_out($count." records selected");
		
		self::_checkTimeLimit();
		$this->_delete();
		
		self::_out("DELETE OK");
	}
	
	private function _delete()
	{
		$sql = "
			SET STATEMENT_TIMEOUT TO ".self::$sqlTimeOut.";	
		
			DELETE FROM ds_units_death_log
			WHERE add_date < (now() - '".$this->timeDelete." DAY'::interval)
			";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
	}
	
	private function _getCount()
	{
		$sql = "
			SELECT
				count(*)
			FROM
				ds_units_death_log
			WHERE
				add_date < (now() - '".$this->timeDelete." DAY'::interval)
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		return (int)$res[0]['count'];
	}
}

?>