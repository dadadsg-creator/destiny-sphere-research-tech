<?php $CVS_Version = '$Revision$ ; $Date$';
require_once ('BS_AssertionException.class.php');


/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * 
 * Эксепшен генирируеться Assertion::preCondition();
 * @version 1.0
 * @created 24-сен-2004 12:36:55
 */

class BS_AssertionPreConditionException extends BS_AssertionException
{

	/**
	 * Конструтор класса
	 * @param message    Текст сообщения об ошибке.
	 * @param errorLevel    Уровень ошибки. Может принимать значением ERROR, WARNING,
	 * NOTISE. По умолчанию = ERROR
	 * @param traceBack    строка где ообнаруженна ошибка
	 * 
	 */
	public function __construct($message, $errorLevel, $traceBack)
	{
		assert ( !empty ( $message ) );
		parent::__construct($message, $errorLevel, $traceBack);
	}

}
?>