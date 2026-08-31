<?php $CVS_Version = '$Revision$ ; $Date$';
class SU_DebugTools
{
    static private $timers = array();   

    static private $currentLoggingPathName = null;

    static private function _getMaxLogSize()
    {
        return constant('LOGING_MAX_LOG_FILE_SIZE');
    }

    static private function _havePathName($pathName)
    {
        return key_exists($pathName, $GLOBALS['LOGING_DEBUG_LOGS_PATH']);
    }

    static private function _getLogPath($pathName = null)
    {
        if(is_null($pathName))
        {
            if(!is_null(self::$currentLoggingPathName))
            {
                assert(self::_havePathName(self::$currentLoggingPathName));
                return $GLOBALS['LOGING_DEBUG_LOGS_PATH'][self::$currentLoggingPathName];
            }else{
                assert(self::_havePathName('MAIN'));
                return $GLOBALS['LOGING_DEBUG_LOGS_PATH']['MAIN'];
            }
        }else{
            assert(self::_havePathName($pathName));
            return $GLOBALS['LOGING_DEBUG_LOGS_PATH'][$pathName];
        }
    }

    static private function _getDebugLogingFlag()
    {
        return constant('LOGING_DEBUG_LOGING_FLAG');
    }

    static private function _getSystemLogingFlag()
    {
        return constant('LOGING_SYSTEM_LOGING_FLAG');
    }

    static public function BackTrace($endVal=1)
    {

        $debug = debug_backtrace();
        $out = '';

        $counter = 1;

        for($i=(count($debug)-1); $i>$endVal; $i--){

            if(key_exists('args', $debug[$i]))
            {
                $argsArray = array();
                foreach($debug[$i]['args'] as $k=>$v)
                {
                    switch (gettype($v))
                    {
                        case 'object':

                            if(method_exists($v, 'getID'))
                            {
                                $id = $v->getID();
                            }else{
                                $id = '#0';//substr((string)$v, 7);
                            }

                            $param = "Object '".get_class($v)."(".$id.")'";

                            break;
                        case 'array':
                            $param = "Array [".count($v)."]";
                            break;
                        case 'boolean':
                            if($v)
                            {
                                $a = 'true';
                            }else{
                                $a = 'false';
                            }

                            $param = $a;
                            break;
                        case 'string':

                            $v = str_replace("\n", " ", $v);
                            $v = str_replace("\r", " ", $v);

                            if(strlen($v)>80)
                            {
                                $a = substr($v, 0, 80)."...";
                            }else{
                                $a = $v;
                            }

                            $param = "'".$a."'";
                            break;
                        default:
                            $param = gettype($v).' '.$v.'';
                    };
                    $argsArray[] = $param;
                }
                $args = @implode(', ', $argsArray);
            }else{
                $args = '';
            }

            if(key_exists('file', $debug[$i]))
            {
                $fileNameTmp = $debug[$i]['file'];
            }else{
                $fileNameTmp = '';
            }
            $file = substr($fileNameTmp, strlen($_SERVER['DOCUMENT_ROOT']));

            if(key_exists('line', $debug[$i]))
            {
                $line = $debug[$i]['line'];
            }else{
                $line = '';
            }

            if(key_exists('class', $debug[$i]))
            {
                $class = $debug[$i]['class'];
            }else{
                $class = '';
            }

            if(key_exists('function', $debug[$i]))
            {
                $function = $debug[$i]['function'];
            }else{
                $function = '';
            }

            if(key_exists('type', $debug[$i]))
            {
                $type = $debug[$i]['type'];
            }else{
                $type = '';
            }

            $out .= $counter.". ".$class.$type.$function."(".$args.") - From ".$file."(".$line.")\n";
            $counter++;
        };
        return $out;
    }

