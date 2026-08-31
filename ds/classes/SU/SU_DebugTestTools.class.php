<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_DebugTestTools
{
    static private $disabledDB = array();
    
    static public function Add($type)
    {
        if(in_array($_SESSION['dbId'], self::$disabledDB))
        {
            return;
        }
        
        $sql = "
            INSERT INTO ds_cheater_log
                (id_user, date, post_data, protocol_history, session_data, log_type)
            VALUES
                (".$_SESSION['uid'].", now(), E'".pg_escape_string(print_r($_POST, true))."', E'".pg_escape_string(SU_DebugTools::GetFormatedProtocolHistory())."', E'".self::_getSessionData()."', '".$type."')
            ";
        $common = DS_Common::CreateForCurentDB();
        $common->executeQuery($sql);
        assert(empty($common->errormsg));       
    }
    
    static private function _getSessionData()
    {
        $res = '';
        
        foreach ($_SESSION as $key => $value)
        {
            if(!is_array($value))
            {
                $res .= $key.' => '.$value."\n";
            } else {
                $res .= $key.' => array['.count($value)."]\n";
            }
        }
        
        return pg_escape_string($res);
    }
    
    static public function UpdateCounter($uiid, $id_useraction = 0)
    {
        if(in_array($_SESSION['dbId'], self::$disabledDB))
        {
            return;
        }
        
        assert(is_int($uiid) && $uiid > 0);
        assert(is_int($id_useraction) && $id_useraction >= 0);
        
        $common = DS_Common::CreateForCurentDB();
        $sql = "
            SELECT
                count
            FROM
                ds_request_counter
            WHERE
                id_userinstance = $uiid
                AND
                id_useractiontype = $id_useraction
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        if(count($res) > 0)
        {
            $sql = "
                UPDATE
                    ds_request_counter
                SET
                    count = count + 1
                WHERE
                    id_userinstance = $uiid
                    AND
                    id_useractiontype = $id_useraction
                ";
        } else {
            $sql = "
                INSERT INTO ds_request_counter
                    (id_userinstance, count, id_useractiontype)
                VALUES
                    ($uiid, 1, $id_useraction)
                ";
        }
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function LogAction($uiid, $id_useraction, $xmldata, $exec_time)
    {
        if(in_array($_SESSION['dbId'], self::$disabledDB))
        {
            return;
        }

        assert(is_int($uiid));
        assert(is_int($id_useraction) && $id_useraction >= 0);
        
        $tempArr = explode('?>', $xmldata);
        $xmldata = substr($tempArr[1], 0 , 255);
        $xmldata = substr($xmldata, 0 , 255);
        $xmldata = pg_escape_string($xmldata);
        
        
        $sql = "
            INSERT INTO ds_request_log
                (id_userinstance, id_useractiontype, xmldata, exec_time)
            VALUES
                ($uiid, $id_useraction, E'$xmldata', $exec_time)
            ";
        $common = DS_Common::CreateForCurentDB();
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
}

?>