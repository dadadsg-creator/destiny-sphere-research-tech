<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_DeleteTrashTradeHistoryArtefact extends SU_DeleteTrashBase
{
	private $common;
	
	public function __construct(DS_Common $common)
	{
		$this->common = $common;
	}
	
	public function delete()
	{
		self::_out("START DELETE OLD TRADE ARTEFACT LOGS");
		
		self::_checkTimeLimit();
		$count = $this->_delete();
		
		self::_out("DELETE TRADE ARTEFACT LOGS OK");
	}
	
	private function _delete()
	{
		$sql = "
			  	SET STATEMENT_TIMEOUT TO ".self::$sqlTimeOut.";
			  
			  	DELETE FROM
			        ds_trade_history_artefactinstances
			    WHERE
			        id_trade NOT IN 
			    	(SELECT id_trade FROM ds_trade_history);
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		return;
	}
}

?>