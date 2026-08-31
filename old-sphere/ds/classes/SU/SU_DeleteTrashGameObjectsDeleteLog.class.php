<?php $CVS_Version = '$Revision$ ; $Date$';
class SU_DeleteTrashGameObjectsDeleteLog extends SU_DeleteTrashBase
{
	private $common;
	
	private $timeDelete = 30;
	
	public function __construct(DS_Common $common)
	{
		$this->common = $common;
	}
	
	public function delete()
	{
		$count = $this->_getCount();
		self::_out("START DELETE OLD GAME OBJECTS DELETE LOG");
		self::_out($count." records selected");
		
		self::_checkTimeLimit();
		$this->_delete();
		
		self::_out("DELETE OK");
	}
	
	private function _delete()
	{
		$sql = "
			SET STATEMENT_TIMEOUT TO ".self::$sqlTimeOut.";
			
			DELETE FROM ds_log_delete_game_objects
			WHERE delete_date < (now() - '".$this->timeDelete." DAY'::interval)
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
				ds_log_delete_game_objects
			WHERE
				delete_date < (now() - '".$this->timeDelete." DAY'::interval)
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		return (int)$res[0]['count'];
	}
}

?>