<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_TestSystemParalelSystem extends SU_TestSystemBase
{
	private $urlsForTest = array();

	public function __construct()
	{
		assert(key_exists('TEST_SYSTEMS_OTHER_SYSTEMS_TO_CHECK', $GLOBALS) && is_array($GLOBALS['TEST_SYSTEMS_OTHER_SYSTEMS_TO_CHECK']));
		$this->urlsForTest = $GLOBALS['TEST_SYSTEMS_OTHER_SYSTEMS_TO_CHECK'];
	}

	public function exec()
	{		
		foreach($this->urlsForTest as $name => $url)
		{
			$this->_outString("Send request to: ".$url, 4);
			
			//Делаем три попытки достучаться
			for ($i = 0; $i <3; $i++)
			{
				$gameTestResult = $this->testParallelSystem($url);
				if ($gameTestResult == 'OK')
				{
					$this->_outString("OK!", 4);
					return true;
				}
			}

			//Если попытки входа в игру неудачные, записываем ошибку
			$result = "$gameTestResult!!! <<<<<<<<<<<<< ERROR!";
			$this->_writeError('EXT', "$gameTestResult", $name);
			$this->_outString($result, 8);
		}
	}

	static private function testParallelSystem($url)
	{
		$contents = file_get_contents($url, false, stream_context_create(self::$options), 0, 100000);

		switch($contents)
		{
			case 'OK':
				return 'OK';
			case 'TO_OLD_LAST_WORK':
				return 'To old last work';
			default:
				return 'No ansver';
		}
	}
}

?>