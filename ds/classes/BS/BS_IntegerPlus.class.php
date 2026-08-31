<?php $CVS_Version = '$Revision$ ; $Date$';
require_once ('BS_SimpleDataType.class.php');


/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Клас для хранения целого числа больше нуля.
 * @version 1.0
 * @created 11-окт-2004 13:55:47
 */
class BS_IntegerPlus extends BS_SimpleDataType
{

	/**
	 * Метод для проверки корреткности устанавливаемого значения
	 * @param val
	 * 
	 */
	protected function check($val)
	{
		if ((int) $val == $val && abs($val) == $val) return true;		
		return false;
		//Метод для проверки корреткности
		//устанавливаемого значения в случае
		//обнаружения несоответсвия по типу
		//генериться ошибка ПХП c сообщением о
		//несоотвествие типа.

	}

}
?>