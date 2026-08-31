<?php $CVS_Version = '$Revision$ ; $Date$';

// коннект к базам, интерфейс с возможностям кеширования zps
// в настоящее вреоя используется только GLOBAL_CACHE

require_once constant('CONFIG_PATH').'/settings.php';

class DS_Common extends BS_Base
{
    /** Экземпляр самого себя для  */
    static private $instance = null;

    /** ID текущего соединения */
    public $conn;

    /** Сообещение об ошибке последнец */
    public $errormsg = '';

    /** Параметры доступа копируються */
    public $dbWorldAccess = array();

    /** forum database */
    public $dbForum = array();

    /** ID текущей БД */
    public $dbId = 0;

    /** Флаг того что транзакция начата ичопльзуеть сятолько в useraction */
    public $transIsStarted = 0; 

    /** LogFile */
    static private $logFile = 'DS_Common.log';

    /**
     * Порог длительности для иденетфикация запроса как медленного
     *
     * @var integer
     */
    static public $slowQueryTimeLimit = 0.2;

    /** При устанвоки данной переменной в случае возникновения ошикбики
     *  происходит отказ от исполнения всех дальнейших SQL
     *  Используеться только для useraction.php
     */
    public $errorBlocking = false;

    /**
     * Флаг блока исполнения запрсов
     */
    public $block = false;

    /** Экземпляр объекта для кеширования */
    public $cache;

    public $res;
    
    static public $connectWithTransactionAndBlocking = false; 

    public function __construct()
    {
        parent::__construct();

        assert(key_exists('dbWorldAccess', $GLOBALS));
        assert( is_array($GLOBALS['dbWorldAccess']));
        foreach($GLOBALS['dbWorldAccess'] as $k=>$v)
        {
            $v['conn'] = false;
            $v['trans'] = false;
            $this->dbWorldAccess[$k] = $v;
        }

        //assert(key_exists('dbForum', $GLOBALS) && is_array($GLOBALS['dbForum']));
        $this->dbForum = @$GLOBALS['dbForum'];

        if(@$this->dbWorldAccess[@$_SESSION["dbId"]])
        {
            $this->dbId = $_SESSION["dbId"];
        }else{
            $this->dbId = 0;
        }

        $this->cache = DS_Cache::Singleton();       
    }
    
    static public function SetConnectWithTransaction($val)
    {
        assert(is_bool($val));
        
        self::$connectWithTransactionAndBlocking = $val;
    }
    
    /**
     * Enter description here...
     *
     * @param integer $dbId
     * @return DS_Common
     */
    public static function CreateForCurrentDB($DBID = null)
    {
        if(is_null($DBID))
        {
            if(isset($_SESSION) && key_exists("dbId", $_SESSION))
            {
                settype($_SESSION["dbId"], "integer");
                assert($_SESSION["dbId"] >= 0);
                
                self::Singleton()->setDbId($_SESSION["dbId"]);
            }else{
                if(!(self::$instance instanceof DS_Common))
                {
                    self::Singleton()->setDbId(0);
                }
            }
        }else{
            settype($DBID, "integer");
            assert($DBID >= 0);
            
            self::Singleton()->setDbId($DBID);
        }
                
        return self::Singleton();   
    }

    /**
     * Синоним предыдущего метода, нужен для смвместимости,
     * имеет неправльное название, если встретите где-то в тексте
     * программы, смело исправляйте!
     *
     * @deprecated 
     * @param integer $dbId
     * @return DS_Common
     */
    public static function CreateForCurentDB($DBID = null)
    {
       return self::CreateForCurrentDB($DBID);
    }

    /**
     * Enter description here...
     *
     * @return DS_Common
     */
    public static function Singleton()
    {
        if(!(self::$instance instanceof DS_Common))
        {
            self::$instance =  new DS_Common();
        }

        return self::$instance;
    }   

    public function startErrorBlocking ()
    {
        $this->log('ERROR BLOCKING MODE IS ON', '', 0, '', 0);
        $this->errorBlocking = true;
    }

