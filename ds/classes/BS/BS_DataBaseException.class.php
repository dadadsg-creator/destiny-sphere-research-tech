<?php $CVS_Version = '$Revision$ ; $Date$';
require_once ('BS_Exception.class.php');


/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Эксепшен швыряеться классом BS_DataBase в случае обнаружения проблем
 * @version 1.0
 * @created 24-сен-2004 12:36:56
 */
class BS_DataBaseException extends BS_Exception
{

	/**
	 * Конструтор класса
	 * @param message    Текст сообщения об ошибке.
	 * @param errorLevel    Уровень ошибки. Может принимать значением ERROR, WARNING,
	 * NOTISE. По умолчанию = ERROR
	 * @param traceBack    На сколько откат по TraceBack где можнообнаружить оишибку
	 * 
	 */

	public function __construct($message, $errorLevel, $traceBack)
	{

		assert ( !empty ( $message ) );
		parent::__construct($message, $errorLevel, $traceBack);
	}

}
?>