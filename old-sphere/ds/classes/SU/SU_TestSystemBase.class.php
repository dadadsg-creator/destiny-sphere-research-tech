<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_TestSystemBase
{		
	private $errorsLisrt = '';	

	static protected $fullReportText = '';
	
	static protected $haveErrors = array();
	
	static protected $ErrorsByServer = array();
	
	static protected $options = array(
									'http' => array(
													'method' => 'GET', 
													'header' => '', 
													'user_agent' => '', 
													'content' => '', 
													'proxy' => '', 
													'request_fulluri' => '', 
													'max_redirects' => '',
													'timeout' => 30
													)
										);
	
	protected function _outString($msg, $level = 1)
	{
		assert($level > 0);
		$sapi = php_sapi_name();
		
		$str = SU_DebugTools::GetInternalDate().str_repeat (' ', $level).$msg."\n";
		
		self::$fullReportText .= $str;
		
		//Если тест выполняется не из командной строки, выводим его на экран
		if(strpos($sapi, "apache"))
		{
			echo $str;		
		}
		
		flush();
	}
	
	public function exec()
	{
		ds_error('Must be implemented');	
	}
	
	protected function _addError($errorType)
	{
		if(!key_exists($errorType, self::$haveErrors))
		{
			self::$haveErrors[$errorType] = 0;	
		}
		
		self::$haveErrors[$errorType]++;
	}
	
	protected function _writeError($errorType, $errorMsg, $server = 'OTHER')
	{
		self::$haveErrors[$errorType][] = $errorMsg;
		
		if (empty($server)) {
			$server = 'OTHER';
		}
		
		self::$ErrorsByServer[$server][] = $errorType;
	}
}

?>