    static public function LogAll ($msg, $file = 'Developers.log', $trace = 1, $protocol = 0)
    {
        assert($trace == 1 || $trace == 0);

        if(gettype($msg) == 'object' || gettype($msg) == 'array')
        {
            $msg = self::Dump($msg);
        }

        $out  = "---- ".self::GetInternalDate()." | ".self::FormIndeteficationString()." | ".self::GetFormatedIP()." | ".self::GetProfiStatus()." | ".self::GetScriptExecType()." ----\n";
        $out .= "\n".$msg."\n\n";

        if($trace == 1)
        {
            $out .= "TRACE:\n";
            $out .= self::BackTrace(0)."\n";
        }

        if($protocol == 1)
        {
            $out .= "BRAUSER INFO:\n";
            $out .= self::GetFormatedBrouserInfo();//."\n";
            $out .= 'Flash: '.@$_SESSION['FLASH_VERSION']."\n\n";
                        
            if(key_exists('old_client_keys_arch', $_SESSION) && is_array($_SESSION['old_client_keys_arch']))
            {
                $out .= "CLIENT_KEY HISTORY:\n";    
                $out .= print_r($_SESSION['old_client_keys_arch'], 1);
            }       

            $out .= "PROTCOL HISTORY:\n";
            $out .= SU_ProtocolAccumulator::GetFormatedProtocolHistory()."\n\n";
        }

        self::WriteToLog($out, $file);

        return  $out;
    }

    static public function GetScriptExecType()
    {
        if(RD_AdeptUserActionToConsole::$commandLineFlag)
        {
            return "CONSOLE = 1";
        }else{
            return "CONSOLE = 0";
        }
    }

    static public function GetInternalDate()
    {
        list($msec, $sec) = explode(" ", microtime());
        
        $msec = number_format(round($msec, 4), 4);
        
        $msec = substr((string)$msec, 2);
                
        return date('Y-m-d H:i:s').'.'.$msec;
    }   

    static public function LogUIID ($uiid, $msg, $file = 'Developers.log', $trace = 1, $protocol = 0)
    {
        assert(is_int($uiid) && $uiid > 0);

        if($_SESSION["uiid"] != $uiid)
        {
            return;
        }

        self::LogAll($msg, $file, $trace, $protocol);
    }

    static public function LogUser ($userName, $msg, $file = 'Developers.log', $trace = 1, $protocol = 0)
    {
        assert(!empty($userName));

        if($_SESSION["s_login"] != $userName)
        {
            return;
        }


        self::LogAll($msg, $userName."_".$file, $trace, $protocol);
    }

    static public function SetNewLogPath($pathName)
    {
        assert(self::_havePathName($pathName));
        self::$currentLoggingPathName = $pathName;
    }

    static public function ResetLogPath()
    {
        self::$currentLoggingPathName = null;
    }

    static public function WriteToLog($msg, $file)
    {
        assert(!empty($file));

        if(gettype($msg) == 'object' || gettype($msg) == 'array')
        {
            $msg = self::Dump($msg);
        }

        $path = self::_getLogPath();

        $fileName = $path.$file;

        self::_testLogSize($path, $file);
        $spaceFlag=self::_testDiskSpace($path);
        if($spaceFlag=='OK')
        {
            if(self::_getSystemLogingFlag())
            {
    
                if(file_exists($fileName))
                {
                    touch($fileName);
                    chmod($fileName, 0666);
                }
    
                error_log ($msg, 3, $fileName);
            }
        }

        self::ResetLogPath();
    }

    static public function FormIndeteficationString()
    {
        if(!isset($_SESSION))
        {
            $_SESSION = array();
        }

        if(key_exists("dbId", $_SESSION))
        {
            $dbID = $_SESSION["dbId"];
        }else{
            $dbID = 'EMPTY_DBID';
        }

        if(@key_exists($dbID, $GLOBALS['dbWorldAccess']))
        {
            $serverName = $GLOBALS['dbWorldAccess'][$dbID]['server'];
        }else{
            $serverName = "EMPTY_SERVER_NAME";
        }

        if(key_exists("world", $_SESSION))
        {
            $worldID = $_SESSION["world"];
        }else{
            $worldID = "EMPTY_WORLD_ID";
        }

        if(key_exists("uiid", $_SESSION))
        {
            $uiid = $_SESSION["uiid"];
        }else{
            $uiid = 'EMPTY_UIID';
        }


        if(key_exists('USERNAME', $_SESSION))
        {
            $userName = $_SESSION['USERNAME'];
        }else{
            if(key_exists('s_login', $_SESSION))
            {
                $userName = $_SESSION['s_login'];
            }else{
                $userName = 'EMPTY_USER_NAME';
            }
        }


        return "$serverName($worldID) $userName($uiid) ".self::GetServerName();
    }


    static public function GetServerName()
    {
        if(key_exists('SERVER_NAME', $_SERVER))
        {
            return $_SERVER['SERVER_NAME'];
        }else{
            return "EMPTY_SERVERNAME";
        }
    }

