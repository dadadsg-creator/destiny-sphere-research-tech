<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Абстрактный класс простого типа данных.
 * @version 1.0
 * @created 11-окт-2004 13:55:48
 */
abstract class BS_SimpleDataType
{

	/**
	 * Атрибут сожержит значение данного.
	 */
	protected $val;

	/**
	 * Конструтор
	 * @param val    Значнеи начальное
	 * 
	 */
	public function __construct($val = NULL)
	{
		//Устанавливает значение по умолчанию ели
		//оно перданно.
		$this->val=$val;
	}

	/**
	 * Возвращает значение переменнотй
	 */
	public function get()
	{
		return $this->val;
	}

	/**
	 * Утснавливает значение переенной
	 */
	public function set($val)
	{
		if ($this->check($val))
		{
			$this->val=$val;
		}
		else
		{
			$this->error($val);
		}
	}

	/**
	 * Метод для проверки корреткности устанавливаемого значения
	 * @param val
	 * 
	 */



	abstract protected function check($val);
	//Метод для проверки корреткности
	//устанавливаемого значения в случае
	//обнаружения несоответсвия по типу
	//генериться ошибка ПХП c сообщением о
	//несоотвествие типа.

	protected function error($val)
	{
		//генерит ошибку
		trigger_error("Type missmatch:".get_class($this)." can't set ".$val, E_USER_ERROR);
	}

}
?>