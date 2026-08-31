<?
// коннект к базам, интерфейс с возможностям кеширования zps
// в настоящее время используется только GLOBAL_CACHE

require_once ( 'GameBase.class.php' );
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";
if(!isset($GLOBALS['dbWorldAccess'])) require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

class DS_Common extends GameBase{

    /** ID текущего соединения */
    var $conn;

    /** Сообещение об ошибке последнец */
    var $errormsg = '';

    /** ?????? */
    var $out = '';

    /** Параметры доступа копируються */
    var $dbWorldAccess = array();

    /** forum database */
    var $dbForum = array();

    /** ID текущей БД  */
    var $dbId = 0;

    /** Флаг того что транзакция начата ичопльзуеть сятолько в useraction */
    var $transIsStarted = 0;

    /**  Устанавливаеться логирование */
    var $isLog = true;

    /** LogFile */
    var $logFile = '!DS_Common.log';

    /** При устанвоки данной переменной в случае возникновения ошикбики
     *  происходит отказ от исполнения всех дальнейших SQL
     *  Используеться только для useraction.php
     */
    var $errorBlocking = false;

    var $reso;

    /**
     * Флаг блока исполнения запрсов
     */
    var $block = false;

    function DS_Common () {
        $this->GameBase ();

        $this->dbWorldAccess = @$GLOBALS['dbWorldAccess'];
        $this->dbForum = @$GLOBALS['dbForum'];

        if(@$this->dbWorldAccess[$_SESSION["dbId"]]){
            $this->dbId = $_SESSION["dbId"];
        }

    }

    function setIsLog ( $val ) {
        $this->isLog = $val;
    }

    function startErrorBlocking () {
        $this->errorBlocking = true;
    }

    function block() {
        if ( $this->errorBlocking ){
            $this->block = true;
        }
    }

    function getRES()
    {
        return $this->reso;
    }

    function getDBName(){
        return $this->dbWorldAccess[$this->dbId]['dbname'];
    }
    /**
      * Метод для начала транзакции. Используеться только в useraction.php
      * Выставляет фолаг начала транзакции.
      */
    function startTrans (){
        if ( $this->transIsStarted ) {
            return true;
        }else{
            $this->transIsStarted = 1;
            $rs = $this->executeQuery ( 'BEGIN' );
            if (!$rs ) {
                return $rs;
            }
            return $this->setTimeOut ( constant ( 'DB_DEFAULT_TIMEOUT' ) );

        }
    }

    /**
     * Устанавливает вре6мя таймаута в милисекундах.
     * Параметры: $time  - время таймаута в милисекундах
     */
    function setTimeOut ( $time = 0 ){

        settype($time, 'integer');

        assert ( $this->executeQuery ( "set STATEMENT_TIMEOUT to ".$time.";" ) );

        return true;
    }

    /** Метод для окнчания транзакции.
      * Используеться только в useraction.php
      * Непозваляет завешать трагнзакцию, елси он ане был аначата.
      */
    function closeTrans ( $val ){
        $val = strtoupper($val);
        if ( $this->transIsStarted ) {
            $this->transIsStarted = 0;
            return $this->executeQuery ( $val );
        }else{
            return true;

        }
    }

    function getDbIdByName($server){
        foreach ( $this->dbWorldAccess as $k=>$v ){
            if ( $v['server'] == $server){
                return $k;
            }
        }
    }

    function setDbId($dbId){
        if(!$this->dbWorldAccess[$dbId]){
            $this->errormsg = 'ERROR: DS_COMMON - uncorrect dbId = '.$dbId;
            return false;
        };

        $this->dbId = $dbId;

        if(!@$this->dbWorldAccess[$this->dbId]['conn']){
            if(!$this->connectSingl($dbId)){
                return false;
            };
            $this->conn = $this->dbWorldAccess[$this->dbId]['conn'];
        }else{
            $this->conn = $this->dbWorldAccess[$this->dbId]['conn'];
        }
        pg_query($this->conn, "SET NAMES 'WIN1251'");
        return true;
    }

    function connectSingl($dbId){
        $host = $this->dbWorldAccess[$dbId]['host'];
        $dbname = $this->dbWorldAccess[$dbId]['dbname'];
        $user = $this->dbWorldAccess[$dbId]['user'];
        $password = $this->dbWorldAccess[$dbId]['password'];

        $conn_string = "host=".$host." dbname=".$dbname." user=".$user." password=".$password;


        $this->dbWorldAccess[$dbId]['conn'] = pg_connect ($conn_string);
        assert($this->dbWorldAccess[$dbId]['conn']);

        if ( !$this->dbWorldAccess[$dbId]['conn'] ) {
            $this->errormsg = "SYSTEM ERROR DS_Common::Cant connect";
            return false;
        }
        pg_query($this->dbWorldAccess[$dbId]['conn'], "SET NAMES 'WIN1251'");
        return true;
    }

