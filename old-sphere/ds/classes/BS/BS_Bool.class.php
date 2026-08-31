<?php $CVS_Version = '$Revision$ ; $Date$';
require_once ('BS_SimpleDataType.class.php');


/**
 * 
 * $Author$
 * $Date$
 * $Revision$
 * 
 * 
 * Класс для хранения логического значения.
 * В отличие от отслаьных классов все передаваемые внутрь него зхначения он молча
 * и тупо проьбразовывает в логические.
 * @version 1.0
 * @created 11-окт-2004 13:55:46
 */
class BS_Bool extends BS_SimpleDataType
{

	/**
	 * Утснавливает значение переенной
	 */
	public function set($val)
	{
		settype ($val,'bool');
		$this->val=$val;
	}

	/**
	 * Метод для проверки корреткности устанавливаемого значения
	 * @param val
	 * 
	 */
	protected function check($val)
	{
		return true;
		//Метод для проверки корреткности
		//устанавливаемого значения в случае
		//обнаружения несоответсвия по типу
		//генериться ошибка ПХП c сообщением о
		//несоотвествие типа.

	}

}
?>