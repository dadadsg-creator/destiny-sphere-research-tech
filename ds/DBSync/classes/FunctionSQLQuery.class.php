<?php

include_once ("classes/Errors.class.php");

				//класс получения параметров функций базы
class FunctionSQLQuery
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
						//функция преобразования массива ID типов данных в массив имен типов данных для функции
	public function ConvertIDTypeArrayToNameTypeArray($DBWorkObject, $Array, $BaseName)
	{
		assert(is_string($BaseName) && ($BaseName!='')
				&& is_array($Array) && (count($Array)>0) 
				&& ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
	
		$ReturnArray=array();
				
		for ($Index=0; $Index<count($Array); $Index++)
		{
			if ($Array[$Index]!='')
			{
				$SQLQuery="select typname from pg_type where oid='$Array[$Index]';";
				$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);
				if (count($ResultQArray)!=1) 
				{
					Errors::ErrorMessage("Не cмог выполнить запрос на получение имени типа по его ID");
				}
				$ResultArray=self::ConvertQueryResultArray($ResultQArray, "typname");
				if (count($ResultArray)!=1)
				{
					Errors::ErrorMessage("Не cмог получить имя типа по его ID");
				}
				if (($ResultArray[0]==NULL) or ($ResultArray[0]==''))
				{
					Errors::ErrorMessage("Получено недопустимое имя типа с ID $Array[$Index]");
				}
				$ReturnArray[$Index]=$ResultArray[0];
			}
			else
			{
				print_r ($Array);
				Errors::ErrorMessage("В переданном для преобразования массиве содержится недопустимый ID");
			}
		}
		return $ReturnArray;
	}						
						//получение списка ID функций для базы
	public function GetFunctionIDList($DBWorkObject, $BaseName)
	{
		assert(is_string($BaseName) && ($BaseName!='') && ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
	
		$SQLQuery="select pg_proc.oid from pg_proc, pg_namespace where pg_proc.pronamespace=pg_namespace.oid and pg_namespace.nspname='public';";
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);		
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Не cмог выполнить запрос на получение переченя ID функций базы $BaseName");
		}
		$ResultArray=self::ConvertQueryResultArray($ResultQArray, "oid");
		if (!$ResultArray)
		{
			Errors::ErrorMessage("Не cмог получить перечень ID функций базы $BaseName");
		}
		return $ResultArray;
	}
						//получение заголовка функции по ее ID в базе
	public function GetFunctionHeader($DBWorkObject, $IDFunction, $BaseName)
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($IDFunction) && ($IDFunction!='')
								    && ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));		
										    //получаем имя функции в базе		
		$SQLQuery="select proname from pg_proc where oid='$IDFunction';";
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Не cмог выполнить запрос на получение имени функции базы $BaseName, ID которой равен $IDFunction");
		}						    
		$ResultArray=self::ConvertQueryResultArray($ResultQArray, "proname");
		if (count($ResultArray)!=1)
		{
			Errors::ErrorMessage("Не cмог получить имя функции базы $BaseName, ID которой равен $IDFunction");
		}								    
		
		if (($ResultArray[0]==NULL) or ($ResultArray[0]==''))
		{
			Errors::ErrorMessage("Получено недопустимое имя функции базы $BaseName, ID которой равен $IDFunction");
		}
		
		$FunctionName=$ResultArray[0];
		 						//получаем количество аргументов функции
		$SQLQuery="select pronargs from pg_proc where oid='$IDFunction';";
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Не cмог выполнить запрос на получение количества аргументов функции $FunctionName для базы $BaseName");
		}						    
		$ResultArray=self::ConvertQueryResultArray($ResultQArray, "pronargs");
		if (count($ResultArray)!=1)
		{
			Errors::ErrorMessage("Не cмог получить количество аргументов функции $FunctionName для базы $BaseName");
		}
		if ($ResultArray[0]==NULL)
		{
			Errors::ErrorMessage("Получено недопустимое значение количества аргументов функции $FunctionName для базы $BaseName");
		}
								//начинаем формировать заголовок функции
		$FunctionHeader ="FUNCTION $FunctionName (";
	
		$ArgNumber=(int) $ResultArray[0];
				
		if ($ArgNumber!=0) //у функции есть аргументы
		{							//получаем имена аргументов
			$SQLQuery="select proargnames from pg_proc where oid='$IDFunction';";
			$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);
			if ($ResultQArray)
			{					
				$ResultArray=self::ConvertQueryResultArray($ResultQArray, "proargnames");
				if (count($ResultArray)!=1)
				{
					Errors::ErrorMessage("Не cмог получить список имен аргументов функции $FunctionName для базы $BaseName");
				}
				if ($ResultArray[0]==NULL)
				{
					$ArgNameList=NULL; //у аргументов нет имен
				}
				else
				{
					$ResultArray[0]=str_replace('{','',$ResultArray[0]);
					$ResultArray[0]=str_replace('}','',$ResultArray[0]);
					$ArgNameList=explode(',',$ResultArray[0]);				
					if (!$ArgNameList)
					{
						Errors::ErrorMessage("Не cмог преобразовать в массив список имен аргументов функции $FunctionName для базы $BaseName");
					}
				}
			}
			else
			{
				Errors::ErrorMessage("Не cмог выполнить запрос на получение списка имен аргументов функции $FunctionName для базы $BaseName");				
			}
									//получаем вид аргументов
			$SQLQuery="select proargmodes from pg_proc where oid='$IDFunction';";
			$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);
			if ($ResultQArray)
			{
				$ResultArray=self::ConvertQueryResultArray($ResultQArray, "proargmodes");
				if (count($ResultArray)!=1)
				{				
					Errors::ErrorMessage("Не cмог получить вид аргументов функции $FunctionName для базы $BaseName"); 
				}
				if ($ResultArray[0]==NULL)
				{
					$ArgModeList=NULL; //все аргументы вида IN
				}
				else
				{
					$ResultArray[0]=str_replace('{','',$ResultArray[0]);
					$ResultArray[0]=str_replace('}','',$ResultArray[0]);
					$ArgModeList=explode(',',$ResultArray[0]);
					if (!$ArgModeList)
					{
						Errors::ErrorMessage("Не cмог преобразовать в массив список видов аргументов функции $FunctionName для базы $BaseName");
					}
				}	
			}
			else
			{
				Errors::ErrorMessage("Не cмог выполнить запрос на получение вида аргументов функции $FunctionName для базы $BaseName"); 				
			}			
									//получаем типы аргументов
			if ($ArgModeList) //аргументы смешанного вида
			{						
				$SQLQuery="select proallargtypes from pg_proc where oid='$IDFunction';";
				$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);
				if (!$ResultQArray)
				{
					Errors::ErrorMessage("Не cмог выполнить запрос на получение списка ID типов аргументов функции $FunctionName для базы $BaseName");
				}
				$ResultArray=self::ConvertQueryResultArray($ResultQArray, "proallargtypes");
				if (count($ResultArray)!=1)
				{
					Errors::ErrorMessage("Не cмог получить список ID типов аргументов функции $FunctionName для базы $BaseName");
				}
				if ($ResultArray[0]==NULL)
				{
					Errors::ErrorMessage("Получен список недопустимых ID типов аргументов функции $FunctionName в $BaseName");
				}
				$ResultArray[0]=str_replace('{','',$ResultArray[0]);
				$ResultArray[0]=str_replace('}','',$ResultArray[0]);
				$ArgTypeList=explode(',',$ResultArray[0]);				
				if (count($ArgTypeList)==0)
				{
					("Не cмог преобразовать в массив список типов аргументов функции $FunctionName для базы $BaseName");
				}
				$ArgTypeList=self::ConvertIDTypeArrayToNameTypeArray($DBWorkObject, $ArgTypeList, $BaseName);				
				if (!$ArgTypeList)
				{
					Errors::ErrorMessage("Не cмог получить список имен типов по их ID для аргументов функции $FunctionName в $BaseName");
				}
			}
			else //все аргументы вида IN
			{				
				$SQLQuery="select proargtypes from pg_proc where oid='$IDFunction';";
				$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);
				if (!$ResultQArray)
				{
					Errors::ErrorMessage("Не cмог выполнить запрос на получение списка ID типов аргументов функции $FunctionName для базы $BaseName");
				}
				$ResultArray=self::ConvertQueryResultArray($ResultQArray, "proargtypes");
				if (count($ResultArray)!=1)
				{
					Errors::ErrorMessage("Не cмог получить список ID типов аргументов функции $FunctionName для базы $BaseName");
				}
				if ($ResultArray[0]==NULL)
				{
					Errors::ErrorMessage("Получен список недопустимых ID типов аргументов функции $FunctionName в $BaseName");
				}								
				$ArgTypeList=explode(' ',$ResultArray[0]);
				if (count($ArgTypeList)==0)
				{
					("Не cмог преобразовать в массив список типов аргументов функции $FunctionName для базы $BaseName");
				}				
				$ArgTypeList=self::ConvertIDTypeArrayToNameTypeArray($DBWorkObject, $ArgTypeList, $BaseName);								
				if (!$ArgTypeList)
				{
					Errors::ErrorMessage("Не cмог получить список имен типов по их ID для аргументов функции $FunctionName в $BaseName");
				}
			}			
							//выполняем проверки количества полученных параметров перед составлением заголовка функции
			if ($ArgNameList)
			{
				if (count($ArgNameList)!=$ArgNumber)
				{
					Errors::ErrorMessage("Выявлено не соответствие количества аргументов числу их имен для функции $FunctionName в $BaseName");
				}
			}
			
			if ($ArgModeList)
			{
				if (count($ArgModeList)!=$ArgNumber)
				{
					Errors::ErrorMessage("Выявлено не соответствие количества аргументов числу их видов  при перечислении для функции $FunctionName в $BaseName");
				}
			}
			
			if ($ArgTypeList)
			{
				if (count($ArgTypeList)!=$ArgNumber)
				{					
					Errors::ErrorMessage("Выявлено не соответствие количества аргументов числу их типов при перечислении для функции $FunctionName в $BaseName");
				}
			}
								//продолжили формирование заголовка функции
			for ($Index=0; $Index<$ArgNumber; $Index++)
			{
				if ($Index)
				{
					$FunctionHeader .=", ";
				}
				
				if ($ArgModeList)
				{
					switch ($ArgModeList[$Index])
					{
						case 'i':
							$FunctionHeader .="IN ";
							break;
						case 'o':
							$FunctionHeader .="OUT ";
							break;
						case 'b':
							$FunctionHeader .="INOUT ";
							break;
						default:
							Errors::ErrorMessage("Получили неизвестный вид аргументов функции $FunctionName в $BaseName");
					}					
				}
				else
				{
					$FunctionHeader .="IN ";
				}
				
				if ($ArgNameList)
				{
					$FunctionHeader .="$ArgNameList[$Index] ";
				}
				
				if ($ArgTypeList)
				{
					$FunctionHeader .="$ArgTypeList[$Index]";
				}				
			}
		}
		
		$FunctionHeader .=")";
	
		return $FunctionHeader;
	}
						//функция получения определения функции базы по ее ID
	public function GetFunctionDefinition($DBWorkObject, $IDFunction, $BaseName)
	{
		assert(is_string($BaseName) && ($BaseName!='') && is_string($IDFunction) && ($IDFunction!='')
								    && ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
								//получаем ID функции по ее ID
		$SQLQuery="select proname from pg_proc where oid='$IDFunction';";
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Не cмог выполнить запрос на получение имени функции базы $BaseName, ID которой равен $IDFunction");
		}						    
		$ResultArray=self::ConvertQueryResultArray($ResultQArray, "proname");
		if (count($ResultArray)!=1)
		{
			Errors::ErrorMessage("Не cмог получить имя функции базы $BaseName, ID которой равен $IDFunction");
		}								    
		
		if (($ResultArray[0]==NULL) or ($ResultArray[0]==''))
		{
			Errors::ErrorMessage("Получено недопустимое имя функции базы $BaseName, ID которой равен $IDFunction");
		}
		
		$FunctionName=$ResultArray[0];						    
								//получаем тип возвращаемого значения для функции
		$SQLQuery="select pg_type.typname from pg_proc, pg_type where pg_proc.oid='$IDFunction' and pg_type.oid=pg_proc.prorettype;";
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Не смог выполнить запрос на получение имени типа возвращаемого значения функции $FunctionName в $BaseName");
		}
		$ResultArray=self::ConvertQueryResultArray($ResultQArray, "typname");
		if (count($ResultArray)!=1)
		{
			Errors::ErrorMessage("Не cмог получить имя типа возвращаемого значения функции $FunctionName в базе $BaseName");
		}
		
		if (($ResultArray[0]==NULL) or ($ResultArray[0]==''))
		{
			Errors::ErrorMessage("Получено недопустимое имя типа возвращаемого значения функции $FunctionName в базе $BaseName");
		}
		
		$FunctionDefinition="RETURNS $ResultArray[0] ";
							//получаем имя языка, на котором написана функция
		$SQLQuery="select pg_language.lanname from pg_proc, pg_language where pg_proc.oid='$IDFunction' and pg_language.oid=pg_proc.prolang;";
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Не смог выполнить запрос на получение имени языка функции $FunctionName в $BaseName");
		}
		$ResultArray=self::ConvertQueryResultArray($ResultQArray, "lanname");
		if (count($ResultArray)!=1)
		{
			Errors::ErrorMessage("Не cмог получить имя языка функции $FunctionName в базе $BaseName");
		}
		
		if (($ResultArray[0]==NULL) or ($ResultArray[0]==''))
		{
			Errors::ErrorMessage("Получено недопустимое имя языка функции $FunctionName в базе $BaseName");
		}
		
		$FunctionDefinition .="LANGUAGE $ResultArray[0] ";
								//получаем тип поведения функции
		$SQLQuery="select provolatile from pg_proc where pg_proc.oid='$IDFunction';";
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Не смог выполнить запрос на получение типа поведения функции $FunctionName в $BaseName");
		}
		$ResultArray=self::ConvertQueryResultArray($ResultQArray, "provolatile");
		if (count($ResultArray)!=1)
		{
			Errors::ErrorMessage("Не cмог получить тип поведения функции $FunctionName в базе $BaseName");
		}
		
		switch ($ResultArray[0])
		{
			case 'i':
				$FunctionDefinition .="IMMUTABLE ";
				break;
			case 's':
				$FunctionDefinition .="STABLE ";
				break;
			case 'v':
				$FunctionDefinition .="VOLATILE ";
				break;
			default:
				Errors::ErrorMessage("Получен недопустимый тип поведения функции $FunctionName в базе $BaseName");
		}
										//получаем тип поведения функции на NULL аргументы
		$SQLQuery="select proisstrict from pg_proc where pg_proc.oid='$IDFunction';";
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Не смог выполнить запрос на получение типа поведения функции $FunctionName в $BaseName на NULL аргументы");
		}
		$ResultArray=self::ConvertQueryResultArray($ResultQArray, "proisstrict");
		if (count($ResultArray)!=1)
		{
			Errors::ErrorMessage("Не cмог получить тип поведения функции $FunctionName в базе $BaseName на NULL аргументы");
		}
		
		if (($ResultArray[0]==NULL) or ($ResultArray[0]==''))
		{
			Errors::ErrorMessage("Получен недопустимый тип поведения функции $FunctionName в базе $BaseName на NULL аргументы");
		}
		
		if ($ResultArray[0]=='f')
		{
			$FunctionDefinition .="CALLED ON NULL INPUT ";
		}
		else
		{
			$FunctionDefinition .="RETURNS NULL ON NULL INPUT ";
		}
								//получаем исполняемый код функции
		$SQLQuery="select prosrc from pg_proc where pg_proc.oid='$IDFunction';";
		$ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);
		if (!$ResultQArray)
		{
			Errors::ErrorMessage("Не смог выполнить запрос на получение исполняемого кода функции $FunctionName в $BaseName");
		}
		$ResultArray=self::ConvertQueryResultArray($ResultQArray, "prosrc");
		if (count($ResultArray)!=1)
		{
			Errors::ErrorMessage("Не cмог получить исполняемого кода функции $FunctionName в базе $BaseName");
		}
		
		if (($ResultArray[0]==NULL) or ($ResultArray[0]==''))
		{
			Errors::ErrorMessage("Получен недопустимый исполняемый код функции $FunctionName в базе $BaseName");
		}
		
		$FunctionDefinition .="AS $$ $ResultArray[0] $$";
		
		return $FunctionDefinition;
	}
}

?>