    // коннект к базе
    function connect ( $dbname = "" ) {

        // dbname == "forum_ru" is deprecated

        if(($dbname == 'forum_ru') || ($dbname == 'forum')){
            $host = $this->dbForum['host'];
            $dbname = $this->dbForum['dbname'];
            $user = $this->dbForum['user'];
            $password = $this->dbForum['password'];

            $conn_string = "host=".$host." dbname=".$dbname." user=".$user." password=".$password;
            $this->dbForum['conn'] = @pg_connect ($conn_string);
            $this->conn = $this->dbForum['conn'];
            if ( !$this->conn ) {
                $this->errormsg = "SYSTEM ERROR DS_Common::DS_Common1";
                return false;
            }
        }else{
            if(@$GLOBALS['DB_SINGL_MODE']){
                $host = $this->dbWorldAccess[$this->dbId]['host'];
                $dbname = $this->dbWorldAccess[$this->dbId]['dbname'];
                $user = $this->dbWorldAccess[$this->dbId]['user'];
                $password = $this->dbWorldAccess[$this->dbId]['password'];

                $conn_string = "host=".$host." dbname=".$dbname." user=".$user." password=".$password;
                $this->dbWorldAccess[$this->dbId]['conn'] = @pg_connect ($conn_string);
                if ( !$this->dbWorldAccess[$this->dbId]['conn'] ) {
                    $this->errormsg = "SYSTEM ERROR DS_Common::DS_Common1";
                    return false;
                }
            }else{
                foreach($this->dbWorldAccess as $k=>$v){
                    if(@$this->dbWorldAccess[$k]['conn']){
                        continue;
                    }

                    $host = $v['host'];
                    $dbname = $v['dbname'];
                    $user = $v['user'];
                    $password = $v['password'];

                    $conn_string = "host=".$host." dbname=".$dbname." user=".$user." password=".$password;
                    $this->dbWorldAccess[$k]['conn'] = pg_connect ($conn_string);
                    if ( !$this->dbWorldAccess[$k]['conn'] )
                    {
                        $this->errormsg = "SYSTEM ERROR DS_Common::DS_Common1";
                        echo $this->errormsg;
                        return false;
                    }
                }
            }


            $this->conn = $this->dbWorldAccess[$this->dbId]['conn'];
        }
        pg_query($this->conn, "SET NAMES 'WIN1251'");

        return true;
    }

    /** Исполняет SQL запрос на  всех активных БД и возвращяет объейдененый результат*/
    function getMultyArray ( $identifier = "", $sql = "", $lifetime = "", $id_userinstance = "", $cache_level = "" ){

        if ( $this->block ){
            return false;
        }

        $tmpDbId = $this->dbId;

        $allrs = array();

        foreach($this->dbWorldAccess as $k=>$v){
            $this->setDbId($k);
            $tmpRs = $this->getResultArray ( $identifier, $sql, $lifetime, $id_userinstance, $cache_level);
            if($tmpRs){
                foreach($tmpRs as $k2=>$v2){
                    $v2['dbId'] = $k;
                    $tmpRs[$k2] = $v2;
                }
                if(is_array($tmpRs)){
                    $allrs = array_merge($allrs, $tmpRs);
                }

            }
        }

        $this->setDbId($tmpDbId);
        return @$allrs;
    }

    function getMultySql ( $sql ){

        if ( $this->block ){
            return false;
        }

        $tmpDbId = $this->dbId;

        $allrs = array();

        foreach($this->dbWorldAccess as $k=>$v){
            $this->setDbId($k);
            $tmpRs = $this->getQueryArray ( $sql );
            if($tmpRs){
                $tmpRs2 = array();
                foreach($tmpRs as $k2=>$v2){
                    $tmpRs2[$k2] = $v2;
                    $tmpRs2[$k2]['dbId'] = $k;
                }
                if(is_array($tmpRs2)){
                    $allrs = array_merge($allrs, $tmpRs2);
                }

            }
        }

        $this->setDbId($tmpDbId);
        return $allrs;
    }

