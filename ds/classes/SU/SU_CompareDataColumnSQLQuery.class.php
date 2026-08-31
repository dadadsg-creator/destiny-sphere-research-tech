<?php $CVS_Version = '$Revision$ ; $Date$';

//класс получения параметров столбцов таблицы базы
class SU_CompareDataColumnSQLQuery
{
    //функция возвращает TRUE, если  SEQUENCE существует
    public static function GetIdConnect($DBWorkObject, $TableName, $BazeName)
    {
        $SQLquery = "SELECT * FROM $TableName";

        $ResultIDConnect = $DBWorkObject->PgQuery($SQLquery, $BazeName);

        return $ResultIDConnect;
    }

    public static function GetTableData($DBWorkObject, $TableName, $BaseName)
    {
        assert(is_string($BaseName) && ($BaseName!=''));
        assert( is_string($TableName) && ($TableName!=''));
        assert($DBWorkObject != NULL);

        $SQLQuery="select * from $TableName;";
        //$SQLQuery="select id_localizedtext from ds_localizedtext";
        //$SQLQuery="select id_language, id_localizedtext, text from ds_localizedtextlanguage l where l.id_language = 1;";
        //$SQLQuery="select id_localizedtext from ds_localizedtext;";
        /*$SQLQuery="select u.id_user, u.id_language, u.username, u.email,
 u.vacationmode, u.admin, u.vacationstarttime, u.vacationstarttime, u.selfdescription,
 u.password, u.time_delta, u.activate_mail from $TableName u";*/
        /*$SQLQuery="select u.id_user, u.id_language, u.username,  --u.registrationdate,
 u.vacationmode, u.admin, u.vacationstarttime, u.vacationstarttime, u.time_delta, u.activate_mail, u.id_user_vkontakte from $TableName u
 -- ORDER BY u.id_user LIMIT 1000
 ";*/
        /*$SQLQuery = "
            select modifiername, value
            from $TableName u";
        
        if ($BaseName == 'VK_MASTER') {
            $SQLQuery .= ' where id_world = 3 ';
        }
        if ($BaseName == 'Unlim') {
            $SQLQuery .= ' where id_world = 1 ';
        }*/
        /*$SQLQuery="select u.id_user, 
 u.password from $TableName u;";*/
        //$SQLQuery="select id_artefact, artefactpower from ds_artefact";

        $ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);

