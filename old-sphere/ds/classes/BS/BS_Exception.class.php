<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Базовый класс  нашего эксепшена.
 * @version 1.0
 * @created 24-сен-2004 12:36:56
 */
class BS_Exception extends Exception
{

	/**
	 * Массив с данными debug_backtrace() который был передан эксепшену.
	 */
	private $backTrace = array();
	/**
	 * Строка с уровнем ошики. Модет принимать три значения ERROR, WARNING, NOTICE
	 */
	private $errorLevel;
	/**
	 * Массив с допутимыми знченими errorLevel
	 */
	private static $allowErrorLevel = array ( 'ERROR', 'WARNING' );
	/**
	 * Содержит значение ошщибки по умолчанию 'ERROR'
	 */
	private static $defaultErrorLevel='ERROR';
	/**
	 * Метод где обнаруженна ошибка ClassName::MethodName
	 */
	private $method;


	/**
	 * Конструтор класса
	 * @param message    Текст сообщения об ошибке.
	 * @param errorLevel    Уровень ошибки. Может принимать значением ERROR, WARNING,
	 * NOTICE. По умолчанию = ERROR
	 * @param traceBack    На сколько откат по TraceBack где можнообнаружить ошибку
	 * 
	 */
	public function __construct($message, $errorLevel, $traceBack)
	{
		assert(!empty($message));
		parent::__construct($message);

		if (empty($errorLevel))
		{
			$this->errorLevel = BS_Exception::$defaultErrorLevel;
		}

		else
		{
			assert(in_array($errorLevel,BS_Exception::$allowErrorLevel));
			$this->errorLevel=$errorLevel;
		}

		$this->backTrace = debug_backtrace();

		$this->method=$this->backTrace[$traceBack]['class']."::".$this->backTrace[$traceBack]['function'];

	}

	/**
	 * Возвращяет значение $this->backTrace
	 */
	public function getBackTrace()
	{
		return $this->backTrace;
	}

	public function getMethod()
	{
		return $this->method;
	}

}

?>