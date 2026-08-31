<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_DeleteTrashEvents extends SU_DeleteTrashBase
{
	/**
	 * Enter description here...
	 *
	 * @var DS_common
	 */
	private $common;
	
	private $limitDelete = 1000;
	
	public function __construct(DS_Common $common)
	{
		$this->common = $common;
	}
		
	public function delete()
	{
		self::_out("DELETE OF TRASH EVENTS STARTED");
		
		$trashEventsCount = $this->_countTrashEvents();		
		
		$iterations = ceil($trashEventsCount/$this->limitDelete);
		
		self::_out("Find ".$trashEventsCount." trash events. Delete limit: ".$this->limitDelete." Iterations: $iterations");
		
		$counter = 1;
		do{			
			self::_checkTimeLimit();
    		$deleteCounter = $this->_deleteTrashEvents($this->limitDelete);
    		self::_out("¹ $counter = $deleteCounter trash events deleted.");
    		
    		$counter++;
    		assert($counter < 1000000);    		
		} while ($deleteCounter > 0);

		
		self::_out("DELETE OF TRASH EVENTS COMPLITED");
	}
	
	private function _countTrashEvents()
	{
		$sql = "SELECT count(*) AS colvo
				FROM ds_eventqueue AS eq
				WHERE eq.istrash = true";
		$res = $this->common->getResultArray('', $sql);
		assert(empty($this->common->errormsg) && count($res) == 1);
		
		return (int)$res[0]['colvo'];		
	}	
	
	private function _deleteTrashEvents($limit)
	{
		assert(is_int($limit) && $limit > 0);
		
		$sql = "
				SET STATEMENT_TIMEOUT TO ".self::$sqlTimeOut.";
				SELECT drop_event_queue(eq.id_eventqueue)
				FROM ds_eventqueue AS eq
				WHERE eq.istrash = true
				LIMIT $limit";
		$res = $this->common->getResultArray('', $sql);
		assert(empty($this->common->errormsg));
		
		return count($res);		
	}	
}


?>