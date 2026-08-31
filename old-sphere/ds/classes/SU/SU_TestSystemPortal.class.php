<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_TestSystemPortal extends SU_TestSystemBase
{
	private $data = array();
	
	public function exec()
	{
		$url = constant('GAME_HOST');		
		
		$string = "Open page [$url]";
		
		$this->_outString($string, 4);
		
		//Делаем три попытки открыть портал
		for ($i = 0; $i <3; $i++)
		{
			$gameTestResult = $this->_testOpenPoratl($url);
			if (strpos($gameTestResult, 'OK'))
			{
				$this->_outString($gameTestResult, 8);	
				return true;
			} 
		}
		
		//Если попытки входа в игру неудачные, записываем ошибку
		$result = "$gameTestResult!!! <<<<<<<<<<<<< ERROR!";
		$this->_writeError('Pl', "$gameTestResult");
		$this->_outString($result, 8);
		
		return false;
	}
	
	private function _testOpenPoratl($url)
	{
		//Засекаем время
		$startTime = getmicrotime();
		
		//Получаем содержимое сайта
		$result = file_get_contents($url, false, stream_context_create(self::$options), 0, 100000);
		
		//Засекаем время
		$endTime = getmicrotime();
		
		//Смотрим, получили ли мы содержимое
		if (!$result || strlen($result) < 10)
		{
			return "Portal NOT OPENED";
		} else {
			//Если получили, то то ли получили								
			if (!strpos($result, '<!--Generation time'))
			{
				return "Portal NOT CONSIST string '<!--Generation time'";
			}
		}
		
		//Вычисляем время открытия страницы
		$timeDelta = $endTime - $startTime;
		
		if ($timeDelta > 7)
		{
			//Если сайт открывался более 7 секунд
			return "Portal opens for $timeDelta seconds";
		} else {
			return "Portal opens for $timeDelta seconds - OK!";
		}
	}
}

?>