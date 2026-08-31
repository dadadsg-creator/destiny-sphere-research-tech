<?php $CVS_Version = '$Revision$ ; $Date$';
require_once('classes/Validator.class.php');
class UC_ControlGlobalAllianceMemberPanel implements UC_ControlInterface 
{
    static private $action = 'index_stat';
    
    static private $globalAlliance;
    
    static private $user;
    
    static private $data;

    static private $msg;
    
    static private $offset = 0;
    
    public static function Exec(DE_User $user, array $data)
    {
        self::_getParams($data);

        self::$user = $user;
        
        //Создаем экземпляр класса Global Alliance
        $globalAllianceId = DS_GlobalAlliance::GetUserGlobalAlliance(self::$user);
        self::$globalAlliance = DE_GlobalAlliance::CreateFromID($globalAllianceId);
        
        
        //Получаем название альянса
        self::$data['ALLIANCE_NAME'] = self::$globalAlliance->getName();
        
        $leaderId = DS_GlobalAlliance::GetLeaderLeader($globalAllianceId);
        assert($leaderId >= 0 );
        
        $leader  = DE_User::CreateFromID((int)$leaderId);
        
        self::$data['ALLIANCE_LEADER'] = $leader->getName();
        
        switch (self::$action)
        {
            case 'index_stat':
                self::_indexStat();
                break;
                
            case 'index_message':
                self::_indexMessage();
                break;
                
            case 'confirm_sent_message':
                self::_confirmSentMessage();
                break;
                
            case 'confirm_sent_message_to_users':
                self::_confirmSentMessageToUsers();
                break;
                
            case 'sent_message':
                self::_sentMessage();
                break;
                
            case 'index_members':
                self::_indexMembers();
                break;
                
            case 'send_message_to_users':
                self::_SendMessageToUsers();
                break;
            
            case 'world_stat':
                self::_worldStat();
                break;
        
            case 'leave_global_alliance_index':
                self::_leaveGlobalAllianceIndex();
                break;
                
            case 'leave_global_alliance':
                self::_leaveGlobalAlliance();
                break;
                
            case 'join_alliance_successful':
                self::_joinAlliancSuccessful();
                break;
                        
            case 'leader_changed':
                self::_leaderChanged();
                break;
        
            default:
                self::_indexStat();
                break;
        }
    }
    
    static private function _indexStat()
    {               
        //Суммарное количество человек в альянсе
        $totalMembersAmmount = DS_GlobalAlliance::GetCountMembers(self::$globalAlliance->getID());
        
        //Сколько человек в каком мире
        $membersAmmountByWorlds = DS_GlobalAlliance::GetMembersAmmountByWorlds(self::$globalAlliance->getID());
        
        //Суммарный бонус от альянсовых артов для членов альянса по мирово
        $bonus = DS_GlobalAlliance::GetBonusByWorlds(self::$globalAlliance->getID());

        self::$data['MEMBERS_AMMOUNT'] = $totalMembersAmmount;
        self::$data['MEMBERS_IN_WORLDS'] = $membersAmmountByWorlds;
        self::$data['BONUS'] = $bonus;
            
        self::$data['MODE'] = 'STATISTIC';
        self::_index();
    }
    
    static private function _leaderChanged()
    {
        self::$data['MODE'] = 'LEADER_CHANGED';
        self::$msg = 'SUCCESSFULL';
        self::$data['MSG_TYPE'] = 'OK'; 
        self::_index();
        return;
    }
    
