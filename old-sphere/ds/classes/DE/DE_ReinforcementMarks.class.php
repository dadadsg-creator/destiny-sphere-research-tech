<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс для опсиания сущности СОТА
 */

class DE_ReinforcementMarks extends DE_Base
{
	private $marks = array();
	
   /**
	* @param $id - ID соты
	* @desc Внести описание здесь...
	*/
	function __construct($id)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($id) && $id>0);
		parent::__construct($id);						
	}//END OF METHOD
	
	static public function CreateFromID($id)
	{
		assert(is_int($id) && $id>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);								
	}		

	public function addMark(GB_Date $addDate, $addPower)
	{
		assert($addPower >= 0);
		
		$this->marks[$addDate->value] = round((float)$addPower);
	}
	
	public function get(GB_Date $fromDate, GB_Date $toDate)
	{
		$outPower = (float)0;
		
		foreach($this->marks as $time=>$power)
		{
			if($time > $fromDate->value && $time < $toDate->value)
			{
				$outPower += $power;
			}
		}
		
		return $outPower;
	}
}//END OF CLASS


?>