<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_CompareDataComparison
{

    //сравниваем данные в таблицах в обеих базах
    public static function CompareTableData($DBWorkObject, $MasterBazeName, $SlaveBazeName, $tables)
    {
        assert(is_string($MasterBazeName) && ($MasterBazeName!=''));
        assert(is_string($SlaveBazeName) && ($SlaveBazeName!=''));

        $SQLQuery=array();
        
        assert(array_key_exists('Tables_to_compare', $GLOBALS));

        foreach ($tables as $key => $tableName)
        {
            $tableList[$key] = $GLOBALS['Tables_to_compare'][$tableName];
            $tableList[$key]['tableName'] = $tableName;
            $key ++;
        }

        $MasterTableList=SU_CompareDataColumnSQLQuery::GetDS_TablesNameList ($DBWorkObject, $MasterBazeName); //список имен таблиц базы master

        if (($MasterTableList==NULL) or (count($MasterTableList)==0))
        {
            echo "Не удалось получить список таблиц для базы $MasterBazeName при сравнении данных в таблицах";
            exit;
        }
        $SlaveTableList=SU_CompareDataColumnSQLQuery::GetDS_TablesNameList ($DBWorkObject, $SlaveBazeName); //список имен таблиц базы slave
        if (($SlaveTableList==NULL) or (count($SlaveTableList)==0))
        {
            echo "Не удалось получить список таблиц для базы $SlaveBazeName при сравнении данных в таблицах";
            exit;
        }


        for ($Index=0; $Index<count($tableList); $Index++) //сравниваем списки таблиц двух баз
        {

            if (!in_array($tableList[$Index]['tableName'], $MasterTableList)) //таблица с данным именем не существует в мастере
            {
                $SQLQuery['Error'][]="В базе $MasterBazeName нет таблицы ".$tableList[$Index]['tableName'].". Сравнение данных не проводилось.";
                $SQLQueryErrorIndex++;
            } elseif (!in_array($tableList[$Index]['tableName'], $MasterTableList)) //таблица с данным именем не существует в слейве
            {
                $SQLQuery['Error'][]="В базе $SlaveBazeName нет таблицы ".$tableList[$Index]['tableName'].". Сравнение данных не проводилось.";
                $SQLQueryErrorIndex++;
            } else
            {
                $SQLQueryIndex=0;

                //Выбираем все данные из таблицы Master по ключевым полям
                $TableDataMaster = SU_CompareDataColumnSQLQuery::GetTableData($DBWorkObject, $tableList[$Index]['tableName'], $MasterBazeName);

                $keyTableDataMaster = self::convertQueryResultArrayToString($TableDataMaster, $tableList[$Index]['FeildKey']);

                //Выбираем все данные из таблицы Slave
                $TableDataSlaver = SU_CompareDataColumnSQLQuery::GetTableData($DBWorkObject, $tableList[$Index]['tableName'], $SlaveBazeName);

                $keyTableDataSlaver = self::convertQueryResultArrayToString($TableDataSlaver, $tableList[$Index]['FeildKey']);

                //получаем списки столбцов таблицы в обеих базах
                $TableColumnNamesMaster=SU_CompareDataColumnSQLQuery::GetTableColumnNameList ($DBWorkObject, $tableList[$Index]['tableName'], $MasterBazeName);
                
                $TableColumnNamesSlave=SU_CompareDataColumnSQLQuery::GetTableColumnNameList ($DBWorkObject, $tableList[$Index]['tableName'], $SlaveBazeName);

                //Проверяем, есть ли вслейве все столбцы, которые есть в мастере
                $diffMaster = array_diff($TableColumnNamesMaster, $TableColumnNamesSlave);
                
                if (!empty($diffMaster))
                {
                    echo "Error: В таблице <b>".$tableList[$Index]['tableName']."</b> в базе $MasterBazeName есть столцы, которых нет в $SlaveBazeName:<br>";
                    
                    foreach ($diffMaster as $key => $fieldName)
                    {
                        echo "<b>$fieldName</b><br>";
                    }
                    exit;
                }
                
                $diffSlave = array_diff($TableColumnNamesSlave, $TableColumnNamesMaster);
                
                if (!empty($diffSlave))
                {
                    echo "Error: В таблице <b>".$tableList[$Index]['tableName']."</b> в базе $SlaveBazeName есть столцы, которых нет в $MasterBazeName:<br>";
                    
                    foreach ($diffSlave as $key => $fieldName)
                    {
                        echo "<b>$fieldName</b><br>";
                    }
                    exit;
                }
                
                $SQLQuery[$tableList[$Index]['tableName']]['fields'] = $TableColumnNamesMaster;
                $SQLQuery[$tableList[$Index]['tableName']]['countUpdate'] = 0;
                $SQLQuery[$tableList[$Index]['tableName']]['countDelete'] = 0;
                $SQLQuery[$tableList[$Index]['tableName']]['countInsert'] = 0;
            
                //Сморим данные, которые есть в слейве, но нет на мастере, и удаляем их
                foreach ($keyTableDataSlaver as $keyHahs => $data)
                {
    
                    
                    if (empty($keyTableDataMaster) || !key_exists($keyHahs, $keyTableDataMaster))
                    {
                        $and = "";
                        $where = "";

                        foreach ($data as $fieldName => $fieldValue)
                        {
                            if (is_null($fieldValue))
                            {
                                $val = 'null';
                            } else {
                                //Экранируем символы
                                $val = "'".pg_escape_string($fieldValue)."'";
                            }

                            $where .= $and."$fieldName = $val";
                            $and = " AND ";
                        }
                        
                        $SQLQuery[$tableList[$Index]['tableName']]['Data'][$SQLQueryIndex]['action'] = "DELETE";
                        $SQLQuery[$tableList[$Index]['tableName']]['Data'][$SQLQueryIndex]['data'] = $data;
                        $SQLQuery[$tableList[$Index]['tableName']]['Data'][$SQLQueryIndex]['script'] = "DELETE FROM ".$tableList[$Index]['tableName']." WHERE $where;";
                        $SQLQuery[$tableList[$Index]['tableName']]['Data'][$SQLQueryIndex]['color'] = "#FF0000";
                        $SQLQuery[$tableList[$Index]['tableName']]['countDelete'] ++;
                        $SQLQueryIndex++;
                    } else {
                        $and = "";
                        $set = "";
                        $comma = "";
                        $flag = false;

                        //Если такие данные есть, то проверяем на соответствие остальные столбцы
                        foreach ($data as $fieldName => $fieldValue)
                        {
                            if (!key_exists($fieldName, $keyTableDataMaster[$keyHahs]))
                            {
                                echo "В таблице ".$tableList[$Index]['tableName']." нет поля $fieldName";
                                exit;
                            }

                            if (stripcslashes($fieldValue) != stripcslashes($keyTableDataMaster[$keyHahs][$fieldName]))
                            {
                                if (is_null($keyTableDataMaster[$keyHahs][$fieldName]))
                                {
                                    $val = 'null';
                                } else {
                                    //Экранируем символы
                                    $val = "'".pg_escape_string($keyTableDataMaster[$keyHahs][$fieldName])."'";
                                }

                                
                            /*  if ($fieldName != 'artefactpower') {
                                    continue;
                                }*/
                                $set .= $comma." $fieldName = $val";
                                $flag = true;
                                $comma = ',';
                            }
                        }

                        if ($flag == true)
                        {
                            $and = "";
                            $where = "";

                            foreach ($tableList[$Index]['FeildKey'] as $key => $field)
                            {
                                if (is_null($data[$field]))
                                {
                                    $val = 'null';
                                } else {
                                    //Экранируем одинарные кавычки
                                    $val = "'".addcslashes(str_replace("\'", "'", $data[$field]), "'")."'";
                                }


                                $where  .= " $and $field = $val";
                                $and = " AND ";
                            }

                            //Если мы нашли несоответствие, то обновляем таблицу
                            $SQLQuery[$tableList[$Index]['tableName']]['Data'][$SQLQueryIndex]['action'] = "UPDATE";
                            $SQLQuery[$tableList[$Index]['tableName']]['Data'][$SQLQueryIndex]['data'] = $data;
                            $SQLQuery[$tableList[$Index]['tableName']]['Data'][$SQLQueryIndex]['oldData'] = $keyTableDataMaster[$keyHahs];
                            $SQLQuery[$tableList[$Index]['tableName']]['Data'][$SQLQueryIndex]['script'] = "UPDATE ".$tableList[$Index]['tableName']." SET $set WHERE $where;";
                            $SQLQuery[$tableList[$Index]['tableName']]['Data'][$SQLQueryIndex]['color'] = "#0000FF";
                            $SQLQuery[$tableList[$Index]['tableName']]['countUpdate'] ++;
                            $SQLQueryIndex++;
                        }
                    }
                }

                //$count = 0;
                //Сморим данные, которые есть в мастере, но нет в слейве, и добавляем их
                foreach ($keyTableDataMaster as $keyHahs => $data)
                {
                    if (empty($keyTableDataSlaver) || !key_exists($keyHahs, $keyTableDataSlaver))
                    {
                        /*$count ++;
                        if ($count > 1000) {
                            ksort($SQLQuery);
                            return $SQLQuery;
                        }*/
                        
                        $fn = "";
                        $fv = "";
                        $comma = "";
                        $tdF = "";
                        $tdV = "";

                        foreach ($data as $fielfName => $fielfValue)
                        {
                            if (is_null($fielfValue))
                            {
                                $val = 'null';
                            } else {
                                //Экранируем одинарные кавычки
                                $val = "'".pg_escape_string(addcslashes(str_replace("\'", "'", $fielfValue), "'"))."'";
                            }

                            $fn .= $comma." $fielfName";
                            $fv .= $comma." $val";

                            $comma = ",";
                        }

                        $SQLQuery[$tableList[$Index]['tableName']]['Data'][$SQLQueryIndex]['action'] = "INSERT";
                        $SQLQuery[$tableList[$Index]['tableName']]['Data'][$SQLQueryIndex]['data'] = $data;
                        $SQLQuery[$tableList[$Index]['tableName']]['Data'][$SQLQueryIndex]['script'] = "INSERT INTO ".$tableList[$Index]['tableName']." ($fn) VALUES ($fv);";
                        $SQLQuery[$tableList[$Index]['tableName']]['Data'][$SQLQueryIndex]['color'] = "#009900";
                        $SQLQuery[$tableList[$Index]['tableName']]['countInsert'] ++;
                        $SQLQueryIndex++;
                    }
                }
            }

        }
        
        ksort($SQLQuery);
/*echo "<pre>";
print_r($SQLQuery);
echo "</pre>";
exit;*/

        return $SQLQuery;
    }

    private static function convertQueryResultArrayToString($array, $keyFields)
    {
        $resArr = '';
        $k = "";
        $delimiter = "";

        foreach ($array as $key => $data)
        {
            foreach ($data as $fieldName => $fieldValue)
            {
                if (in_array($fieldName, $keyFields))
                {
                    $k .= "$delimiter$fieldValue";

                    $delimiter = '#';
                }
            }

            $resArr[$k] = $data;

            $k = '';
            $delimiter = '';
        }

        return $resArr;
    }
}

?>