        return $ResultQArray;
    }

    //получаем имена таблиц пользователя в базе
    public static function GetDS_TablesNameList ($DBWorkObject, $BaseName)
    {
        assert(is_string($BaseName) && ($BaseName!=''));
        assert($DBWorkObject != NULL);

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
    
    //функция для получения списка имен столбцов таблицы
    public static function GetTableColumnNameList ($DBWorkObject, $TableName, $BaseName)
    {
        assert(is_string($BaseName) && ($BaseName!=''));
        assert( is_string($TableName) && ($TableName!=''));
        assert($DBWorkObject != NULL);
        
        $TableID=self::GetIDTable ($DBWorkObject, $TableName, $BaseName);       
        if (!$TableID)
        {
            echo "Error: Не могу получить ID таблицы $TableName базы $BaseName при получении списка ее столбцов";
            exit;
        }
        
        $SQLQuery="select attname from pg_attribute where attrelid='$TableID';";        
        $ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);      
        if (!$ResultQArray)
        {
            echo "Error: Не удалось выполнить запрос на получение списка имен столбцов таблицы $TableName базы $BaseName";          
            exit;
        }
        
        $TableColumnsList=self::ConvertQueryResultArray($ResultQArray, "attname");
        
        if (count($TableColumnsList)==0)
        {
            echo "Error: Не смог получить список имен столбцов таблицы $TableName базы $BaseName";          
            exit;
        }       
        
        $Index1=0;
        
        for ($Index=0; $Index<count($TableColumnsList); $Index++)
        {
            if (strpos ($TableColumnsList[$Index], '.')===FALSE)
            {
                if (($TableColumnsList[$Index]==NULL) or ($TableColumnsList[$Index]==''))
                {
                    echo "Error: Получено недопустимое значение для имени столбца таблицы $TableName базы $BaseName";
                    exit;   
                }
                
                //Если столбец не является системным
                if (!self::IsColumnSystem($DBWorkObject, $TableColumnsList[$Index], $TableName, $BaseName))
                $ResultArray[$Index1]=$TableColumnsList[$Index];
                $Index1++;
            }
        }
        return $ResultArray;
    }
    
    //функция получает ID таблицы по ее имени   
    public static function GetIDTable ($DBWorkObject, $TableName, $BaseName)//Polu4enie ID tablicy po ee imeni
    {
        assert(is_string($BaseName) && ($BaseName!=''));
        assert( is_string($TableName) && ($TableName!=''));
        assert($DBWorkObject != NULL);
        
        $SQLQuery="select oid from pg_class where relname='$TableName' and relkind='r';";       
        $ResultArray=$DBWorkObject->Get($SQLQuery,$BaseName);       
        if (!$ResultArray)
        {
            echo "Error: Не удалось выполнить запрос на получение ID таблицы $TableName";           
            exit;
        }
        
        $IDTableArray=self::ConvertQueryResultArray($ResultArray, "oid");
                
        if (count($IDTableArray)!=1)
        {
            echo "Error: Не смог найти ID таблицы $TableName";
            exit;
            
        }

        if (($IDTableArray[0]==NULL) or ($IDTableArray[0]==''))
        {
            echo "Error: Получено недопустимое значение ID таблицы $TableName";
            exit;
        }
            
        return $IDTableArray[0];         
    }
    
    //преобразование ассоциативного многомерного массива ответа базы данных в обычный массив
    public static function ConvertQueryResultArray ($QueryResultArray, $FieldName)
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
    
    //функция возвращает TRUE, если столбец является системным в таблице (его создавал не пользователь)
    public static function IsColumnSystem ($DBWorkObject, $ColumnName, $TableName, $BaseName)
    {
        assert(is_string($BaseName) && ($BaseName!=''));
        assert( is_string($ColumnName) && ($ColumnName!=''));
        assert($DBWorkObject != NULL);
        
        if (self::GetNumberOfColumnFromHisName ($DBWorkObject, $ColumnName, $TableName, $BaseName)>0)
        {
            return FALSE;           //используется правило, по которому столбцы пользователя нумеруются с 1, а системные имеют номер от -1
        }                           //и меньше
        else
        {
            return TRUE;
        }
    }
    
                    //функция возвращает порядковый номер столбца в таблице по его имени (нумерация столбцов начинается с 1)
    public static function GetNumberOfColumnFromHisName ($DBWorkObject, $ColumnName, $TableName, $BaseName)    
    {
        assert(is_string($BaseName) && ($BaseName!=''));
        assert( is_string($ColumnName) && ($ColumnName!=''));
        assert($DBWorkObject != NULL);
        
        $TableID=self::GetIDTable ($DBWorkObject, $TableName, $BaseName);       
        if (!$TableID)
        {
            self::ErrorMessage("Не могу получить ID таблицы $TableName базы $BaseName при определении номера столбца таблицы по его имени");
        }
        
        $SQLQuery="select attnum from pg_attribute where attrelid='$TableID' and attname='$ColumnName';";
        $ResultQArray=$DBWorkObject->Get($SQLQuery,$BaseName);      
        if (!$ResultQArray)
        {
            self::ErrorMessage("Не удалось выполнить запрос на получение номера столбца $ColumnName таблицы $TableName базы $BaseName");            
        }
        
        $ResultArray=self::ConvertQueryResultArray($ResultQArray, "attnum");
        if (count($ResultArray)!=1)
        {
            self::ErrorMessage("Не смог получить номер столбца $ColumnName таблицы $TableName базы $BaseName");         
        }
        
        if (($ResultArray[0]==NULL) or ($ResultArray[0]==''))
        {
            self::ErrorMessage("Получено недопустимое значение номера столбца $ColumnName таблицы $TableName базы $BaseName");  
        }
        
        return $ResultArray[0];
    }
    
    public static function ErrorMessage ($Message)
    {
        assert(is_string($Message) && ($Message!=''));
        
        echo $Message, "<br>\n", "<br>\n", "<br>\n";
        
        exit;
    }
}
?>