    static private function _indexMembers()
    {       
        if (!key_exists('world', self::$data))
        {
            self::$data['world'] = 'ALL';
        }
        
        //Получаем список миров
        $worlds = DS_GlobalAlliance::GetWordList();
        self::$data['WORLDS'] = $worlds;
        
        //получаем список членов 
        if (self::$data['world'] != 'ALL')
        {
            $tempArr = explode('_', self::$data['world']);
            $dbId = (int)$tempArr[0];
            $worldId = (int)$tempArr[1];
            $worldName = $tempArr[2];
            
            $members = DS_GlobalAlliance::GetMembersInWorld(self::$globalAlliance->getID(), $dbId, $worldId);
            
            foreach ($members as $key => $data)
            {
                    
                $resultMembers[$data['username']][$worldName]['runking'] = $data['runking'];
                $resultMembers[$data['username']][$worldName]['battlexp'] = $data['battlexp'];
            }       
        } else {
            //Выбираем участников альянса
            $glabalAllianceUsers = DS_GlobalAlliance::GetGlobalAllianceUsers(self::$globalAlliance->getId());       
            foreach ($glabalAllianceUsers as $k => $id_user)
            {
                //Выбираем все миры игрока
                $userWorlds = DS_GlobalAlliance::GetUserWorlds((int)$id_user['id_user']);
                
                foreach ($userWorlds as $key => $userWorld)
                {
                    $userRunking = DS_GlobalAlliance::GetUserRunking($userWorld['dbname'], $userWorld['id_world'], $id_user['id_user']);                
                    $resultMembers[$id_user['username']][$userWorld['worldname']]['runking'] = $userRunking[0]['runking'];
                    $resultMembers[$id_user['username']][$userWorld['worldname']]['battlexp'] = $userRunking[0]['battlexp'];
                }
            }
        }

        //Смещение
        if(isset(self::$data["offset"]))
        {
            if(empty(self::$data["offset"]))
            {
                $offset = 0;
            } else {
                $offset = (int)self::$data["offset"];
            }
        } else {
            $offset = 0;
        }
    
        
        if(!isset($offset))
        {
            $offset = 0;
        }

        $prevOffset = $offset - 20;

        if ($prevOffset < 0 )
        {
            $prevOffset = '3';
        }

        $nextOffset = $offset + 20;
        
        if ($nextOffset > sizeof($topMembers))
        {
            $nextOffset = '3';
        }

        self::$data['PREV_OFFSET'] = $prevOffset;
        self::$data['NEXT_OFFSET'] = $nextOffset;
            
        /*for($key = $offset; $key < ($offset + 20) && $key < count($topMembers); $key++)
        {
            $resultMembers[] = $topMembers[$key];
        }*/             
        
        $tempArr = explode('_', self::$data['world']);
        self::$data['DBID']= $tempArr[0];
        self::$data['WORLDID']= $tempArr[1];
    
        self::$data['MEMBERS'] = $resultMembers;
        self::$data['OFFSET'] = self::$offset;
    
        self::$data['MODE'] = 'INDEX_MEMBERS';
        self::_index();
    }
        
    static private function _joinAlliancSuccessful()
    {
        self::$data['MODE'] = 'JOIN_ALLIANCE_SUCCESSFUL';
        self::$msg = 'SUCCESSFULL';
        self::$data['MSG_TYPE'] = 'OK'; 
        self::_index();
        return;
    }
    
    static private function _confirmSentMessageToUsers()
    {   
        
        if (!key_exists('world', self::$data))
        {
            self::$data['world'] = 'ALL';
        }
        
        //Если не выбран ни один игрок, ничего не делаем
        if (!key_exists('users', self::$data))
        {
            self::_indexMembers();
        }
        
        if (key_exists('send_message', self::$data))
        {
            self::$data['ACTION'] = 'SEND_MESSAGE'; 
        }
        
        if (key_exists('expel', self::$data))
        {
            self::$data['ACTION'] = 'EXPEL';    
        }
        
        if (key_exists('expelWithBL', self::$data))
        {
            self::$data['ACTION'] = 'EXPEL_WITH_BL';    
        }
        
        self::$data['MODE'] = 'COMFIRM_SEND_MESSAGE_TO_USERS';
        self::_index();
    }   
    
    static private function _leaveGlobalAllianceIndex()
    {   
        self::$data['MODE'] = 'CONFIRM_LEAVE_ALLIANCE'; 
        self::_index();
        return;
    }   
    