    public function stopErrorBlocking()
    {
        $this->log('ERROR BLOCKING MODE IS OFF', '', 0, '', 0);
        $this->errorBlocking = false;
    }

    public function block()
    {
        if($this->errorBlocking)
        {
            $this->log('EXEC OF SQLS IS BLOCKED', '', 0, '', 0);
            $this->block = true;
        }
    }

    public function unBlock()
    {
        $this->log('EXEC OF SQLS IS UNBLOCKED', '', 0, '', 0);
        $this->block = false;
    }

    public function getDBName($dbID = null)
    {
        if(is_null($dbID))
        {
            $dbID = $this->dbId;
        }
        assert(key_exists($dbID, $this->dbWorldAccess));

        return $this->dbWorldAccess[$this->dbId]['dbname'];
    }

    /**
      * Метод для начала транзакции. Используеться только в useraction.php
      * Выставляет фолаг начала транзакции.
      */
    public function startTrans ()
    {
        if($this->dbWorldAccess[$this->dbId]['trans'])
        {
            return true;
        }else{
            $this->dbWorldAccess[$this->dbId]['trans'] = 1;
            $rs = $this->executeQuery('BEGIN');

            if(!$rs)
            {
                return $rs;
            }

            return $this->setTimeOut(constant('DB_DEFAULT_TIMEOUT'));
        }
    }

    /**
     * Устанавливает вре6мя таймаута в милисекундах.
     * Параметры: $time  - время таймаута в милисекундах
     */
    public function setTimeOut($time = 0)
    {

        settype($time, 'integer');
        assert($time>=0);

        assert($this->executeQuery ( "set STATEMENT_TIMEOUT to ".$time.";"));

        return true;
    }

    /** Метод для окнчания транзакции.
      * Используеться только в useraction.php
      * Непозваляет завешать трагнзакцию, елси он ане был аначата.
      */
    public function closeTrans($val)
    {
        $val = strtoupper($val);
        
        if($this->dbWorldAccess[$this->dbId]['trans'])
        {
            $this->dbWorldAccess[$this->dbId]['trans'] = 0;
            return $this->executeQuery($val);
        }else{
            return true;
        }
    }

    /**
     * Получение ИД базы по имени, статик версия 
     *
     * @param string $serverName
     * @return int
     */
    static public function GetDBIDByServerName($serverName)
    {
        assert(!empty($serverName));
        
        foreach ($GLOBALS['dbWorldAccess'] as $dbID => $db)
        {
            if($db['server'] == $serverName)
            {
                return $dbID;
            }
        }
        
        return -1;
    }
    
    /**
     * Получение ИД базы по имени, старая версия
     *
     * @param string $serverName
     * @return int
     * @deprecated 
     */
    public function getDBIDByName($serverName)
    {
        assert(!empty($serverName));

        foreach ( $this->dbWorldAccess as $k=>$v )
        {
            if($v['server'] == $serverName)
            {
                return $k;
            }
        }

        return false;
    }

    public function deleteDB($dbId)
    {
        settype($dbId, 'integer');
        assert($dbId > 0);
        assert(key_exists($dbId, $this->dbWorldAccess));

        unset($this->dbWorldAccess[$dbId]);
    }

    public function setDbId($dbId)
    {
        settype($dbId, 'integer');
        assert($dbId>=0);
        assert(key_exists($dbId, $this->dbWorldAccess));
        // $_SESSION["dbId"] = $dbId;

        $this->dbId = $dbId;

        if(!@$this->dbWorldAccess[$this->dbId]['conn'])
        {
            if(!$this->connectSingl($dbId))
            {
                return false;
            };

            $this->conn = $this->dbWorldAccess[$this->dbId]['conn'];
        }else{
            $this->conn = $this->dbWorldAccess[$this->dbId]['conn'];
        }
        pg_query($this->conn, "SET NAMES 'WIN1251'");
        
        return true;
    }

    
    public function getCurrentDBID()
    {       
        return $this->dbId;     
    }
    
