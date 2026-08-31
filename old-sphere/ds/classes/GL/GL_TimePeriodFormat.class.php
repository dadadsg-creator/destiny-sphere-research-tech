<?php $CVS_Version = '$Revision$ ; $Date$';



/**
 * Класс задает формат выода времени в игры с учетом локализации
 * 
 */
class GL_TimePeriodFormat extends GameBase
{
	/** Обозначение дня краткое */
	public $daysSimvol = '';
	
	/** Обозначение часов краткое */
	public $hoursSimvol = '';
	
	/** Обозначение минут краткое */
	public $minsSimvol = '';
	
	/** Обозначение секунд краткое*/
	public $secondsSimvol = '';
	
	/** Время турна в секундах */
	public $turnTime = '';
		
	
	function GL_TimePeriodFormat()
	{
		$this->GameBase();
		$localization = DE_Localization::CreateForCurentLang();
		$worldSettings = DE_WorldSettings::CreateForCurentWorld();
		$this->daysSimvol = $localization->get(30003);
		$this->hoursSimvol = $localization->get(30004);
		$this->minsSimvol = $localization->get(30005);
		$this->secondsSimvol = $localization->get(30006);
		
		$this->turnTime = $worldSettings->get('TURN_TIME') * 60;		
	}
		
	/**
	* @return string lolozed and formated
	* @param Ходы  $turns
	* @desc Возвращает время переджаное в турнах в виде троки форматирвоанной локализованной
	*/
	function getFromTurns($turns)
	{
		assert (!empty($turns));	
		$time = round ($turns*$this->turnTime);
		settype($time, 'integer');
		return $this->getFromSeconds($time);
	}
	
	/**
	* @return string lolozed and formated
	* @param Время в секундах $seconds	
	* @desc Возвращает время переджаное в секунлдах в виде строки форматирвоанной локализованной
	*/
	function getFromSeconds($seconds)
	{
		settype($seconds, 'integer');
		assert (!empty($seconds));
		
//logAll ( "--== NEW ==--",'!TmiPeriodFormat.log');

		$time = $seconds;
		$out = '';
		
//logAll ( "Seconds = $seconds",'!TmiPeriodFormat.log');
//logAll ( "Time = $time",'!TmiPeriodFormat.log');
//logAll ( "Out = $out",'!TmiPeriodFormat.log');
			
		$dayLength = 86400;
		$hourLength = 3600;
		$minLength = 60;		
		
//logAll ( "DAYS",'!TmiPeriodFormat.log');

		$days = floor ($time / $dayLength);
		if($days)
		{
			$time = $time - $days * $dayLength;
			$out .= $days.$this->daysSimvol." ";
		}
		
//logAll ( "Time = $time",'!TmiPeriodFormat.log');
//logAll ( "Out = $out",'!TmiPeriodFormat.log');

//logAll ( "HOURS",'!TmiPeriodFormat.log');

		$hours = floor ($time / $hourLength);
		if($hours)
		{
			$time = $time - $hours * $hourLength;
			$out .= $hours.$this->hoursSimvol." ";
		}
		
//logAll ( "Time = $time",'!TmiPeriodFormat.log');
//logAll ( "Out = $out",'!TmiPeriodFormat.log');

//logAll ( "MINS",'!TmiPeriodFormat.log');

		$mins = floor ($time / $minLength);
		if($mins)
		{
			$time = $time - $mins * $minLength;
			$out .= $mins.$this->minsSimvol." ";
		}
		
//logAll ( "Time = $time",'!TmiPeriodFormat.log');
//logAll ( "Out = $out",'!TmiPeriodFormat.log');

		return trim($out);				
	}
	
}//END OF CLASS

?>