    static private function _leaveGlobalAlliance()
    {   
        //Если нажали кнопку "отмена"
        if (key_exists('cansel', self::$data))
        {
            self::_indexMembers();
        }
        
        $worlds = DS_GlobalAlliance::GetWordList();
    
        foreach ($worlds as $key => $data)      
        {
            $IdInstance = DS_GlobalAlliance::GetUserInstaceByUserIdAndWorldId(self::$user->getID(), (int)$data['dbid'], (int)$data['id_world']);
            
            //Если у пользователя есть инстанс в этом мире, то удаляем его из альянса
            if (!empty($IdInstance))
            {
                $userInstance = DE_UserInstance::CreateFromID((int)$IdInstance);
                $id_local_alliance = DS_GlobalAlliance::GetLocalAllianceByGlobalAllianceAndWorldID(self::$globalAlliance->getId(), (int)$data['dbid'], (int)$data['id_world']);
                $localAlliance = DE_Alliance::CreateFromID((int)$id_local_alliance);
                ME_ExpelAlliance::Exec($localAlliance, $userInstance);
            }
        }
        
        //Удаляем пользователя из списка глобальных альянсов
        DS_GlobalAlliance::DeleteUserFromGlobalAlliance(self::$user->getID(), self::$globalAlliance->getId());
        
        //Посылаем сообщение игрокам
        //Выбираем все миры
        $worlds = DS_GlobalAlliance::GetWordList();
        $subject = 'Выход из альнса';
        $message = "Игрок ".self::$user->getName()." покинул наш альянс";
        foreach ($worlds as $k => $worldData)
        {
            //Отправляем сообщение
            DS_GlobalAlliance::SendMessageToChoosenWorld(0, (int)$worldData['dbid'], (int)$worldData['id_world'], $subject, $message, self::$globalAlliance->getID());
        }

        self::$data['ALLIANCE_NAME'] = self::$globalAlliance->getName();    
        self::$data['ga_action'] = 'leave_alliance_successful'; 
        UC_ControlGlobalAllianceRegistration::Exec(self::$user, self::$data);
        return;
    }   
    
    static private function _SendMessageToUsers()
    {       
        //Если нажали кнопку "отмена"
        if (key_exists('cansel', self::$data))
        {
            self::_indexMembers();
        }
        
        //Если не выбран ни один игрок, ничего не делаем
        if (!key_exists('users', self::$data))
        {
            self::_indexMembers();
        }

        if (!self::_validateMessage())
        {
            self::$data['ACTION'] = 'SEND_MESSAGE';
            self::$data['MODE'] = 'COMFIRM_SEND_MESSAGE_TO_USERS';
            self::_index();
        }
        foreach (self::$data['users'] as $key => $userName)
        {
            //Получаем id игрока по его имени
            $userId = DS_GlobalAlliance::GetUserIdByName($userName);

            //Выбираем все инстансы игрока, которому хотим послать сообщение
            $instancesTo = DS_GlobalAlliance::GetUserInstance((int)$userId);

            //Выбираем инстансы игрока, от которого хотим послать сообщение
            $instancesFrom = DS_GlobalAlliance::GetUserInstance(self::$user->getId());

            foreach ($instancesTo as $key => $instance)
            {
                //посылаем игроку сообщение
                DS_GlobalAlliance::SendAllianceMessageToUser($instancesFrom[0]['id_userinstance'], $instance['id_userinstance'], $instance['id_world'], $instance['dbname'], self::$data['subject'], self::$data['message'], self::$globalAlliance->getID());
            }
        }
        self::$msg = 'SUCCESSFULL_SEND_MESSAGE';
        self::$data['MSG_TYPE'] = 'OK';

        self::_indexMembers();
    }   
    
    static private function _indexMessage()
    {   
        $worldsList = DS_GlobalAlliance::GetWordList();
                                    
        $sendMessageCost = (int)DE_GlobalSettings::Get('GLOBAL_ALLIANCE_MESSAGE_COST');
        $sendMessageCredits = DE_GlobalSettings::Get('GLOBAL_ALLIANCE_MESSAGE_COST_CREDITS');
        
        
        self::$data['SEND_MESSAGE_COST'] = $sendMessageCost;
        self::$data['SEND_MESSAGE_CREDITS'] = $sendMessageCredits;
        
        self::$data['WORLD_LIST'] = $worldsList;
        self::$data['MODE'] = 'INDEX_MESSAGE';
        self::_index();
    }
    
    static private function _worldStat()
    {   
        //проверяем корректность db_id и world_id
        if (self::$data['db_id'] >= 0 && self::$data['world_id'] > 0)
        {
            
        } else {
            self::$data['MODE'] = 'STATISTICS';
            self::_index();
        }

        $worldStat = DS_GlobalAlliance::GetWordStat((int)self::$data['db_id'], (int)self::$data['world_id'], self::$globalAlliance->getID());
        $members = DS_GlobalAlliance::GetMembersListInWorld(self::$globalAlliance->getID(), (int)self::$data['db_id'], (int)self::$data['world_id']);
        
        foreach ($members as $key => $udata)
        {
            $memberList[] = $udata['username'];
        }

        self::$data['STAT'] = $worldStat;
        self::$data['MEMBERS'] = $memberList;
        
        self::$data['MODE'] = 'WORLD_STAT';
        self::_index();
    }
    
