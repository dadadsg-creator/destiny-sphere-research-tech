<?php

class CheckMainController
{
	static private $actionName = '';
	
	static private $worldShortName = '';
	
	static private $out = '';
	
	static private $checkList = array(
		'CheckManagerTotalSumm',
		'CheckManagerBalance'		
	);
	
	static public function Exec(array $inputData)
	{
		self::_getParams($inputData);
		
		switch (self::$actionName)
		{
			case 'check':
				self::_checkWorld(self::$worldShortName);
				break;
			default:
				self::_firstScreen();
		}				
	}
	
	static private function _firstScreen()
	{
		$res = ChechSearch::GetWorldsList();
			
		echo "<h1> Check accounts</h1>";
		echo "Select world for check:<br>";
		
		foreach($res AS $k=>$worldData)
		{
			echo "<a href=".$_SERVER['PHP_SELF']."?ACTION=check&WORLD_S_NAME=".$worldData['world_identifier'].">".$worldData['worldname']."</a><br>";
		}
	}
	
	
	static private function _checkWorld($worldShortName)
	{		
		assert(is_string($worldShortName) && !empty($worldShortName));
			
		$worldData = ChechSearch::GetWorldDataByShortName($worldShortName);
		
		echo "<pre>";
		
		$str = "START CHEK WORLD: ".$worldData['worldname'];
		CheckManager::out($str);
		
		foreach(self::$checkList as $k=>$v)
		{
			require_once "./classes/$v.class.php";
			
			$str = "Test name: $v";
			CheckManager::out($str);
			$a = new $v($worldData);
			$a->exec();
		}
				
	}
	
	static private function _getParams(array $inputData)
	{
		if(key_exists('ACTION', $inputData))
		{
			self::$actionName = (string)$inputData['ACTION'];
		}
		
		if(key_exists('WORLD_S_NAME', $inputData))
		{
			self::$worldShortName = $inputData['WORLD_S_NAME'];
		}
	}
}
?>