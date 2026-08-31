<?php $CVS_Version = '$Revision$ ; $Date$';
require_once ('BS_DataBaseException.class.php');
require_once ('BS_Base.class.php');


/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * БАзовый класс для работы с БД.
 * 
 * Реализует простейшие действия с БД.
 * @version 1.0
 * @created 24-сен-2004 12:36:56
 */
class BS_DataBase extends BS_Base
{

	/**
	 * ID соединения с БД
	 */
	private $conn;
	/**
	 * Последний исполнений SQL запрос
	 */
	private $sql;

	/**
	 * Конструктор класса. Осуществляет подключение к БД
	 * @param host    Хост
	 * @param user    Имя юзера
	 * @param password    Пароль
	 * @param dbName    Имя базы
	 * 
	 */
	public function __construct($host, $user, $password, $dbName)
	{
		//Подключаеться к БД
		$this->conn=@pg_connect ("host=".$host.
					" dbname=".$dbname.
					" user=".$user.
					" password=".$password);
		if (! $this->conn) 
		{
			throw new BS_DataBaseException("Can't connect to dBase.",'ERROR');
		}
	}

	/**
	 * Метод проверки целостности класса
	 */
	function invariant()
	{
	}

	/**
	 * Выполняет SQL запрос любой
	 * @param sql    SQL для исполнения
	 * 
	 */
	public function exec($sql)
	{
		//ВЫполняет SQL запрос любой, в случае
		//возникновения ошибок швыряет эксепшен с
		//текстом ошибки.
		$res=@pg_query($this->conn, $sql);
		if (!$res)
		{
			throw new BS_DataBaseException("Can't execute query.",'ERROR');
		}
		$this->sql=$sql;
	}

	/**
	 * Исполняет SELECT запрос и результат возвращает в виде массива двумерного
	 * @param sql    Запрсо SQL на исполнение
	 * 
	 */
	public function getArray($sql)
	{
		$res=@pg_query($this->conn, $sql);
		if (!$res)
		{
			throw new BS_DataBaseException("Can't execute query.",'ERROR');
		}
		$this->sql=$sql;
		if (pg_num_rows ($res)>0) 
		{			
			$result = pg_fetch_all ($res);		
		}
		return $result;
	}

	/**
	 * Деструктор. Убивает соединение с БД
	 */
	public function __destruct()
	{
		if(!pg_close($this->conn))
		{
			throw new BS_DataBaseException("Can't disconnect from dBase.",'WARNING');
		}
	}

}
?>