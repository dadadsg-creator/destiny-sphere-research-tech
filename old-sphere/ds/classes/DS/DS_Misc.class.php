<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * статический Класс контейнер для SQL зпросов свзяанных с сотами.
 */
class DS_Misc extends DS_Base
{
    
    static public function GetLocolizedText($ID, $langID)
    {
        
        assert(is_int($ID) && $ID>=0);
        assert(is_int($langID) && $langID>0);
        
        $common = self::getCommon();
        
        $sql =  "SELECT ltl.text
                FROM ds_localizedtextlanguage as ltl
                WHERE
                    ltl.id_localizedtext = ".$ID." AND
                    ltl.id_language = ".$langID;
        
        $res = $common->getResultArray (self::FormMark(__METHOD__, $ID."_".$langID), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        
        if(!$res) 
        {           
            return false;
        }else{
            return $res[0];
        }
    }//END OF METHOD    

    static public function GetAllLocolizedText($langID, $withCache = true)
    {
                
        assert(is_int($langID) && $langID>0);
        
        $common = self::getCommon();
        
        if($withCache)
        {
            $cacheType = 'GLOBAL_CACHE';
        }else{
            $cacheType = '';
        }
        
        $sql =  "SELECT 
                    ltl.id_localizedtext as id,
                    ltl.text
                FROM ds_localizedtextlanguage as ltl
                WHERE       
                    ltl.id_language = ".$langID;        
        $res = $common->getResultArray (self::FormMark(__METHOD__, $langID), $sql, "", "SHORT_PREFIX", $cacheType);
        assert(empty($common->errormsg));
                
        return $res;        
    }//END OF METHOD    
    
    static public function GetServerSideInterfaceLocalization($langID, $withCache = true)
    {
                
        assert(is_int($langID) && $langID>0);
        
        $common = self::getCommon();
        
        if($withCache)
        {
            $cacheType = 'GLOBAL_CACHE';
        }else{
            $cacheType = '';
        }
        
        $sql =  "SELECT 
                    *
                FROM 
                    ds_serversideinterface AS sl
                    INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = sl.id_localizedtext)
                WHERE       
                    ltl.id_language = ".$langID;        
        $res = $common->getResultArray (self::FormMark(__METHOD__, $langID), $sql, "", "SHORT_PREFIX", $cacheType);
        assert(empty($common->errormsg));
                
        return $res;        
    }//END OF METHOD    
    