    public function getCurrentDBName()
    {       
        return $this->dbWorldAccess[$this->dbId]['server'];
    }

    public function haveErrorsInCurrentConnection()
    {
        return  pg_last_error($this->conn);
    }
    
    public function connectSingl($dbId)
    {
        settype($dbId, 'integer');
        assert($dbId>=0);
        assert(key_exists($dbId, $this->dbWorldAccess));

        $host = $this->dbWorldAccess[$dbId]['host'];
        $dbname = $this->dbWorldAccess[$dbId]['dbname'];
        $user = $this->dbWorldAccess[$dbId]['user'];
        $password = $this->dbWorldAccess[$dbId]['password'];

        $conn_string = "host=".$host." dbname=".$dbname." user=".$user." password=".$password;
        SU_DebugTools::TimerStart('SQL');
        $conn = pg_connect ($conn_string);

        $timeDelta = SU_DebugTools::TimerStop('SQL');

        if(!is_resource($conn))
        {
            ds_error("Cant connect to db ".$dbId);
        }

        $this->log ("CONNECT to DBID = ".$dbId, pg_last_error(), 0, '', $timeDelta);

        $this->dbWorldAccess[$dbId]['conn'] = $conn;
        $this->conn = $conn;
        
        SU_DebugTools::TimerStart('CONNECT_START');
        
        if(self::$connectWithTransactionAndBlocking)
        {
            $this->startTrans();
            $this->startErrorBlocking();
        }
        
        return true;
    }

    // коннект к базе
    public function connect ($dbname = "")
    {

        // dbname == "forum_ru" is deprecated

        if(($dbname == 'forum_ru') || ($dbname == 'forum'))
        {
            $host = $this->dbForum['host'];
            $dbname = $this->dbForum['dbname'];
            $user = $this->dbForum['user'];
            $password = $this->dbForum['password'];

            $conn_string = "host=".$host." dbname=".$dbname." user=".$user." password=".$password;
            $this->dbForum['conn'] = @pg_connect ($conn_string);
            $this->conn = $this->dbForum['conn'];
            if(!$this->conn)
            {
                $this->errormsg = "SYSTEM ERROR DS_Common::DS_Common1";
                return false;
            }
        }else{

            if(@$GLOBALS['DB_SINGL_MODE'])
            {
                $this->connectSingl($this->dfId);

            }else{
                foreach($this->dbWorldAccess as $k=>$v)
                {
                    if(key_exists('conn', $this->dbWorldAccess[$k]) && $this->dbWorldAccess[$k]['conn'])
                    {
                        continue;
                    }

                    $this->connectSingl($k);
                }
            }
        }

        return true;
    }

    /** Исполняет SQL запрос на  всех активных БД и возвращяет объейдененый результат*/
    function getMultyArray($identifier = "", $sql = "", $lifetime = "", $id_userinstance = "", $cache_level = "")
    {

        assert(!empty($sql));

        if($this->block)
        {
            return false;
        }

        $tmpDbId = $this->dbId;

        $allrs = array();

        foreach($this->dbWorldAccess as $k=>$v)
        {
            $this->setDbId($k);

            $tmpRs = $this->getResultArray($identifier, $sql, $lifetime, $id_userinstance, $cache_level);

            if($tmpRs)
            {
                foreach($tmpRs as $k2=>$v2)
                {
                    $v2['dbId'] = $k;
                    $v2['dbname'] = $v['server'];
                    $tmpRs[$k2] = $v2;
                }

                if(is_array($tmpRs))
                {
                    $allrs = array_merge($allrs, $tmpRs);
                }
            }
        }

        $this->setDbId($tmpDbId);

        return $allrs;
    }

