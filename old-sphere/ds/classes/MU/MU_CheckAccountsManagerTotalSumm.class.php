<?php

class MU_CheckAccountsManagerTotalSumm extends MU_CheckAccountsManager
{
	private $etalonSumm = 2000000000;
	
	public function __construct(array $worldData)
	{
		parent::__construct($worldData);
	}
	
	public function Exec()
	{		
		
		$totalSumm = MU_CheckAccountsSearch::GetMoneySummOnWorld($this->worldData['dbname'], $this->worldData['world_identifier']);		
		
		foreach($totalSumm as $k=>$v)
		{
			$str = "	World val $k = $v. Etalon = ".$this->etalonSumm;
			self::out($str, true);
			
			if((int)$this->etalonSumm == (int)$v)
			{
				$str = "	OK";
				self::out($str, true);					
			}else{
				$delta = $v - $this->etalonSumm;
				$str = "UncorectSumm Must: ".$this->etalonSumm." Have: $k = $v Delta: $delta";
				self::processError($str, 'System', 'WorldtotalSumm');
			}
		}
	}
}

?>