    static private function _sentMessage()
    {   
        //Если нажали кнопку "отмена"
        if (key_exists('cansel', self::$data))
        {
            self::_indexMessage();
        }
        
        if(!key_exists('worlds', self::$data))
        {
            self::$msg = 'CHOOSE_WORLD';
            self::_indexMessage();
            return;
        }
        
        if (!self::_validateMessage())
        {
            self::_indexMessage();
            return;
        }
                
        $allianceId = DS_GlobalAlliance::GetUserGlobalAlliance(self::$user);
        
        if (self::$data['global_message'] == 'YES')
        {
            // Проверяем есть ли денежка для отправки сообщения
            /*if(!UC_GlobalAllianceMoneyOperations::CheckMoneyForOperation(self::$user, 'MESSAGE'))
            {
                self::$msg = 'NOT_ENOUGHT_CREDITS_FOR_SEND_MESSAGE';
                self::_indexMessage();
                return;
            }*/
            
            //Снимаем деньги со счета
            //UC_GlobalAllianceMoneyOperations::GetMoneyForOperation(self::$user, 'MESSAGE');
            
            //Отправляем глобальное сообщение в миры
            DS_GlobalAlliance::SendGlobalMessageToChoosenWorld(self::$user->getID(), self::$data['worlds'], self::$data['subject'], self::$data['message']);
            
        } else {
            //Отправляем альянсовое сообщение всем членам яльянса в мире
            foreach (self::$data['worlds'] as $k => $worldData)
            {
                
                $tempArr = explode('_', $worldData);
                $dbId = (int)$tempArr[0];
                $worldId = (int)$tempArr[1];
                
                //Получаем инстанс того, от имени которого посылаем сообщение
                $userInstance = DS_GlobalAlliance::GetUserInstaceByUserIdAndWorldId(self::$user->getId(), $dbId, $worldId);
                
                //Если у игрока нет инстанса в этом мире, то посылаем сообщение от имени ds
                if (empty($userInstance))
                {
                    $userInstance = 0;
                }

                //Отправляем сообщение
                DS_GlobalAlliance::SendMessageToChoosenWorld($userInstance, $dbId, $worldId, self::$data['subject'], self::$data['message'], self::$globalAlliance->getID());
            }
         }

         self::$msg = 'SUCCESSFULL_SEND_MESSAGE';
        self::$data['MSG_TYPE'] = 'OK';

        self::_indexMessage();
    }   
        
    static private function _validateMessage()
    {
        if (empty(self::$data['subject']))
        {
            self::$msg = 'EMPTY_SUBJECT';
            return false;
        }
        
        if (empty(self::$data['message']) || self::$data['message'] == ' ')
        {
            self::$msg = 'EMPTY_MESSAGE';
            return false;
        }
        
        self::$data['subject'] = trim(self::$data['subject']);
        self::$data['message'] = trim((self::$data['message']));

        
        /**
         * Анти-Мат спам фильтр сообщений.
         */
        self::$data['subject'] = GB_AntiMatFilter::Exec(self::$data['subject']);
        self::$data['message'] = GB_AntiMatFilter::Exec(self::$data['message']);
        
        // проверяем тему
        //$arr = array ( "'", '`',  '<', '>', '\n', '\t');
        //self::$data['subject'] = str_replace ( $arr, "", self::$data['subject']);


        self::$data['subject'] = htmlspecialchars(self::$data['subject']);
        self::$data['message'] = htmlspecialchars(self::$data['message']);
        

        if ( strlen ( self::$data['subject'] ) > 200 ) {
            self::$msg = 'SUBJECT_TOO_LONG';
            return false;
        }

        if ( strlen ( self::$data['subject'] ) < 3 ) {
            self::$msg = 'SUBJECT_TOO_SHORT';
            return false;
        }

        if ( strlen ( self::$data['message'] ) > 10000 ) {
            self::$msg = 'MESSAGE_TOO_LONG';
            return false;
        }

        if ( strlen ( self::$data['message'] ) < 3 ) {
            self::$msg = 'MESSAGE_TOO_SHORT';
            return false;
        }
        
        return true;
    }
    