    function getMultySql($sql)
    {
        assert(!empty($sql));

        if ( $this->block )
        {
            return false;
        }

        $tmpDbId = $this->dbId;

        $allrs = array();

        foreach($this->dbWorldAccess as $k=>$v)
        {
            $this->setDbId($k);
            $tmpRs = $this->getQueryArray($sql);

            if($tmpRs)
            {
                $tmpRs2 = array();

                foreach($tmpRs as $k2=>$v2)
                {
                    $tmpRs2[$k2] = $v2;
                    $tmpRs2[$k2]['dbId'] = $k;
                }

                if(is_array($tmpRs2))
                {
                    $allrs = array_merge($allrs, $tmpRs2);
                }
            }
        }

        $this->setDbId($tmpDbId);

        return $allrs;
    }

    static public function GetServerID($dbname)
    {
       foreach($GLOBALS['dbWorldAccess'] AS $l => $db)
     {
      if($db['server'] == $dbname)
      {
        return $l;
      }
     }    
     return -1;
    }

    // возвращает массив с результатами выборки sql-запроса
    function getResultArray($identifier = "", $sql = "", $lifetime = "", $prefix = "", $cache_level = "")
    {

        assert(!empty($sql));
        if($this->block)
        {
            return false;
        }
        $this->errormsg = '';
        
        SU_DebugTools::TimerStart('SQL');
        if(constant("ZPS_INSTALLED") && $cache_level == 'GLOBAL_CACHE')
        {       
            
            if(empty($lifetime))
            {
                $lifetime = constant('DB_CACHE_DEFAULT_TIME_OUT');
            }
                
            if($prefix == 'SHORT_PREFIX')
            {
                $identifier = CACHING_PREFIX.$identifier;
            }else{
                $identifier = CACHING_PREFIX.$this->dbId.'_'.$identifier;
            }
            
            $res_assoc = $this->cache->get($identifier);
            
            if($res_assoc)
            {               
                $cacheLogFlag = 1;
            }else{
                $res_assoc = $this->getQuery($sql);
                $this->cache->set($identifier, $lifetime, $res_assoc);
                $cacheLogFlag = 2;
            }   
        }
        else
        {
      $res_assoc = $this->getQuery($sql);   
            $cacheLogFlag = 0;  
        }
        $timeDelta = SU_DebugTools::TimerStop('SQL');
        
        $this->log($sql, pg_last_error(), $cacheLogFlag, $res_assoc, $timeDelta);
        
        if($this->errormsg != '')
        {
            $res_assoc = false;
        }elseif($res_assoc == ""){
            $res_assoc = array ();
        }

        return $res_assoc;
    }

    function getQuery($sql)
    {
    
        if($this->block)
        {
            return false;
        }

        $this->errormsg = '';
        
        assert(is_resource($this->conn));
        $res = pg_query($this->conn, $sql);
        
        if(!$res)
        {
            $this->errormsg = pg_last_error($this->conn)." dbId = ".$this->dbId;
            $this->block ();
            return false;
        }

        if (pg_num_rows($res)>0)
        {
            $res_assoc = pg_fetch_all ($res);
        }else{
            $res_assoc = array ();
        }

        return $res_assoc;
    }

    function getQueryArray($sql)
    {
        if ( $this->block )
        {
            return false;
        }

        $this->errormsg = '';
        SU_DebugTools::TimerStart('SQL');
        assert(is_resource($this->conn));
        $res = pg_query($this->conn, $sql);
        $timeDelta = SU_DebugTools::TimerStop('SQL');
        $this->log ($sql, pg_last_error(), 0, pg_fetch_all ($res), $timeDelta);

        if ( !$res ) {
            $this->errormsg = pg_last_error ( $this->conn )." dbId = ".$this->dbId;
            $this->block ();
            return false;
        }

        if(pg_num_rows($res)>0)
        {
            $res_assoc = pg_fetch_all ($res);
        } else {
            $res_assoc = array();
        }

        return $res_assoc;
    }