    static public function GetLocolizedSystemMessage($messageName, $langID)
    {       
        assert(!empty($messageName));
        $langID = (int)$langID;
        assert($langID>0);
        
        $common = self::getCommon();
        
        $sql =  "
            SELECT
                ltl.text as message,
                sm.numberofparams
            FROM
                ds_systemmessage as sm
                INNER JOIN ds_localizedtextlanguage as ltl on (sm.id_localizedtext = ltl.id_localizedtext)
            WHERE   
                sm.messagename = '".$messageName."'
                AND
                ltl.id_language = ".$langID;
        
        $res = $common->getResultArray (self::FormMark(__METHOD__, $messageName."_".$langID), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        if(!$res) 
        {           
            return false;
        }else{
            return $res[0];
        }
    }
    
    public static function GetNow()
    {
        $common = self::getCommon();
        
        $sql =  "SELECT now() as now";      
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        return $res[0]['now'];
    }
    
    static public function GetInterfaceLocolization($langID)
    {
        assert(is_int($langID) && $langID>0);
        
        $common = self::getCommon();
        
        $sql =  "SELECT
                    i.interfacename,
                    ltl.text
                FROM
                    ds_interface AS i
                    INNER JOIN ds_localizedtext AS lt ON (i.id_localizedname = lt.id_localizedtext)
                    INNER JOIN ds_localizedtextlanguage AS ltl ON (lt.id_localizedtext = ltl.id_localizedtext)
                WHERE
                    ltl.id_language = ".$langID;        
        $res = $common->getResultArray (self::FormMark(__METHOD__, $langID), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        
        if(!$res) 
        {           
            return false;
        }else{
            return $res;
        }
    }
    
    static public function GetResourceByID($ID, $withCache = true)
    {
        assert(is_int($ID) && $ID>0);
        
        $common = self::getCommon();
        
        if($withCache)
        {
            $caheType = "GLOBAL_CACHE";
        }else{
            $caheType = "";
        }
        
        $sql =  "
            SELECT 
                public.ds_attributename.attributename,
                public.ds_attributename.id_localizedname,
                public.ds_resource.id_locolizedshortname,
                public.ds_resource.id_attributename,
                public.ds_resource.exchangerate,
                public.ds_resource.startamount
            FROM
                public.ds_resource
                INNER JOIN public.ds_attributename ON (public.ds_resource.id_attributename = public.ds_attributename.id_attributename)
            WHERE
                (public.ds_resource.id_attributename = ".$ID.")";       
        $res = $common->getResultArray (self::FormMark(__METHOD__, $ID), $sql, "", "SHORT_PREFIX", $caheType);
        assert(empty($common->errormsg));
        
        if(!$res) 
        {           
            return false;
        }else{
            return $res[0];
        }
    }
    
    static public function GetResourceIDByName($name)
    {
        assert(!empty($name));
        
        $common = self::getCommon();
        
        $sql =  "
            SELECT 
                public.ds_resource.id_attributename
            FROM
                public.ds_resource
                INNER JOIN public.ds_attributename ON (public.ds_resource.id_attributename = public.ds_attributename.id_attributename)
            WHERE
                (public.ds_attributename.attributename = '".$name."')";     
        $res = $common->getResultArray (self::FormMark(__METHOD__, $name), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        
        if(!$res) 
        {           
            return false;
        }else{
            return (int)$res[0]['id_attributename'];
        }
    }
    
    static public function GetAllAtrributes($withCache = true)
    {
        $common = self::getCommon();
        
        if($withCache)
        {
            $cacheType = "GLOBAL_CACHE";
        }else{
            $cacheType = "";
        }
        
        $sql =  "SELECT 
                    a.id_attribute,
                    an.attributename,
                    at.typename,
                    an.id_attributename,
                    at.id_attributetype,
                    an.id_localizedname
                 FROM
                    ds_attribute AS a
                    INNER JOIN ds_attributename AS an ON (an.id_attributename = a.id_attributename)
                    INNER JOIN ds_attributetype AS at ON (at.id_attributetype = a.id_attributetype)";       
        $res = $common->getResultArray (self::FormMark(__METHOD__), $sql, "", "SHORT_PREFIX", $cacheType);
        assert(empty($common->errormsg));

        return $res;    
    }
    
    static public function GetAttributeNameBuID($attributeID)
    {
        assert($attributeID>0);
        
        $common = self::getCommon();
        
        $sql = "SELECT attributename 
                FROM ds_attributename
                WHERE id_attributename = ".$attributeID;
        $res = $common->getResultArray ( self::FormMark(__METHOD__, $attributeID), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
        assert(empty($common->errormsg));

        assert(count($res)>0);
        
        return $res[0]['attributename'];
    }
    
    static public function GetSystemMessages(DE_SystemMessages $systemMessages)
    {       
        $common = self::getCommon();
        
        $sql = "SELECT *
                FROM ds_userinstancesystemmessages
                WHERE id_userinstance = ".$systemMessages->getID()."
                ORDER BY id_userinstancesystemmessages DESC
                ";
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        return $res;                        
    }
    
    static public function DeleteSystemMessages(DE_SystemMessages $systemMessages)
    {       
        $common = self::getCommon();
        
        $sql = "DELETE FROM ds_userinstancesystemmessages
                WHERE id_userinstance = ".$systemMessages->getID();
        $res = $common->getResultArray ("", $sql, "", "", "");      

        assert(empty($common->errormsg));
    }
    
    /*static public function UpdateSystemMessages(DE_SystemMessages $systemMessages)
    {       
        $common = self::getCommon();
        
        $sql = "UPDATE ds_userinstancesystemmessages
                SET  messages = '".$systemMessages->getForOut()."'
                WHERE id_userinstance = ".$systemMessages->getID();
        $res = $common->getResultArray ("", $sql, "", "", "");      
        assert(empty($common->errormsg));
    }*/
    
    static public function InsertSystemMessages(DE_SystemMessages $systemMessages)
    {       
        $common = self::getCommon();
        
        $sql = "INSERT INTO ds_userinstancesystemmessages
                (id_userinstance, messages)
                VALUES
                (".$systemMessages->getID().", '".pg_escape_string($systemMessages->getForOut())."')";
        $res = $common->getResultArray ("", $sql, "", "", "");

        assert(empty($common->errormsg));
    }       
    
    static public function GetGlobalSettings()
    {
        $common = self::getCommon();
        
        $sql = "
            SELECT
                *
            FROM
                ds_global_settings
            ";
        $res = $common->getResultArray ("GLOBAL_GAME_SETTINGS", $sql, "", "", "GLOBAL_CACHE");
        assert(empty($common->errormsg));                       
    
        return $res;
    }
    
    /**
     * Логирование ошибок локинга
     * 
     * @magic Вызывать эту функцию можно только если скрипт уже умирает, текущая транзакция откатывается
     *
     * @param unknown_type $reason
     * @param unknown_type $time
     * @param unknown_type $info
     */
    static public function LogLockerError($reason, $time, $info)
    {
        assert(!empty($reason));
        assert(is_int($time));
        if(!key_exists('uid', $_SESSION))
        {
            return;
        }
        assert(key_exists('uid', $_SESSION));
        
        $common = self::getCommon();
        $common->setDbId(0);
        
        //
        $common->closeTrans('ROLLBACK');
        
        $sql = "
            INSERT INTO ds_user_locking_error
                (id_user, id_worldcollection, date, reason, lock_time, info)
            SELECT
                ".$_SESSION['uid'].", 
                id_worldcollection, 
                now(), 
                E'".pg_escape_string($reason)."', 
                $time, 
                E'".pg_escape_string($info)."'
            FROM
                ds_userinstancecollection
            WHERE
                id_user = ".$_SESSION['uid']."
                AND
                id_userinstance = ".$_SESSION['uiid']."
            ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
        
        $common->setDbId($_SESSION['dbId']);
    }
    
    /////////////////////////////////////////////////////
    
    static public function GetMoneyByID($id)
    {       
        assert(is_integer($id) && $id>0);
        $common = self::getCommon();
        
        $sql = "SELECT *
                FROM ds_money
                WHERE id_money = ".$id;
        $res = $common->getResultArray (self::FormMark(__METHOD__, $id), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg) && count($res)== 1);

        return $res[0]; 
    }        
    
    static public function GetMoneyIDByShortName($shortName)
    {       
        assert(!empty($shortName));
        
        $common = self::getCommon();
        
        $sql = "SELECT id_money
                FROM ds_money
                WHERE short_name = '".$shortName."'";
        $res = $common->getResultArray (self::FormMark(__METHOD__, $shortName), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg) && count($res)== 1);                        
                
        return (int)$res[0]['id_money'];                
    }        
    
    static public function GetMoneyIDByName($name)
    {       
        assert(!empty($name));
        
        $common = self::getCommon();
        
        $sql = "SELECT id_money
                FROM ds_money
                WHERE money_name = '".$name."'";
        $res = $common->getResultArray (self::FormMark(__METHOD__, $name), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg) && count($res)== 1);                        
        
        return (int)$res[0]['id_money'];                
    }        
}//END OF CLASS


?>