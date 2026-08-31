<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_CompareDataConnection			//описание класса для работой с базами
{	
	private static $Instance; //признак существования объекта для работы с базой данных
	private $ListofBases;//список баз и параметров подключения к ним
	private $ListofIDConnection;//спискок подключенных баз и ID подключений

	function __construct()
	{		
		assert(array_key_exists('List_of_Bases', $GLOBALS));
		assert(isset($GLOBALS['List_of_Bases']));
		assert(is_array($GLOBALS['List_of_Bases']));
		assert((count($GLOBALS['List_of_Bases'])>0));
		
		$this->ListofBases=$GLOBALS['List_of_Bases'];
	}
	
	private function GetIDConnection ($BaseName)//Получаем ID соединения с базой по имени базы
	{
		assert(is_string($BaseName) && ($BaseName!=''));		
		
		$IDConnection=NULL;		
										//Проверили, устанавливали с этой базой соединение ранее
		if (array_key_exists($BaseName, (array) $this->ListofIDConnection))
		{
			$IDConnection=$this->ListofIDConnection[$BaseName]; //берем номер соединения
		}			
								
		return $IDConnection;
	}
		
	private function Disconnect ($BaseName)//Разрываем соединение с конкретной базой
	{
		assert(is_string($BaseName) && ($BaseName!='')); 
		
		$IDConnection=self::GetIDConnection($BaseName); 
		
		if (!$IDConnection)
		{
			echo "С базой $BaseName соединения нет";
			exit;
		}
		
		$ResultOperation=pg_close($IDConnection);
		
		if (!$ResultOperation)
		{
			echo "Не могу правильно отключиться от базы $BaseName";			
			exit;
		}
		
		return $ResultOperation;
	}			
		
	public static function  SingleTone() //Создаем объект для работы с базой
	{
		if (!(self::$Instance instanceof SU_CompareDataConnection))
		{ 										//проверка единственности экземпляра объекта в системе 
			self::$Instance=new SU_CompareDataConnection;
		}
		
		return self::$Instance;  
	}
		
	private function Connect ($BaseName)//Пытаемся соединиться с базой по ее внешнему имени, возвращаем ID соединения с базой
	{
		assert(is_string($BaseName) && ($BaseName!='')); 

		$ConnectResult=self::GetIDConnection($BaseName); //проверяем наличие соединения с базой
		
		if (!$ConnectResult)//соединения нет, пытаемся соединиться с базой
		{
			if (array_key_exists($BaseName, $this->ListofBases))
			{
				$Host=$this->ListofBases[$BaseName]['host'];
				$User=$this->ListofBases[$BaseName]['user'];
				$Dbname=$this->ListofBases[$BaseName]['dbname'];
				$Password=$this->ListofBases[$BaseName]['password'];
														//соединяемся
				$ConnectResult=pg_connect("host=$Host user=$User dbname=$Dbname password=$Password");
				
				if (!$ConnectResult) //не смогли подключиться к базе
				{						
					echo "Не могу подключиться к базе $BaseName";					
					exit;
				}
				
				$this->ListofIDConnection[$BaseName]=$ConnectResult;
			}
			else
			{
				echo "Не нашел в списке баз и параметров подключения к ним базу $BaseName";
				exit;				
			}			
		}		
		
		return $ConnectResult;		//Возвращаем ID соединения
	}
		
	public function Exec ($SQLquery, $BaseName) //Выполняется запрос к базе, который не требует ответа
	{	
		assert(is_string($BaseName) && ($BaseName!='') && is_string($SQLquery) && ($SQLquery!='') && (strlen($SQLquery)>3));
		$BaseConnectionID=self::Connect($BaseName); //Проверяем соединение с базой
		if (!$BaseConnectionID) 
		{
			echo "Не могу подключиться к базе $BaseName для выполнения запроса";
			exit;			
		}
								//Выполняем запрос к базе
		$RequestResult=pg_query($BaseConnectionID, $SQLquery);
		
		if(!$RequestResult)     //Выполнить запрос не удалось
		{
			echo "Выполнить запрос($SQLquery) к базе $BaseName не удалось: <br> $ErrorName";
			exit;			
		}
		
		return TRUE;
	}
			
	public function Get ($SQLquery, $BaseName) //Выполняем запрос к базе, в результате которого будет ответ
	{											
		assert(is_string($BaseName) && ($BaseName!='') && is_string($SQLquery) && ($SQLquery!='')&& (strlen($SQLquery)>3));
		
		$BaseConnectionID=self::Connect($BaseName); //Проверяем соединение с базой, создаем его в случае отсутствия
		
		if (!$BaseConnectionID) //Нет соединения с базой
		{
			echo "Не могу подключиться к базе $BaseName для выполнения запроса";	
			exit;		
		}
		
		$RequestResult=pg_query($BaseConnectionID, $SQLquery);
		
		
		if((!$RequestResult) or (!(is_resource($RequestResult))))
		{
			echo "Выполнить запрос ($SQLquery) к базе $BaseName не удалось:'(";	
			exit;		
		}
		
		//Преобразуем ответ базы в массив
		$ResultArray=pg_fetch_all($RequestResult);
		
		return $ResultArray;
	}
	
	public function PgQuery ($SQLquery, $BaseName) //Выполняем запрос к базе, в результатом которого будет id коннекта 
	{											
		assert(is_string($BaseName) && ($BaseName!='') && is_string($SQLquery) && ($SQLquery!='') && (strlen($SQLquery)>3));
		$BaseConnectionID=self::Connect($BaseName); //Проверяем соединение с базой
		if (!$BaseConnectionID) 
		{
			Errors::ErrorMessage("Не могу подключиться к базе $BaseName для выполнения запроса");			
		}
								//Выполняем запрос к базе
		$RequestResult=pg_query($BaseConnectionID, $SQLquery);
		
		if(!$RequestResult)     //Выполнить запрос не удалось
		{
			echo "Выполнить запрос($SQLquery) к базе $BaseName не удалось: <br> $ErrorName";	
			exit;		
		}
		
		return $RequestResult;
	}
	
	public function ExecWithErrorReporting($SQL, $BaseName) //Выполняется запрос к базе, который не требует ответа
	{	
		assert(is_string($BaseName) && ($BaseName!='') && is_string($SQL) && ($SQL!='') && (strlen($SQL)>3));
		$BaseConnectionID=self::Connect($BaseName); //Проверяем соединение с базой
		if (!$BaseConnectionID) 
		{
			echo "Не могу подключиться к базе $BaseName для выполнения запроса";			
			exit;
		}

		$SQLQuery = "BEGIN;".$SQL;
		
		
		//Выполняем запрос к базе
		$RequestResult=pg_query($BaseConnectionID, $SQLQuery);
		
		if(!$RequestResult)     //Выполнить запрос не удалось
		{
			$IDConnection=self::GetIDConnection($BaseName); 
			$ErrorName = pg_last_error($IDConnection);
			pg_query($BaseConnectionID, "ROLLBACK;");
			
			echo "<br><font color=blue>$SQL</font><br>
								 <font color=red>$ErrorName</font>";
			exit;
		}
		else{
			pg_query($BaseConnectionID, "COMMIT;");
		}
		return TRUE;
	}	
	
	function __destruct()
	{						
		foreach ((array) $this->ListofIDConnection as $Key=>$Value)
		{
			self::Disconnect ($Key);
		}
	}
}
?>