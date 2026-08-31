<?php

class MU_CheckAccountsMainController
{
	static private $actionName = '';
	
	static private $worldShortName = array();
	
	static private $out = '';
	
	static private $checkList = array(
		'CheckManagerTotalSumm',
		'CheckManagerBalance'		
	);
	
	static public function Exec(array $inputData)
	{
/*print_r($inputData);
echo "<br>";		
echo "<br>";		
echo "<br>";*/		
		self::_getParams($inputData);
//echo "actionName = ".self::$actionName."<br>";
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
		echo "<font color=#F8F8FF><b>Select world for check:</b></font><br>";
		
		echo "<form action='".$_SERVER['PHP_SELF']."' method='GET'>";
		foreach($res AS $k=>$worldData)
		{
			echo "<input type='checkbox' name='WORLD_S_NAME[]' value=".$worldData['world_identifier']."> <font color=#F8F8FF>".$worldData['worldname']."</font><br>";

			//echo "<a href=".$_SERVER['PHP_SELF']."?ACTION=check&WORLD_S_NAME=".$worldData['world_identifier'].">".$worldData['worldname']."</a><br>";
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
			$worldData = ChechSearch::GetWorldDataByShortName($world);

			echo "<pre>";

			$str = "START CHEK WORLD: ".$worldData['worldname'];
			CheckManager::out($str);

			foreach(self::$checkList as $k=>$v)
			{
				$includeString = "../classes/$v.class.php";
				require_once $includeString;

				$str = "Test name: $v";
				CheckManager::out($str);
				$a = new $v($worldData);
				$a->exec();
			}
		}
	}
	
	static private function _getParams(array $inputData)
	{
//print_r($inputData);		
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