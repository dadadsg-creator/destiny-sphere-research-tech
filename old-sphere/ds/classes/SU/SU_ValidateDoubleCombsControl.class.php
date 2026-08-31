<?php
/********************************
 *      Валидация переездов     *
 ********************************
  
1) запуск из браузера

- берем список всех игроков не админов, у которых более одного инстанса     
        
- для каждого смотрим
    - если реально нету инстанса, а только запись в колекции, то ничего не делаем
    - если лишняя сота(ы) записана в очередь на удаление то все нормально, но предупреждаем, если она там давно
    - если лишняя сота(ы) заморожена то предупреждаем (опционально можно добавить в очередь на удаление)
    - если активны, то
        - ссылка на сводную таблицу по аккаунтам игрока
        в таблице поля: 
            дата создания инстанса, 
            кол-во логинов, 
            дата последнего логина, 
            кол-во торгов всего (отдельно кол-во с момента появления лишней соты), 
            кол-во торгов за серебро (отдельно кол-во с момента появления лишней соты)
        - кнопки заморозить и/или в очередь на удаление
                
        
2) запуск из консоли
        
Формируется отчет, и рассылается по мылу
        
    - если лишняя сота(ы) записаны в очередь на удаление то все нормально, но предупреждаем, если она там давно
    - если лишняя сота(ы) заморожены то предупреждаем
    - если активны, то ошибка
        
*/
class SU_ValidateDoubleCombsControl
{
    static private $logFile = 'TransferValidation2.log';
    
    static private $action;
    
    static private $mode;
    
    /**
     * Enter description here...
     *
     * @var DE_User
     */
    static private $user;
    
    /**
     * Enter description here...
     *
     * @var DE_World
     */
    static private $world;
    
    static private $count;
    
    static private $mailTo = array('nikita@destinysphere.com');
    
    static public function Exec(array $data)
    {       
        if(GameDown())
        {
            self::_log('---==== GAME DOWN ====---');
            return;
        }
                
        self::_getParams($data);
        
        SU_ValidateDoubleCombsOut::SetMode(self::$mode);
        
        switch (self::$action) {
            case 'validate':
                self::_validate();
                break;
                
            case 'index':
                self::_index();
                break;
        
            case 'delete':
                self::_delete();
                break;
                
            case 'freez':
                self::_freez();
                break;
                
            case 'show':
                self::_showInfo();
                break;
                
            case 'allow':
                self::_allow();
                break;
                
            default:
                self::_log('>>>>>>>>>>>>> ERROR: Unknown action "'.self::$action.'"');
                break;
        }
    }
    
    static private function _getParams(array $data)
    {
        if(php_sapi_name() == 'apache')
        {
            self::$mode = 'screen';
        } else {
            self::$mode = 'console';
        }
        
        if(key_exists('action', $data))
        {
            self::$action = $data['action'];
        }
        
        if(empty(self::$action))
        {
            if(self::$mode == 'console')
            {
                self::$action = 'validate';
            } else {
                self::$action = 'index';
            }
        }
        
        if(key_exists('user_id', $data))
        {
            self::$user = DE_User::CreateFromID((int)$data['user_id']);
        }
        
        if(key_exists('username', $data))
        {
            if(!VD_UserName::Validate($data['username']) || !VD_UserName::CheckIfExists($data['username'], SU_ValidateDoubleCombsSearch::GetCommon()))
            {
                self::$action = 'index';
            } else {
                self::$user = DE_User::CreateFromName($data['username']);
            }
        }
        
        if(key_exists('world', $data))
        {
            self::$world = DE_WorldFromCollection::CreateFromID($data['world']);
        }
        
        if(key_exists('count', $data))
        {
            self::$count = (int)$data['count'];
        }
    }
    
    static private function _showInfo()
    {
        assert(self::$user instanceof DE_User);
        
        GE_SearchStart::SetCommon(SU_ValidateDoubleCombsSearch::GetCommon());
        $worlds = GE_SearchStart::GetWorldsWhereUserIsRegistered(self::$user->getID());
        
        $result = array();
        
        foreach ($worlds as $k => $w)
        {
            $data = SU_ValidateDoubleCombsSearch::GetUserinstanceInfo(self::$user->getID(), SU_ValidateDoubleCombsSearch::GetCommon()->getDBIDByName($w['dbname']), (int)$w['id_world']);
            $data['in_delete_list'] = SU_ValidateDoubleCombsSearch::IsUserInDeleteList(self::$user->getID(), $w['world_identifier']);
            $result[$w['world_identifier']] = $data;
        }
        
        SU_ValidateDoubleCombsOut::OutUserInfo($result, self::$user);
    }
    
    static private function _allow()
    {
        assert(self::$user instanceof DE_User);
        assert(is_int(self::$count) && self::$count >= 0);
        
        SU_ValidateDoubleCombsSearch::AddPermisionForInstances(self::$user, self::$count);
        
        self::_showInfo();
    }
    
