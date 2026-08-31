<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_DeleteTrashTradeHistory extends SU_DeleteTrashBase
{
	private $common;
	
	private $timeDelete = 60;
	
	public function __construct(DS_Common $common)
	{
		$this->common = $common;
	}
	
	public function delete()
	{
		self::_out("START DELETE OLD TRADE LOGS");
		
		self::_checkTimeLimit();
		$count = $this->_delete();
		
		self::_out("DELETED ".$count." LOGS");
	}
	
	private function _delete()
	{
		$sql = "
			SET STATEMENT_TIMEOUT TO ".self::$sqlTimeOut.";
			
			SELECT
				clear_trade_history(".$this->timeDelete.") as count
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		return (int)$res[0]['count'];
	}
}

?>