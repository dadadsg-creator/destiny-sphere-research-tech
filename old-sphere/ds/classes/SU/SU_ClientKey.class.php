<?php $CVS_Version = '$Revision: 1.5 $ ; $Date: 2008/07/01 08:54:56 $';

class SU_ClientKey
{   
    static private $postClientKey;
    
    static public function setCheckGetVars($mode)
    {
        assert(is_bool($mode));
        assert(session_id() !== '');
        
        $_SESSION['client_key_check_get'] = $mode;
    }
    
    static public function isCheckGetVarsSet()
    {
        if (session_id() === '') exit(0);
        
        if(key_exists('client_key_check_get', $_SESSION))
        {
            return $_SESSION['client_key_check_get'];
        }
        
        return false;
    }
    
    static public function checkKey($sendToClient = true)
    {
        assert(session_id() !== '');
        if (!key_exists('client_key', $_SESSION)) exit;
        
        $sessionClientKey = $_SESSION['client_key'];
        
        if(key_exists('client_key_check_get', $_SESSION) && $_SESSION['client_key_check_get'])
        {
            $fromArr = $_REQUEST;
        } else {
            $fromArr = $_POST;
        }
        
        if(key_exists('ck', $fromArr))
        {
            self::$postClientKey = $fromArr['ck'];
        } elseif(key_exists('s_ck', $_SESSION)) {
            self::$postClientKey = $_SESSION['s_ck'];
        } else {
            self::$postClientKey = '';
        }
                
        if($sessionClientKey != self::$postClientKey)
        {
            if(key_exists('old_client_keys', $_SESSION))
            {       
                if(!in_array(self::$postClientKey, $_SESSION['old_client_keys']))
                {
                    if(!in_array(self::$postClientKey, $_SESSION['old_client_keys_arch']) && !empty(self::$postClientKey))
                    {
                        SU_DebugTools::LogAll("ERROR! WRONG CLIENT KEY\nSESSION: $sessionClientKey CLIENT: self::$postClientKey\nOLD: ".print_r($_SESSION['old_client_keys_arch'], true)."\nGET: ".self::isCheckGetVarsSet()."\nSTART SID: ".$_SESSION['session_start_sid']."\nCURRENT SID: ".session_id(), 'ClientKeyErrorsLog.log', 0, 0);
                    }
                } else {
                    return true;                    
                }
            }
            
            GE_Login::execLogout(false);
            
            if($sendToClient == true)
            {
                die("loadkey=<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<key valid=\"0\" addr=\"".GE_Login::GetPortalHome()."\"/>");
            } else {
                Header("Location: ".GE_Login::GetPortalHome());
                exit;
            }
        }
    }
    
    static public function GetLastRecivedKey()
    {
        return self::$postClientKey;
    }
    
    static public function setKey($sendToClient = true)
    {
        assert(session_id() !== '');
        // создаем новый ключ
        $newKey = GB_RandomPassword::Generate(10);
        // сохраняем старый ключ
        if(key_exists('client_key', $_SESSION))
        {
            if(!key_exists('old_client_keys', $_SESSION))
            {
                $_SESSION['old_client_keys'] = array();
                $_SESSION['old_client_keys_arch'] = array();
            }
            
            $_SESSION['old_client_keys'][] = $_SESSION['client_key'];
            $_SESSION['old_client_keys_arch'][] = $_SESSION['client_key'];
            
            if(count($_SESSION['old_client_keys']) > 5)
            {
                $_SESSION['old_client_keys'] = array_slice($_SESSION['old_client_keys'], 1, 5);
            }
            if(count($_SESSION['old_client_keys_arch']) > 10)
            {
                $_SESSION['old_client_keys_arch'] = array_slice($_SESSION['old_client_keys'], 1, 10);
            }
        }
        // заносим его в сессию
        $_SESSION['client_key'] = $newKey;
        
        if($sendToClient)
        {
            // отправляем клиенту
            SU_OutputHandler::Singleton()->addString($newKey, 'ck');
        }
    }
    
    
    static public function logNewKey($key)
    {
        assert(key_exists('uid', $_SESSION));
        $uid = (int)$_SESSION['uid'];
        assert($uid > 0);
        
        assert(key_exists('uiid', $_SESSION));
        $uiid = (int)$_SESSION['uiid'];
        assert($uiid > 0);
        
        $common = DS_Common::CreateForCurentDB();
        
        $sql = "
            INSERT INTO ds_clientkeyhistory
                (id_user, id_userinstance, client_key, sid, date)
            VALUES
                (".$uid.", ".$uiid.", ".$key.", ".session_id().", now())
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
}

?>