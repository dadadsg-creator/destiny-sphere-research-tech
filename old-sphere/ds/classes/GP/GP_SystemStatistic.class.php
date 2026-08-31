<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс для сбора истемной статистики
 */
class GP_SystemStatistic
{
	static private $data = array
	(		
		'DS_COMMON' => array
		(
			'TIME_CONNECTION_TOTAL' => 0,
			'SUM_TIME_OF_WORK' => 0,
			'COUNT_QUERYS_TOTAL' => 0,
			'1 -------------------' => '',
			'COUNT_SELECT' => 0,
			'COUNT_INSERT' => 0,
			'COUNT_DELETE' => 0,
			'COUNT_UPDATE' => 0,
			'COUNT_OTHER' => 0,
			'2 -------------------' => '',
			'SUMM_TIME_SELECT' => 0,
			'SUMM_TIME_INSERT' => 0,
			'SUMM_TIME_DELETE' => 0,
			'SUMM_TIME_UPDATE' => 0,
			'SUMM_TIME_OTHER' => 0,
			'3 -------------------' => '',
			'COUNT_SELECT_WITH_CACHE_MARK' => 0,
			'COUNT_SELECT_WITHOUT_CACHE_MARK' => 0,						
			'COUNT_CACHE_MISSES' => 0,
			'COUNT_CACHE_MACHES' => 0,						
			'SUMM_TIME_NO_CACHED_QUERYS' => 0,
			'SUMM_TIME_SETED_QUERYS' => 0,			
			'SUMM_TIME_QUERYS_FROM_CACHE' => 0,				
		),		
		
		'DB_CACHE' => array
		(			
			'SUMM_SET_TIME' => 0,			
			'SUMM_GET_TIME' => 0,						
			'1 -------------------' => '',					
			'SET_COUNT' => 0,			
			'GET_COUNT' => 0,
			'2 -------------------' => '',
			'COUNT_MISSES' => 0,
			'COUNT_MACHES' => 0,						
		),		
		
		'IMPORT_FILES' => array
		(
			'IMPORT_FILES_COUNT' => 0,
			'SUMMARY_IMPORT_TIME' => 0			
		),
		
		'USERACTION' => array
		(
			'ACTION_TAG' => '',
			'EXEC_TIME' => 0,
			'HAHDLERS_STARTED' => '',
			'NEW_FORMAT_OBJECTS_CREATED' => 0,
			'HANDLED_EVENTS' => 0			
		)		
	);
	
	static public function AddToArray($sectionName, $paramName, $value)
	{
		if(!constant('LOGING_SYSTEM_STATISTIC_FLAG'))
		{
			return;
		}

		assert(key_exists($sectionName, self::$data));
		
		if(!self::_canLogSection($sectionName))
		{
			return;
		}			
		
		self::$data[$sectionName][$paramName][] = $value;
	}
	
	
	static public function GetParam($sectionName, $paramName)
	{
		if(!constant('LOGING_SYSTEM_STATISTIC_FLAG'))
		{
			return;
		}
		
		assert(key_exists($sectionName, self::$data));
		
		if(!self::_canLogSection($sectionName))
		{
			return;
		}			
		
		if(!key_exists($paramName, self::$data[$sectionName]))
		{
			self::$data[$sectionName][$paramName] = 0;
		}
		
		return self::$data[$sectionName][$paramName];
	}
	
	static public function SetParam($sectionName, $paramName, $value)
	{
		assert(defined('LOGING_SYSTEM_STATISTIC_FLAG'));
		
		if(!constant('LOGING_SYSTEM_STATISTIC_FLAG'))
		{
			return;
		}
		
		assert(key_exists($sectionName, self::$data));
		
		if(!self::_canLogSection($sectionName))
		{
			return;
		}			
		
		self::$data[$sectionName][$paramName] = $value;
	}
	
	static public function DeltaParam($sectionName, $paramName, $value)
	{
		assert(defined('LOGING_SYSTEM_STATISTIC_FLAG'));
		
		if(!constant('LOGING_SYSTEM_STATISTIC_FLAG'))
		{
			return;
		}
		
		assert(key_exists($sectionName, self::$data));
		
		if(!self::_canLogSection($sectionName))
		{
			return;
		}			
		
		if(!key_exists($paramName, self::$data[$sectionName]))
		{
			self::$data[$sectionName][$paramName] = 0;
		}
		
		self::$data[$sectionName][$paramName] += $value;
	}
	
	static public function FormStatistic()
	{	
		if(!constant('LOGING_SYSTEM_STATISTIC_FLAG'))
		{
			return;
		}
	
		if(!in_array(self::GetParam('USERACTION', 'ACTION_TAG'), $_GLOBALS['ACTIONS_FOR_LOG_SYSTEM_STAT']))
		{
			return;
		}
		
		assert(key_exists('SYSTEM_STATISTIC_LOG_SECTIONS', $_GLOBALS));
		
		$msg = "\n";
		//ksort(self::$data);
		foreach(self::$data as $k=>$v)
		{
			assert(key_exists($k, $_GLOBALS['SYSTEM_STATISTIC_LOG_SECTIONS']));
		
			if(!$_GLOBALS['SYSTEM_STATISTIC_LOG_SECTIONS'][$k])
			{
				continue;
			}
		
			$msg .= $k.":\n";
		
			//ksort($v); 
			foreach($v as $k2=>$v2)
			{			
				if(is_array($v2))
				{
					$msg .= "    ".$k2." = ".implode(', ',$v2)."\n";			
				}else{
					$msg .= "    ".$k2." = ".$v2."\n";			
				}
			
			}
			$msg .= "\n";
		}
	
		/*$msg .= "SYSTEM_LOAD:\n";
	
		$msg .= GP_AnalizeServerLoad::GetLog();*/
	}
	
	static private function _canLogSection($sectionName)
	{
		assert(key_exists('SYSTEM_STATISTIC_LOG_SECTIONS', $_GLOBALS));
		assert(key_exists($sectionName, $_GLOBALS['SYSTEM_STATISTIC_LOG_SECTIONS']));
						
		return $_GLOBALS['SYSTEM_STATISTIC_LOG_SECTIONS'][$sectionName];
	}
	
}

?>