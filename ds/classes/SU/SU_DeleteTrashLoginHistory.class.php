<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_DeleteTrashLoginHistory extends SU_DeleteTrashBase
{
	private $common;
	
	private $timeToDelete = 150; // за сколько дней хранить историю заходов 
	
	public function __construct(DS_Common $common)
	{
		$this->common = $common;
	}
	
	public function delete()
	{
		$countToDelete = $this->_getCount();
		
		self::_out("START DELETE LOGIN HISTORY");
		self::_out($countToDelete." records selected");
		
		if($countToDelete > 0)
		{
			self::_checkTimeLimit();
			$this->_delete();
		}
		
		self::_out("DELETE OK");
	}
	
	private function _getCount()
	{
		$sql = "
			SELECT
				count(*)
			FROM
				ds_loginhistory
			WHERE
				lastvisit < now() - '".$this->timeToDelete." day'::interval;
			";
		$res = $this->common->getResultArray("", $sql, "", "" ,"");
		assert(empty($this->common->errormsg));
		
		return (int)$res[0]['count'];
	}
	
	private function _delete()
	{
		$sql = "
			SET STATEMENT_TIMEOUT TO ".self::$sqlTimeOut.";
		
			delete from 
				ds_loginhistory 
			where 
				lastvisit < now() - '".$this->timeToDelete." day'::interval;
		";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
	}
}

?>