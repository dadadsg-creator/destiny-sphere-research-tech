<?php

/**
 * Основной контроллер операций по по проверки межбазовый транзакций
 *
 */
class MU_InterworldController extends MU_InterworldBase
{			
	
	static public $tmp = array();
	
	public function Exec()
	{		
		self::Out('============================================================================================');
		self::Out('======================== START VALIDATE INTEWORLD OPERATIONS ===============================');
		self::Out('============================================================================================');
		
		$worldsData = MU_InterworldSearch::GetWorldsList();
		self::Out('Get worlds list. Count = '.count($worldsData));
		
		foreach($worldsData as $k=>$worldData)
		{
			self::Out('============================================================================================');
			self::Out('=================== START validate world ['.$worldData['world_identifier'].'] ==============');
			self::Out('============================================================================================');
			
			$check2 = new MU_InterworldExecTestSend($worldData);
			$check2->exec();
			
			$check1 = new MU_InterworldExecTestRecive($worldData);
			$check1->exec();			
		}
		
		self::Out('============================================================================================');
		self::Out('======================== END VALIDATE INTEWORLD OPERATIONS =================================');
		self::Out('============================================================================================');
		
		$out = "\n";		
	}
}

?>