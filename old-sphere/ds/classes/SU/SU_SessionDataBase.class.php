<?php
class SU_SessionDataBase implements SU_SessionInterface
{
    /**
     * Атрибут для реализации синглетона
     *
     * @var 
     */
    static private $instance;
    
    static public function Singleton()
    {
        if(!(self::$instance instanceof SU_SessionDataBase))
        {
            self::$instance = new SU_SessionDataBase();
        } 
                
        return self::$instance;
    }
    
    function __construct()
    {
        session_set_save_handler(
        array(&$this, 'session_open'),
        array(get_class($this), 'session_close'),
        array(&$this, 'session_read'),
        array(&$this, 'session_write'),
        array(&$this, 'session_delete'),
        array(&$this, 'session_gc')
        );
    }

    //function session_open($path, $name)
    function session_open()
    {
        $storage = new SU_SessionDataBase;

        return true;
    }

    public static function session_close()
    {
        return true;
    }

    function session_read($sid)
    {
        $dbWork = DS_MySQLWork::Singleton();

        try {
            $dbWork->connect('LOCKING');
        }catch(SU_MySQLException $e){
            ds_error('ERROR: '.$e->getMessage());
            return false;
        }

        try {
            $sql = "UPDATE sessions SET atime=NOW() WHERE sid='$sid'";
            $dbWork->exec($sql);
        }catch(SU_MySQLException $e){
            ds_error('ERROR: '.$e->getMessage());
            return false;
        }

        try {
            $sql = "SELECT data FROM sessions WHERE sid='$sid'";
            $rs = $dbWork->get($sql);
        }catch(SU_MySQLException $e){
            ds_error('ERROR: '.$e->getMessage());
            return false;
        }


        if(count($rs) == 0)
        {
            try {
                $sql = "INSERT INTO sessions (sid) VALUES ('$sid')";
                $dbWork->exec($sql);
                return ""; // Т.к. данных нет, то возвращаю пустую строку
            }catch(SU_MySQLException $e){
                ds_error('ERROR: '.$e->getMessage());
                return false;
            }
        }

        return $rs[0]['data'];
    }
    
     function session_write($sid, $data)
    {
        $sessionDbWork = DS_MySQLWork::Singleton();
        $sessionDbWork->connect('LOCKING');
        
        $data = mysql_real_escape_string($data); // Настоятельно рекомендую
        
        try {
            $sql = "UPDATE sessions set atime = NOW(), data = '$data' WHERE sid = '$sid'";
            $sessionDbWork->exec($sql);
        }catch(SU_MySQLException $e){
            ds_error('ERROR: '.$e->getMessage());
            return false;
        }
    }

    static function session_delete($sid)
    {
        $sessionDbWork = DS_MySQLWork::Singleton();
        $sessionDbWork->connect('LOCKING');
        
        try {
            $sql = "DELETE FROM sessions WHERE sid='$sid'";
            $sessionDbWork->exec($sql);
        }catch(SU_MySQLException $e){
            ds_error('ERROR: '.$e->getMessage());
            return false;
        }
    }

    function session_gc()
    {
        $sessionDbWork = DS_MySQLWork::Singleton();
        $sessionDbWork->connect('LOCKING');

        $lifetime = 3660;

        try {
            $sql = "DELETE FROM sessions WHERE atime < DATE_ADD(NOW(), INTERVAL -$lifetime SECOND)";
            $sessionDbWork->exec($sql);
        }catch(SU_MySQLException $e){
            ds_error('ERROR: '.$e->getMessage());
            return false;
        }
        
        //SU_DebugTools::LogAll($sql, "SessionErrors.log");
        // Для оптимизации atime не должно участвовать в вычислении выражения
    }
    
    static function sessionExist($sido)
    {
        $dbWork = DS_MySQLWork::Singleton();
        $dbWork->connect('LOCKING');

        try {
            $sql = "SELECT * FROM sessions WHERE sid='$sido'";
            $rs = $dbWork->get($sql);
        }catch(SU_MySQLException $e){
            ds_error('ERROR: '.$e->getMessage());
            return false;
        }

        if (count($rs) == 0)
        {
            //Ошибка!
            $str  = "Cant find id session in data base\n";
            $str .= "START_DATE = ".@$_SESSION['session_start_date']."\n";
            $str .= "LAS_CONTINUE_DATE = ".@$_SESSION['session_last_continue_date']."\n";

            SU_DebugTools::LogAll($str, "SessionErrors.log");
            return false;
        } else {
            return true;
        }
    }
    
    static function countUsersOnLine()
    {
        $dbWork = DS_MySQLWork::Singleton();
        $dbWork->connect('LOCKING');
        
        $onlineTime = 5400;

        try {
            $sql = "SELECT count(*) FROM sessions WHERE atime > DATE_ADD(NOW(), INTERVAL - $onlineTime SECOND)";
            $rs = $dbWork->get($sql);
        }catch(SU_MySQLException $e){
            ds_error('ERROR: '.$e->getMessage());
            return false;
        }

        return $rs[0]['count(*)'];
    }
}
?>