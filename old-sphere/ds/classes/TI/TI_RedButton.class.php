<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс с данными для интерфейса красной кнопки
 *
 */
class TI_RedButton
{
	/**
	 * Списко колоний которые есть у чувака для филтров в интерфейсе
	 * array(
	 * 	'ID' => ,
	 *  'NAME' => ,
	 *  'ADDRESS' => 
	 * )
	 * 
	 * @var array
	 */
	public $colonis = array();
	
	/**
	 * Типы армий которые есть в конопке.
	 *	
	 * array(
	 *    'INTERNAL_NAME' => ,
	 *    'LOCALIZED_NAME' => ,
	 * )
	 * 
	 * @var array
	 */
	public $armyTypes = array();
	
	/**
	 * Имя пользователя. Нахера надо непонятно.
	 *
	 * @var string
	 */
	public $userName = '';
	
	/**
	 * Текущее время
	 *
	 * @var GB_Date
	 */
	public $timeCurrent;
	
	/**
	 * Максимальнео время прибытия из списка общего
	 *
	 * @var string
	 */
	public $timeMax;
	
	/**
	 * Минимальное время прибытия из списка общего
	 *
	 * @var string
	 */
	public $timeMin;
	
	/**
	 * Массив с армиями на вывод
	 * array(
	 *   'ID' => ,
	 *   'SCAN' => ,
	 *   'NAME' => ,
	 *   'TYPE' => ,
	 *   'USER_FROM' => ,
	 * 	 'USER_TO' => ,
	 *   'SPEED' => ,
	 *   'POWER' => ,
	 *   'ACTION' => ,
	 *   'START_COMB' => ,
	 *   'END_COMB' => ,
	 *   'START_TIME' => ,  
	 *   'END_TIME' => , 
	 * )
	 * 
	 * 
	 * @var array
	 */
	public $armys = array();
	
	public function setColony($ID, $name, $adress, $isCurr)
	{
		assert(is_int($ID) && $ID > 0);
		assert(is_string($name) && !empty($name));
		assert(is_string($adress) && !empty($adress));
		assert($isCurr == 1 || $isCurr == 0);
		
		$this->colonis[] = array(
			'ID' => $ID,
	 	    'NAME' => $name,
	 	    'ADDRESS' => $adress,
	 	    'CUR' => $isCurr
		);
	}
	
	public function setArmyTypes($internalName, $locolizedName, $isCurr)
	{
		assert(is_string($internalName) && !empty($internalName));
		assert(is_string($locolizedName) && !empty($locolizedName));
		assert($isCurr == 1 || $isCurr == 0);
		
		$this->armyTypes[] = array(
			'INTERNAL_NAME' => $internalName,
	 	    'LOCALIZED_NAME' => $locolizedName,
	 	    'CUR' => $isCurr
		);
	}
	
	public function setUserName($userName)
	{
		assert(is_string($userName));
		$this->userName = $userName;
	}
	
	public function setCurrentTime($time)
	{
		assert(is_string($time));
		$this->timeCurrent = $time;
	}
	
	public function setMinTime($time)
	{
		assert(is_string($time));
		$this->timeMin = $time;
	}
	
	public function setMaxTime($time)
	{
		$this->timeMax = $time;
	}
	
	public function setArmyData($ID, $name, $type, $userFrom, $userTo, $speed, $power, $action, $startComb, $endComb, $startTime, $endTime)
	{
		assert(is_int($ID) && $ID > 0);
		assert(is_string($type) && !empty($type));
		assert(is_string($userFrom) && !empty($userFrom));
		assert(is_string($userTo) && !empty($userTo));
		assert(is_string($action) && !empty($action));
		assert(is_string($startComb) && !empty($startComb));
		assert(is_string($endComb) && !empty($endComb));
		assert(is_string($startTime) && !empty($startTime));
		assert(is_string($endTime) && !empty($endTime));
		assert(is_string($speed));
		assert(is_int($power) && $power > 0);
		
		$this->armys[] =  array(
	    	'ID' => $ID,
	     	'SCAN' => 1,
	     	'NAME' => $name,
	     	'TYPE' => $type,
	    	'USER_FROM' => $userFrom,
	    	'USER_TO' => $userTo,
	    	'SPEED' => $speed,
	    	'POWER' => $power,
	    	'ACTION' => $action,
	    	'START_COMB' => $startComb,
	    	'END_COMB' => $endComb,
	    	'START_DATE' =>  $startTime,
	    	'END_DATE' => $endTime
	  );
	}
}

?>



