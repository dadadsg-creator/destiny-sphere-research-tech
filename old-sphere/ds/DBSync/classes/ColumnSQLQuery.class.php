<?php

require_once 'classes/TableSQLQuery.class.php';
require_once 'classes/Errors.class.php';

				//класс получения параметров столбцов таблицы базы
class ColumnSQLQuery
{ 				//преобразование ассоциативного многомерного массива ответа базы данных в обычный массив
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
				//функция для получения списка имен столбцов таблицы
	public function GetTableColumnNameList ($DBWorkObject, $TableName, $BaseName)
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($TableName) && ($TableName!='')
											&& ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
		
		$TableID=TableSQLQuery::GetIDTable ($DBWorkObject, $TableName, $BaseName);		
		if (!$TableID)
		{
			Errors::ErrorMessage("Не могу получить ID таблицы $TableName базы $BaseName при получении списка ее столбцов");
		}
		
		$SQLQuery="select attname from pg_attribute where attrelid='$TableID';";		
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);		
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Не удалось выполнить запрос на получение списка имен столбцов таблицы $TableName базы $BaseName");			
		}
		
		$TableColumnsList=self::ConvertQueryResultArray($ResultQArray, "attname");
		
		if (count($TableColumnsList)==0)
		{
			Errors::ErrorMessage("Не смог получить список имен столбцов таблицы $TableName базы $BaseName");			
		}
		
		$Index1=0;
		
		for ($Index=0; $Index<count($TableColumnsList); $Index++)
		{
			if (strpos ($TableColumnsList[$Index], '.')===FALSE)
			{
				if (($TableColumnsList[$Index]==NULL) or ($TableColumnsList[$Index]==''))
				{
					Errors::ErrorMessage("Получено недопустимое значение для имени столбца таблицы $TableName базы $BaseName");	
				}
				$ResultArray[$Index1]=$TableColumnsList[$Index];
				$Index1++;
			}
		}
		return $ResultArray;
	}
				//функция получения ID типа данных для столбца таблицы
	public function GetTableColumnTypeID($DBWorkObject, $ColumnName, $TableName, $BaseName)
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($TableName) && ($TableName!='')
											&& is_string($ColumnName) && ($ColumnName!='')
											&& ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
		
		$TableID=TableSQLQuery::GetIDTable ($DBWorkObject, $TableName, $BaseName);		
		if (!$TableID)
		{
			Errors::ErrorMessage("Не могу получить ID таблицы с именем $TableName базы $BaseName при определении ID типа данных в столбце");
		}
		
		$SQLQuery="select atttypid from pg_attribute where attrelid='$TableID' and attname='$ColumnName';";
		$ResultArray=$DBWorkObject->Get($SQLQuery,$BaseName);
		
		if (!$ResultArray)
		{
			Errors::ErrorMessage("Не удалось выполнить запрос на получение ID типа данных для столбца $ColumnName таблицы $TableName базы $BaseName");			
		}
		
		$TableColumnTypeIDArray=self::ConvertQueryResultArray($ResultArray, "atttypid"); 
				
		if (count($TableColumnTypeIDArray)!=1)
		{
			Errors::ErrorMessage("Не могу найти ID типа данных для столбца $ColumnName таблицы $TableName базы $BaseName");
		}
		
		if (($TableColumnTypeIDArray[0]==NULL) or ($TableColumnTypeIDArray[0]==''))
		{
			Errors::ErrorMessage("Получено недопустимое значение ID типа данных для столбца $ColumnName таблицы $TableName базы $BaseName");
		}
				
		return $TableColumnTypeIDArray[0];									
	}
				//функция получения имени типа данных для столбцы таблицы
	public function GetTableColumnTypeName($DBWorkObject, $ColumnName, $TableName, $BaseName)
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($TableName) && ($TableName!='')
											&& is_string($ColumnName) && ($ColumnName!='')
											&& ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
		
		$TableColumnTypeID=self::GetTableColumnTypeID($DBWorkObject, $ColumnName, $TableName, $BaseName);
		if (!$TableColumnTypeID)
		{
			Errors::ErrorMessage("Не могу получить ID типа данных столбца $ColumnName таблицы $TableName базы $BaseName при определении имени типа");
		}
		
		$SQLQuery="select typname from pg_type where oid='$TableColumnTypeID';";
		$ResultArray=$DBWorkObject->Get($SQLQuery,$BaseName);
		
		if (!$ResultArray)
		{
			Errors::ErrorMessage("Не удалось выполнить запрос на получение имени типа данных в столбце $ColumnName таблицы $TableName базы $BaseName");			
		}
		
		$TableColumnTypeNameArray=self::ConvertQueryResultArray($ResultArray, "typname");
		
		if (count($TableColumnTypeNameArray)!=1)
		{
			Errors::ErrorMessage("Не смог найти имя типа данных в столбце $ColumnName таблицы $TableName базы $BaseName");		
		}
		
		if (($TableColumnTypeNameArray[0]==NULL) or ($TableColumnTypeNameArray[0]==''))
		{
			Errors::ErrorMessage("Получено недопустимое значение имени типа данных для столбца $ColumnName таблицы $TableName базы $BaseName");
		}
		
		return $TableColumnTypeNameArray[0];
	}
				//функция возвращает TRUE, если столбец не может содержать NULL значения
	public function IsColumnNotNull($DBWorkObject, $ColumnName, $TableName, $BaseName)
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($TableName) && ($TableName!='')
											&& is_string($ColumnName) && ($ColumnName!='')
											&& ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
		
		$TableID=TableSQLQuery::GetIDTable ($DBWorkObject, $TableName, $BaseName);		
		if (!$TableID)
		{
			Errors::ErrorMessage("Не могу получить ID таблицы $TableName базы $BaseName при получении признака NOT NULL для столбца");
		}
		
		$SQLQuery="select attnotnull from pg_attribute where attrelid='$TableID' and attname='$ColumnName';";
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);		
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Не удалось выполнить запрос на получение признака NotNull для столбца $ColumnName таблицы $TableName базы $BaseName");			
		}
		
		$ResultArray=self::ConvertQueryResultArray($ResultQArray, "attnotnull");		
		if (count($ResultArray)!=1)
		{
			Errors::ErrorMessage("Не смог получить признак NotNull для столбца $ColumnName таблицы $TableName базы $BaseName");			
		}
		
		if (($ResultArray[0]==NULL) or ($ResultArray[0]==''))
		{
			Errors::ErrorMessage("Получено недопустимое значение признака NotNull для столбца $ColumnName таблицы $TableName базы $BaseName");
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
				Errors::ErrorMessage("Получено недопустимое значение ($ResultArray[0]) признака NotNull для столбца $ColumnName таблицы $TableName базы $BaseName");
		}
	}
				//функция возвращает TRUE, если у столбца есть значение по умолчанию
	public function HasColumnDefaultValue($DBWorkObject, $ColumnName, $TableName, $BaseName)
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($TableName) && ($TableName!='')
											&& is_string($ColumnName) && ($ColumnName!='')
											&& ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
											
		$TableID=TableSQLQuery::GetIDTable ($DBWorkObject, $TableName, $BaseName);		
		if (!$TableID)
		{
			Errors::ErrorMessage("Не могу получить ID таблицы $TableName при определении признака наличия у столбца $ColumnName значения по умолчанию  в базе $BaseName");
		}
		
		$SQLQuery="select atthasdef from pg_attribute where attrelid='$TableID' and attname='$ColumnName';";
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);		
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Не удалось выполнить запрос на получениепризнака наличия значения по умолчанию у столбца $ColumnName таблицы $TableName базы $BaseName");			
		}
		
		$ResultArray=self::ConvertQueryResultArray($ResultQArray, "atthasdef");		
		if (count($ResultArray)!=1)
		{
			Errors::ErrorMessage("Не смог получить признак наличия значения по умолчанию у столбца $ColumnName таблицы $TableName базы $BaseName");			
		}
		
		if (($ResultArray[0]==NULL) or ($ResultArray[0]==''))
		{
			Errors::ErrorMessage("Получено недопустимое значение признака наличия значения по умолчанию у столбца $ColumnName таблицы $TableName базы $BaseName");
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
				Errors::ErrorMessage("Получено недопустимое значение ($ResultArray[0]) признака наличия значения по умолчанию у столбца $ColumnName таблицы $TableName базы $BaseName");
		}		
	}
				//функция возвращает порядковый номер столбца в таблице по его имени (нумерация столбцов начинается с 1)
	public function GetNumberOfColumnFromHisName ($DBWorkObject, $ColumnName, $TableName, $BaseName)	
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($TableName) && ($TableName!='')
											&& is_string($ColumnName) && ($ColumnName!='')
											&& ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
		
	    $TableID=TableSQLQuery::GetIDTable ($DBWorkObject, $TableName, $BaseName);		
		if (!$TableID)
		{
			Errors::ErrorMessage("Не могу получить ID таблицы $TableName базы $BaseName при определении номера столбца таблицы по его имени");
		}
		
		$SQLQuery="select attnum from pg_attribute where attrelid='$TableID' and attname='$ColumnName';";
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);		
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Не удалось выполнить запрос на получение номера столбца $ColumnName таблицы $TableName базы $BaseName");			
		}
		
		$ResultArray=self::ConvertQueryResultArray($ResultQArray, "attnum");
		if (count($ResultArray)!=1)
		{
			Errors::ErrorMessage("Не смог получить номер столбца $ColumnName таблицы $TableName базы $BaseName");			
		}
		
		if (($ResultArray[0]==NULL) or ($ResultArray[0]==''))
		{
			Errors::ErrorMessage("Получено недопустимое значение номера столбца $ColumnName таблицы $TableName базы $BaseName");	
		}
		
		return $ResultArray[0];
	}
				//функция возвращает текстовое определение значения по умолчанию для столбца
	public function GetColumnDefaultValueSTR ($DBWorkObject, $ColumnName, $TableName, $BaseName)
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($TableName) && ($TableName!='')
											&& is_string($ColumnName) && ($ColumnName!='')
											&& ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
											
		$TableID=TableSQLQuery::GetIDTable ($DBWorkObject, $TableName, $BaseName);		
		if (!$TableID)
		{
			Errors::ErrorMessage("Не могу получить ID таблицы $TableName базы $BaseName при получении значения по умолчанию  в виде строки для столбца $ColumnName");
		}
		
		$ColumnNumber=self::GetNumberOfColumnFromHisName ($DBWorkObject, $ColumnName, $TableName, $BaseName);
		if(!$ColumnNumber)
		{
			Errors::ErrorMessage("Не могу получить ID столбца $ColumnName таблицы $TableName базы $BaseName при получении для него значения по умолчанию в виде строки");
		}
		
		$SQLQuery="select pg_get_expr(adbin, adrelid) from pg_attrdef where adrelid='$TableID' and adnum='$ColumnNumber';";
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);		
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Не удалось выполнить запрос на получение текстового отображения значения по умолчанию для столбца $ColumnName таблицы $TableName базы $BaseName");			
		}
		
		$ResultArray=self::ConvertQueryResultArray($ResultQArray, "pg_get_expr");
		if (count($ResultArray)!=1)
		{
			Errors::ErrorMessage("Не удалось получить текстовое отображение значения по умолчанию для столбца $ColumnName таблицы $TableName базы $BaseName");
		}
		
		if (($ResultArray[0]==NULL) or ($ResultArray[0]==''))
		{
			Errors::ErrorMessage("Получено недопустимое значение текстового отображения значения по умолчанию для столбца $ColumnName таблицы $TableName базы $BaseName");	
		}
		
		return $ResultArray[0];
	}
				//функция возвращает TRUE, если столбец является системным в таблице (его создавал не пользователь)
	public function IsColumnSystem ($DBWorkObject, $ColumnName, $TableName, $BaseName)
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($TableName) && ($TableName!='')
											&& is_string($ColumnName) && ($ColumnName!='')
											&& ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
		
		if (self::GetNumberOfColumnFromHisName ($DBWorkObject, $ColumnName, $TableName, $BaseName)>0)
		{
			return FALSE;			//используется правило, по которому столбцы пользователя нумеруются с 1, а системные имеют номер от -1
		}							//и меньше
		else
		{
			return TRUE;
		}
	}
}
?>
