<?php $CVS_Version = '$Revision$ ; $Date$';


/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Статический класс для обработки ошибок
 * @version 1.0
 * @created 24-сен-2004 12:36:56
 */
require_once ('BS_Config.class.php');

require_once ('BS_Exception.class.php');
require_once ('BS_AssertionPostConditionException.class.php');
require_once ('BS_AssertionPreConditionException.class.php');
require_once ('BS_AssertionAssertException.class.php');


class BS_Error
{

	/**
	 * Массив с допустимыми значениями уровня  ошибок.
	 * Используеться для проверок параметров методов.
	 */
	private static $allowErrorLevels = array ( 'ERROR', 'WARNING' );
	/**
	 * Дефолтовоезначение отката по backTrace для оперделния мета откуда была
	 * определена ошибка
	 */
	private static $defaultTraceBack = 1;
	/**
	 * Эксепшен который кидаеться по умолчанию.
	 */
	private static $defaultExeption = 'BS_Exception';


	/**
	 * Метод для отправки на обраоботку ошибкиуровня ERROR
	 * @param msg    Текст сообщения об ошибке.
	 * @param exeption    имя эксепшена для отправки Необзеательный параметр
	 * @param traceBack    На сколько откатиться в трасе для определния того мест агде
	 * определнна ошибка. Необезательный параметр.
	 * 
	 */
	public static function castError($msg, $exception, $traceBack)
	{
		if (empty($exception)){$exception=BS_Error::$defaultExeption;}
		if (empty($traceBack)){$traceBack=BS_Error::$defaultTraceBack;}
		
		if (in_array('ERROR',BS_Error::$allowErrorLevels))
		{
			BS_Error::cast ( $msg, $exception,$traceBack, 'ERROR');
		}
	}

	/**
	 * Оптравляем на обработку ошибку уровня WARNING
	 * @param msg    Строка с ошибкой
	 * @param exception    Имя бросаемого эксепшена
	 * @param traceBack    На сколько шагов откатываемся джля определния реальной
	 * строки ошибки
	 * 
	 */
	public static function castWarning($msg, $exception, $traceBack)
	{

		if (empty($exception)){$exception=BS_Error::$defaultExeption;}
		if (empty($traceBack)){$traceBack=BS_Error::$defaultTraceBack;}

		if (in_array('WARNING',BS_Error::$allowErrorLevels))
		{
			BS_Error::cast ( $msg, $exception,$traceBack, 'WARNING');
		}
	}

	/**
	 * castWarning и casrError фактичски просто вызывают этот метод. Сделано для
	 * избавления от лишних параметров.
	 * @param msg    Сообщение об ошибке
	 * @param exception    имя эксепшена
	 * @param traceBack    На сколько откатываемся в trace
	 * @param errorLevel    уровень ошибки
	 * 
	 */
	private static function cast($msg, $exception, $traceBack, $errorLevel)
	{
		if ($errorLevel == 'ERROR')
		{
			throw new $exception($msg,$errorLevel,$traceBack);
		}
		else 
		if ($errorLevel == 'WARNING')
		{
			throw new $exception($msg,$errorLevel,$traceBack);
		}
	}

}
?>