<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2007/10/24 10:41:57 $';

include_once ("classes/TableSQLQuery.class.php");
include_once ("classes/Errors.class.php");

					//класс получения параметров индексов
class IndexSQLQuery
{					//преобразование ассоциативного многомерного массива ответа базы данных в обычный массив
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
					//получение списка имен индексов для таблицы базы
	public function GetTableIndexNameList ($DBWorkObject, $TableName, $BaseName)
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($TableName) && ($TableName!='')									
								    && ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
								    
		$TableID=TableSQLQuery::GetIDTable ($DBWorkObject, $TableName, $BaseName);		
		if (!$TableID)
		{
			Errors::ErrorMessage("Не могу получить ID таблицы $TableName при получении списка ее индексов");
		}
		
		$SQLQuery="select pg_class.relname
							from pg_class, pg_index 
							where pg_class.relkind='i' and pg_class.oid=pg_index.indexrelid and pg_index.indrelid='$TableID';";		
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);		
		if ($ResultQArray)
		{
			$ResultArray=self::ConvertQueryResultArray($ResultQArray, "relname");			
			if (count($ResultArray)==0)
			{
				Errors::ErrorMessage("Не смог получить список имен индексов таблицы $TableName");			
			}
			return $ResultArray;
		}
		else
		{
			return NULL;
		}		
	}
					
	
	//получение текстового определения индекса
	public function GetIndexDefinitionStr($DBWorkObject, $IndexName, $TableName, $BaseName)
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($TableName) && ($TableName!='')
									&& is_string($IndexName) && ($IndexName!='')
								    && ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
		
		$TableID=TableSQLQuery::GetIDTable ($DBWorkObject, $TableName, $BaseName);		
		if (!$TableID)
		{
			Errors::ErrorMessage("Не могу получить ID таблицы $TableName при получении текстового определения индекса $IndexName");
		}
		
		$SQLQuery="select pg_get_indexdef (pg_class.oid)
				   from pg_index, pg_class
				   where pg_class.relkind='i' and pg_class.relname='$IndexName' and pg_index.indexrelid=pg_class.oid and pg_index.indrelid='$TableID';";		
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);		
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Не удалось выполнить запрос на получение текстового определения индекса $IndexName таблицы $TableName");			
		}
		
		$ResultArray=self::ConvertQueryResultArray($ResultQArray, "pg_get_indexdef");

		if (count($ResultArray)!=1)
		{
			Errors::ErrorMessage("Не смог получить текстового определения индекса $IndexName таблицы $TableName");			
		}
		if (($ResultArray[0]==NULL) or ($ResultArray[0]==''))
		{
			Errors::ErrorMessage("Получено недопустимое значение текстового определения индекса $IndexName таблицы $TableName");
		}
		return $ResultArray[0];
	}	
					
	
		//получение текстового определения индекса и его куска
	public function GetIndexShortDefinitionStr($DBWorkObject, $IndexName, $TableName, $BaseName)
	{
		
		
		assert(is_string($BaseName) && ($BaseName!='') && is_string($TableName) && ($TableName!='')
									&& is_string($IndexName) && ($IndexName!='')
								    && ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));

		$TableID=TableSQLQuery::GetIDTable ($DBWorkObject, $TableName, $BaseName);		
		if (!$TableID)
		{
			Errors::ErrorMessage("Не могу получить ID таблицы $TableName при получении текстового определения индекса $IndexName");
		}
		
		$SQLQuery="select pg_get_indexdef (pg_class.oid)
				   from pg_index, pg_class
				   where pg_class.relkind='i' and pg_class.relname='$IndexName' and pg_index.indexrelid=pg_class.oid and pg_index.indrelid='$TableID';";		
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);	
		if(isset($ResultQArray[0]))
		{
				foreach ($ResultQArray[0] as $key=>$value)
			{
				$defini=stristr($value, ' on ');
				$allIndInf[0]=$value;
				$allIndInf[1]=$IndexName;

				$FinalFromSQL[$defini]=$allIndInf;
				//echo "$defini :<br> $value<br><br>";
			}
		}
		else 
		{
			Errors::ErrorMessage("Не удалось выполнить запрос на получение текстового определения индекса $IndexName таблицы $TableName");			
		}
		
		$ResultArray=self::ConvertQueryResultArray($ResultQArray, "pg_get_indexdef");

		if (count($ResultArray)!=1)
		{
			Errors::ErrorMessage("Не смог получить текстового определения индекса $IndexName таблицы $TableName");			
		}
		if (($ResultArray[0]==NULL) or ($ResultArray[0]==''))
		{
			Errors::ErrorMessage("Получено недопустимое значение текстового определения индекса $IndexName таблицы $TableName");
		}
		//print_r($FinalFromSQL);
		return $FinalFromSQL;
		
		//return $ResultArray[0];
	}	
	
	//получение имен полей, на которые ссылается индекс
	/*public function GetIndexFieldName($DBWorkObject, $IndexName, $TableName, $BaseName)
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($TableName) && ($TableName!='')
									&& is_string($IndexName) && ($IndexName!='')
								    && ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
		
		$TableID=TableSQLQuery::GetIDTable ($DBWorkObject, $TableName, $BaseName);		
		if (!$TableID)
		{
			Errors::ErrorMessage("Не могу получить ID таблицы $TableName при получении текстового определения индекса $IndexName");
		}
//echo "<br>index name is".$IndexName."<br>";
		//$SQLQuery="select * from pg_attribute where attrelid='$TableID'";
		$SQLQuery="select indexdef from pg_indexes ";
		
		/*$SQLQuery="select pg_get_indexdef (pg_class.oid)
				   from pg_index, pg_class
				   where pg_class.relkind='i' and pg_class.relname='$IndexName' and pg_index.indexrelid=pg_class.oid and pg_index.indrelid='$TableID';";		
		SELECT  pg_constraint.conname,
    					    pg_attribute.attname
						FROM
						    pg_constraint,
						    pg_attribute
						WHERE
						    pg_attribute.attnum IN (ARRAY_TO_STRING(pg_constraint.conkey, ','))
						    AND
						    pg_attribute.attrelid=pg_constraint.conrelid
						    AND
						    conrelid='$TableID'
						    AND contype='$ConstraintType';
		
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);	
		foreach ($ResultQArray as $key => $value)
		{
		 echo $key;
		 echo $value[indexdef];
		 echo '<br>ok<br>';
		} 
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Не удалось выполнить запрос на получение текстового определения индекса $IndexName таблицы $TableName");			
		}
		//$ResultArray=self::ConvertQueryResultArray($ResultQArray, "pg_get_indexdef");

		//if (count($ResultArray)!=1)
		{
			Errors::ErrorMessage("Не смог получить текстового определения индекса $IndexName таблицы $TableName");			
		}
		if (($ResultArray[0]==NULL) or ($ResultArray[0]==''))
		{
			Errors::ErrorMessage("Получено недопустимое значение текстового определения индекса $IndexName таблицы $TableName");
		}
		return $ResultArray[0];
	}	*/
}
?>