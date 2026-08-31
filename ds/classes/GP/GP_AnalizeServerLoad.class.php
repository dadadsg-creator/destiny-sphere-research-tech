<?php $CVS_Version = '$Revision$ ; $Date$';

class GP_AnalizeServerLoad
{
	static private $data = array();
	
	static private $step = '  ';
	
	static private $inProcess = array();

	static public $dbTimers = array();
	
	static public function StartHandler($handler)
	{
		return;
		
		assert(is_object($handler));

		DebugTools::TimerStart(self::_formID($handler));
		
		self::$data[self::_formID($handler)] = "";
		
		self::$dbTimers[self::_formID($handler)] = 0;
	
		self::$inProcess[self::_formID($handler)] = array(
			'HTTP' => 0,
			'DELTA' => 0,
			'DB' => 0
		);
	}
	
	static public function StopHandler($handler)
	{
		return;
		
		assert(is_object($handler));

		$delta = round(SU_DebugTools::TimerStop(self::_formID($handler)), 3);		
		
		$dbTime = round(self::$dbTimers[self::_formID($handler)], 3);		
		unset(self::$dbTimers[self::_formID($handler)]);
				
		$http = $delta - $dbTime;
		
		//////////////////////
		$http -= self::$inProcess[self::_formID($handler)]['HTTP'];
		$dbTime -= self::$inProcess[self::_formID($handler)]['DB'];
		$delta -= self::$inProcess[self::_formID($handler)]['DELTA'];
		
		unset(self::$inProcess[self::_formID($handler)]);	
		
		foreach(self::$inProcess as $k=>$v)
		{
			self::$inProcess[$k]['HTTP'] += $http;
			self::$inProcess[$k]['DB'] += $dbTime;
			self::$inProcess[$k]['DELTA'] += $delta;
		}
		
		self::$data[self::_formID($handler)] = self::_formStep().self::_formID($handler).": HTTP ".$http." DB ".$dbTime ;		
	}		
	
	static public function SetDBTime($time)
	{
		return '';
		
		assert($time>=0);
		
		foreach(self::$dbTimers as $k=>$v)
		{
			self::$dbTimers[$k] += $time;
		}
	}
	
	static public function SetLog()
	{
				
		return;
		
		SU_DebugTools::LogAll(implode("\n", self::$data), 'AnalizeServerLoad.log', 0);
	}
	
	static public function GetLog()
	{
		return '';
		
		return implode("\n", self::$data);
	}
	
	static private function _formStep()
	{
		return '';
		
		$out = "";
		
		$i = 0;
		while($i<count(self::$inProcess))
		{
			$i++;
			$out .= self::$step; 
		}
		
		return $out;
	}
	
	static private function _formID($handler)
	{
		return '';
		
		assert(is_object($handler));
				
		return get_class($handler)."(".substr((string)$handler, 7).")";;
	}
}

?>