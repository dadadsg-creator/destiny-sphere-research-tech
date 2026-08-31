<?php $CVS_Version = '$Revision$ ; $Date$';
require_once ('BS_Base.class.php');
require_once ('BS_Config.class.php');
require_once ('BS_Assertion.class.php');

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Базовый класс для логирования.
 * 
 * В случае обнаруджения ошибок генерит ошибьку пхп уровня ворнинг.
 * @version 1.0
 * @created 24-сен-2004 12:36:57
 */
class BS_Logger extends BS_Base
{

	/**
	 * Максимальный допустимый размер файла лога в байтах
	 */
	private $maxSize;
	/**
	 * В гачало или в конец йайла записывается строка
	 */
	private $writeDirection = 'END';
	/**
	 * ИСпользуеться для проверки корректности writeDirection атрибута
	 */
	private static $writeDirectionType = array ( 'BEGIN', 'END' );
	/**
	 * Определяет будет ли лок файла при записи или нет.
	 */
	private $withLocking = false;
	/**
	 * Путь до логов
	 */
	private $path;
	/**
	 * Имя лог файла который создаем
	 */
	private $file;
	/**
	 * Указательна открытый файл
	 */
	private $pointer;
	/**
	 * Определяет поведение при переполнение файла логов. DELETE или STOP
	 */
	private $maxSizeBehavior = 'STOP';
	/**
	 * РСпользуеться для проверки корректнности атритуа maxSizeBehavior
	 */
	private static $maxSizeBehaviorType = array ( 'DELETE', 'STOP' );

	/**
	 * Контсруктор класса
	 * @param file    имя файла для лога
	 * @param direction    Направление записи
	 * @param locking    необходим ли локинг
	 * 
	 */
	public function __construct($file, $direction = 'END', $locking = false)
	{
		//Устанавливает требуемые атрибуты.
		//Создает файл слогом елси его нет, а
		//если есть то проверяет его на
		//доступность для записи.
		//
		//Параметры  устанавливаютсья следующим
		//образхом:
		//
		//maxSize - из конфига.
		//path -  из конфига.

		$this->file=$file;
		$this->writeDirection=$direction;
		$this->withLocking=$locking;
		$this->maxSize=BS_Config::get('LOG','MAX_SIZE');
		$this->maxReadSize=BS_Config::get('LOG','MAX_READ_SIZE');
		$this->path=BS_Config::get('LOG','PATH');

		if(file_exists($this->path.$this->file))
		{
			try
			{
				BS_Assertion::preCondition(is_writable($this->path.$this->file),'ERROR');
			}
			catch(Exception $e)
			{
				echo($e->getMethod());
			}	

			if (filesize($this->path.$this->file)>$this->maxSize)
			{	
				if ($this->maxSizeBehavior=='DELETE')
				{
					try
					{
						BS_Assertion::preCondition(unlink($this->path.$this->file),'ERROR');
					}
					catch(Exception $e)
					{
						echo($e->getMethod());
					}	
				}
				else if ($this->maxSizeBehavior=='STOP')
				{
					return true;
				}
			}
			
		}
		$this->pointer=fopen($this->path.$this->file,'a+');


		try
		{
			BS_Assertion::preCondition($this->pointer,'ERROR');
		}
		catch(Exception $e)
		{
			echo($e->getMethod());
		}	
	}


	/**
	 * метод для записи в лог файл
	 * @param str    Строка в лог
	 * 
	 */
	public function log($str)
	{
		//
		//Записывает в лог файл строку
		//переданнцую.
		//
		//Поддерживает неие улсви шаблонизации:
		//
		//1. {DATE} - Заменяет на датуц формата
		//YYYY-MM-DD
		//2. {DATE_TIME} - Заменет на дату
		//формата YYYY-MM-DD hh:mm:ss
		//
		//В зависимости от устанвленных атрибутов
		//записывает или в начало или в конец
		//файла.
		//
		//В зависимости от атрибутов лочит файл
		//на вермя записи.
		//
		//
		$this->writeEnd($this->prepare($str));
	}

	/**
	 * Метод проверки целостности класса
	 */
	function invariant()
	{
	}

	/**
	 * Устанавливает значение атрибуьа maxSize
	 * @param maxSize
	 * 
	 */
	public function setMaxSize($maxSize)
	{
		$this->maxSize=$maxSize;
	}

	/**
	 * Устанавыливает значение атрибута writeDirection
	 * @param writeDirection
	 * 
	 */
	public function setWriteDirection($writeDirection)
	{
		$this->writeDirection=$writeDirection;
	}

	/**
	 * Устанавливает атриьбут maxSizeBehavior
	 * @param maxSizeBahavior
	 * 
	 */
	public function setMaxSizeBehavior($maxSizeBahavior)
	{
		if (in_array($maxSizeBahavior,$maxSizeBehaviorType))
		{
			$this->maxSizeBahavior=$maxSizeBahavior;
		}
	}

	/**
	 * метод для -хписи в конец файла
	 * @param str
	 * 
	 */
	private function writeBegin($str)
	{
		$tmp=fread($this->pointer,filesize($this->path.$this->file));
		
                try
		{
			BS_Assertion::assert(fwrite($this->pointer, $str.$tmp),"ERROR");
		}
		catch(Exception $e)
		{
			echo($e->getMethod());
		}
	}

	/**
	 * метод для запис в коней файла
	 * @param str
	 * 
	 */
	private function writeEnd($str)
	{
		try
		{
			BS_Assertion::assert(fwrite($this->pointer, $str),"ERROR");
		}
		catch(Exception $e)
		{
			echo($e->getMethod());
		}
	}

	/**
	 * Методля подготовки с роки к пуьбликации. ЗАмена якорей знаениями и п.р.
	 * @param str
	 * 
	 */
	private function prepare($str)
	{
		$str=preg_replace("|{DATE_TIME}|",date('d.m.Y h:m:s'),$str);
		$str=preg_replace("|{DATE}|",date('d.m.Y'),$str);
		return $str;
	}

	/**
	 * Деструктор класса
	 */
	public function __destruct()
	{
		//
		//Закрывает файл, котрытый в конструкторе
		try
		{
			BS_Assertion::postCondition(fclose($this->pointer),'ERROR');
		}
		catch(Exception $e)
		{
			echo($e->getMethod());
		}
	}

}
?>