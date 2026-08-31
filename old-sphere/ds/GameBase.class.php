<?PHP

/** 
 * Базовый класс, для всех классов игры.
 * Содержит основные базовые методы и атрибуты
 */

class GameBase {

	/** Текст сообщения генерируемы методом invariant() */
	var $invarianErrorMsg = "ERROR! \n Type: INVARIANT \n Class: #CLASS# File: #FILE# \n Line: #LINE# \n Code: #PREFIX##CODE#";
	
	/** ID созданого обекта. */
	var $objectId = '';
	
	/** Коснтуртор класса */
	function GameBase (){
		$this->setObjectId();
		return true;
	}
	
	/**
	 * метод при создани еобъекта назначает ему ID уникальный
	 * удобно исопльзовать для логирования когда создаеться несколько экземпляров одног
	 * объекта.
	 */
	function setObjectId(){
		$this->objectId = $this->getmicrotime();
	}
	
	/**
	 * Время с микросекундами таймстемнп
	 */
	function getmicrotime() { 
    	list($usec, $sec) = explode(" ", microtime()); 
    	return ((float)$usec + (float)$sec); 
	} 
	
	/**
	 * Формируем текст сообщения для invariant()
	 * имя класса поределяет сама.
	 *
	 * Параметры:
	 * $invarFile - ИМя файла в котором находиться invariant нашедший ошибку
	 * $file - Имя файла в котором вызвали invariant
	 * $line - Строка в которой вызывался invariant
	 * $code - Код ошибки Invariant
	 */
	function invariantError($invarFile, $file, $line, $code){
	
		assert ( '!empty ( $invarFile )' );
		assert ( '!empty ( $file )' );
		assert ( '!empty ( $line )' );
		assert ( '$line > 0' );
		assert ( '!empty ( $code )' );
		assert ( '!empty ( $this->invarianErrorMsg )' );
		
		$class = get_class ($this);
		assert ( '!empty ( $class )' );
		
		$from = strrpos ( $invarFile, "/" ) + 1;
		$to = strpos ( $invarFile, '.' );
		assert ( '$from < $to');
		$length = $to - $from;
		assert ( '$length > 0');
		$prefix = substr ($invarFile,  $from,  $length);
		
		$file = substr ($file, strrpos ( $file, "/" )+1);
		
		$out = $this->invarianErrorMsg;
		$out = str_replace("#CLASS#", $class, $out);
		$out = str_replace("#FILE#", $file, $out);
		$out = str_replace("#LINE#", $line, $out);
		$out = str_replace("#CODE#", $code, $out);
		$out = str_replace("#PREFIX#", $prefix, $out);
		
		assert ( '!empty ( $out )' );
		return $out;
	}//END OF METHOD setInvariantErrorMsg()
	
	/**
	 * Мтод для вывода ошибки в клинта.
	 * Использует:
	 * $GLOBALS['oh'] - экземпляр OutputHandler
	 * Параметры: 
	 */
	function outError ( $msg ){
//		assert ( !empty ( $GLOBALS['oh'] ) );
//		assert ( is_object ( $GLOBALS['oh'] ) );
//		assert ( is_a ( $GLOBALS['oh'], 'OutputHandler' ) );
		
		echo '&xmlerror=<error y="0">!!!!!!!!!!!!!!!!!1'.$msg.'</error>';
		flush();
//		$GLOBALS['oh']->addString( '<error y="0">'.$msg.'</error>', 'xmlerror');
//		$GLOBALS['oh']->flushAll();
	}//END OF METHOD outError ( $msg )
}//END OF CLASS
?>
