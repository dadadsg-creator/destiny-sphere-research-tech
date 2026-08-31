<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_DeleteTrashBattles extends SU_DeleteTrashBase
{
	private $common;
	
	private $timeDeleteFull;
	
	private $timeDeletePart;
	
	private $iterationSize = 1000;
	
	private $maxIterations = 100;
	
	function __construct(DS_Common $common)
	{
		$this->common = $common;
		
		$this->timeDeleteFull = 14;
		
		$this->timeDeletePart = 10;
	}
	
	public function delete()
	{
		self::_out("START DELETE OLD BATTLES");
		
		$count = $this->_getCountForPartDelete($this->timeDeletePart);
		$iterations = (int)min(ceil($count / $this->iterationSize), $this->maxIterations);
		self::_out("Found ".$count." battles for part delete; ".$iterations." iterations by ".$this->iterationSize.' battles');
		for($i=1; $i<=$iterations; $i++)
		{
			self::_checkTimeLimit();
			$this->_deletePart($iterations);
			self::_out($i." deleted ".$this->iterationSize." battles");
		}
		
		$count = $this->_getCountOldBattles($this->timeDeleteFull);
		$iterations = min(ceil($count / $this->iterationSize), $this->maxIterations);
		self::_out("Found ".$count." battles for full delete; ".$iterations." iterations by ".$this->iterationSize.' battles');
		
		for($i=1; $i<=$iterations; $i++)
		{
			self::_checkTimeLimit();
			$this->_deleteFull();
			self::_out($i." deleted ".$this->iterationSize." battles");
		}
	}
	
	private function _deletePart($iteration)
	{
		assert(is_int($iteration) && $iteration >= 0);
		
		$sql = "
			SET STATEMENT_TIMEOUT TO ".self::$sqlTimeOut.";
		
			DELETE FROM
				ds_battleresult
			WHERE id_battleround in (
					SELECT 
						br.id_battleround
					FROM 
						ds_battleround AS br
						INNER JOIN ds_battle AS b USING (id_battle)
					WHERE 
						b.battleenddate IS NOT NULL 					 				
						AND 
						b.battleenddate < (now() - '".$this->timeDeletePart." DAY'::interval)
					LIMIT 
						".$this->iterationSize."
					OFFSET ".($this->iterationSize * $iteration)."
				)
			";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
	}
	
	private function _deleteFull()
	{
		$sql = "
			SET STATEMENT_TIMEOUT TO ".self::$sqlTimeOut.";
		
			DELETE FROM
				ds_battle
			WHERE id_battle in 
			(
				SELECT 
					b.id_battle
				FROM 
					ds_battle AS b
				WHERE 
					b.battleenddate IS NOT NULL 					 				
					AND 
					b.battleenddate < (now() - '".$this->timeDeleteFull." DAY'::interval)
				LIMIT 
					".$this->iterationSize."
			)
			";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
	}
	
	private function _getCountOldBattles($days)
	{
		assert(is_int($days) && $days > 0);
		
		$sql = "
			SELECT
				count(*)
			FROM
				ds_battle as b
			WHERE
				b.battleenddate IS NOT NULL 					 				
				AND 
				b.battleenddate < (now() - '".$days." DAY' ::interval)
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		return (int)$res[0]['count'];
	}
	
	private function _getCountForPartDelete($days)
	{
		assert(is_int($days) && $days > 0);
		
		$sql = "
			SELECT
				count(DISTINCT b.id_battle)
			FROM
				ds_battleresult AS bre 
				INNER JOIN ds_battleround AS br ON (bre.id_battleround = br.id_battleround)
				INNER JOIN ds_battle AS b USING (id_battle)
			WHERE 
				b.battleenddate IS NOT NULL 					 				
				AND 
				b.battleenddate < (now() - '".$days." DAY'::interval)
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		return (int)$res[0]['count'];
	}
}

?>