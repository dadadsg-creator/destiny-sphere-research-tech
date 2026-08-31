<?php

include_once ("classes/Errors.class.php");

				//класс получения параметров таблиц базы
class TableSQLQuery
{						//преобразование ассоциативного многомерного массива ответа базы данных в обычный массив
	public function ConvertQueryResultArray ($QueryResultArray, $FieldName)
	{
		assert(is_array($QueryResultArray) && is_string($FieldName));
		
		$MaxElementNumber=count($QueryResultArray);
		
		if ($MaxElementNumber==0)
		{
			$ResultArray=Null;
		}
		else
		{
			for ($Index=0; $Index<$MaxElementNumber; $Index++)
			{
				$ResultArray[$Index]=$QueryResultArray[$Index]["$FieldName"];
			}
		}
					
  		return $ResultArray;
	}
						//получаем имена таблиц пользователя в базе
	public function GetDS_TablesNameList ($DBWorkObject, $BaseName)
	{									
		assert(is_string($BaseName) && ($BaseName!='') && ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
		
		$SQLQuery="select tablename from pg_tables where tablename like 'ds_%';";										
		$TablesNameArray=$DBWorkObject->Get($SQLQuery,$BaseName);
		
		if (!$TablesNameArray)
		{
			$ResultTablesNameList=NULL;			
		}
		else
		{									
			$ResultTablesNameList=self::ConvertQueryResultArray($TablesNameArray, "tablename");
		}									
		return $ResultTablesNameList;
	}
						//функция получает ID таблицы по ее имени	
	public function GetIDTable ($DBWorkObject, $TableName, $BaseName)//Polu4enie ID tablicy po ee imeni
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($TableName) && ($TableName!='')
											&& ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
		
		$SQLQuery="select oid from pg_class where relname='$TableName' and relkind='r';";		
		$ResultArray=$DBWorkObject->Get($SQLQuery,$BaseName);		
		if (!$ResultArray)
		{
			Errors::ErrorMessage("Не удалось выполнить запрос на получение ID таблицы $TableName");			
		}
		
		$IDTableArray=self::ConvertQueryResultArray($ResultArray, "oid");
				
		if (count($IDTableArray)!=1)
		{
			Errors::ErrorMessage("Не смог найти ID таблицы $TableName");
			
		}

		if (($IDTableArray[0]==NULL) or ($IDTableArray[0]==''))
		{
			Errors::ErrorMessage("Получено недопустимое значение ID таблицы $TableName");
		}
			
		return $IDTableArray[0];		 
	}
						//функция проверяет, есть ли в таблице OID столбец
	public function HasTableOIDColumn ($DBWorkObject, $TableName, $BaseName) 
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($TableName) && ($TableName!='')											
											&& ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
		
		$TableID=self::GetIDTable ($DBWorkObject, $TableName, $BaseName);		
		if (!$TableID)
		{
			Errors::ErrorMessage("Не могу получить ID таблицы $TableName (узнавали, есть ли OID столбец в таблице)");
		}
		
		$SQLQuery="select relhasoids from pg_class where oid='$TableID';";		
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);		
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Не удалось выполнить запрос на получение признака наличия OID столбца в таблице $TableName");			
		}
		
		$ResultArray=self::ConvertQueryResultArray($ResultQArray, "relhasoids");
		
		if (count($ResultArray)!=1)
		{
			Errors::ErrorMessage("Не смог получить признак наличия OID столбца в таблице $TableName");		
		}
		if (($ResultArray[0]=='') or ($ResultArray[0]==NULL))
		{
			Errors::ErrorMessage("Получен недопустимое значение признака наличия OID столбца в таблице $TableName");
		}
		
		switch ($ResultArray[0])
		{
			case 'f':
				return FALSE;
				break;
			case 't':
				return TRUE;
				break;
			default:
				Errors::ErrorMessage("Получено недопустимое значение ($ResultArray[0]) признака наличия OID столбца в таблице $TableName");
		}
	}
}
?>