    static public function ResetLog($file)
    {
        assert(!empty($file));

        $path = self::_getLogPath();

        $fileName = $path.$file;

        $fp = fopen($fileName, 'w');
        fclose($fp);
    }

    static public function Dump($var)
    {
        return print_r($var, 1);
    }

    static public function TimerStart($timerName)
    {
        assert(!empty($timerName));

        self::$timers[$timerName] = microtime(true);
    }

    static public function TimerStop($timerName)
    {
        assert(!empty($timerName));

        if(key_exists($timerName, self::$timers))
        {
            return microtime(true) - self::$timers[$timerName];
        }else{
            return 0;
        }
    }

    static public function TimerStopLog($timerName, $logFile = 'Timer.log')
    {
        $time = self::TimerStop($timerName);

        SU_DebugTools::LogAll($timerName." = ".$time, $logFile);
    }

    static public function GetTime()
    {
        return microtime(true);
    }

    
    static private function _testLogSize($path, $file)

    {       

        assert(!empty($path));
        assert(!empty($file));

        $fileName = $path.$file;
        if (!file_exists($fileName)) {
            return;
        } else {
            
        if(filesize($fileName) > self::_getMaxLogSize()) 
        
            {
                $fh = fopen('/tmp/su_debug_tools.lock', 'w+');
                if (flock($fh, LOCK_EX) || true) 
                {
                    $tempFileName=$path.'del_'.$file;
                    rename($fileName, $tempFileName);
                    $fh2=fopen($tempFileName, 'rb');
                    if( filesize($tempFileName)<69000000)
                    {
                        $fileContent=fread($fh2, filesize($tempFileName));
    
                        $gzFileName=self::_getLogPath('BACKUP').$file.'_'.date('Ymd-His').'.bak.gz';
                        $fhgz=gzopen($gzFileName, 'w');
                        gzwrite($fhgz, $fileContent);
                            
                        //copy($gzFileName, self::_getLogPath('BACKUP').$file.'_'.date('Ymd-His').'.bak.gz');
                    }
                    unlink($tempFileName);
                    flock($fh, LOCK_UN);
                    fclose($fh);
                }
            }
        }
    }

    static public function GetIP($mode = 1)
    {
        if($mode == 1)
        {
            $nu_ya_ne_znauuu = "Can't detect";
        }
        else
        {
            $nu_ya_ne_znauuu = "";
        }

        $out = array(
            'IP' => '',
            'PROXY' => ''
        );

        if (isset($_SERVER['HTTP_CLIENT_IP']))
        {
            $out['PROXY'] = $_SERVER['HTTP_CLIENT_IP'];
            $out['IP'] = $_SERVER['REMOTE_ADDR'];
        }
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $tmp_arr[] = $_SERVER['REMOTE_ADDR'];
            $tmp_arr +=  explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']);

            if(sizeof($tmp_arr) == 0)
            {
                $out['IP'] = $nu_ya_ne_znauuu;
                $out['PROXY'] = "";
            }
            else
            {
                $out['IP'] = array_pop($tmp_arr);
                $out['PROXY'] = join(",",$tmp_arr);
            }
        }
        else
        {
            $out['PROXY'] = '';
            if(key_exists('REMOTE_ADDR', $_SERVER))
            {
                $out['IP'] = $_SERVER['REMOTE_ADDR'];
            }
            else
            {
                $out['IP'] = $nu_ya_ne_znauuu;
            }
        }

        return $out;
    }

    static public function GetProfiStatus()
    {
        if(!key_exists('profi_game', $_SESSION))
        {
            return "PROFI = 0";
        }
        
        if($_SESSION['profi_game'] == 1)
        {
            return "PROFI = 1";
        }else{
            return "PROFI = 0";
        }
    }


    static public function GetFormatedIP()
    {
        $ip = self::GetIP();
        return "IP(".$ip['IP'].") PROXY(".$ip['PROXY'].")";
    }   

    static public function GetFormatedBrouserInfo()
    {
        if(key_exists('HTTP_USER_AGENT', $_SERVER))
        {
            $str = $_SERVER['HTTP_USER_AGENT']."\n";
        }else{
            $str = "Cant detect";
        }

        return $str;
    }
    
    static public function _testDiskSpace($path)
    {
        $diskFreeSpace=disk_free_space($path);
        if($diskFreeSpace>50000000)
        {
            return 'OK';
        }
        
    }
}
?>