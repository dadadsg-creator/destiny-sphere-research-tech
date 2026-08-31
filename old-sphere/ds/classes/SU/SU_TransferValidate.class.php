<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_TransferValidate
{
    /**
     * Enter description here...
     *
     * @var DS_Common
     */
    static private $common;
    
    static private $dbID;
    
    static private $worldID;
    
    static private $logFile = 'TransferValidation.log';
    
    static private $testResult = array();
    
    static private $mailTo = array(
            'nikita@destinysphere.com'
        );
    
    static public function Validate($dbID, $worldID)
    {
        assert(is_int($dbID) && $dbID >= 0);
        assert(is_int($worldID) && $worldID > 0);
        
        self::_out();
        self::_out("=========== START VALIDATION DB $dbID WORLD $worldID ============");
        
        if(GameDown($dbID, $worldID))
        {
            self::_out('---==== GAME DOWN ====---');
            return;
        }
        
        self::$dbID = $dbID;
        self::$common = DS_Common::CreateForCurrentDB(self::$dbID);
        self::$worldID = $worldID;
        
        $logs = self::_getTransferLog();
        
        foreach ($logs as $k => $log)
        {
            assert(key_exists('id_worldtransfer_log', $log));
            if(self::_validate($log))
            {
                // помечаем что переезд проверен
                self::_markAsValid((int)$log['id_worldtransfer_log']);
            } else {
                // морозим соту
                // self::_freezUser($log);
            }
        }
        
        self::_formStat();
        
        self::_out('================== VALIDATION DONE ===================');
        self::_out();
    }
    
    static private function _out($msg = '')
    {
        if(!empty($msg))
        {
            $msg = date('Y-d-m H:i:s')." - ".$msg;
        }
        
        $msg .= "\n";
        
        echo $msg;
        
        SU_DebugTools::WriteToLog($msg, self::$logFile);
    }
    
    static private function _addResultToStat($userName, $result, $msg = '')
    {
        $validResult = array('OK', 'INFO', 'WARNING', 'ERROR');
        
        assert(VD_UserName::Validate($userName));
        
        self::_out($userName.'  >>>>>> '.$result.' '.$msg);
        
        self::$testResult[] = array('USER' => $userName, 'RES' => $result, 'MSG' => $msg);
    }
    
    static private function _formStat()
    {
        $colors = array('OK' => '#00ff00', 'INFO' => '#a8cd3e', 'WARNING' => '#c3c600', 'ERROR' => '#ff0000');
        
        $str = '';
        $head = '[DS] Transfer validation';
        $str .= 'DATE: '.date('<\b>Y-d-m</\b> H:i:s')."<br>\n";
        $str .= 'DB: <b>'.$GLOBALS['dbWorldAccess'][self::$dbID]['server'].'</b> WORLD: <b>'.self::$worldID."</b><br>\n";
        $str .= 'total users checked: <b>'.count(self::$testResult)."</b><br><hr><br>\n";
        
        $countErr = 0;
        
        $str .= '<table border=1>';
        $str .= '<tr>';
        $str .= '<td width=15%><b>USER NAME</b></td><td width=20%><b>RES</b></td><td><b>INFO</b></td>';
        $str .= '</tr>';
        
        foreach (self::$testResult as $k => $tr)
        {
            $str .= '<tr>';
                        
            if($tr['RES'] == 'ERROR')
            {
                $countErr++;    
            }
            
            $str .= '<td>'.$tr['USER'].'</td><td color="'.$colors[$tr['RES']].'"><b>'.$tr['RES'].'</b></td><td>'.$tr['MSG']."</td>\n";
            $str .= '</tr>';
        }
        $str .= '</table>';
        
        $str .= '<br>total users with double active combs: <b>'.$countErr."</b><br>\n";
        
        $eol="\r\n";
        # Common Headers
        $headers  = 'From: noreply <noreply@destinysphere.com>'.$eol;
        $headers .= "Message-ID: <".time()." TheSystem@".$_SERVER['SERVER_NAME'].">".$eol;
        $headers .= "X-Mailer: PHP v".phpversion().$eol; 
        $headers .= "Content-Type: text/html; charset=windows-1251".$eol;
        
        mail(implode(',',self::$mailTo), $head, $str, $headers);
    }
    
    static private function _getTransferLog()
    {
        self::$common->setDbId(self::$dbID);
        
        $sql = "
            SELECT
                wt.*,
                u.username
            FROM
                ds_worldtransfer_log AS wt
                INNER JOIN ds_user AS u USING (id_user)
            WHERE
                id_world = ".self::$worldID."
                AND
                (
                    NOT transfer_valid
                    OR
                    transfer_valid IS NULL
                )
                
            ";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));     
        
        return $res;
    }
    
    static private function _freezUser(array $data)
    {
        assert(key_exists('id_user', $data));
        assert(key_exists('id_world', $data));
        
        self::$common->setDbId(self::$dbID);
        
        $sql = "
            ISNERT INTO ds_vocationmode
                (id_userinstance, startdate, enddate, password)
            SELECT
                id_userinstance,
                now(),
                null,
                'pereezd_".rand()."'
            FROM
                ds_userinstance
            WHERE
                id_user = ".(int)$data['id_user']."
                AND
                id_world = ".(int)$data['id_world']."
            ";
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));
    }
    
    static private function _validate(array $data)
    {
        assert(key_exists('id_user', $data));
        assert(key_exists('username', $data));
        assert(key_exists('id_world', $data));
        assert(key_exists('transfer_date', $data));
        assert(key_exists('account_freezed', $data));
        assert(key_exists('added_to_delete_list', $data));
        assert(key_exists('id_worldtransfer_log', $data));
        
        $userName = $data['username'];
        $resCode = 'OK';
        
        $accountData = self::_getAccountData((int)$data['id_user'], (int)$data['id_world']);
        
        // нет начальной соты нет проблем
        if(count($accountData) == 0)
        {
            self::_addResultToStat($userName, 'OK');
            return true;
        }
        
        // сота есть, но она в очереди на удаление
        if($data['added_to_delete_list'] == 't' && self::_checkDeleteList((int)$data['id_user'], $accountData['world_identifier']))
        {
            self::_addResultToStat($userName, 'INFO', 'not deleted, waiting in death row');
            return true;
        }
        
        // сота не в очереди, но заморожена
        if($data['account_freezed'] == 't' && !empty($accountData['startdate']) && empty($accountData['enddate']))
        {
            self::_addResultToStat($userName, 'WARNING', 'freezed comb');
            return true;
        }
        
        // нет дубля, тож нету проблем, если не считать неудавшегося переезда
        $doubleComb = self::_checkDoubleComb((int)$data['id_user'], $data['transfer_date']);
        if(count($doubleComb) == 0)
        {
            self::_addResultToStat($userName, 'INFO', 'no transfer');
            return true;
        } else {
            // дубль заморожен
            if(!empty($doubleComb['startdate']) && empty($doubleComb['enddate']))
            {
                self::_addResultToStat($userName, 'WARNING', 'double comb freezed');
                return true;
            }
        }
        
        // есть дубль
        self::_addResultToStat($userName, 'ERROR', 'double comb');
        
        return false;
    }
    
    
    static private function _checkDoubleComb($userID, $transferDate)
    {
        assert(is_int($userID) && $userID > 0);
        $date = GB_TypeFactory::CreateBase('GB_Date', $transferDate);
        $endDate = GB_TypeFactory::CreateBase('GB_Date', $transferDate);
        $endDate->value += 1800;
            
        $sql = "
            SELECT
                *
            FROM
                ds_userinstance AS ui
                INNER JOIN ds_world AS w USING (id_world)
                LEFT JOIN ds_vocationmode AS vm USING (id_userinstance)
            WHERE
                ui.id_user = $userID
                AND
                ui.creation_date > '".$date->getPgFormat()."'
                AND
                ui.creation_date < '".$endDate->getPgFormat()."'
            ";
        
        foreach ($GLOBALS['dbWorldAccess'] as $dbID => $db)
        {
            self::$common->setDbId($dbID);
            $res = self::$common->getResultArray('', $sql);
            assert(empty(self::$common->errormsg));
            
            if(count($res) > 0)
            {
                if($dbID == self::$dbID && $res[0]['id_world'] == self::$worldID)
                {
                    continue;
                }
                
                return $res[0];
                break;
            }
        }
        
        return array();
    }
    
    static private function _checkDeleteList($userID, $world)
    {
        assert(is_int($userID) && $userID > 0);
        assert(preg_match('/^[A-Z]{1,3}$/', $world));
        
        self::$common->setDbId(0);
        
        $sql = "
            SELECT
                *
            FROM
                ds_user_delete_list
            WHERE
                id_user = $userID
                AND
                world_identifier = '$world'
                AND
                delete_date IS NULL
            ";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        if(count($res))
        {
            return true;
        }
        
        return false;
    }
    
    static private function _markAsValid($worldTransferLogID)
    {
        assert(is_int($worldTransferLogID) && $worldTransferLogID > 0);
        
        self::$common->setDbId(self::$dbID);
        
        $sql = "
            UPDATE
                ds_worldtransfer_log
            SET
                transfer_valid = true
            WHERE
                id_worldtransfer_log = $worldTransferLogID
            ";
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));
    }
    
    static private function _getAccountData($userID, $worldID)
    {
        self::$common->setDbId(self::$dbID);
        
        $sql = "
            SELECT
                ui.creation_date,
                vm.*,
                w.world_identifier
            FROM
                ds_userinstance AS ui
                INNER JOIN ds_world AS w USING (id_world)
                LEFT JOIN ds_vocationmode AS vm ON (vm.id_userinstance = ui.id_userinstance AND vm.password like 'pereezd%')
            WHERE
                ui.id_user = $userID
                AND
                ui.id_world = $worldID
            LIMIT 1
            ";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        if(count($res) > 0)
        {
            return $res[0];
        }
        
        return $res;
    }
}


?>