    static private function _delete()
    {
        assert(self::$user instanceof DE_User);
        assert(self::$world instanceof DE_WorldFromCollection);
        
        SU_DeleteUserList::AddToList(self::$user, self::$world, 'ADMIN');
        
        self::_showInfo();
    }
    
    static private function _freez()
    {
        assert(self::$user instanceof DE_User);
        assert(self::$world instanceof DE_WorldFromCollection);
        
        SU_ValidateDoubleCombsSearch::AddVocationMode(self::$user, self::$world);
            
        self::_showInfo();
    }
    
    static private function _validate()
    {
        if(self::$mode == 'console')
        {
            self::_log('');
            self::_log('---==== START VALIDATION ====---');
        }
        
        $users = SU_ValidateDoubleCombsSearch::GetUsersWithDoubleCombs();
        
        $result = array();
        
        foreach ($users as $k => $u)
        {
            $tmp = self::_validateUser((int)$u['id_user']);
            if(count($tmp) > 0)
            {
                $result[(int)$u['id_user']] = array('WORLDS' => $tmp, 'USER' => DE_User::CreateFromID((int)$u['id_user']));
            }
        }
        
        $str = SU_ValidateDoubleCombsOut::OutCheckResult($result);
        
        if(self::$mode == 'console')
        {
            self::_mailResult($str);
            self::_log('Found '.count($result).' users with double combs');
            self::_log('---==== END VALIDATION ====---');
            self::_log('');
        }
    }
    
    static private function _index()
    {
        SU_ValidateDoubleCombsOut::OutIndex();
    }
    
    static private function _validateUser($userID)
    {
        assert(is_int($userID) && $userID > 0);
        
        GE_SearchStart::SetCommon(SU_ValidateDoubleCombsSearch::GetCommon());
        $worlds = GE_SearchStart::GetWorldsWhereUserIsRegistered($userID);
        
        $worldStatus = array();
        
        foreach ($worlds as $k => $w)
        {
            $status = self::_checkUserWorld($userID, SU_ValidateDoubleCombsSearch::GetCommon()->getDBIDByName($w['dbname']), (int)$w['id_world']);
            if($status['STATUS'] == 'ACTIVE' && SU_ValidateDoubleCombsSearch::IsUserInDeleteList($userID, $w['world_identifier']))
            {
                $status['STATUS'] = 'DELETE';
            }
            
            $worldStatus[$w['world_identifier']] = $status;
        }
        
        foreach ($worldStatus as $w => $status)
        {
            foreach ($worldStatus as $w2 => $status2)
            {
                if(($w != $w2) && ($status2['STATUS'] == 'ACTIVE') && ($status['STATUS'] == 'ACTIVE') && ($status2['COMB'] == $status['COMB']))
                {
                    $worldStatus[$w]['STATUS'] = 'SAME';
                    $worldStatus[$w2]['STATUS'] = 'SAME';
                }
            }
        }
        
        foreach ($worldStatus as $w => $status)
        {
            $worldStatus[$w] = $status['STATUS'];
        }
        
        return $worldStatus;
    }
    
    static private function _checkUserWorld($userID, $dbID, $worldID)
    {
        assert(is_int($userID) && $userID > 0);
        assert(is_int($dbID) && $dbID >= 0);
        assert(is_int($worldID) && $worldID > 0);
        
        $instance = SU_ValidateDoubleCombsSearch::GetInstance($userID, $dbID, $worldID);
        
        if(count($instance) == 0)
        {
            return array('STATUS' => 'NO_INSTANCE', 'COMB' => $instance['combname']);
        }
        
        if(strstr($instance['password'], 'pereezd_'))
        {
            return array('STATUS' => 'FREEZ', 'COMB' => $instance['combname']);
        }
        
        return array('STATUS' => 'ACTIVE', 'COMB' => $instance['ringnumber'].'_'.$instance['combname']);
    }
    
    static private function _mailResult($str)
    {
        $eol="\r\n";
        # Common Headers
        $headers  = 'From: noreply <noreply@destinysphere.com>'.$eol;
        $headers .= "Message-ID: <".time()." TheSystem@".$_SERVER['SERVER_NAME'].">".$eol;
        $headers .= "X-Mailer: PHP v".phpversion().$eol; 
        $headers .= "Content-Type: text/html; charset=windows-1251".$eol;
        
        $head = '[DS] Transfer validation';
        mail(implode(',',self::$mailTo), $head, $str, $headers);
    }
        
    static private function _log($msg = '')
    {
        if(!empty($msg))
        {
            $msg = date('Y-d-m H:i:s')." - ".$msg;
        }
        
        $msg .= "\n";
        
        SU_DebugTools::WriteToLog($msg, self::$logFile);
    }
}

?>