    // insert, update, delete - запросы
    function executeQueryMulty ( $sql ) {
        if ( $this->block ){
            return false;
        }

        $this->errormsg = '';

        foreach($this->dbWorldAccess as $k=>$v){
            $this->setDbId($k);
            if(pg_last_error()){
                return false;
            }

            SU_DebugTools::TimerStart('SQL');
            assert(is_resource($this->conn));
            $res = pg_query($this->conn, $sql);
            $timeDelta = SU_DebugTools::TimerStop('SQL');
            $this->log ($sql, pg_last_error(), 0, $res, $timeDelta);
            if ( !$res ) {
                $this->errormsg = " SYSTEM ERROR DS_Common::getQuery1222 in dbId = ".$k." dbId = ".$this->dbId;

                $this->block ();
                return false;
            }
        }

        return true;
    }

    function executeQuery($sql)
    {
        if($this->block)
        {
            return false;
        }

        if(is_array($sql))
        {
            $sql = implode(";\n", $sql);
        }


        assert(!empty($sql));
        SU_DebugTools::TimerStart('SQL');
        assert(is_resource($this->conn));

        //ВСтавлено так как не всегдка почему-то результат от предидущего завроса заьирсялся из соединения
        //надо будет потом найти где и как это и убрать это.
        pg_get_result ($this->conn);
        $res = pg_query($this->conn, $sql);
        $timeDelta = SU_DebugTools::TimerStop('SQL');
        $this->log ($sql, pg_last_error(), 0, $res, $timeDelta);
        if(!$res)
        {
            //$this->errormsg =  " SYSTEM ERROR DS_Common::getQuery1"." dbId = ".$this->dbId;
      $this->errormsg = pg_last_error($this->conn)." dbId=".$this->dbId;
            $this->block();
            return false;
        }

        $this->res = $res;
        return true;
    }

    function getRES()
    {
        return $this->res;
    }

    public function GetLastOID()
    {
        return pg_last_oid($this->res);
    }

    function clearLocalCache ( $uiid )
    {
        return true;
    }

    function disconnect() 
    {       
        if(!pg_close($this->conn)) 
        {
            return false;       
        }
                
        return true;
    }   

