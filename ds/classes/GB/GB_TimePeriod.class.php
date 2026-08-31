<?php $CVS_Version = '$Revision$ ; $Date$';

class GB_TimePeriod extends GB_DataType 
{
	
	private static $dayLength = 86400;
	
	private static $hourLength = 3600;
	
	private static $minLength = 60;
	
	protected function _validateValue(&$value)
	{
		parent::_validateValue($value);		
		assert(is_numeric($value));
	}
		
	public function getDSFormat()
	{
		$localization = DE_Localization::CreateForCurentLang();		
		$daysSimvol = $localization->get(30003);
		$hoursSimvol = $localization->get(30004);
		$minsSimvol = $localization->get(30005);
		$secondsSimvol = $localization->get(30006);
		
		$time = $this->value;
		$out = '';
		
		$days = floor ($time / self::$dayLength);
		if($days)
		{
			$time = $time - $days * self::$dayLength;
			if($days>0)
			{
				$out .= $days.$daysSimvol." ";
			}
			
		}

		$hours = floor ($time / self::$hourLength);
		if($hours)
		{
			$time = $time - $hours * self::$hourLength;
			
			if($hours>0)
			{
				$out .= $hours.$hoursSimvol." ";
			}
			
		}		

		$mins = floor ($time / self::$minLength);
		if($mins)
		{
			$seconds = $time - $mins * self::$minLength;
			$out .= $mins.$minsSimvol." ";
			
			if($seconds>0)
			{
				$out .= $seconds.$secondsSimvol;
			}
			
		}else{
			if($time>0)
			{
				$out .= $time.$secondsSimvol;
			}		
		}
		
		return trim($out);				
	}
	
	public function getWithParts()
	{
		$period = array(
			'DAYS' => '0',
			'HOURS' => '0',
			'MINUTES' => '0',
			'SECONDS' => '0'
		);
		
		$time = $this->value;		
		
		$days = floor ($time / self::$dayLength);
		if($days)
		{
			$period['DAYS'] = $days;
			$time = $time - $days * self::$dayLength;		
		}

		$hours = floor ($time / self::$hourLength);
		if($hours)
		{
			$period['HOURS'] = $hours;
			$time = $time - $hours * self::$hourLength;			
		}
		
		$mins = floor ($time / self::$minLength);
		if($mins)
		{
			$period['MINUTES'] = $mins;
			$time = $time - $mins * self::$minLength;			
		}						
		
		$period['SECONDS'] = $time;		
		
		return $period;
	}
}
?>