    static private function _confirmSentMessage()
    {   
        if (empty(self::$data['subject']))
        {
            self::$msg = 'EMPTY_SUBJECT';
            self::_indexMessage();
            return;
        }

        if(!key_exists('worlds', self::$data))
        {
            self::$msg = 'CHOOSE_WORLD';
            self::_indexMessage();
            return;
        }
        
        if (empty(self::$data['message']) || self::$data['message'] == ' ')
        {
            self::$msg = 'EMPTY_MESSAGE';
            self::_indexMessage();
            return;
        }
        
        //self::$data['subject'] = trim(utf8_decode(self::$data['subject']));
        //self::$data['message'] = trim(utf8_decode(self::$data['message']));
        
        self::$data['subject'] = trim(self::$data['subject']);
        self::$data['message'] = trim((self::$data['message']));

        
        /**
         * Анти-Мат спам фильтр сообщений.
         */
        self::$data['subject'] = GB_AntiMatFilter::Exec(self::$data['subject']);
        self::$data['message'] = GB_AntiMatFilter::Exec(self::$data['message']);
        
        $arr = array ( "'", '`',  '<', '>', '\n', '\t');

        // проверяем тему
        self::$data['subject'] = str_replace ( $arr, "", self::$data['subject']);

        if ( strlen ( self::$data['subject'] ) > 200 ) {
            self::$msg = 'SUBJECT_TOO_LONG';
            self::_indexMessage();
            return;
        }

        if ( strlen ( self::$data['subject'] ) < 3 ) {
            self::$msg = 'SUBJECT_TOO_SHORT';
            self::_indexMessage();
            return;
        }

        self::$data['message'] = str_replace ( $arr, "", self::$data['message']);
        
        if ( strlen ( self::$data['message'] ) > 10000 ) {
            self::$msg = 'MESSAGE_TOO_LONG';
            self::_indexMessage();
            return;
        }

        if ( strlen ( self::$data['message'] ) < 3 ) {
            self::$msg = 'MESSAGE_TOO_SHORT';
            self::_indexMessage();
            return;
        }
                
        $allianceId = DS_GlobalAlliance::GetUserGlobalAlliance(self::$user);
        
        //DS_GlobalAlliance::SendMessageToCoosenWorld(self::$user->getID(), self::$data['worlds'], self::$data['subject'], self::$data['message'], self::$globalAlliance->getID());
        //DS_GlobalAlliance::SendMessageToCoosenWorld(self::user, self::$data['worlds'], self::$data['subject'], self::$data['message']);
        
        //self::$data['MODE'] = 'CONFIRM_SEND_MESSAGE';
        
        
        //Получаем список миров, в которые отправляем сообщение
        $comma = "";
        foreach (self::$data['worlds'] as $k => $d)
        {
            $tempArr = explode("_", $d);
            $worldList .= $comma.$tempArr['2'];
        }
        self::$data['WORLD_LIST'] = $worldList;
        
        //Если сообщение глобальное
        if (key_exists('global_message', self::$data))
        {
            self::$data['GLOBAL_MESSAGE'] = 'YES';
            
            $sendMessageCost = (int)DE_GlobalSettings::Get('GLOBAL_ALLIANCE_MESSAGE_COST');
            $sendMessageCredits = DE_GlobalSettings::Get('GLOBAL_ALLIANCE_MESSAGE_COST_CREDITS');
            
            self::$data['SEND_MESSAGE_COST'] = $sendMessageCost;
            self::$data['SEND_MESSAGE_CREDITS'] = $sendMessageCredits; 
            
            
            // Проверяем есть ли денежка для отправки сообщения
            /*if(!UC_GlobalAllianceMoneyOperations::CheckMoneyForOperation(self::$user, 'MESSAGE'))
            {
                self::$msg = 'NOT_ENOUGHT_CREDITS_FOR_SEND_MESSAGE';
                self::_indexMessage();
                return;
            }*/
        
        } else {
            self::$data['GLOBAL_MESSAGE'] = 'NO'; 
        }
        
        self::$data['MODE'] = 'CONFIRM_SEND_MESSAGE';
        self::_index();
    }
    
    static private function _index()
    {   
        if(!key_exists('MODE', self::$data))
        {
            self::$data['MODE'] = 'INDEX';
        }
        
        self::$data['MSG'] = self::$msg;

        //UC_OutUserCabinet::Out('GlobalAllianceControlPanel', self::$data);
        UC_OutUserCabinet::Out('GlobalAllianceMemberPanel', self::$data);
    }
    
    static private function _getParams(array $data)
    {   
        if(key_exists('ga_action', $data))
        {
            self::$action = $data['ga_action'];
        }
            
        self::$data = $data;
        
    }
    
}

?>