    function log($sql, $error, $isCache, $result, $timeDelta)
    {               
        static $total = 0;      

        $sql = trim($sql);      
        //$scriptTime = SU_DebugTools::TimerStop('SCRIPT_START_TIME');
        $scriptTime = '';
        
        //Логирование длительных запросов
        if(self::$slowQueryTimeLimit > 0 && $isCache != 1 && $timeDelta > self::$slowQueryTimeLimit)
        {
            $msg = "$sql \n\n ExecTime: $timeDelta";
            SU_DebugTools::LogAll($msg, 'SlowQuerys.log');
        }
/*      
        if($timeDelta > 0)
        {
            $total++;                   
            GP_SystemStatistic::DeltaParam('DS_COMMON', 'COUNT_QUERYS_TOTAL', 1);
            GP_SystemStatistic::DeltaParam('DS_COMMON', 'SUM_TIME_OF_WORK', $timeDelta);
        }

        //Счетчик типов запросов
        if(stristr($sql, 'DELETE'))
        {
            $type = "DELETE";           
            GP_SystemStatistic::DeltaParam('DS_COMMON', 'COUNT_DELETE', 1);
            GP_SystemStatistic::DeltaParam('DS_COMMON', 'SUMM_TIME_DELETE', $timeDelta);
        }elseif(stristr($sql, 'UPDATE')){
            $type = "UPDATE";           
            GP_SystemStatistic::DeltaParam('DS_COMMON', 'COUNT_UPDATE', 1);
            GP_SystemStatistic::DeltaParam('DS_COMMON', 'SUMM_TIME_UPDATE', $timeDelta);
        }elseif(stristr($sql, 'INSERT')){
            $type = "INSERT";           
            GP_SystemStatistic::DeltaParam('DS_COMMON', 'COUNT_INSERT', 1);
            GP_SystemStatistic::DeltaParam('DS_COMMON', 'SUMM_TIME_INSERT', $timeDelta);
        }else{
            if(stristr($sql, 'SELECT'))
            {
                $type = "SELECT";   
                GP_SystemStatistic::DeltaParam('DS_COMMON', 'COUNT_SELECT', 1);                         
                GP_SystemStatistic::DeltaParam#DS_COMMON', 'SUMM_TIME_SELECT', $timeDelta);                            
                if ($isCache)
                {                   
                    GP_SystemStatistic::DeltaParam('DS_COMMON', gCOUNT_SELECT_WITH_CACHE_MARK', 1);                 
                    
                    if($isCache == 1)
                    {
                        GP_SystemStatistic::DeltaParam('DS_COMMON', 'COUNT_CACHE_MACHES', 1);
                        GP_SystemStatistic::DeltaParam('DS_COMMON', 'SUMM_TIME_QUERYS_FROM_CACHE', $timeDelta); 
                    }else{
                        GP_SystemStatistic::DeltaParam('DS_COMMON', 'COUNT_CACHE_MISSES', 1);               
                        GP_SystemStatistic::DeltaParam('DS_COMMON', 'SUMM_TIME_SETED_QUERYS', $timeDelta);
                    }   
                }else{              
                    GP_SystemStatistic::DeltaParam('DS_COMMON', 'COUNT_SELECT_WITHOUT_CACHE_MARK', 1);
                    GP_SystemStatistic::DeltaParam('DS_COMMON', 'SUMM_TIME_NO_CACHED_QUERYS', $timeDelta);
                }
            }else{              
                $type = "OTHER";                
                
                if($timeDelta > 0)
                {
                    GP_SystemStatistic::DeltaParam('DS_COMMON', 'COUNT_OTHER', 1);
                    GP_SystemStatistic::DeltaParam('DS_COMMON', 'SUMM_TIME_OTHER', $timeDelta);
                }               
            }
        }
*/              
        //Формируем текст для лога
        $msg = '';
        $msg .= "TotalCounter = $total; IsCached = $isCache; Type = $ type; SQLExecTime = $timeDelta; ScriptExecTime = $scriptTime\n\n";
        $msg .= "SQL:\n ".$sql."\n\n";

        if(!empty($error))
        {
            $msg .= $error;
        }

        /*if($type == 'SELECT' && count($result)>0 && empty($error))
        {
            $msg .= "RESULT:\n".SU_DebugTools::Dump($result)."\n\n";
        }*/

        //Записываем куда
        if(constant('LOGING_DS_COMMON_FLAG'))
        {
            SU_DebugTools::LogAll($msg, self::$logFile, 1, 0);
        }

        if(!empty($error))
        {
            SU_DebugTools::LogAll($msg, 'SystemErrors.log', 1, 0);
        }

        if(!empty($error) && (ereg('canceling statement due to statement timeout', $error)))
        {
            SU_DebugTools::LogAll($msg, 'DBTimeOutErrors.log', 1, 0);
            DS_DBTimeOutHandler();
        }
    }

    function closeConnection($DBID = null)
    {
        if(is_null($DBID))
        {
            $DBID = $this->dbId;    
        }
        
        assert($DBID >= 0);             
        pg_close($this->conn);
        
        $this->dbWorldAccess[$DBID]['conn'] = false;
        
        //$timeDelta = SU_DebugTools::TimerStop('CONNECT_START');
        //GP_SystemStatistic::DeltaParam('DS_COMMON','TIME_CONNECTION_TOTAL', $timeDelta);
    }       
    
    public function disconnectFromAll()
    {
        foreach ($this->dbWorldAccess as $dbId => $db)
        {
            if(is_resource($db['conn']))
            {
                pg_close($this->conn);
                $this->dbWorldAccess[$dbId]['conn'] = false;
            }
        }
        
        //$timeDelta = SU_DebugTools::TimerStop('CONNECT_START');
        //GP_SystemStatistic::DeltaParam('DS_COMMON','TIME_CONNECTION_TOTAL', $timeDelta);
    }

    function __destruct()
    {
        parent::__destruct();
    }
}

?>