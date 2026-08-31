<?php

class SU_SessionFile implements SU_SessionInterface
{
    /**
     * Атрибут для реализации синглетона
     *
     * @var 
     */
    static private $instance;
    
    static function session_delete($id)
    {
        if(file_exists(session_save_path().'/sess_'.session_id()))
        {
            unlink(session_save_path().'/sess_'.session_id());
        }
    }
    
    static public function Singleton()
    {
        if(!(self::$instance instanceof SU_SessionFile))
        {
            self::$instance = new SU_SessionFile();
        } 
                
        return self::$instance;
    }
    
    static function sessionExist($sido)
    {
        $mySessionFile = session_save_path() ."/sess_".$sido;

        if(file_exists($mySessionFile))
        {
            /*if(!session_id())
            {
                return true;
            }else {
                return false;
            }*/
            return true;
        } else {
            $str  = "Cant find session store\n";
            $str .= "FILE_NAME = ".$mySessionFile."\n";
            $str .= "START_DATE = ".@$_SESSION['session_start_date']."\n";
            $str .= "LAS_CONTINUE_DATE = ".@$_SESSION['session_last_continue_date']."\n";

            SU_DebugTools::LogAll($str, "SessionErrors.log");

            return false;
        }
    }
    
    static public function countUsersOnLine()
    {
        $common = DS_Common::CreateForCurrentDB(0);
        $sql = "select count(*) as cnt from ds_loginhistory where lastvisit > now() - interval '90 minute'";
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));
        return $res[0]['cnt'];
    }
    
    function __construct()
    {
        //global $lang;
        //var_dump($lang);
    }
    
    //public function session_open($path, $name)
    public function session_open()
    {
        
    }

    public static function session_close()
    {
        
    }
    public function session_start()
    {
        
    }

    public function session_read($sid)
    {
        
    }

    public function session_write($sid, $data)
    {
        
    }

    public function session_gc()
    {
        
    }

}
?>