<?php

class CheckManagerTotalSumm extends CheckManager
{
	private $etalonSumm = 2000000000;
	
	public function __construct(array $worldData)
	{
		parent::__construct($worldData);
	}
	
	public function Exec()
	{		

		$totalSumm = ChechSearch::GetMoneySummOnWorld($this->worldData['dbname'], $this->worldData['world_identifier']);		

		foreach($totalSumm as $k=>$v)
		{
			$str = "	World val $k = $v. Etalon = ".$this->etalonSumm;
			self::out($str);
			
			if((int)$this->etalonSumm == (int)$v)
			{
				$str = "	OK";
				self::out($str);					
			}else{
				$str = "	Uncorrect summ! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR!";
				self::out($str);	
			}
		}
	}
}

?>