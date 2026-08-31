<?php $CVS_Version = '$Revision$ ; $Date$';
class ME_LoginHistory
{
    static $common;
    static $id_loginhistry = false;
    static $idlogincookie = false;
    static $userID;

    public static function Exec($userID, $common)
    {       
        settype ($userID, 'integer');
        assert ($userID > 0);
        assert(is_a($common, 'DS_Common'));

        ME_LoginHistory::$common = $common;
        ME_LoginHistory::$userID = $userID;

        if (!ME_LoginHistory::isSetCookie())
        {
            ME_LoginHistory::_insertLoginCookie();
        } elseif(!ME_LoginHistory::checkCookiePresentInDB($common))
        {                       
            ME_LoginHistory::_insertLoginCookie();
        }

        ME_LoginHistory::_insertLoginHistory();
        ME_LoginHistory::_setCookie();

        return true;
    }
    
    public static function checkCookiePresentInDB($common)
    {   
        $sql = "SELECT
                        count(lc.*)
                    FROM
                        ds_logincookie lc
                    WHERE
                        lc.id_cookie =".ME_LoginHistory::$idlogincookie;
        
        $res = $common->getResultArray ( "", $sql, "", "", "" );
            
        assert(empty($common->errormsg) && count($res) == 1);
        
        if($res[0]['count'] > 0)
        {
            return true;
        }
        
        return false;
    }

    public static function isSetCookie()
    {
        if (isset($_COOKIE['cnt']) && key_exists(ME_LoginHistory::_domainIdentifier(), $_COOKIE['cnt']) && $_COOKIE['cnt'][ME_LoginHistory::_domainIdentifier()])
        {
            ME_LoginHistory::$idlogincookie = (int)$_COOKIE['cnt'][ME_LoginHistory::_domainIdentifier()];
            return true;
        }
        return false;
    }

    public static function _getNewLoginHistory()
    {       
        if (ME_LoginHistory::$id_loginhistry)
        {   
            return ME_LoginHistory::$id_loginhistry;
        } else {
            $common = ME_LoginHistory::$common;
            assert(is_a($common, 'DS_Common'));

            $sql = "select nextval('ds_loginhistory_id_loginhistory_seq') as id";
            $res = $common->getResultArray ( "", $sql, "", "", "" );
            
            assert(empty($common->errormsg) && count($res) == 1);

            ME_LoginHistory::$id_loginhistry = (int)$res[0]['id'];      
                
            return (int)$res[0]['id'];
        }
    }

    public static function _getNewCookieID()
    {
        if (ME_LoginHistory::$idlogincookie)
        {
            return ME_LoginHistory::$idlogincookie;
        } 
        else 
        {
            $common = ME_LoginHistory::$common;
            assert(is_a($common, 'DS_Common'));

            $sql = "select nextval('ds_logincookie_id_cookie_seq') as id";
            $res = $common->getResultArray ( "", $sql, "", "", "" );
            assert(empty($common->errormsg) && count($res) == 1);

            ME_LoginHistory::$idlogincookie = (int)$res[0]['id'];
            return (int)$res[0]['id'];
        }
    }

    public static function _insertLoginHistory()
    {
        $common = ME_LoginHistory::$common;
        assert(is_a($common, 'DS_Common'));

        $dated = ME_LoginHistory::GetInternalDate();

        $sql = "INSERT INTO ds_loginhistory (id_loginhistory, id_cookie, lastvisit, ip, ip_proxy, id_user)
                    VALUES
                (".ME_LoginHistory::_getNewLoginHistory().",".
                    ME_LoginHistory::_getNewCookieID().
                    ",to_timestamp ('".$dated."', 'YYYY-MM-DD HH24:MI'),".
                    ME_LoginHistory::GetFormatedIP().",".
                    ME_LoginHistory::GetFormatedProxy().", ".
                    ME_LoginHistory::$userID.");
        ";

        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));   
        
        $sql = "UPDATE ds_last_refresh SET lastrefresh = to_timestamp ('".$dated."', 'YYYY-MM-DD HH24:MI')
        WHERE id_user = ".ME_LoginHistory::$userID;
        
        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }

    public static function _insertLoginCookie()
    {
        $common = ME_LoginHistory::$common;
        assert(is_a($common, 'DS_Common'));

        $dated = ME_LoginHistory::GetInternalDate();
        ME_LoginHistory::$idlogincookie = ME_LoginHistory::_getNewCookieID();
        $sql = "
                INSERT INTO ds_logincookie (id_cookie, putdate, domain)
                    VALUES
                (".ME_LoginHistory::$idlogincookie.",to_timestamp ('".$dated."', 'YYYY-MM-DD HH24:MI'), '".$_SERVER['HTTP_HOST']."');
        ";
//echo $sql;
        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }

    public static function _domainIdentifier()
    {
        return md5($GLOBALS['dbWorldAccess'][0]['host']);
    }

    public static function _setCookie()
    {

        $cookieName = "cnt[".ME_LoginHistory::_domainIdentifier()."]";
        setcookie($cookieName, ME_LoginHistory::_getNewCookieID(), time()+31536000);
    }

    public static function GetFormatedIP()
    {
        $array = SU_DebugTools::GetIP(2);
        if ($array['IP'] == "")
        {
            return "NULL";
        } else {
            return "'".$array['IP']."'";
        }
    }

    public static function GetFormatedProxy()
    {
        $array = SU_DebugTools::GetIP(2);
        if ($array['PROXY'] == "")
        {
            return "NULL";
        } else {
            return "'".$array['PROXY']."'";
        }
    }

    public static function GetInternalDate()
    {
        return date("Y-m-d H:i:s");
    }
}


?>