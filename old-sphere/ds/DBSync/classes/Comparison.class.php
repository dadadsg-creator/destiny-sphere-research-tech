<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2007/10/24 10:42:04 $';

include_once ("classes/TableSQLQuery.class.php");
include_once ("classes/ColumnSQLQuery.class.php");
include_once ("classes/FunctionSQLQuery.class.php");
include_once ("classes/TriggerSQLQuery.class.php");
include_once ("classes/IndexSQLQuery.class.php");
include_once ("classes/ConstraintSQLQuery.class.php");
include_once ("classes/Errors.class.php");

class Comparison
{						//функция сравнения таблиц
	public function CompareTables($DBWorkObject, $MasterBazeName, $SlaveBazeName)
	{
		assert(is_string($MasterBazeName) && ($MasterBazeName!=''));
		assert(is_string($SlaveBazeName) && ($SlaveBazeName!=''));
		assert(($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));

		$SQLQuery=array();
		$SQLQueryIndex=0;
		$SQLQueryErrorIndex=0;

		//получаем список таблиц в обеих базах
		$MasterTableList=TableSQLQuery::GetDS_TablesNameList ($DBWorkObject, $MasterBazeName); //список имен таблиц базы master

		if (($MasterTableList==NULL) or (count($MasterTableList)==0))
		{
			Errors::ErrorMessage("Не удалось получить список таблиц для базы $MasterBazeName");
		}
		$SlaveTableList=TableSQLQuery::GetDS_TablesNameList ($DBWorkObject, $SlaveBazeName); //список имен таблиц базы slave
		if (($SlaveTableList==NULL) or (count($SlaveTableList)==0))
		{
			Errors::ErrorMessage("Не удалось получить список таблиц для базы $SlaveBazeName");
		}
		for ($Index=0; $Index<count($MasterTableList); $Index++) //сравниваем списки имен таблиц двух баз
		{
			if (!(in_array($MasterTableList[$Index],$SlaveTableList))) //в slave нет таблицы из базы master
			{
				//запрос на создание таблицы с учетом наличия/отсутствия в ней столбца идентификаторов OID
				if (TableSQLQuery::HasTableOIDColumn($DBWorkObject,$MasterTableList[$Index], $MasterBazeName))
				{
					$SQLQueryPart='WITH';
				}
				else
				{
					$SQLQueryPart='WITHOUT';
				}

				$SQLQuery['Comment'][$SQLQueryIndex]="Создаем таблицу $MasterTableList[$Index].";
				/*$SQLQuery['Script'][$SQLQueryIndex]='CREATE TABLE public.$MasterTableList[$Index]() $SQLQueryPart OIDS;
													ALTER TABLE ".'public.$MasterTableList[$Index].'"() OWNER TO "dsgame"';*/
				
				$SQLQuery['Script'][$SQLQueryIndex]="CREATE TABLE public.$MasterTableList[$Index]() $SQLQueryPart OIDS;";

				$SQLQuery['Color'][$SQLQueryIndex]='009900';
				$SQLQueryIndex++;
			}
			else
			{					//в обеих базах есть таблицы с одинаковым именем; проверим наличие в них столбцов идентификаторов OID
				$IsOIDMaster=TableSQLQuery::HasTableOIDColumn($DBWorkObject,$MasterTableList[$Index], $MasterBazeName);
				$IsOIDSlave=TableSQLQuery::HasTableOIDColumn($DBWorkObject,$MasterTableList[$Index], $SlaveBazeName);

				if ($IsOIDMaster == $IsOIDSlave)
				{
					$SQLQuery['Comment'][$SQLQueryIndex]="Таблица $MasterTableList[$Index] => ОК.";
					$SQLQuery['Script'][$SQLQueryIndex]=NULL;
					$SQLQuery['Color'][$SQLQueryIndex]='000000';
					$SQLQueryIndex++;
				}
				else
				{
					if ($IsOIDMaster)
					{
						$SQLQuery['Error'][$SQLQueryErrorIndex]="Нельзя создать для таблицы $MasterTableList[$Index] столбец идентификаторов OID.";
						$SQLQueryErrorIndex++;
						//$SQLQuery['Comment'][$SQLQueryIndex]="Создаем в таблице $MasterTableList[$Index] столбец идентификаторов OID.";
						//$SQLQuery['Script'][$SQLQueryIndex]="ALTER TABLE $MasterTableList[$Index] SET WITH OIDS;";
						//$SQLQuery['Color'][$SQLQueryIndex]='0000ff';
						//$SQLQueryIndex++;
					}
					else
					{
						if($IsOIDSlave)
						{
							$SQLQuery['Comment'][$SQLQueryIndex]="Удаляем в таблице $MasterTableList[$Index] столбец идентификаторов OID.";
							$SQLQuery['Script'][$SQLQueryIndex]="ALTER TABLE $MasterTableList[$Index] SET WITHOUT OIDS;";
							$SQLQuery['Color'][$SQLQueryIndex]='0000ff';
							$SQLQueryIndex++;
						}
					}
				}
			}
		}
		//удаляем из slave талицы, которых нет в master
		for ($Index=0; $Index<count($SlaveTableList);$Index++)
		{
			if (!in_array($SlaveTableList[$Index], $MasterTableList))
			{
				$SQLQuery['Comment'][$SQLQueryIndex]="Удаляем таблицу $SlaveTableList[$Index].";
				$SQLQuery['Script'][$SQLQueryIndex]="DROP TABLE public.$SlaveTableList[$Index] CASCADE;";
				$SQLQuery['Color'][$SQLQueryIndex]='ff0000';
				$SQLQueryIndex++;
			}
		}

		return $SQLQuery;
	}
	//сравниваем столбцы таблиц в обеих базах
	public function CompareColumns($DBWorkObject, $MasterBazeName, $SlaveBazeName)
	{
		assert(is_string($MasterBazeName) && ($MasterBazeName!=''));
		assert(is_string($SlaveBazeName) && ($SlaveBazeName!=''));
		assert(($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));

		$SQLQuery=array();
		$SQLQueryIndex=0;
		$SQLQueryErrorIndex=0;

		$MasterTableList=TableSQLQuery::GetDS_TablesNameList ($DBWorkObject, $MasterBazeName); //список имен таблиц базы master
		if (($MasterTableList==NULL) or (count($MasterTableList)==0))
		{
			Errors::ErrorMessage("Не удалось получить список таблиц для базы $MasterBazeName");
		}
		$SlaveTableList=TableSQLQuery::GetDS_TablesNameList ($DBWorkObject, $SlaveBazeName); //список имен таблиц базы slave
		if (($SlaveTableList==NULL) or (count($SlaveTableList)==0))
		{
			Errors::ErrorMessage("Не удалось получить список таблиц для базы $SlaveBazeName");
		}

		for ($Index=0; $Index<count($MasterTableList); $Index++) //сравниваем списки таблиц двух баз
		{
			if (in_array($MasterTableList[$Index],$SlaveTableList)) //таблица с данным именем существует в обеих базах, начинаем сравнение столбцов
			{
				$SQLQuery['Comment'][$SQLQueryIndex]="Работаем с таблицей $MasterTableList[$Index]";
				$SQLQuery['Script'][$SQLQueryIndex]=NULL;
				$SQLQuery['Color'][$SQLQueryIndex]='000000';
				$SQLQuery['Header'][$SQLQueryIndex]=TRUE;
				$SQLQueryIndex++;

				//получаем списки столбцов таблицы в обеих базах
				$TableColumnNamesMaster=ColumnSQLQuery::GetTableColumnNameList ($DBWorkObject, $MasterTableList[$Index], $MasterBazeName);
				if (($TableColumnNamesMaster==NULL) or (count($TableColumnNamesMaster)==0))
				{
					Errors::ErrorMessage("Не удалось получить список столбцов таблицы $MasterTableList[$Index] базы $MasterBazeName");
				}
				
				$TableColumnNamesSlave=ColumnSQLQuery::GetTableColumnNameList ($DBWorkObject, $MasterTableList[$Index], $SlaveBazeName);
				if (($TableColumnNamesSlave==NULL) or (count($TableColumnNamesSlave)==0))
				{
					Errors::ErrorMessage("Не удалось получить список столбцов таблицы $MasterTableList[$Index] базы $SlaveBazeName");
				}

				for ($Index1=0; $Index1<count($TableColumnNamesMaster); $Index1++)
				{
					//проверяем условие, столбец не является системным, которые никак не обрабатываются
					if (!ColumnSQLQuery::IsColumnSystem($DBWorkObject, $TableColumnNamesMaster[$Index1], $MasterTableList[$Index], $MasterBazeName))
					{											//столбец существует в таблицах обеих баз
						if (in_array($TableColumnNamesMaster[$Index1],$TableColumnNamesSlave))
						{
							$ColumnOKFlag=TRUE;
							//сравниваем типы столбцов таблиц в обеих базах
							$ColumnTypeMaster=ColumnSQLQuery::GetTableColumnTypeName($DBWorkObject, $TableColumnNamesMaster[$Index1], $MasterTableList[$Index], $MasterBazeName);
							if ($ColumnTypeMaster=='')
							{
								Errors::ErrorMessage("Не удалось получить имя типа данных столбца ".$TableColumnNamesMaster[$Index1]." таблицы ".$MasterTableList[$Index]." базы $MasterBazeName");
							}
							$ColumnTypeSlave=ColumnSQLQuery::GetTableColumnTypeName($DBWorkObject, $TableColumnNamesMaster[$Index1], $MasterTableList[$Index], $SlaveBazeName);
							if ($ColumnTypeSlave=='')
							{
								Errors::ErrorMessage("Не удалось получить имя типа данных столбца ".$TableColumnNamesMaster[$Index1] ." таблицы ". $MasterTableList[$Index]." базы $SlaveBazeName");
							}

							if ($ColumnTypeMaster!=$ColumnTypeSlave)//сравниваем типы данных в столбцах
							{
								$SQLQuery['Comment'][$SQLQueryIndex]="Изменяем тип данных столбца ".$TableColumnNamesMaster[$Index1];
								$SQLQuery['Script'][$SQLQueryIndex]="ALTER TABLE public.".$MasterTableList[$Index]." ALTER COLUMN ".$TableColumnNamesMaster[$Index1]." TYPE $ColumnTypeMaster;";
								$SQLQuery['Color'][$SQLQueryIndex]='0000ff';
								$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
								$ColumnOKFlag=FALSE;
								$SQLQueryIndex++;
							}
							//проверяем условие наличия в столбце NULL значений, делая соответствующие запросы к базам
							$ColumnNotNullConstraintMaster=ColumnSQLQuery::IsColumnNotNull($DBWorkObject, $TableColumnNamesMaster[$Index1], $MasterTableList[$Index], $MasterBazeName);
							$ColumnNotNullConstraintSlave=ColumnSQLQuery::IsColumnNotNull($DBWorkObject, $TableColumnNamesMaster[$Index1], $MasterTableList[$Index], $SlaveBazeName);
							if ($ColumnNotNullConstraintMaster != $ColumnNotNullConstraintSlave)
							{
								if ($ColumnNotNullConstraintMaster)//столбец в Master не имеет NULL значений, а в Slave - может
								{
									$Comment="не";
									$SQLNullQueryPart='SET';
								}
								else
								{								//столбец в Master имеет NULL значения, а в Slave - нет
									$Comment="";
									$SQLNullQueryPart='DROP';
								}
								$SQLQuery['Comment'][$SQLQueryIndex]="Столбец $TableColumnNamesMaster[$Index1] $Comment может иметь NULL значения.";
								$SQLQuery['Script'][$SQLQueryIndex]="ALTER TABLE public.$MasterTableList[$Index] ALTER COLUMN $TableColumnNamesMaster[$Index1] $SQLNullQueryPart NOT NULL;";
								$SQLQuery['Color'][$SQLQueryIndex]='0000ff';
								$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
								$ColumnOKFlag=FALSE;
								$SQLQueryIndex++;
							}
							//проверяем признак наличия у столбца значения по умолчанию
							$ColumnHasDefaultValueMaster=ColumnSQLQuery::HasColumnDefaultValue($DBWorkObject, $TableColumnNamesMaster[$Index1], $MasterTableList[$Index], $MasterBazeName);
							$ColumnHasDefaultValueSlave=ColumnSQLQuery::HasColumnDefaultValue($DBWorkObject, $TableColumnNamesMaster[$Index1], $MasterTableList[$Index], $SlaveBazeName);
							if ($ColumnHasDefaultValueMaster)//в master есть значение по умолчанию
							{
								if ($ColumnHasDefaultValueSlave) //в slave есть значение по умолчанию
								{
									$ColumnDefaultValueMaster=ColumnSQLQuery::GetColumnDefaultValueSTR($DBWorkObject, $TableColumnNamesMaster[$Index1], $MasterTableList[$Index], $MasterBazeName);
									if ($ColumnDefaultValueMaster==NULL)
									{
										Errors::ErrorMessage("Не удалось получить значение по умолчанию для столбца $TableColumnNamesMaster[$Index1] таблицы $MasterTableList[$Index] базы $MasterBazeName");
									}
									$ColumnDefaultValueSlave=ColumnSQLQuery::GetColumnDefaultValueSTR($DBWorkObject, $TableColumnNamesMaster[$Index1], $MasterTableList[$Index], $SlaveBazeName);
									if ($ColumnDefaultValueSlave==NULL)
									{
										Errors::ErrorMessage("Не удалось получить значение по умолчанию для столбца $TableColumnNamesMaster[$Index1] таблицы $MasterTableList[$Index] базы $SlaveBazeName");
									}
									//в базах у столбцов разные значения по умолчанию
									if ($ColumnDefaultValueSlave!=$ColumnDefaultValueMaster)
									{
										$substrVM  =  substr("$ColumnDefaultValueMaster", 0, 7);

										//Если значение поля является sequens
										if ($substrVM == "nextval")
										{
											$sequenceName = explode("'", $ColumnDefaultValueMaster);
											$sequenceName = $sequenceName[1];
											$ifSequensExist = ColumnSQLQuery::IsSquenceExist($DBWorkObject, $sequenceName, $SlaveBazeName);

											//И такого sequens не буществует в базе данных
											if ($ifSequensExist == FALSE)
											{
												$SQLQuery['Comment'][$SQLQueryIndex]="Создаем SEQUENCE $sequenceName. У столбца $TableColumnNamesMaster[$Index1] обновляем значение по умолчанию";
												$SQLQuery['Script'][$SQLQueryIndex]="CREATE SEQUENCE $sequenceName; \n ALTER TABLE public.$MasterTableList[$Index] ALTER COLUMN $TableColumnNamesMaster[$Index1] DROP DEFAULT; \n ALTER TABLE public.$MasterTableList[$Index] ALTER COLUMN $TableColumnNamesMaster[$Index1] SET DEFAULT $ColumnDefaultValueMaster;";
												$SQLQuery['Color'][$SQLQueryIndex]='0000ff';
												$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
												$ColumnOKFlag=FALSE;
												$SQLQueryIndex++;
											}
											else { //sequens уже существует в slave
												$SQLQuery['Comment'][$SQLQueryIndex]="У столбца $TableColumnNamesMaster[$Index1] обновляем значение по умолчанию";
												$SQLQuery['Script'][$SQLQueryIndex]="ALTER TABLE public.$MasterTableList[$Index] ALTER COLUMN $TableColumnNamesMaster[$Index1] DROP DEFAULT; \n ALTER TABLE public.$MasterTableList[$Index] ALTER COLUMN $TableColumnNamesMaster[$Index1] \n SET DEFAULT $ColumnDefaultValueMaster;";
												$SQLQuery['Color'][$SQLQueryIndex]='0000ff';
												$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
												$ColumnOKFlag=FALSE;
												$SQLQueryIndex++;
											}

										}
										else { //Если значение поля не является sequens
											$SQLQuery['Comment'][$SQLQueryIndex]="У столбца $TableColumnNamesMaster[$Index1] обновляем значение по умолчанию";
											$SQLQuery['Script'][$SQLQueryIndex]="ALTER TABLE public.$MasterTableList[$Index] ALTER COLUMN $TableColumnNamesMaster[$Index1] DROP DEFAULT;  \n ALTER TABLE public.$MasterTableList[$Index] ALTER COLUMN $TableColumnNamesMaster[$Index1] \n SET DEFAULT $ColumnDefaultValueMaster;";
											$SQLQuery['Color'][$SQLQueryIndex]='0000ff';
											$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
											$ColumnOKFlag=FALSE;
											$SQLQueryIndex++;
										}
									}
								}
								else //в slave нет значения по умолчанию
								{
									$ColumnDefaultValueMaster=ColumnSQLQuery::GetColumnDefaultValueSTR($DBWorkObject, $TableColumnNamesMaster[$Index1], $MasterTableList[$Index], $MasterBazeName);
									$substrVM  =  substr("$ColumnDefaultValueMaster", 0, 7);

									//Если значение поля является sequens
									if ($substrVM == "nextval")
									{
										$sequenceName = explode("'", $ColumnDefaultValueMaster);
										$sequenceName = $sequenceName[1];
										$ifSequensExist = ColumnSQLQuery::IsSquenceExist($DBWorkObject, $sequenceName, $SlaveBazeName);

										//И такого sequens не буществует в базе данных
										if ($ifSequensExist == FALSE)
										{
											$SQLQuery['Comment'][$SQLQueryIndex]="Для столбца $TableColumnNamesMaster[$Index1] создаем значение по умолчанию.";
											$SQLQuery['Script'][$SQLQueryIndex]="CREATE SEQUENCE $sequenceName; \n ALTER TABLE public.$MasterTableList[$Index] ALTER COLUMN $TableColumnNamesMaster[$Index1] \n SET DEFAULT $ColumnDefaultValueMaster;";
											$SQLQuery['Color'][$SQLQueryIndex]='009900';
											$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
											$ColumnOKFlag=FALSE;
											$SQLQueryIndex++;
										}
										else
										{
											$SQLQuery['Comment'][$SQLQueryIndex]="Для столбца $TableColumnNamesMaster[$Index1] создаем значение по умолчанию.";
											$SQLQuery['Script'][$SQLQueryIndex]="ALTER TABLE public.$MasterTableList[$Index] ALTER COLUMN $TableColumnNamesMaster[$Index1] SET DEFAULT $ColumnDefaultValueMaster;";
											$SQLQuery['Color'][$SQLQueryIndex]='009900';
											$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
											$ColumnOKFlag=FALSE;
											$SQLQueryIndex++;
										}
									}
									else {
										$SQLQuery['Comment'][$SQLQueryIndex]="Для столбца $TableColumnNamesMaster[$Index1] создаем значение по умолчанию.";
										$SQLQuery['Script'][$SQLQueryIndex]="ALTER TABLE public.$MasterTableList[$Index] ALTER COLUMN $TableColumnNamesMaster[$Index1] SET DEFAULT $ColumnDefaultValueMaster;";
										$SQLQuery['Color'][$SQLQueryIndex]='009900';
										$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
										$ColumnOKFlag=FALSE;
										$SQLQueryIndex++;
									}
								}
							}
							else		//в master нет значения по умолчанию
							{
								if ($ColumnHasDefaultValueSlave) //в slave есть значение по умолчанию, делаем запрос на удаление
								{
									$SQLQuery['Comment'][$SQLQueryIndex]="Для столбца $TableColumnNamesMaster[$Index1] удаляем значение по умолчанию.";
									$SQLQuery['Script'][$SQLQueryIndex]="ALTER TABLE public.$MasterTableList[$Index] ALTER COLUMN $TableColumnNamesMaster[$Index1] DROP DEFAULT;";
									$SQLQuery['Color'][$SQLQueryIndex]='ff0000';
									$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
									$ColumnOKFlag=FALSE;
									$SQLQueryIndex++;
								}
							}
							if ($ColumnOKFlag)
							{
								$SQLQuery['Comment'][$SQLQueryIndex]="Столбец $TableColumnNamesMaster[$Index1] => OK.";
								$SQLQuery['Script'][$SQLQueryIndex]=NULL;
								$SQLQuery['Color'][$SQLQueryIndex]='000000';
								$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
								$SQLQueryIndex++;
							}
						}
						else //столбца нет, генерируем запрос на его создание в таблице базы Slave
						{								//получаем тип столбца
							$ColumnTypeMaster=ColumnSQLQuery::GetTableColumnTypeName($DBWorkObject, $TableColumnNamesMaster[$Index1], $MasterTableList[$Index], $MasterBazeName);
							if ($ColumnTypeMaster==NULL)
							{
								Errors::ErrorMessage("Не удалось получить имя типа данных для столбца $TableColumnNamesMaster[$Index1] таблицы $MasterTableList[$Index] базы $MasterBazeName");
							}

							//выясняем, могут ли быть в столбце NULL значения
							$ColumnNotNull=ColumnSQLQuery::IsColumnNotNull($DBWorkObject, $TableColumnNamesMaster[$Index1], $MasterTableList[$Index], $MasterBazeName);
							if ($ColumnNotNull)
							{
								$ColumnNullConstraint='NOT NULL';
							}
							else
							{
								$ColumnNullConstraint='NULL';
							}

							$ColumnHasDefaultValueMaster=ColumnSQLQuery::HasColumnDefaultValue($DBWorkObject, $TableColumnNamesMaster[$Index1], $MasterTableList[$Index], $MasterBazeName);
							if ($ColumnHasDefaultValueMaster)//в master есть значение по умолчанию
							{
								$ColumnDefaultValueMaster=ColumnSQLQuery::GetColumnDefaultValueSTR($DBWorkObject, $TableColumnNamesMaster[$Index1], $MasterTableList[$Index], $MasterBazeName);
								if ($ColumnDefaultValueMaster==NULL)
								{
									Errors::ErrorMessage("Не удалось получить значение по умолчанию для столбца $TableColumnNamesMaster[$Index1] таблицы $MasterTableList[$Index] базы $MasterBazeName");
								}

								$substrVM  =  substr("$ColumnDefaultValueMaster", 0, 7);

								//Если значение поля является sequens
								if ($substrVM == "nextval")
								{
									$sequenceName = explode("'", $ColumnDefaultValueMaster);
									$sequenceName = $sequenceName[1];
									$ifSequensExist = ColumnSQLQuery::IsSquenceExist($DBWorkObject, $sequenceName, $SlaveBazeName);

									//И такого sequens не буществует в базе данных
									if ($ifSequensExist == FALSE)
									{
										$SQLQuery['Comment'][$SQLQueryIndex]="Создаем SEQUENCE $sequenceName. Создаем столбец $TableColumnNamesMaster[$Index1];";
										$SQLQuery['Script'][$SQLQueryIndex]="CREATE SEQUENCE $sequenceName; \n ALTER TABLE public.$MasterTableList[$Index] ADD COLUMN $TableColumnNamesMaster[$Index1] $ColumnTypeMaster $ColumnNullConstraint DEFAULT $ColumnDefaultValueMaster;";
										$SQLQuery['Color'][$SQLQueryIndex]='0000ff';
										$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
										$ColumnOKFlag=FALSE;
										$SQLQueryIndex++;
									}
									else { //sequens уже существует в slave
										$SQLQuery['Comment'][$SQLQueryIndex]="Создаем столбец $TableColumnNamesMaster[$Index1].";
										$SQLQuery['Script'][$SQLQueryIndex]="ALTER TABLE public.$MasterTableList[$Index] ADD COLUMN $TableColumnNamesMaster[$Index1] $ColumnTypeMaster $ColumnNullConstraint DEFAULT $ColumnDefaultValueMaster;";
										$SQLQuery['Color'][$SQLQueryIndex]='009900';
										$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
										$ColumnOKFlag=FALSE;
										$SQLQueryIndex++;
									}
								}
								else //Если значение поля не является sequens
								{
									$SQLQuery['Comment'][$SQLQueryIndex]="Создаем столбец $TableColumnNamesMaster[$Index1].";
										$SQLQuery['Script'][$SQLQueryIndex]="ALTER TABLE public.$MasterTableList[$Index] ADD COLUMN $TableColumnNamesMaster[$Index1] $ColumnTypeMaster $ColumnNullConstraint DEFAULT $ColumnDefaultValueMaster;";
										$SQLQuery['Color'][$SQLQueryIndex]='009900';
										$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
										$ColumnOKFlag=FALSE;
										$SQLQueryIndex++;
								}
							}
							else //Если в мастере нет значения по умолчанию 
							{
								$SQLQuery['Comment'][$SQLQueryIndex]="Создаем столбец $TableColumnNamesMaster[$Index1].";
								$SQLQuery['Script'][$SQLQueryIndex]="ALTER TABLE public.$MasterTableList[$Index] ADD COLUMN $TableColumnNamesMaster[$Index1] $ColumnTypeMaster $ColumnNullConstraint;";
								$SQLQuery['Color'][$SQLQueryIndex]='009900';
								$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
								$SQLQueryIndex++;
							}
						}
					}
				}
				//делаем запросы на удаление столбцов из таблицы slave, которых нет в такой же таблице в master
				for ($Index1=0; $Index1<count($TableColumnNamesSlave); $Index1++)
				{
					if ((!in_array($TableColumnNamesSlave[$Index1],$TableColumnNamesMaster))
					and (!ColumnSQLQuery::IsColumnSystem($DBWorkObject, $TableColumnNamesSlave[$Index1], $MasterTableList[$Index], $SlaveBazeName)))
					{
						$SQLQuery['Comment'][$SQLQueryIndex]="Удаляем столбец $TableColumnNamesSlave[$Index1].";
						$SQLQuery['Script'][$SQLQueryIndex]="ALTER TABLE public.$MasterTableList[$Index] DROP COLUMN $TableColumnNamesSlave[$Index1] CASCADE;";
						$SQLQuery['Color'][$SQLQueryIndex]='ff0000';
						$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
						$SQLQueryIndex++;
					}
				}
			}
			else //в базе slave нет таблицы, которая есть в master
			{
				$SQLQuery['Error'][$SQLQueryErrorIndex]="В базе $SlaveBazeName нет таблицы $MasterTableList[$Index], которая есть в базе $MasterBazeName. Сравнение столбцов не проводилось.";
				$SQLQueryErrorIndex++;
			}
		}
		return $SQLQuery;
	}
	//сравниваем ограничения у таблиц обеих баз
	public function CompareTableConstraints($DBWorkObject, $MasterBazeName, $SlaveBazeName, $ConstraintType)
	{
		assert(is_string($MasterBazeName) && ($MasterBazeName!=''));
		assert(is_string($SlaveBazeName) && ($SlaveBazeName!=''));
		assert(($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
		assert(is_string($ConstraintType));
		assert(($ConstraintType=='u') or ($ConstraintType=='p') or ($ConstraintType=='c') or ($ConstraintType=='f'));

		$SQLQuery=array();
		$SQLQueryIndex=0;
		$SQLQueryErrorIndex=0;

		switch ($ConstraintType) //тип ограничения
		{
			case "p":
				$ConstraintName="PrimaryKey"; //имя ограничения
				break;
			case "u":
				$ConstraintName="Unique";
				break;
			case "c":
				$ConstraintName="Check";
				break;
			case "f":
				$ConstraintName="Foreign key";
				break;
		}

		$MasterTableList=TableSQLQuery::GetDS_TablesNameList ($DBWorkObject, $MasterBazeName); //список имен таблиц базы master
		if (($MasterTableList==NULL) or (count($MasterTableList)==0))
		{
			Errors::ErrorMessage("Не удалось получить список таблиц для базы $MasterBazeName при сравнении $ConstraintName ограничений");
		}
		$SlaveTableList=TableSQLQuery::GetDS_TablesNameList ($DBWorkObject, $SlaveBazeName); //список имен таблиц базы slave
		if (($SlaveTableList==NULL) or (count($SlaveTableList)==0))
		{
			Errors::ErrorMessage("Не удалось получить список таблиц для базы $SlaveBazeName при сравнении $ConstraintName ограничений");
		}

		for ($Index=0; $Index<count($MasterTableList); $Index++) //сравниваем списки таблиц двух баз
		{
			if(in_array($MasterTableList[$Index],$SlaveTableList)) //таблица с данным именем существует в обеих базах
			{
				$SQLQuery['Comment'][$SQLQueryIndex]="Работаем с таблицей $MasterTableList[$Index]";
				$SQLQuery['Script'][$SQLQueryIndex]=NULL;
				$SQLQuery['Color'][$SQLQueryIndex]='000000';
				$SQLQuery['Header'][$SQLQueryIndex]=TRUE;
				$SQLQueryIndex++;
				//получаем для каждой базы список имен ограничений заданного типа
				$MasterConstraintList=ConstraintSQLQuery::GetTableConstraintList ($DBWorkObject, $MasterTableList[$Index], $MasterBazeName, $ConstraintType);
				$SlaveConstraintList=ConstraintSQLQuery::GetTableConstraintList ($DBWorkObject, $MasterTableList[$Index], $SlaveBazeName, $ConstraintType);

				if (count($MasterConstraintList)!=0) //в master есть ограничения
				{
					if(count($SlaveConstraintList)!=0) //в slave есть ограничения
					{
						$flag = FALSE;

						//Удаляем из slave ограничения, которых нет в master
						foreach ($SlaveConstraintList as $key => $slaveConcat)
						{
							foreach ($MasterConstraintList as $key => $masterConcat)
							{
								$SlaveConstraintDefinition=ConstraintSQLQuery::GetConstraintDefinitionStr($DBWorkObject, $slaveConcat['conname'], $MasterTableList[$Index], $SlaveBazeName, $ConstraintType);
								$MasterConstraintDefinition=ConstraintSQLQuery::GetConstraintDefinitionStr($DBWorkObject, $masterConcat['conname'], $MasterTableList[$Index], $MasterBazeName, $ConstraintType);
								
								if ($SlaveConstraintDefinition == $MasterConstraintDefinition)
								{
									$flag = TRUE;
								}
							}
							if ($flag == FALSE)//Если в  Slave есть ограничение, которого нет в Master
							{
								$SQLQuery['Comment'][$SQLQueryIndex]="Удаляем ограничение ".$slaveConcat['attname'];
								$SQLQuery['Script'][$SQLQueryIndex]='ALTER TABLE '.$MasterTableList[$Index].' DROP CONSTRAINT "'.$slaveConcat['conname'].'" CASCADE;';
								$SQLQuery['Color'][$SQLQueryIndex]='ff0000';
								$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
								$SQLQueryIndex++;
							}
							$flag = FALSE;
						}

						//Создаем отсутствующие в slave ограничения
						foreach ($MasterConstraintList as $key => $masterConcat)
						{

							foreach ($SlaveConstraintList as $key => $slaveConcat)
							{
								$SlaveConstraintDefinition=ConstraintSQLQuery::GetConstraintDefinitionStr($DBWorkObject, $slaveConcat['conname'], $MasterTableList[$Index], $SlaveBazeName, $ConstraintType);
								$MasterConstraintDefinition=ConstraintSQLQuery::GetConstraintDefinitionStr($DBWorkObject, $masterConcat['conname'], $MasterTableList[$Index], $MasterBazeName, $ConstraintType);
								
								if ($SlaveConstraintDefinition == $MasterConstraintDefinition)
								{
									$flag = TRUE;
								}
							}
		
							if ($flag == FALSE)//Если в  Slave нет ограничения, которое есть в Master
							{
								//получили текстовое определение ограничения для master
								$MasterConstraintDefinition=ConstraintSQLQuery::GetConstraintDefinitionStr($DBWorkObject, $masterConcat['conname'], $MasterTableList[$Index], $MasterBazeName, $ConstraintType);
								
								if($MasterConstraintDefinition==NULL)
								{
									Errors::ErrorMessage("Не удалось получить текстовое определение $ConstraintName ограничения $MasterConstraintList[$Index1] таблицы $MasterTableList[$Index] базы $MasterBazeName");
								}

								switch ($ConstraintType) //составляем запрос взависимости от типа ограничения
								{
									case 'p':
										$SQLTemp ="ALTER TABLE $MasterTableList[$Index] ADD $MasterConstraintDefinition;";
										break;
									case 'u':
										$SQLTemp ="ALTER TABLE $MasterTableList[$Index] ADD $MasterConstraintDefinition;";
										break;
									case 'c':
										$SQLTemp ="ALTER TABLE $MasterTableList[$Index] ADD $MasterConstraintDefinition;";
										break;
									case 'f':
										$SQLTemp ="ALTER TABLE $MasterTableList[$Index] ADD $MasterConstraintDefinition;";
										break;
								}

								$SQLQuery['Comment'][$SQLQueryIndex]="Создаем ограничение ".$masterConcat['conname'];
								$SQLQuery['Script'][$SQLQueryIndex]=$SQLTemp;
								$SQLQuery['Color'][$SQLQueryIndex]='0000ff';
								$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
								$SQLQueryIndex++;
							}
							$flag = FALSE;
						}
					}
					else //в master есть ограничения, а в slave их нет вообще; создаем все ограничения из master для slave
					{
						//for ($Index1=0; $Index1<count($MasterConstraintList); $Index1++)
						foreach ($MasterConstraintList as $key => $masterConcat)
						{
							$MasterConstraintDefinition=ConstraintSQLQuery::GetConstraintDefinitionStr($DBWorkObject, $masterConcat['conname'], $MasterTableList[$Index], $MasterBazeName, $ConstraintType);
							if($MasterConstraintDefinition==NULL)
							{
								Errors::ErrorMessage("Не удалось получить текстовое определение $ConstraintName ограничения $MasterConstraintList[$Index1] таблицы $MasterTableList[$Index] базы $MasterBazeName.");
							}
							switch ($ConstraintType) //составляем запрос в зависимости от типа ограничения
							{
								case 'p':
									$SQLTemp ="ALTER TABLE $MasterTableList[$Index] ADD $MasterConstraintDefinition;";
									break;
								case 'u':
									$SQLTemp ="ALTER TABLE $MasterTableList[$Index] ADD $MasterConstraintDefinition;";
									break;
								case 'c':
									$SQLTemp ="ALTER TABLE $MasterTableList[$Index] ADD $MasterConstraintDefinition;";
									break;
								case 'f':
									$SQLTemp ="ALTER TABLE $MasterTableList[$Index] ADD $MasterConstraintDefinition;";
									break;
							}
							$SQLQuery['Comment'][$SQLQueryIndex]="Создаем ограничение ".$masterConcat['attname'];
							$SQLQuery['Script'][$SQLQueryIndex]=$SQLTemp;
							$SQLQuery['Color'][$SQLQueryIndex]='B03060';
							$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
							$SQLQueryIndex++;
							$SQLTemp = "";
						}
					}
				}
				else // в master нет ограничений
				{
					if($SlaveConstraintList) //в slave есть ограничения, удаляем их все
					{
						//for ($Index1=0; $Index1<count($SlaveConstraintList); $Index1++)
						foreach ($SlaveConstraintList as $key => $slaveConcat)
						{

							$SQLQuery['Comment'][$SQLQueryIndex]="Удаляем ограничение с именем ".$slaveConcat['conname'];
							$SQLQuery['Script'][$SQLQueryIndex]='ALTER TABLE '.$MasterTableList[$Index].' DROP CONSTRAINT "'.$slaveConcat['conname'].'" CASCADE;';
							$SQLQuery['Color'][$SQLQueryIndex]='ff0000';
							$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
							$SQLQueryIndex++;
						}
					}
					else
					{
						$SQLQuery['Comment'][$SQLQueryIndex]="Ограничений нет.";
						$SQLQuery['Script'][$SQLQueryIndex]=NULL;
						$SQLQuery['Color'][$SQLQueryIndex]='000000';
						$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
						$SQLQueryIndex++;
						$SQLTemp = "";
					}
				}
			}
			else //в базе slave нет таблицы, которая есть в master
			{
				$SQLQuery['Error'][$SQLQueryErrorIndex]="В базе $SlaveBazeName нет таблицы $MasterTableList[$Index], которая есть в базе $MasterBazeName. Сравнение $ConstraintName ограничений не проводилось.";
				$SQLQueryErrorIndex++;
			}

		}
		return $SQLQuery;
	}
	//производим сравнение триггеров в базах
	public function CompareTableTriggers($DBWorkObject, $MasterBazeName, $SlaveBazeName)
	{
		assert(is_string($MasterBazeName) && ($MasterBazeName!='') && is_string($SlaveBazeName) && ($SlaveBazeName)
		&& ($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));

		$SQLQuery=array();
		$SQLQueryIndex=0;
		$SQLQueryErrorIndex=0;

		$MasterTableList=TableSQLQuery::GetDS_TablesNameList ($DBWorkObject, $MasterBazeName); //список имен таблиц базы master
		if (($MasterTableList==NULL) or (count($MasterTableList)==0))
		{
			Errors::ErrorMessage("Не удалось получить список таблиц для базы $MasterBazeName при сравнении триггеров");
		}
		$SlaveTableList=TableSQLQuery::GetDS_TablesNameList ($DBWorkObject, $SlaveBazeName); //список имен таблиц базы slave
		if (($SlaveTableList==NULL) or (count($SlaveTableList)==0))
		{
			Errors::ErrorMessage("Не удалось получить список таблиц для базы $SlaveBazeName при сравнении триггеров");
		}
		for ($Index=0; $Index<count($MasterTableList); $Index++) //сравниваем списки таблиц двух баз
		{
			if (in_array($MasterTableList[$Index],$SlaveTableList)) //таблица с данным именем существует в обеих базах, начинаем сравнение триггеров
			{
				$SQLQuery['Comment'][$SQLQueryIndex]="Работаем с таблицей $MasterTableList[$Index]";
				$SQLQuery['Script'][$SQLQueryIndex]=NULL;
				$SQLQuery['Color'][$SQLQueryIndex]='000000';
				$SQLQuery['Header'][$SQLQueryIndex]=TRUE;
				$SQLQueryIndex++;
				
				//получаем списки триггеров для таблицы в обеих базах
				$MasterTableTriggerNameList=TriggerSQLQuery::GetTableTriggerNameList($DBWorkObject, $MasterTableList[$Index], $MasterBazeName);
				$SlaveTableTriggerNameList=TriggerSQLQuery::GetTableTriggerNameList($DBWorkObject, $MasterTableList[$Index], $SlaveBazeName);

				if (count($MasterTableTriggerNameList)==0) //в master нет триггеров
				{
					if (count($SlaveTableTriggerNameList)==0) //в slave нет триггеров - ничего не делаем
					{
						$SQLQuery['Comment'][$SQLQueryIndex]="Триггеры отсутствуют.";
						$SQLQuery['Script'][$SQLQueryIndex]=NULL;
						$SQLQuery['Color'][$SQLQueryIndex]='000000';
						$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
						$SQLQueryIndex++;
					}
					else //в slave есть триггреы - удаляем их все
					{
						for ($Index1=0; $Index1<count($SlaveTableTriggerNameList); $Index1++)
						{
							$SQLQuery['Comment'][$SQLQueryIndex]="Удаляем триггер $SlaveTableTriggerNameList[$Index1].";
							$SQLQuery['Script'][$SQLQueryIndex]="DROP TRIGGER $SlaveTableTriggerNameList[$Index1] on $MasterTableList[$Index] CASCADE;";
							$SQLQuery['Color'][$SQLQueryIndex]='ff0000';
							$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
							$SQLQueryIndex++;
						}
					}
				}
				else //в master есть триггеры
				{
					if (count($SlaveTableTriggerNameList)==0) //в slave нет триггеров - создаем для slave все триггеры из master
					{
						for ($Index1=0; $Index1<count($MasterTableTriggerNameList);$Index1++)
						{
							$MasterTriggerDefinition=TriggerSQLQuery::GetTriggerDefinitionStr($DBWorkObject, $MasterTableTriggerNameList[$Index1], $MasterTableList[$Index], $MasterBazeName);
							if($MasterTriggerDefinition==NULL)
							{
								Errors::ErrorMessage("Не удалось получить текстовое определение триггера $MasterTableTriggerNameList[$Index1] таблицы $MasterTableList[$Index] базы $MasterBazeName");
							}
							$SQLQuery['Comment'][$SQLQueryIndex]="Создаем триггер $MasterTableTriggerNameList[$Index1].";
							$SQLQuery['Script'][$SQLQueryIndex]=$MasterTriggerDefinition.";";
							$SQLQuery['Color'][$SQLQueryIndex]='8B008B';
							$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
							$SQLQueryIndex++;
						}
					}
					else //в slave есть триггреы - производим сравнение
					{     //удаляем из slave триггеры, которых нет в master
						for ($Index1=0; $Index1<count($SlaveTableTriggerNameList); $Index1++)
						{
							if (!in_array($SlaveTableTriggerNameList[$Index1],$MasterTableTriggerNameList))
							{
								$SQLQuery['Comment'][$SQLQueryIndex]="Удаляем триггер $SlaveTableTriggerNameList[$Index1].";
								$SQLQuery['Script'][$SQLQueryIndex]="DROP TRIGGER $SlaveTableTriggerNameList[$Index1] on $MasterTableList[$Index] CASCADE;";
								$SQLQuery['Color'][$SQLQueryIndex]='ff0000';
								$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
								$SQLQueryIndex++;
							}
						}
						//создаем отсутствующие в slave триггеры, которые есть в master
						for ($Index1=0; $Index1<count($MasterTableTriggerNameList);$Index1++)
						{
							//получаем текстовое определение триггра для таблицы в базе master
							$MasterTriggerDefinition=TriggerSQLQuery::GetTriggerDefinitionStr($DBWorkObject, $MasterTableTriggerNameList[$Index1], $MasterTableList[$Index], $MasterBazeName);
							if($MasterTriggerDefinition==NULL)
							{
								Errors::ErrorMessage("Не удалось получить текстовое определение триггера $MasterTableTriggerNameList[$Index1] таблицы $MasterTableList[$Index] базы $MasterBazeName");
							}
							if (in_array($MasterTableTriggerNameList[$Index1],$SlaveTableTriggerNameList)) //триггер с таким именем есть в таблицах обеих баз
							{
								//получаем текстовое определение триггра для таблицы в базе slave
								$SlaveTriggerDefinition=TriggerSQLQuery::GetTriggerDefinitionStr($DBWorkObject, $MasterTableTriggerNameList[$Index1], $MasterTableList[$Index], $SlaveBazeName);
								if ($SlaveTriggerDefinition==NULL)
								{
									Errors::ErrorMessage("Не удалось получить текстовое определение триггера $MasterTableTriggerNameList[$Index1] таблицы $MasterTableList[$Index] базы $SlaveBazeName");
								}
								if ($MasterTriggerDefinition!=$SlaveTriggerDefinition)
								{
									$SQLQuery['Comment'][$SQLQueryIndex]="Обновляем определение триггера $MasterTableTriggerNameList[$Index1].";
									$SQLQuery['Script'][$SQLQueryIndex]="DROP TRIGGER $MasterTableNameIndexList[$Index1] on $MasterTableList[$Index] CASCADE; \n".$MasterTriggerDefinition.";";
									$SQLQuery['Color'][$SQLQueryIndex]='0000ff';
									$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
									$SQLQueryIndex++;
								}
								else
								{
									$SQLQuery['Comment'][$SQLQueryIndex]="Триггер $MasterTableTriggerNameList[$Index1] => OK.";
									$SQLQuery['Script'][$SQLQueryIndex]=NULL;
									$SQLQuery['Color'][$SQLQueryIndex]='000000';
									$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
									$SQLQueryIndex++;
								}
							}
							else //триггер есть в master, но его нет в slave, делаем запрос на его создание
							{
								$SQLQuery['Comment'][$SQLQueryIndex]="Создаем триггер $MasterTableTriggerNameList[$Index1].";
								$SQLQuery['Script'][$SQLQueryIndex]=$MasterTriggerDefinition.";";
								$SQLQuery['Color'][$SQLQueryIndex]='8B008B';
								$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
								$SQLQueryIndex++;
							}
						}
					}
				}
			}
			else //в master есть таблица, которой нет в slave
			{
				$SQLQuery['Error'][$SQLQueryErrorIndex]="В базе $SlaveBazeName нет таблицы $MasterTableList[$Index], которая есть в базе $MasterBazeName. Сравнение триггеров не проводилось.";
				$SQLQueryErrorIndex++;
			}
		}
		return $SQLQuery;
	}
	//сравниваем индексы в обеих базах
	public function CompareTableIndexes($DBWorkObject, $MasterBazeName, $SlaveBazeName)
	{
		$TempArray=array();
		
		assert(is_string($MasterBazeName) && ($MasterBazeName!=''));
		assert(is_string($SlaveBazeName) && ($SlaveBazeName!=''));
		assert(($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));

		$SQLQuery=array();
		$SQLQueryIndex=0;
		$SQLQueryErrorIndex=0;

		$MasterTableList=TableSQLQuery::GetDS_TablesNameList ($DBWorkObject, $MasterBazeName); //список имен таблиц базы master
		if (($MasterTableList==NULL) or (count($MasterTableList)==0))
		{
			Errors::ErrorMessage("Не удалось получить список таблиц для базы $MasterBazeName при сравнении индексов");
		}
		$SlaveTableList=TableSQLQuery::GetDS_TablesNameList ($DBWorkObject, $SlaveBazeName); //список имен таблиц базы slave
		if (($SlaveTableList==NULL) or (count($SlaveTableList)==0))
		{
			Errors::ErrorMessage("Не удалось получить список таблиц для базы $SlaveBazeName при сравнении индексов");
		}
		
		for ($Index=0; $Index<count($MasterTableList); $Index++) //сравниваем списки таблиц двух баз
		{
			$SlaveIndexDescr=array();
			$MasterIndexDescr=array();
			
			if (in_array($MasterTableList[$Index],$SlaveTableList)) //таблица с данным именем существует в обеих базах, начинаем сравнение индексов
			{
				$SQLQuery['Comment'][$SQLQueryIndex]="Работаем с таблицей $MasterTableList[$Index]";

				$SQLQuery['Script'][$SQLQueryIndex]=NULL;
				$SQLQuery['Color'][$SQLQueryIndex]='000000';
				$SQLQuery['Header'][$SQLQueryIndex]=TRUE;
				$SQLQueryIndex++;
				//получаем списки индексов для таблицы обеих баз
				$MasterTableNameIndexList=IndexSQLQuery::GetTableIndexNameList ($DBWorkObject, $MasterTableList[$Index], $MasterBazeName);
				$SlaveTableNameIndexList=IndexSQLQuery::GetTableIndexNameList ($DBWorkObject, $MasterTableList[$Index], $SlaveBazeName);

				//$SlaveTableDescrIndexList=IndexSQLQuery::GetIndexShortDefinitionStr();
				
				if(isset($MasterTableNameIndexList))
				{
					foreach($MasterTableNameIndexList as $key=>$value)
					{
						$TempArray=IndexSQLQuery::GetIndexShortDefinitionStr($DBWorkObject, $value, $MasterTableList[$Index],$MasterBazeName);
						$MasterIndexDescr=array_merge($MasterIndexDescr, $TempArray);
					}
				}

				if(isset($SlaveTableNameIndexList))
				{
					foreach($SlaveTableNameIndexList as $key=>$value)
					{
						$TempArray=IndexSQLQuery::GetIndexShortDefinitionStr($DBWorkObject, $value, $MasterTableList[$Index],$SlaveBazeName);
						$SlaveIndexDescr=array_merge($SlaveIndexDescr, $TempArray);
					}
				}

				$SlaveKeys=array_keys($SlaveIndexDescr);
				sort($SlaveKeys);
				$inSlaveCount=count($SlaveKeys);
//print_r($MasterIndexDescr);
				
				$MasterKeys=array_keys($MasterIndexDescr);
				sort($MasterKeys);
				$inMasterCount=count($MasterIndexDescr);
			
				if ($inMasterCount==0) //в master нет индексов
				{
					if ($inSlaveCount==0) //в slave нет индексов - выводим информационное сообщение
					{
						$SQLQuery['Comment'][$SQLQueryIndex]="Индексы отсутствуют.";
						$SQLQuery['Script'][$SQLQueryIndex]=NULL;
						$SQLQuery['Color'][$SQLQueryIndex]='000000';
						$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
						$SQLQueryIndex++;

					}
					else //в slave есть индексы - удаляем все индексы из slave
					{
						for ($Index1=0; $Index1<$inSlaveCount; $Index1++)
						{
							$SQLQuery['Comment'][$SQLQueryIndex]="Удаляем индекс $SlaveTableNameIndexList[$Index1].";
							$SQLQuery['Script'][$SQLQueryIndex]="DROP INDEX $SlaveTableNameIndexList[$Index1] CASCADE;";
							$SQLQuery['Color'][$SQLQueryIndex]='ff0000';
							$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
							$SQLQueryIndex++;
						}
					}
				}
				else //в master есть индексы
				{
					if ($inSlaveCount==0) //в slave нет индексов - создаем для slave все индексы из master
					{
						for ($Index1=0; $Index1<$inMasterCount; $Index1++)
						{
							//$MasterIndexDefinition=IndexSQLQuery::GetIndexDefinitionStr($DBWorkObject, $MasterTableNameIndexList[$Index1], $MasterTableList[$Index], $MasterBazeName);

							/*if($MasterIndexDefinition==NULL)
							{
								Errors::ErrorMessage("Не удалось получить текстовое определение индекса $MasterTableNameIndexList[$Index1] таблицы $MasterTableList[$Index] базы $MasterBazeName");
							}*/
							$KeyForNew=$MasterKeys[$Index1];
							$SQLQuery['Comment'][$SQLQueryIndex]="Создаем индекс ".$MasterIndexDescr[$KeyForNew][1];
							$SQLQuery['Script'][$SQLQueryIndex]=$MasterIndexDescr[$KeyForNew][0].";";
							$SQLQuery['Color'][$SQLQueryIndex]='8B008B';
							$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
							$SQLQueryIndex++;

						}
					}
					else //в slave есть индексы - производим сравнение таблиц двух баз
					{ 				//удаляем индексы, которые есть в slave, но нет в master
						for($Index1=0; $Index1<$inSlaveCount; $Index1++)
						{
//echo $SlaveKeys[$Index1] ."we work with<br>";
							if(!key_exists($SlaveKeys[$Index1], $MasterIndexDescr))
							{

								$KeyForNew=$SlaveKeys[$Index1];
								$SQLQuery['Comment'][$SQLQueryIndex]="Удаляем индекс ". $SlaveIndexDescr[$KeyForNew][1];

								$SQLQuery['Script'][$SQLQueryIndex]="DROP INDEX ".$SlaveIndexDescr[$KeyForNew][1]." CASCADE;";
								$SQLQuery['Color'][$SQLQueryIndex]='ff0000';
								$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
								$SQLQueryIndex++;
								unset($SlaveKeys[$Index1]);
								sort($SlaveKeys);
								$inSlaveCount=count($SlaveKeys);
//echo $SlaveKeys[$Index1]. "- index ".$SlaveIndexDescr[$KeyForNew][1]." is not in master, we drop it!<br>";							
							}
						}
						
//						for ($Index1=0; $Index1<count($SlaveTableNameIndexList); $Index1++)
//						{
//							if (!in_array($SlaveTableNameIndexList[$Index1],$MasterTableNameIndexList))
//							{
//								$SQLQuery['Comment'][$SQLQueryIndex]="Удаляем индекс $SlaveTableNameIndexList[$Index1].";
//								$SQLQuery['Script'][$SQLQueryIndex]="DROP INDEX $SlaveTableNameIndexList[$Index1] CASCADE;";
//								$SQLQuery['Color'][$SQLQueryIndex]='ff0000';
//								$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
//								$SQLQueryIndex++;
//							}
//						}
						//сравниваем индексы, которые есть в таблицах обеих баз
						for ($Index1=0; $Index1<$inMasterCount;$Index1++)
						{
							//получаем текстовое определение индекса для таблицы в базе master
							/*$MasterIndexDefinition=IndexSQLQuery::GetIndexDefinitionStr($DBWorkObject, $MasterTableNameIndexList[$Index1], $MasterTableList[$Index], $MasterBazeName);
							if($MasterIndexDefinition==NULL)
							{
								Errors::ErrorMessage("Не удалось получить текстовое определение индекса $MasterTableNameIndexList[$Index1] таблицы $MasterTableList[$Index] базы $MasterBazeName");
							}*/
							//индекс с таким именем есть и в slave, и в master
							if (key_exists($MasterKeys[$Index1], $SlaveIndexDescr))
							{							//получаем текстовое определение индекса для таблицы в базе slave
//								$SlaveIndexDefinition=IndexSQLQuery::GetIndexDefinitionStr($DBWorkObject, $MasterTableNameIndexList[$Index1], $MasterTableList[$Index], $SlaveBazeName);
//								if ($SlaveIndexDefinition==NULL)
//								{
//									Errors::ErrorMessage("Не удалось получить текстовое определение индекса $MasterTableNameIndexList[$Index1] таблицы $MasterTableList[$Index] базы $SlaveBazeName");
//								}
//								if ($MasterIndexDefinition!=$SlaveIndexDefinition) //текстовые определения индексов различны
//								{
//									$SQLQuery['Comment'][$SQLQueryIndex]="Обновляем определение индекса $MasterTableNameIndexList[$Index1].";
//									$SQLQuery['Script'][$SQLQueryIndex]="DROP INDEX $MasterTableNameIndexList[$Index1] CASCADE; \n".$MasterIndexDefinition.";";
//									$SQLQuery['Color'][$SQLQueryIndex]='0000ff';
//									$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
//									$SQLQueryIndex++;
//								}
//								else //текстовые определения индексов одинаковы - делаем инф. сообщение
//								{
									$KeyForNew=	$MasterKeys[$Index1];						
									$SQLQuery['Comment'][$SQLQueryIndex]="Индекс ". $MasterIndexDescr[$KeyForNew][1] ."=> OK.";
									$SQLQuery['Script'][$SQLQueryIndex]=NULL;
									$SQLQuery['Color'][$SQLQueryIndex]='8B008B';
									$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
									$SQLQueryIndex++;
//echo $SlaveKeys[$Index1]. "- index ".$SlaveIndexDescr[$KeyForNew][1]." is OK<br>";	
							}
							else //индекс есть в master, но нет в slave - создаем его
							{
								$KeyForNew=$MasterKeys[$Index1];
								$SQLQuery['Comment'][$SQLQueryIndex]="Создаем индекс ". $MasterIndexDescr[$KeyForNew][1];
								$SQLQuery['Script'][$SQLQueryIndex]=$MasterIndexDescr[$KeyForNew][0].";";
								$SQLQuery['Color'][$SQLQueryIndex]='8B008B';
								$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
								$SQLQueryIndex++;
//echo $SlaveKeys[$Index1]. "- index ".$SlaveIndexDescr[$KeyForNew][1]." is not in master and we create it<br>";	
							}
						}
					}
				}
			}
			else //в master есть таблица, которой нет в slave
			{
				$SQLQuery['Error'][$SQLQueryErrorIndex]="В базе $SlaveBazeName нет таблицы $MasterTableList[$Index], которая есть в базе $MasterBazeName. Сравнение триггеров не проводилось.";
				$SQLQueryErrorIndex++;
			}
			$SlaveIndexDescr='';
			$MasterIndexDescr='';
		}
		return $SQLQuery;
	}



	//сравниваем функции обеих баз
	public function CompareTableFunction($DBWorkObject, $MasterBazeName, $SlaveBazeName)
	{
		assert(is_string($MasterBazeName) && ($MasterBazeName!=''));
		assert(is_string($SlaveBazeName) && ($SlaveBazeName!=''));
		assert(($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));

		$SQLQuery=array();
		$SQLQueryIndex=0;
		$SQLQueryErrorIndex=0;
		//получаем список ID функций базы master
		$MasterFunctionIDList=FunctionSQLQuery::GetFunctionIDList($DBWorkObject, $MasterBazeName);
		//получаем список ID функций базы slave
		$SlaveFunctionIDList=FunctionSQLQuery::GetFunctionIDList($DBWorkObject, $SlaveBazeName);

		if (count($MasterFunctionIDList)==0) //в master нет функций
		{
			if (count($SlaveFunctionIDList)==0) //в slave нет функций
			{
				$SQLQuery['Comment'][$SQLQueryIndex]="В базах функции отсутствуют.";
				$SQLQuery['Script'][$SQLQueryIndex]=NULL;
				$SQLQuery['Color'][$SQLQueryIndex]='000000';
				$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
				$SQLQueryIndex++;
			}
			else //в slave есть функции, их все удаляем
			{
				//получаем заголовки функций для базы slave
				for ($Index=0;$Index<count($SlaveFunctionIDList);$Index++)
				{
					$Temp=FunctionSQLQuery::GetFunctionHeader($DBWorkObject, $SlaveFunctionIDList[$Index], $SlaveBazeName);
					if (($Temp==NULL) or ($Temp==''))
					{
						Errors::ErrorMessage("Получен недопустимый заголовок для функции с ID $SlaveFunctionIDList[$Index] базы $SlaveBazeName.");
					}
					$SlaveFunctionHeader[$SlaveFunctionIDList[$Index]]=$Temp;
				}
				//удаляем из slave все функции, которых нет в master
				foreach ($SlaveFunctionHeader as $Value)
				{
					$SQLQuery['Comment'][$SQLQueryIndex]="Удаляем функцию $Value.";
					$SQLQuery['Script'][$SQLQueryIndex]="DROP $Value CASCADE;";
					$SQLQuery['Color'][$SQLQueryIndex]='ff0000';
					$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
					$SQLQueryIndex++;
				}
			}
		}
		else //в master есть функции
		{
			if (count($SlaveFunctionIDList)==0) //в slave нет функций - создаем все функции из master
			{
				//получаем заголовки функций для базы master
				for ($Index=0;$Index<count($MasterFunctionIDList);$Index++)
				{
					$Temp=FunctionSQLQuery::GetFunctionHeader($DBWorkObject, $MasterFunctionIDList[$Index], $MasterBazeName);
					if (($Temp==NULL) or ($Temp==''))
					{
						Errors::ErrorMessage("Получен недопустимый заголовок для функции с ID $MasterFunctionIDList[$Index] базы $MasterBazeName.");
					}
					$MasterFunctionHeader[$MasterFunctionIDList[$Index]]=$Temp;
				}
				foreach ($MasterFunctionHeader as $Key => $Value)
				{
					//получаем определение для функции из master
					$MasterFunctionDefinition=FunctionSQLQuery::GetFunctionDefinition($DBWorkObject, "$Key", $MasterBazeName);
					if (($MasterFunctionDefinition==NULL) or ($MasterFunctionDefinition==''))
					{
						Errors::ErrorMessage("Получено недопустимое описание для функции $Value базы $MasterBazeName");
					}
					$SQLQuery['Comment'][$SQLQueryIndex]="Создаем функцию $Value.";
					$SQLQuery['Script'][$SQLQueryIndex]="CREATE $Value $MasterFunctionDefinition;";
					$SQLQuery['Color'][$SQLQueryIndex]='8B008B';
					$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
					$SQLQueryIndex++;
				}
			}
			else //в slave есть функции, производим сравнение
			{
				//получаем заголовки функций для базы slave
				for ($Index=0;$Index<count($SlaveFunctionIDList);$Index++)
				{
					$Temp=FunctionSQLQuery::GetFunctionHeader($DBWorkObject, $SlaveFunctionIDList[$Index], $SlaveBazeName);
					if (($Temp==NULL) or ($Temp==''))
					{
						Errors::ErrorMessage("Получен недопустимый заголовок для функции с ID $SlaveFunctionIDList[$Index] базы $SlaveBazeName.");
					}
					$SlaveFunctionHeader[$SlaveFunctionIDList[$Index]]=$Temp;
				}
				//получаем заголовки функций для базы master
				for ($Index=0;$Index<count($MasterFunctionIDList);$Index++)
				{
					$Temp=FunctionSQLQuery::GetFunctionHeader($DBWorkObject, $MasterFunctionIDList[$Index], $MasterBazeName);
					if (($Temp==NULL) or ($Temp==''))
					{
						Errors::ErrorMessage("Получен недопустимый заголовок для функции с ID $MasterFunctionIDList[$Index] базы $MasterBazeName.");
					}
					$MasterFunctionHeader[$MasterFunctionIDList[$Index]]=$Temp;
				}

				//удаляем из slave все функции, которых нет в master
				foreach ($SlaveFunctionHeader as $Value)
				{
					if (!in_array($Value, $MasterFunctionHeader))
					{
						$SQLQuery['Comment'][$SQLQueryIndex]="Удаляем функцию $Value.";
						$SQLQuery['Script'][$SQLQueryIndex]="DROP $Value CASCADE;";
						$SQLQuery['Color'][$SQLQueryIndex]='ff0000';
						$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
						$SQLQueryIndex++;
					}
				}
				
				//сравниваем существующие функции в master и slave
				foreach ($MasterFunctionHeader as $Key => $Value)
				{
					//получаем определение для функции из master
					$MasterFunctionDefinition=FunctionSQLQuery::GetFunctionDefinition($DBWorkObject, "$Key", $MasterBazeName);
					if (($MasterFunctionDefinition==NULL) or ($MasterFunctionDefinition==''))
					{
						Errors::ErrorMessage("Получено недопустимое описание для функции $Value базы $MasterBazeName");
					}
					if (in_array($Value, $SlaveFunctionHeader)) //функция с таким заголовком есть в обеих базах, сравниваем определение
					{
						$SlaveFunctionID=array_search($Value, $SlaveFunctionHeader);
						if (($SlaveFunctionID==NULL) or ($SlaveFunctionID==''))
						{
							Errors::ErrorMessage("Не смог восстановить из массива ID функции $Value базы $SlaveBazeName");
						}
						$SlaveFunctionDefinition=FunctionSQLQuery::GetFunctionDefinition($DBWorkObject, "$SlaveFunctionID", $SlaveBazeName);

						if (($SlaveFunctionDefinition==NULL) or ($SlaveFunctionDefinition==''))
						{
							Errors::ErrorMessage("Получено недопустимое описание для функции $Value базы $SlaveBazeName");
						}
						
						//Преобразуем определения функфий для сравнения
						$MasterFunction = self::replase($MasterFunctionDefinition);
						$SlaveFunction = self::replase($SlaveFunctionDefinition);

						if ($SlaveFunction == $MasterFunction) //описания функций одинаковы
						{
							$SQLQuery['Comment'][$SQLQueryIndex]="Функция $Value => OK.";
							$SQLQuery['Script'][$SQLQueryIndex]=NULL;
							$SQLQuery['Color'][$SQLQueryIndex]='000000';
							$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
							$SQLQueryIndex++;
						}
						else //описания функций различны, удаляем существующую функцию и создаем новую
						{
							$SQLQuery['Comment'][$SQLQueryIndex]="Обновляем определение функции $Value.";
							$SQLQuery['Script'][$SQLQueryIndex]="DROP $Value CASCADE; \n CREATE $Value $MasterFunctionDefinition;";
							$SQLQuery['Color'][$SQLQueryIndex]='0000ff';
							$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
							$SQLQueryIndex++;
						}
					}
					else //функция есть в master, но ее нет в slave - создаем ее
					{
						$SQLQuery['Comment'][$SQLQueryIndex]="Создаем функцию $Value.";
						$SQLQuery['Script'][$SQLQueryIndex]="CREATE $Value $MasterFunctionDefinition;";
						$SQLQuery['Color'][$SQLQueryIndex]='8B008B';
						$SQLQuery['Header'][$SQLQueryIndex]=FALSE;
						$SQLQueryIndex++;
					}
				}
			}
		}
		return $SQLQuery;
	}
	
	//сравниваем данные в таблицах в обеих базах
	public function CompareTableData($DBWorkObject, $MasterBazeName, $SlaveBazeName)
	{
		assert(is_string($MasterBazeName) && ($MasterBazeName!=''));
		assert(is_string($SlaveBazeName) && ($SlaveBazeName!=''));
		assert(($DBWorkObject instanceof Connection) && ($DBWorkObject != NULL));
		
		$SQLQuery=array();
		$SQLQueryIndex=0;
		$SQLQueryErrorIndex=0;
		
		$MasterTableList=TableSQLQuery::GetDS_TablesNameList ($DBWorkObject, $MasterBazeName); //список имен таблиц базы master
		if (($MasterTableList==NULL) or (count($MasterTableList)==0))
		{
			Errors::ErrorMessage("Не удалось получить список таблиц для базы $MasterBazeName при сравнении данных в таблицах");
		}
		$SlaveTableList=TableSQLQuery::GetDS_TablesNameList ($DBWorkObject, $SlaveBazeName); //список имен таблиц базы slave
		if (($SlaveTableList==NULL) or (count($SlaveTableList)==0)) 
		{
			Errors::ErrorMessage("Не удалось получить список таблиц для базы $SlaveBazeName при сравнении данных в таблицах");
		}
		
		for ($Index=0; $Index<count($MasterTableList); $Index++) //сравниваем списки таблиц двух баз
		{
			
			if (in_array($MasterTableList[$Index],$SlaveTableList)) //таблица с данным именем существует в обеих базах, начинаем сравнение данных
			{														
				$SQLQuery['Comment'][$SQLQueryIndex]="Работаем с таблицей $MasterTableList[$Index]";
				$SQLQuery['Script'][$SQLQueryIndex]=NULL;
				$SQLQuery['Color'][$SQLQueryIndex]='000000';
				$SQLQuery['Header'][$SQLQueryIndex]=TRUE;
				$SQLQueryIndex++;	

				//Выбираем все данные из таблицы Master
				$TableDataMaster = ColumnSQLQuery::GetTableData($DBWorkObject, $MasterTableList[$Index], $MasterBazeName);

				//Выбираем все данные из таблицы Slave
				$TableDataSlaver = ColumnSQLQuery::GetTableData($DBWorkObject, $MasterTableList[$Index], $SlaveBazeName);
				
				
				//получаем списки столбцов таблицы в обеих базах
				/*$TableColumnNamesMaster=ColumnSQLQuery::GetTableColumnNameList ($DBWorkObject, $MasterTableList[$Index], $MasterBazeName);
				if (($TableColumnNamesMaster==NULL) or (count($TableColumnNamesMaster)==0))
				{
					Errors::ErrorMessage("Не удалось получить список столбцов таблицы $MasterTableList[$Index] базы $MasterBazeName");
				}
				$TableColumnNamesSlave=ColumnSQLQuery::GetTableColumnNameList ($DBWorkObject, $MasterTableList[$Index], $SlaveBazeName);
				if (($TableColumnNamesSlave==NULL) or (count($TableColumnNamesSlave)==0))
				{
					Errors::ErrorMessage("Не удалось получить список столбцов таблицы $MasterTableList[$Index] базы $SlaveBazeName");
				}*/				
				
				//Сморим данные, которые есть в слейве, но нет на мастере, и удаляем их
				for ($i =1; $i < count($TableDataSlaver); $i ++)
				{
					if (empty($TableDataMaster) || !key_exists($TableDataSlaver[$i], $TableDataMaster))
					{
						$SQLQuery['Comment'][$SQLQueryIndex]="Удаляем данные из таблицы ".$MasterTableList[$Index];
						$SQLQuery['Script'][$SQLQueryIndex]="DELETE FROM ".$MasterTableList[$Index]." DELETE FROM tablename WHERE ";
						$SQLQuery['Color'][$SQLQueryIndex]='ff0000';
						
						$valuesArr  = explode(',', $TableDataSlaver[$i]);
						$fieldArr = explode(',', $TableDataSlaver[0]);
						
						$and = "";
						foreach ($valuesArr as $k => $data)
						{
							if (!key_exists($k, $fieldArr))
							{
								Errors::ErrorMessage("Количество значений не совпадает с количеством полей в таблице ".$MasterTableList[$Index]);	
							}
							$data = trim($data);
							$SQLQuery['Script'][$SQLQueryIndex] .= " $and ".$fieldArr[$k]." = $data";
							$and = ' AND ';
						}
						
						$SQLQuery['Script'][$SQLQueryIndex] .= ";";
						$SQLQueryIndex++;
					}
				}
				
				//Сморим данные, которые есть в мастере, но нет в слейве, и добавляем их
				for ($i =1; $i < count($TableDataMaster); $i ++)
				{
					if (empty($TableDataSlaver) || !key_exists($TableDataMaster[$i], $TableDataSlaver))
					{
						$SQLQuery['Comment'][$SQLQueryIndex]="Добавляем данные в таблицу ".$MasterTableList[$Index];
						$SQLQuery['Script'][$SQLQueryIndex]="INSERT INTO ".$MasterTableList[$Index]." (".$TableDataMaster[0].") VALUES (".$TableDataMaster[$i].");";
						$SQLQuery['Color'][$SQLQueryIndex]='009900';
						$SQLQueryIndex++;
					}
				}


if ($Index > 30)
{
	echo "END";
	return $SQLQuery;
}
							
				
				
				
			}
			else //в master есть таблица, которой нет в slave
			{
				$SQLQuery['Error'][$SQLQueryErrorIndex]="В базе $SlaveBazeName нет таблицы $MasterTableList[$Index], которая есть в базе $MasterBazeName. Сравнение данных не проводилось.";				
				$SQLQueryErrorIndex++;
			}
		}
		return $SQLQuery;
	}
	
	private function replase($string)
	{
		$str = str_replace ("\r", " ", $string);
		$str = str_replace ("\n", " ", $str);
		$sesult = ereg_replace (" +", " ", $str);
		
		return $sesult;
	}
	
}

?>