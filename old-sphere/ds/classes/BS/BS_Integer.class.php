<?php $CVS_Version = '$Revision$ ; $Date$';
require_once ('BS_SimpleDataType.class.php');


/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 *  лас дл€ хранени€ целого числа.
 * @version 1.0
 * @created 11-окт-2004 13:55:47
 */
class BS_Integer extends BS_SimpleDataType
{

	/**
	 * ћетод дл€ проверки корреткности устанавливаемого значени€
	 * @param val
	 * 
	 */
	protected function check($val)
	{
		//ѕровер€т полученно езанение на
		//соотвествие типу. елси же тип не
		//соотвесвует то генерит ошибку PHP.
		//
		//¬ данном случае проверка
		//осущуствл€етьс€ на то что бы это было
		//целое
		if ((int) $val == $val) return true;
		return false;
	}

}
?>