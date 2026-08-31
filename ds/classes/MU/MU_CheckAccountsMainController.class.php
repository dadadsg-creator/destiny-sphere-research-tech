<?php

class MU_CheckAccountsMainController
{
	static private $actionName = '';
	
	static private $worldShortName = array();
	
	static private $out = '';
	
	static private $checkList = array(
		'MU_CheckAccountsManagerTotalSumm',
		'MU_CheckAccountsManagerBalance'		
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
		$res = MU_CheckAccountsSearch::GetWorldsList();
		echo "<h1> Check accounts</h1>";
		echo "<b>Select world for check:</b><br>";
		
		echo "<form action='".$_SERVER['PHP_SELF']."' method='GET'>";
		foreach($res AS $k=>$worldData)
		{
			echo "<input type='checkbox' name='WORLD_S_NAME[]' value=".$worldData['world_identifier'].">".$worldData['worldname']."<br>\n";
		}
		echo '<input type="hidden" name="ACTION" value="check">';
		echo "<br>";
		echo '<input type="submit" class="Submit" value="Check">';
		echo "</form>";
	}
	
	
	static private function _checkWorld($worldShortName)
	{		
		assert(is_array($worldShortName) && !empty($worldShortName));
		
		foreach ($worldShortName as $key => $world)
		{
			$worldData = MU_CheckAccountsSearch::GetWorldDataByShortName($world);
			MU_CheckAccountsManager::$worldName = $world;
			
			echo "<pre>";
			
			$str = " ====== START CHEK WORLD: ".$worldData['worldname']." ======";
			MU_CheckAccountsManager::out($str, true);
						
			foreach(self::$checkList as $k=>$v)
			{				
				$str = "Test name: $v";
				MU_CheckAccountsManager::out($str);
				$a = new $v($worldData);
				$a->exec();
			}
			
			$str = " ====== END CHEK WORLD: ".$worldData['worldname']." ======";
			MU_CheckAccountsManager::out($str, true);
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
			foreach ($inputData['WORLD_S_NAME'] as $key => $data)
			{
				self::$worldShortName[] = $data;
			}
		}
	}
}
?>