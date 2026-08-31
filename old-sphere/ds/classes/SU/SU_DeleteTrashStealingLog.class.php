<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_DeleteTrashStealingLog extends SU_DeleteTrashBase
{
	private $common;
	
	private $timeDelete = 14;
	
	public function __construct(DS_Common $common)
	{
		$this->common = $common;
	}
			
	public function delete()
	{
		$count = $this->_getCount();
		self::_out("START DELETE OLD STEALING LOG");

		self::_out($count." records selected");

		self::_checkTimeLimit();
		$this->_delete();

		self::_out("DELETE OK");
	}

	private function _delete()
	{
		$sql = "
			SET STATEMENT_TIMEOUT TO ".self::$sqlTimeOut.";	
		
			DELETE FROM ds_resource_stealing_tax_log
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
				ds_resource_stealing_tax_log
			WHERE
				add_date < (now() - '".$this->timeDelete." DAY'::interval)
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));

		return (int)$res[0]['count'];
	}
}

?>