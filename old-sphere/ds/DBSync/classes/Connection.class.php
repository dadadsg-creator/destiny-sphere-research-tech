<?php $CVS_Version = '$Revision: 1.1 $ ; $Date: 2007/06/18 08:18:57 $';

class Connection			//описание класса для работой с базами
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
			Errors::ErrorMessage("С базой $BaseName соединения нет");
		}
		
		$ResultOperation=pg_close($IDConnection);
		
		if (!$ResultOperation)
		{
			Errors::ErrorMessage("Не могу правильно отключиться от базы $BaseName");			
		}
		
		return $ResultOperation;
	}			
		
	public static function  SingleTone() //Создаем объект для работы с базой
	{
		if (!(self::$Instance instanceof Connection))
		{ 										//проверка единственности экземпляра объекта в системе 
			self::$Instance=new Connection;
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
					Errors::ErrorMessage("Не могу подключиться к базе $BaseName");					
				}
				
				$this->ListofIDConnection[$BaseName]=$ConnectResult;
			}
			else
			{
				Errors::ErrorMessage("Не нашел в списке баз и параметров подключения к ним базу $BaseName");				
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
			Errors::ErrorMessage("Не могу подключиться к базе $BaseName для выполнения запроса");			
		}
								//Выполняем запрос к базе
		$RequestResult=pg_query($BaseConnectionID, $SQLquery);
		
		if(!$RequestResult)     //Выполнить запрос не удалось
		{
			Errors::ErrorMessage("Выполнить запрос($SQLquery) к базе $BaseName не удалось: <br>
									$ErrorName");			
		}
		
		return TRUE;
	}
			
	public function Get ($SQLquery, $BaseName) //Выполняем запрос к базе, в результате которого будет ответ
	{											
		assert(is_string($BaseName) && ($BaseName!='') && is_string($SQLquery) && ($SQLquery!='')&& (strlen($SQLquery)>3));
		
		$BaseConnectionID=self::Connect($BaseName); //Проверяем соединение с базой, создаем его в случае отсутствия
		
		if (!$BaseConnectionID) //Нет соединения с базой
		{
			Errors::ErrorMessage("Не могу подключиться к базе $BaseName для выполнения запроса");			
		}
		
		$RequestResult=pg_query($BaseConnectionID, $SQLquery);
		
		if((!$RequestResult) or (!(is_resource($RequestResult))))
		{
			Errors::ErrorMessage("Выполнить запрос ($SQLquery) к базе $BaseName не удалось");			
		}
								//Преобразуем ответ базы в массив
		$ResultArray=pg_fetch_all($RequestResult);
		/*
		if (!$ResultArray)
		{
			Errors::ErrorMessage("Преобразовать ответ на запрос ($SQLquery) к базе $BaseName в массив не удалось");	
		}
		*/
		return $ResultArray;
	}
	
	public function ExecWithErrorReporting($SQL, $BaseName) //Выполняется запрос к базе, который не требует ответа
	{	
		assert(is_string($BaseName) && ($BaseName!='') && is_string($SQL) && ($SQL!='') && (strlen($SQL)>3));
		$BaseConnectionID=self::Connect($BaseName); //Проверяем соединение с базой
		if (!$BaseConnectionID) 
		{
			Errors::ErrorMessage("Не могу подключиться к базе $BaseName для выполнения запроса");			
		}

		$SQLQuery = "BEGIN;".$SQL;
		//Выполняем запрос к базе
		$RequestResult=pg_query($BaseConnectionID, $SQLQuery);
		
		if(!$RequestResult)     //Выполнить запрос не удалось
		{
			$IDConnection=self::GetIDConnection($BaseName); 
			$ErrorName = pg_last_error($IDConnection);
			pg_query($BaseConnectionID, "ROLLBACK;");
			Errors::ErrorMessage("<br><font color=blue>$SQL</font><br>
								 <font color=red>$ErrorName</font>");
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