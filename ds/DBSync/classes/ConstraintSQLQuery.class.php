<?php

include_once ("classes/TableSQLQuery.class.php");
include_once ("classes/Errors.class.php");

				//таблица получения параметров ограничений
class ConstraintSQLQuery
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
				//получение списка имен ограничений для таблицы базы
	public function GetTableConstraintList ($DBWorkObject, $TableName, $BaseName, $ConstraintType)
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($TableName) && ($TableName!='')											
				&& is_string($ConstraintType)
				&& (($ConstraintType=='u') or ($ConstraintType=='p') or ($ConstraintType=='c') or ($ConstraintType=='f'))
				&& ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
	
		$TableID=TableSQLQuery::GetIDTable ($DBWorkObject, $TableName, $BaseName);		
		if (!$TableID)
		{
			Errors::ErrorMessage("Не могу получить ID для таблицы $TableName при получении списка ее ограничений");
		}
		
		$SQLQuery="select conname
				   from pg_constraint 
				   where conrelid='$TableID' and contype='$ConstraintType';";		
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);		
		if ($ResultQArray)
		{
			$ResultArray=self::ConvertQueryResultArray($ResultQArray, "conname");
			if (!$ResultArray)
			{
				Errors::ErrorMessage("Не смог получить список ограничений типа $ConstraintType таблицы $TableName");			
			}
			return $ResultArray;			
		}
		else
		{
			return NULL;
		}
	}
				//получение тестового определения ограничения
	public function GetConstraintDefinitionStr($DBWorkObject, $ConstraintName, $TableName, $BaseName, $ConstraintType)
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($TableName) && ($TableName!='')											
											&& is_string($ConstraintName) && ($ConstraintName!='')
											&& is_string($ConstraintType)
											&& (($ConstraintType=='u') or ($ConstraintType=='p') or ($ConstraintType=='c') or ($ConstraintType=='f'))
											&& ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
	
		$TableID=TableSQLQuery::GetIDTable ($DBWorkObject, $TableName, $BaseName);		
		if (!$TableID)
		{
			Errors::ErrorMessage("Не могу получить ID для таблицы $TableName при получении текстового определения ее ограничения");
		}
		
		$SQLQuery="select pg_get_constraintdef (oid)
				   from pg_constraint
				   where conname='$ConstraintName' and conrelid='$TableID' and contype='$ConstraintType'";		
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);		
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Не удалось выполнить запрос на получение текстового определения ограничения $ConstraintName таблицы $TableName");			
		}
		
		$ResultArray=self::ConvertQueryResultArray($ResultQArray, "pg_get_constraintdef");
		if (count($ResultArray)!=1)
		{
			Errors::ErrorMessage("Не смог получить текстового определения ограничения $ConstraintName таблицы $TableName");			
		}
		
		if (($ResultArray[0]==NULL) or ($ResultArray[0])=='')
		{
			Errors::ErrorMessage("Получено недопустимое значение для текстового определения ограничения $ConstraintName таблицы $TableName");
		}
		
		return $ResultArray[0];
	}
}
?>