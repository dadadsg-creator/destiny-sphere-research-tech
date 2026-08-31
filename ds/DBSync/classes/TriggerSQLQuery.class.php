<?php

include_once ("classes/Errors.class.php");
include_once ("classes/TableSQLQuery.class.php");
					//класс получени€ параметров триггеров
class TriggerSQLQuery
{				//преобразование ассоциативного многомерного массива ответа базы данных в обычный массив
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
				//получаем имена триггеров дл€ таблицы в базе
	public function GetTableTriggerNameList($DBWorkObject, $TableName, $BaseName)
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($TableName) && ($TableName!='')										
											&& ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
		
		$TableID=TableSQLQuery::GetIDTable ($DBWorkObject, $TableName, $BaseName);		
		if (!$TableID)
		{
			Errors::ErrorMessage("Ќе могу получить ID таблицы с именем $TableName при определении списка имен триггеров дл€ нее");
		}
		
		$SQLQuery="select tgname from pg_trigger where tgrelid='$TableID' and tgname like 'ds_%';";
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);
		
		if (!$ResultQArray)
		{
			return NULL;			
		}
		else
		{
			$ResultArray=self::ConvertQueryResultArray($ResultQArray, "tgname"); 
				
			if (!$ResultArray)
			{
				Errors::ErrorMessage("Ќе смог получить списка имен триггеров таблицы $TableName");
			}
		
			return $ResultArray;	
		}
	}
				//получаем текстовое определение триггера
	public function GetTriggerDefinitionStr($DBWorkObject, $TriggerName, $TableName, $BaseName)
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($TableName) && ($TableName!='')										
			   && is_string($TriggerName) && ($TriggerName!='')							
			   && ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
	
		$TableID=TableSQLQuery::GetIDTable ($DBWorkObject, $TableName, $BaseName);		
		if (!$TableID)
		{
			Errors::ErrorMessage("Ќе могу получить ID таблицы $TableName при запросе текстового оперделени€ триггера $TriggerName");
		}
		
		$SQLQuery="select pg_get_triggerdef (oid)
				   from pg_trigger
				   where tgrelid='$TableID' and tgname='$TriggerName';";		
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);		
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Ќе удалось выполнить запрос на получение текстового определени€ триггера $TriggerName таблицы $TableName");			
		}
		
		$ResultArray=self::ConvertQueryResultArray($ResultQArray, "pg_get_triggerdef");
		if (count($ResultArray)!=1)
		{
			Errors::ErrorMessage("Ќе смог получить текстового определени€ триггера $TriggerName таблицы $TableName");			
		}
		
		if (($ResultArray[0]==NULL) or ($ResultArray[0]==''))
		{
			Errors::ErrorMessage("ѕолучено недопустимое значение текстового определени€ триггера $TriggerName таблицы $TableName");
		}
		
		return $ResultArray[0];
	}	
}

?>