    // возвращает массив в результами выборки sql-запроса
    function getResultArray ( $identifier = "", $sql = "", $lifetime = "", $id_userinstance = "", $cache_level = "" ) {

        if ( $this->block ){
            return false;
        }

        $this->errormsg = '';

        $identifier = constant("CACHING_PREFIX")."_".$this->dbId.'_'.$identifier;

        if ( $cache_level == "" ) $cache_level = "NO_CACHE";
        if ( $lifetime == "" ) $lifetime = 9999;

        if ( $sql == "" ) {
            $this->errormsg = " SYSTEM ERROR DS_Common::getResultArray1";
            return false;
        }

        if ( @ZPS_INSTALLED ) {

            if ( ZPS_INSTALLED == 1 ) {
                $zps = 1;
            }
        }

        if ( !isset ( $zps ) || $cache_level == 'NO_CACHE' || $cache_level == 'LOCAL_CACHE' ) { // no cached query

            // без кеширования
            $res_assoc = @unserialize ( $this->getQuery ( $sql ) );
$this->log ($sql, pg_last_error(), 0);
        } else {
            if ( $sql == "" ) {
                $this->errormsg = "SYSTEM ERROR DS_Common::getResultArray2";
                return false;
            }
            // с кешированием
            $res_assoc = @unserialize ( output_cache_fetch ( $identifier, '$this->getQuery ( "$sql" )', $lifetime ) );
$this->log ($sql, pg_last_error(), 1);
        }

        if ( $this->errormsg != '' ) {

            $res_assoc = false;

        } else if ( $res_assoc == "" ) {

            $res_assoc = array ();
        }

        return $res_assoc;
    }

    function getQuery ( $sql ) {

        if ( $this->block ){
            return false;
        }

        $this->errormsg = '';

        $res = @pg_query ( $this->conn, $sql );
        if ( !$res ) {
            $this->errormsg = pg_last_error ( $this->conn )." dbId = ".$this->dbId;
            $this->block ();
            return false;
        }

        if ( pg_num_rows ( $res ) > 0 ) {
            $res_assoc = pg_fetch_all ($res);

        } else {

            $res_assoc = array ();
        }

        return serialize ( $res_assoc );
    }

    function getQueryArray ( $sql ) {
        if ( $this->block ){
            return false;
        }

        $this->errormsg = '';

        $res = @pg_query ( $this->conn, $sql );
$this->log ($sql, pg_last_error(), 0);
        if ( !$res ) {
            $this->errormsg = pg_last_error ( $this->conn )." dbId = ".$this->dbId;
            $this->block ();
            return false;
        }

        if ( pg_num_rows ( $res ) > 0 ) {
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
            $res = @pg_query ( $this->conn, $sql );
$this->log ($sql, pg_last_error(), 0);
            if ( !$res ) {
                $this->errormsg = " SYSTEM ERROR DS_Common::getQuery1 in dbId = ".$k." dbId = ".$this->dbId;

                $this->block ();
                return false;
            }
        }

        return true;
    }

    function executeQuery ( $sql ) {
            if ( $this->block ){
                return false;
            }

            $res = @pg_query ( $this->conn, $sql );
$this->log ($sql, pg_last_error($this->conn), 0);
            if ( !$res ) {
echo pg_last_error($this->conn);
                $this->errormsg = " SYSTEM ERROR DS_Common::getQuery1"." dbId = ".$this->dbId;
                $this->block ();
                return false;
            }
        $this->reso = $res;
        return true;
    }

    function clearLocalCache ( $uiid ) {
        return true;
    }



    function disconnect() {

        if ( !pg_close ( $this->conn ) ) {
            return false;
        }

        return true;
    }

    function logAction ( $id_userinstance, $id_type, $module, $success = 0, $error = '', $command ) {

        // $id_type = 1 - action
        // $id_type = 2 - event

        global $common;

        if ( $success == 0 ) $success = 'false'; else $success = 'true';

        $sql = sprintf ( "insert into ds_log ( logtime, id_type, module, success, error, id_userinstance, command )
                values ( now(), %d, '%s', %s, '%s', %d, '%s' )",
                $id_type, $module, $success, $error, $id_userinstance, $command );

        $res = $common->executeQuery ( $sql );

        if ( $res === false ) {
            $this->errormsg = 'SE - DS_Common::logAction1';
            return false;
        }

        return true;
    }

    function log ($sql, $error, $isCache)
    {
        
        return true;

        static $total = 0;
        static $noCache = 0;
        static $cache = 0;

//      return;

        /*if(!$this->isLog){
            return;
        }*/

        $file = $GLOBALS['LOGING_DEBUG_LOGS_PATH']['MAIN'].'/'.$this->logFile;

        /*if ( $a == 0 ){
            @unlink ($file);
        }*/

            $total++;
            if ( $isCache ){
                $cache++;
            }else{
                $noCache++;
            }

            $debug = debug_backtrace();
            $fileName = @$debug[2]['file'];
            $line = @$debug[2]['line'];
            $method = @$debug[2]['class'].@$debug[2]['type'].@$debug[2]['function'];

            $msg = "------- [".$this->objectId."] CountTotal: ".$total." CountNoCashe: ".$noCache." CountCashe: ".$cache." IsCache: $isCache Time: ".date('H:i:s')." \n".
                    "SQL = ".$sql."\n\n".
                    "FILE = ".$fileName." LINE = ".$line."\n".
                    "METHOD = ".$method."\n\n".
                    "".$error."\n\n";
            error_log ($msg, 3, $file);
    }
}

?>