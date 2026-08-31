<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_DeleteUserList
{
    /**
     * Enter description here...
     *
     * @var DS_Common
     */
    static private $common;
    
    static private $deleteList = array();
    
    static private $log = 'DeleteUsersList.log';
    
    static private $deleteSlice = 10;
    
    static private $maxProcessCount = 15;
    
    static private $waitProcessEnd = 360;
    
    static private $pid = 0;
    
    static private $pid_array;
    
    static private $downStart = '330';
    
    static private $downEnd = '730';
    
    static public function Execute()
    {
        self::_log();
        self::_log("============ START TO WORK ============");
        
        self::_checkDown();
        
        self::$common = DS_Common::CreateForCurentDB(0);
        
        $deleteList = self::_getDeleteList();
        
        self::_log("FOUND ".count($deleteList)." USERS IN DELETE LIST");
        
        self::$common->stopErrorBlocking();
        

        foreach ($deleteList as $userData)
            self::_deleteUser($userData);

        /*

        self::$pid_array = array();
        
        $pid = -1;
        $iterations = 0;
       
        // а вот не знаю что за хрень, в теории выгд€дит так
        while(count($deleteList) > 0)
        {
            // берем часть пользователей на удаление
            $tmpList = array_slice($deleteList, 0, self::$deleteSlice);
            $deleteList = array_slice($deleteList, self::$deleteSlice);
            
            $iterations++;
            
            self::_log('-------======= START '.count($tmpList).' =======-------');

            foreach ($tmpList as $k => $userData)
            {               
                // форкаемс€, если можно
                if(function_exists('pcntl_fork'))
                {
                    $pid = pcntl_fork();
                }
                
                if($pid == -1)
                {
                    // если сегодн€ не форкаетс€, то просто удал€ем
                    self::_deleteUser($userData);
                } elseif($pid) {
                    // в родительском процессе собираем ѕ»ƒы детей
                    self::$pid_array[] = $pid;
                } elseif($pid == 0) {
                    // запускаем дочерний процесс
                    self::_launchChild($userData);
                }
            }
            
            // здесь должен быть только родитель
            
            // ждем окончани€ пачки процессов
            if(count(self::$pid_array) > self::$maxProcessCount)
            {
                // чтоб не скапливались процессы, ждем по-дольше
                $timeOut = 2 * self::$waitProcessEnd;
            } else {
                $timeOut = self::$waitProcessEnd;
            }       
            self::_clearProcessList($timeOut);

            
            self::_log('-----======= END ITERATION =======-----');
        }
        
        // ждем окончани€ всех процессов
        self::_clearProcessList(self::$waitProcessEnd * 2 * $iterations);
        
        self::_log("================ DONE =================");
        self::_log();
        */
    }
    
    static private function _checkDown()
    {
        if(GameDown() || (date('Hi') > self::$downStart && date('Hi') < self::$downEnd))
        {
            self::_log("========== DOWN IS OUT THERE ==========");
            self::_log();
            exit;
        }
    }
    
    static private function _launchChild(array $userData)
    {
        // в дочернем процессе удал€ем пользовател€ одного и вываливаемс€
        if(function_exists('posix_getpid'))
        {
            self::$pid = posix_getpid();
        } else {
            self::$pid = 'c_'.rand(100, 2000);
        }
        
        self::$common->disconnectFromAll();
        self::$common = DS_Common::CreateForCurentDB(0);
        self::_deleteUser($userData);
        exit;
    }
    
    static private function _clearProcessList($timeOut)
    {
        assert(is_int($timeOut) && $timeOut >= 0);
        
        $startTime = time();
        
        while(count(self::$pid_array) > 0)
        {
            usleep(1000);
            
            foreach (self::$pid_array as $k => $pid)
            {
                if(pcntl_waitpid($pid, $status, WNOHANG || WUNTRACED) || !posix_kill($pid, 0))
                { 
                    unset(self::$pid_array[$k]);
                    self::_log('PROCESS '.$pid.' DIED. '.count(self::$pid_array).' LEFTED');
                }
            }
            
            if((time() - $startTime) > $timeOut)
            {
                break;
            }
        }
    }
    
    static private function _log($msg = '')
    {
        if(!empty($msg))
        {
            $str = date('Y-d-m H:i:s').'  ';
            $str .= self::$pid;
            if(self::$pid == 0)
            {
                $str .= "\t  ";
            }
            $msg = $str.'   '.$msg;
        }
        $msg .= "\n";
        
        if(strpos($sapi, "apache") === false)
        {
            echo $msg;
        }
        
        SU_DebugTools::WriteToLog($msg, self::$log);
    }
    
    static private function _markAsDelete($id)
    {
        assert(is_int($id) && $id > 0);
        
        self::$common->setDbId(0);
        
        $sql = "
            UPDATE
                ds_user_delete_list
            SET
                delete_date = now()
            WHERE
                id_user_delete_list = $id
            ";
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));
    }
    
    static private function _getDeleteList()
    {
        $sql = "
            SELECT
                u.username,
                udl.*
            FROM
                ds_user_delete_list AS udl
                INNER JOIN ds_user AS u USING (id_user)
            WHERE
                delete_date IS NULL
            ORDER BY add_date
            ";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        return $res;
    }
    
    static private function _deleteUser(array $data)
    {
        self::_checkDown();
        
        assert(key_exists('username', $data));
        assert(key_exists('world_identifier', $data));      
        assert(key_exists('id_user', $data));
        $userID = (int)$data['id_user'];
        assert($userID > 0);
        assert(key_exists('world_identifier', $data));
        $world = DE_WorldFromCollection::CreateFromID($data['world_identifier']);
        assert(key_exists('id_user_delete_list', $data));
        
        self::_log("DELETE ".$data['username']." IN WORLD ".$data['world_identifier']);
SU_DebugTools::LogAll($world->getDBName(), 'AnnitaDeleteUserList.log');     
        $dbId = self::$common->getDBIDByName($world->getDBName());
        self::$common->setDbId($dbId);
SU_DebugTools::LogAll($dbId." ".$data['world_identifier'], 'AnnitaDeleteUserList.log');

        self::$common->startTrans();
        
        self::$common->executeQuery("SET statement_timeout TO 900000");
        assert(empty(self::$common->errormsg));

        $sql = "    
            SET STATEMENT_TIMEOUT to 120000;
            SELECT
                drop_uiid(id_userinstance)
            FROM
                ds_userinstance AS ui
            WHERE
                ui.id_world = '".$world->getWorldID()."'
                AND
                ui.id_user = $userID;
            ";
SU_DebugTools::LogAll($sql, 'AnnitaDeleteUserList.log');
        
        if ($dbId == 1 && $data['world_identifier'] != 'PHN') {
SU_DebugTools::LogAll($sql, 'AnnitaDeleteUserListError.log');
        } else {
            self::$common->executeQuery($sql);
        }
        $mode = 'ROLLBACK';
        if(empty(self::$common->errormsg))
        {
            self::_markAsDelete((int)$data['id_user_delete_list']);
            $mode = 'COMMIT';
            self::_log("OK.");
        } else {
            self::_log("ERROR.");
            self::$common->errormsg = '';
        }
        
        self::$common->setDbId($dbId);
        self::$common->closeTrans($mode);
    }
    
    static public function AddToList(DE_User $user, DE_WorldFromCollection $world, $reason = 'ADMIN')
    {
        self::$common = DS_Common::CreateForCurentDB(0);
        assert(preg_match('/^[A-Z]+$/', $reason));
        
        $sql = "
            INSERT INTO ds_user_delete_list
                (id_user, world_identifier, delete_reason)
            VALUES
                (".$user->getID().", '".$world->getShortName()."', ".self::_getReasonID($reason).")
            ";
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));
    }
    
    static private function _getReasonID($reasonName)
    {
        assert(preg_match('/^[A-Z]+$/', $reasonName));
        
        $sql = "
            SELECT
                id_delete_reason
            FROM
                ds_user_delete_reason
            WHERE
                reason_name = '$reasonName'
            ";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg) && count($res) == 1);
        
        return (int)$res[0]['id_delete_reason'];
    }
}

?>