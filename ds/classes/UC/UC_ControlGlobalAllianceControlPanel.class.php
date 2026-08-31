<?php $CVS_Version = '$Revision$ ; $Date$';
require_once('classes/Validator.class.php');
class UC_ControlGlobalAllianceControlPanel implements UC_ControlInterface 
{
    static private $action = 'index_stat';
    
    static private $avatarPreview;
    
    static private $msg;
    
    static private $avatar;
    
    static private $globalAlliance;
    
    static private $user;
    
    static private $data;
    
    static private $offset = 0;
    
    static private $avatarLocation = "/ds/allianceavatars/";
    
    public static function Exec(DE_User $user, array $data)
    {
        self::_getParams($data);
        
        self::$user = $user;
        
        //Делаем проверку на лидерство
        //Делаем проверку, является ли текущий пользователем лидером альянса
        //Заодно получаем id альянса
        self::_checkIsUserLeader();
        
        //Получаем название альянса
        self::$data['ALLIANCE_NAME'] = self::$globalAlliance->getName();
        
        //Получаем количество заявок
        $requests = DS_GlobalAlliance::GetListingRequest(self::$globalAlliance->getID());
        self::$data['COUNT_REQUEST'] = count($requests);
        
        switch (self::$action)
        {
            case 'index_stat':
                self::_indexStat();
                break;
                
            case 'index_join':
                self::_indexJoin();
                break;
                
            case 'join_control':
                self::_joinControl();
                break;
                
            case 'index_message':
                self::_indexMessage();
                break;
                
            case 'confirm_sent_message':
                self::_confirmSentMessage();
                break;
                
            case 'sent_message':
                self::_sentMessage();
                break;
                
            case 'sent_message_to_users':
                self::_sentMessageToUsers();
                break;
                
            case 'index_control':
                self::_indexControl();
                break;
                
            case 'index_members':
                self::_indexMembers();
                break;
                
            case 'confirm_expel_members':
                self::_confirmExpelMembers();
                break;
                
            case 'expel_members':
                self::_expelMembers();
                break;
                    
            case 'black_list':
                self::_blackList();
                break;
                
            case 'add_user_to_bl':
                self::_AddUserToBlackList();
                break;
                
            case 'del_user_from_bl':
                self::_delUserFromBlackList();
                break;
                
            case 'world_stat':
                self::_worldStat();
                break;
                    
            case 'settings':
                self::_settings();
                break;
                
            case 'change_settings':
                self::_changeSettings();
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
    
    static private function _indexJoin()
    {   
        //Cписок заявок на вступление
        $requestListing = DS_GlobalAlliance::GetListingRequest(self::$globalAlliance->getID());
    
        foreach ($requestListing as $key => $data)
        {
            $finalRequestListing[$key]['worlds'] .= $comma.$v['worldname'];
            
            
            $worlds = DS_GlobalAlliance::GetUserWorlds($data['id_user']);
                        
            $requestListing[$key]['worlds'] = "";
            $comma = "";
            
            foreach ($worlds as $k => $v)
            {
                $requestListing[$key]['worlds'] .= $comma.$v['worldname'];
                $comma = ", ";
            }
        }
        
        //Выбыраем тип альянса (с заявками на вступление или нет)
        $needConfirmation = self::$globalAlliance->getConfirmation('pg');

        self::$data['REQUEST_LISTING'] = $requestListing;
        self::$data['NEED_CONFIRMATION'] = $needConfirmation;
    
        self::$data['MODE'] = 'INDEX_JOIN';
        self::_index();
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
        
    static private function _confirmExpelMembers()
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
            if (in_array(self::$user->getName(), self::$data['users']))
            {
                self::$msg = 'CANT_EXPEL_YOURSELF';
                self::_indexMembers();
            }
            self::$data['ACTION'] = 'EXPEL';    
        }
        
        if (key_exists('expelWithBL', self::$data))
        {
            if (in_array(self::$user->getName(), self::$data['users']))
            {
                self::$msg = 'CANT_EXPEL_YOURSELF';
                self::_indexMembers();
            }
            self::$data['ACTION'] = 'EXPEL_WITH_BL';    
        }
        
        self::$data['MODE'] = 'CONFIRM_EXPEL';
        self::_index();
    }   
    
    static private function _expelMembers()
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
        
        if (key_exists('send_message', self::$data))
        {
            if (!self::_validateMessage())
            {
                self::$data['ACTION'] = 'SEND_MESSAGE';
                self::$data['MODE'] = 'COMFIRM_EXPEL';
                self::_index();
            }       
        
            self::$data['MODE'] = 'CONFIRM_SEND_MESSAGE_TO_USERS';
            self::_index();
            return;
        }
        
        if (key_exists('expelWithBL', self::$data))
        {
            //Проверяем корректность комментариев
            $arr = array ( "'", '`',  '<', '>', '\n', '\t');
echo "длина = ".strlen ( self::$data['comments'])."!<br>";          
            $comments = str_replace ( $arr, "", self::$data['comments']);
            
            if ( strlen ( $comments ) > 600 ) {
                self::$msg = 'COMMENTS_TOO_LONG';
                self::$data['MODE'] = 'CONFIRM_EXPEL';
                self::$data['ACTION'] = 'EXPEL_WITH_BL';
                self::_index();
                return;
            }
        }

        foreach (self::$data['users'] as $key => $userName)
        {
            if (self::$user->getName() == $userName)
            {
                continue;
            }

            $userId = DS_GlobalAlliance::GetUserIdByName($userName);

            //Посылаем игроку сообщение, что он был исклбчен
            //Выбираем инстансы игрока, которому хотим послать сообщение
            $instancesTo = DS_GlobalAlliance::GetUserInstance((int)$userId);

            $subject = 'Исключение из альнса';
            $message = 'Вы были исключены из альянса '.self::$globalAlliance->getName();

            if (key_exists('expelWithBL', self::$data))
            {
                $message .= ' с занесением вас в черный список этого альянса';
            }

            foreach ($instancesTo as $key => $instance)
            {
                //посылаем игроку сообщение
                DS_GlobalAlliance::SendAllianceMessageToUser(0 , $instance['id_userinstance'], $instance['id_world'], $instance['dbname'], $subject, $message, self::$globalAlliance->getID());
            }

            //Удаляем игрока из альянса
            self::_expelUser($userId);
            
            if (key_exists('expelWithBL', self::$data))
            {
                //Если игрок уже не занесен в черный список
                if (!DS_GlobalAlliance::IsUserInBlackList(self::$globalAlliance->getId(), $userId))
                {
                    //заносим игрока в черный список
                    DS_GlobalAlliance::InsertUserToBlackList(self::$globalAlliance->getID(), (int)$userId, $comments);
                }
            }
        }

        if (key_exists('expelWithBL', self::$data))
        {           
            self::$msg = 'SUCCESSFULL_EXPEL_WITH_BL';
            self::$data['MSG_TYPE'] = 'OK';
        } else {
            self::$msg = 'SUCCESSFULL_EXPEL';
            self::$data['MSG_TYPE'] = 'OK';
        }

        self::_indexMembers();
    }   
    
    static private function _expelUser($userId)
    {
        assert($userId > 0);
        
        $worldList = DS_GlobalAlliance::GetWordList();
                
        foreach($worldList as $key=> $worldData)
        {
            $worldId = (int)$worldData['id_world'];
            $dbId = (int)$worldData['dbid'];
            $worldName = $worldData['worldname'];

            //Получаем UserInstace
            $userInstance = DS_GlobalAlliance::GetUserInstaceByUserIdAndWorldId($userId, $dbId, $worldId);

            //Если у игрока есть UserInstace в этом мире
            if (!empty($userInstance))
            {
                //Выясняем id локального альянса игрока в этом мире
                $idLocalAlliance = DS_GlobalAlliance::GetUserLocalAllianceByUserInstance($dbId, $userInstance);

                //Если игрок состоит в альянсе в этом мире, то исключаем его
                if (!empty($idLocalAlliance))
                {
                    $alliance = DE_Alliance::CreateFromID((int)$idLocalAlliance);
                    $userInstance = DE_UserInstance::CreateFromID((int)$userInstance);
                    ME_ExpelAlliance::Exec($alliance, $userInstance);
                }
            }
        }
        //Удаляем пользователя из списка глобальных альянсов
        DS_GlobalAlliance::DeleteUserFromGlobalAlliance((int)$userId, self::$globalAlliance->getID());
    }
        
    static private function _joinControl()
    {   
        if(!key_exists('users', self::$data))
        {
            self::$msg = 'CHOOSE_WORLD';
            self::$data['MODE'] = 'INDEX_JOIN';
            self::_indexJoin();
        }
        
        //Если нажали кнопку "принять", принимаем заявку на вступление
        if (key_exists('accept', self::$data))
        {
            $subject = 'Вступление в альянс';
            $message = ' Вы приняты в альянс '.self::$globalAlliance->getName().'.';
            
            $count = 0;
            //Проходимся по каждому пользователю
            foreach (self::$data['users'] as $key => $id_user)
            {
                assert($id_user >= 0);

                //Удаляем заявку из списка ожидающих
                DS_GlobalAlliance::DeleteOrder(self::$globalAlliance->getID(), $id_user);
                
                $user = DE_User::CreateFromID((int)$id_user);
                
                //Заносим игрока в альянс
                self::_joinUserToAlliance($user, self::$globalAlliance);
                
                //Заносим игрока в список членов глобального альянса
                DS_GlobalAlliance::AddUserToGlobalAlliance((int)$id_user, self::$globalAlliance->getID());
                
                //SU_DebugTools::LogAll("globalAllianceId = ".self::$globalAlliance->getID(), "SessionErrors.log");
                
                //Посылаем игроку сообщение, что он принят в альянс
                //Выбираем инстансы игрока, которому хотим послать сообщение
                $instancesTo = DS_GlobalAlliance::GetUserInstance($user->getId());
                
                foreach ($instancesTo as $key => $instance)
                {
                    //посылаем игроку сообщение
                    DS_GlobalAlliance::SendAllianceMessageToUser(0 , $instance['id_userinstance'], $instance['id_world'], $instance['dbname'], $subject, $message, self::$globalAlliance->getID());
                }
                $count ++;
            }

            if ($count > 1)
            {
                self::$msg = 'SUCCESSFULL_ACCEPTS';
            } else {
                self::$msg = 'SUCCESSFULL_ACCEPT';
            }
            self::$data['MSG_TYPE'] = 'OK';
        }
        
        //Если нажали кнопку "отклонить", отклоняем заявку на вступление
        if (key_exists('decline', self::$data) || key_exists('decline_to_black_list', self::$data))
        {
            $subject = 'Вступление в альянс';
            $message = ' Ваша заявка на вступление в альянс '.self::$globalAlliance->getName().' отклонена.';
            
            $count = 0;
            
            //Проходимся по каждому пользователю
            foreach (self::$data['users'] as $key => $id_user)
            {
                //Удаляем заявку из списка ожидающих
                DS_GlobalAlliance::DeleteOrder(self::$globalAlliance->getID(), $id_user);
                
                if (key_exists('decline_to_black_list', self::$data))
                {
                    //Заносим пользователя в черный список
                    DS_GlobalAlliance::InsertUserToBlackList(self::$globalAlliance->getID(), $id_user);
                    $message .= ' Вы занесены в черный список этого альянса.';
                }
                
                //Посылаем игроку сообщение, что он принят в альянс
                //Выбираем инстансы игрока, которому хотим послать сообщение
                $instancesTo = DS_GlobalAlliance::GetUserInstance((int)$id_user);
                foreach ($instancesTo as $key => $instance)
                {
                    //посылаем игроку сообщение
                    DS_GlobalAlliance::SendAllianceMessageToUser(0 , $instance['id_userinstance'], $instance['id_world'], $instance['dbname'], $subject, $message, self::$globalAlliance->getID());
                }
                $count ++;
            }
            
            if ($count > 1)
            {
                self::$msg = 'SUCCESSFULL_DECLINES';
            } else {
                self::$msg = 'SUCCESSFULL_DECLINE';
            }
            self::$data['MSG_TYPE'] = 'OK'; 
        }
        
        $requests = DS_GlobalAlliance::GetListingRequest(self::$globalAlliance->getID());
        self::$data['COUNT_REQUEST'] = count($requests);
        
        self::_indexJoin();
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
    
    static private function _indexControl()
    {           
        $confirmation = self::$globalAlliance->getConfirmation('pg');
        self::$data['CONFIRMATION'] = $confirmation;
        
        $renameCost = (int)DE_GlobalSettings::Get('GLOBAL_ALLIANCE_RENAME_COST');
        $renameCredits = DE_GlobalSettings::Get('GLOBAL_ALLIANCE_RENAME_COST_CREDITS');
        
        $changeAvaterCost = (int)DE_GlobalSettings::Get('GLOBAL_ALLIANCE_CHANGE_AVATAR_COST');
        $changeAvaterCredits = DE_GlobalSettings::Get('GLOBAL_ALLIANCE_CHANGE_AVATAR_COST_CREDITS');

        self::$data['RENAME_COST'] = $renameCost;
        self::$data['RENAME_CREDITS'] = $renameCredits;
        
        self::$data['AVATAR_COST'] = $changeAvaterCost;
        self::$data['AVATAR_CREDITS'] = $changeAvaterCredits;
        
        //Получаем настройку возможности брать плату за вступление
        $joinPaymentSettings = DE_GlobalSettings::Get('GLOBAL_ALLIANCE_JOIN_PAYMENT');
        
        //Если эта настройка определяется лидером, т.е. равна 0
        if ($joinPaymentSettings == 0)
        {
            //Получаем текущее значение платы за вступление
            $joinPayment = self::$globalAlliance->getJoinPayment();
        }
        self::$data['JOIN_PAYMENT'] = $joinPayment;
        self::$data['JOIN_PAYMENT_SETTINGS'] = $joinPaymentSettings;
        
        
        //Смотрим, есть ли  у пользователя альянсовая аватарка
        $allianceAvatar = DS_GlobalAlliance::GetAllianceAvatar(self::$globalAlliance->getID());

        if (empty($allianceAvatar))
        {
            self::$data['USER_HAVE_AVATAR'] = 'NO';
        } else {
            self::$data['USER_HAVE_AVATAR'] = 'YES';
        }
        
        self::$data['MODE'] = 'INDEX_CONTROL';
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
/*echo "<pre>";
print_r($worldStat);
exit;*/         
        foreach ($members as $key => $udata)
        {
            $memberList[] = $udata['username'];
        }

        self::$data['STAT'] = $worldStat;
        self::$data['MEMBERS'] = $memberList;
        
        self::$data['MODE'] = 'WORLD_STAT';
        self::_index();
    }
    
    static private function _settings()
    {
        switch (self::$data['set_action'])
        {
            case 'change_password':
                self::$data['MODE'] = 'CHANGE_PASSWORD';
                self::_index();
                break;
                
            case 'change_leader':
                self::$data['MODE'] = 'CHANGE_LEADER';
                self::_index();
                break;
                
            case 'change_join':
                //Получаем текущее значение подтверждения
                $confirmation = self::$globalAlliance->getConfirmation('pg');
                self::$data['CONFIRMATION'] = $confirmation;
                
                //Получаем настройку возможности брать плату за вступление
                $joinPaymentSettings = DE_GlobalSettings::Get('GLOBAL_ALLIANCE_JOIN_PAYMENT');
                
                //Если эта настройка определяется лидером, т.е. равна 0
                if ($joinPaymentSettings == 0)
                {
                    //Получаем текущее значение платы за вступление
                    $joinPayment = self::$globalAlliance->getJoinPayment();
                }
                self::$data['JOIN_PAYMENT'] = $joinPayment;
                self::$data['JOIN_PAYMENT_SETTINGS'] = $joinPaymentSettings;
        
                self::$data['MODE'] = 'CHANGE_JOIN';
                self::_index();
                break;
                
            case 'add_avatar':
                self::$data['MODE'] = 'ADD_AVATAR';
                self::_index();
                break;
            case 'delete_alliance':
                self::$data['MODE'] = 'DELETE_ALLIANCE';
                self::_index();
                break;
            case 'restore_password':
                self::$data['MODE'] = 'RESTORE_PASSWORD';
                self::_index();
                break;
            case 'change_alliance_name':
                self::$data['MODE'] = 'CHANGE_ALLIANCE_NAME';
                $renameCost = (int)DE_GlobalSettings::Get('GLOBAL_ALLIANCE_RENAME_COST');
                $renameCredits = DE_GlobalSettings::Get('GLOBAL_ALLIANCE_RENAME_COST_CREDITS');

                self::$data['RENAME_COST'] = $renameCost;
                self::$data['RENAME_CREDITS'] = $renameCredits;
                self::_index();
                break;
            case 'change_avatar':
                $changeAvaterCost = (int)DE_GlobalSettings::Get('GLOBAL_ALLIANCE_CHANGE_AVATAR_COST');
                $changeAvaterCredits = DE_GlobalSettings::Get('GLOBAL_ALLIANCE_CHANGE_AVATAR_COST_CREDITS');
                
                self::$data['AVATAR_COST'] = $changeAvaterCost;
                self::$data['AVATAR_CREDITS'] = $changeAvaterCredits;
                
                self::$data['MODE'] = 'CHANGE_AVATAR';
                self::_index();
                break;
        }
    }
    
    static private function _changeSettings()
    {
        //Если нажали кнопку "отмена"
        if (key_exists('cansel', self::$data))
        {
            self::_indexControl();
        }
        switch (self::$data['change_action'])
        {
            case 'change_password':
                if(isset(self::$data['new_password']) && strlen(self::$data['new_password']) > 0)
                 {
                    if(strcmp(self::$data['old_password'], self::$globalAlliance->getPassword()) == 0)
                    {

                        if (!VD_Password::Validate(self::$data['new_password']))
                        {
                            self::$msg = VD_Password::FetchErrorCode();
                            self::$data['MODE'] = 'CHANGE_PASSWORD';
                            self::_index();
                            return;
                        }

                        if (self::$data['new_password'] != self::$data['confirm_password'])
                        {
                            self::$msg = 'MISMATCH_PASSWORD';
                            self::$data['MODE'] = 'CHANGE_PASSWORD';
                            self::_index();
                            return;
                        }

                        self::$globalAlliance->setPassword(self::$data['new_password']);
                        
                        self::$msg = 'SUCCESSFULL_CHANGE_PASSWORD';
                        self::$data['MSG_TYPE'] = 'OK'; 
                    }
                    else
                    {
                        self::$msg = 'WRONG_OLD_PASSWORD';
                        self::$data['MODE'] = 'CHANGE_PASSWORD';
                        self::_index();
                        return;
                    }
                } else {
                    self::$data['MODE'] = 'CHANGE_PASSWORD';
                    self::_index();
                    break;
                }
                
                break;
                /*___________________________________________________________________*/
                
            case 'change_leader':
                if(isset(self::$data['new_leader']) && strlen(self::$data['new_leader']) > 0)
                {
                    if(strcmp(self::$data['password'], self::$globalAlliance->getPassword()) != 0)
                    {
                        self::$msg = 'WRONG_PASSWORD';
                        self::$data['MODE'] = 'CHANGE_LEADER';
                        self::_index();
                        return;
                    }
                    
                    //Проверяем, есть ли пользователь с введенным ником
                    $userId = DS_GlobalAlliance::GetUserIdByName(self::$data['new_leader']);
                    if (empty($userId))
                    {
                        self::$msg = 'USER_NOT_EXIST';
                        self::$data['MODE'] = 'CHANGE_LEADER';
                        self::_index();
                        return;
                    }

                    //Проверяем, состоит ли этот пользователь в альянсе
                    if (!DS_GlobalAlliance::IsUserInAlliance((int)$userId, self::$globalAlliance->getID()))
                    {
                        self::$msg = 'USER_NOT_IN_ALLIANCE';
                        self::$data['MODE'] = 'CHANGE_LEADER';
                        self::_index();
                        return;
                    }

                    //Меняем лидера
                    $newLeader = DE_User::CreateFromName(self::$data['new_leader']);
                    self::$globalAlliance->setLeader($newLeader);

                    GB_SingletonObjectStorage::CommitAllCommitable();
                    
                    //Отправляем игрокам сообщение о смене лидера
                    $subject = "Смена лидера альянса";
                    $message = "У альянса ".self::$globalAlliance->getName()." новый лидер: ".self::$data['new_leader']."" ;
                    
                    
                    self::SendMessageToAllAllianceMembers($subject, $message);
                    
                    self::$data['ga_action'] = 'leader_changed';
                    self::$data['NEW_LEADER'] = $newLeader->getName();
                    
                    UC_ControlGlobalAllianceMemberPanel::Exec(self::$user, self::$data);
                    return;
                }
                break;

                /*___________________________________________________________________*/

            case 'change_join':
                if(isset(self::$data['confirm_join']))
                {
                    self::$globalAlliance->setConfirmation(true);
                } else {
                    self::$globalAlliance->setConfirmation(false);
                }
                if(isset(self::$data['new_join_payment']) && (int)self::$data['new_join_payment'] >= 0)
                {
                    self::$globalAlliance->setJoinPayment((int)self::$data['new_join_payment']);
                } 
                self::$msg = 'SUCCESSFULL_CHANGE_JOIN';
                self::$data['MSG_TYPE'] = 'OK'; 

            case 'add_avatar':

                if(isset($_FILES['ga_avatar']) && strlen($_FILES['ga_avatar']['name']) > 0)
                {
                    $imageSize = getimagesize($_FILES['ga_avatar']['tmp_name']);
                
                    if ($imageSize[0] > 100 || $imageSize[1] > 100)
                    {
                        self::$msg = 'WRONG_IMAGE_SIZE';
                        self::$data['set_action'] = 'add_avatar';
                        self::_settings();
                        return;
                    }
                    
                    //Загружаем аватарку
                    if(!self::_saveAvatarOnServer())
                    {
                        self::$msg = 'CANT_LOAD_AVATAR_ON_SERVER';
                        self::$data['set_action'] = 'add_avatar';
                        self::_settings();
                        return;
                    }

                    //Обновляем сведения об аватаре в бд
                    DS_GlobalAlliance::AddAvatar(self::$globalAlliance->getID(), self::$avatar);
                    
                    self::$msg = 'SUCCESSFULL_ADD_AVATAR';
                    self::$data['MSG_TYPE'] = 'OK';
                }
                
                break;
                /*___________________________________________________________________*/

            case 'delete_alliance':
                if(strcmp(self::$data['password'], self::$globalAlliance->getPassword()) != 0)
                {
                    self::$msg = 'WRONG_PASSWORD';
                    self::$data['MODE'] = 'DELETE_ALLIANCE';
                    self::_index();
                    return;
                }
                
                //Отправляем игрокам сообщение о роспуске альянса
                $subject = "Роспуск альянса";
                $message = "Альянс ".self::$globalAlliance->getName()." был распущен." ;
                self::SendMessageToAllAllianceMembers($subject, $message);

                //Получаем список членов альянса
                //Выбираем участников альянса
                $glabalAllianceUsers = DS_GlobalAlliance::GetGlobalAllianceUsers(self::$globalAlliance->getId());
        
                foreach ($glabalAllianceUsers as $k => $id_user)
                {
                    //Исключаем игрока
                    self::_expelUser($id_user['id_user']);
                }
                
                //Удаляем глобальные альянсы из таблички ds_allince
                $worlds = DS_GlobalAlliance::GetWordList();
                foreach ($worlds as $key => $data)
                {
                    DS_GlobalAlliance::DeleteLocalAlliance(self::$globalAlliance->getId(), (int)$data['dbid'], (int)$data['id_world']);
                }
                
                //Удаляем альянс из таблички ds_global_allince
                DS_GlobalAlliance::DeleteAlliance(self::$globalAlliance);
                
                GB_SingletonObjectStorage::CommitAllCommitable();

                //Переходим на страничку создания и вступления в альянс
                
                self::$data['ga_action'] = 'index';
                UC_ControlGlobalAllianceRegistration::Exec(self::$user, self::$data);
                //UC_ControlGlobalAlliance::Exec(self::$user, self::$data);
                return;
                
                break;
                /*___________________________________________________________________*/
                
            case 'restore_password':
                $password = self::$globalAlliance->getPassword();
                
                $subject = 'Пароль глобального альянса';
                $mailBody = "Ваш пароль глобального альянса : '$password'";
                
                $eol="\r\n";
                # Common Headers
                $headers .= 'From: noreply <noreply@destinysphere.com>'.$eol;
                $headers .= "Message-ID: <".time()." TheSystem@".$_SERVER['SERVER_NAME'].">".$eol;
                $headers .= "X-Mailer: PHP v".phpversion().$eol;
                $headers .= "Content-Type: text/html; charset=windows-1251".$eol;

                if(!@mail(self::$user->getEmail(), $subject, $mailBody, $headers))
                {
                    self::$msg = 'MAIL_PROBLEMS';
                    self::$data['set_action'] = 'restore_password';
                    self::_settings();
                    return;
                }
                
                self::$msg = 'PASSWORD_WAS_SEND';
                self::$data['MSG_TYPE'] = 'OK';
                
                break;
                /*___________________________________________________________________*/

            case 'change_alliance_name':
                if(isset(self::$data['new_allince_name']) && strlen(self::$data['new_allince_name']) > 0 && strlen(self::$data['new_allince_tag']) > 0)
                {
                
                    if(strcmp(self::$data['password'], self::$globalAlliance->getPassword()) != 0)
                    {
                        self::$msg = 'WRONG_PASSWORD';
                        self::$data['ALLIANCE_NAME'] = 'new_allince_name';
                        self::$data['ALLIANCE_TAG'] = 'new_allince_tag';
                        self::$data['set_action'] = 'change_alliance_name';
                        self::_settings();
                        return;
                    }
                // Проверяем есть ли денежка для преименования
                    /*if(!UC_GlobalAllianceMoneyOperations::CheckMoneyForOperation(self::$user, 'RENAME'))
                    {
                        self::$msg = 'NOT_ENOUGHT_CREDITS_FOR_RENAME';
                        self::_indexControl();
                        return;
                    }*/
                    
                    $oldName = self::$globalAlliance->getName();
                    $oldTag = self::$globalAlliance->getShortName();
                    
                    //Если пользователь ввел не текущее название
                    if (self::$globalAlliance->getName() != self::$data['new_allince_name'])
                    {
                        //Проверяем, нет ли уже альянса с таким же названием
                        if (DS_GlobalAlliance::CheckUniqAllianceName(self::$data['new_allince_name']))
                        {
                            //self::$data['WORLD_WHERE_EXIST_ALLINCE'] = DS_GlobalAlliance::CheckUniqAllianceName(self::$data['new_allince_name']);
                            //Big Alliance
                            self::$msg = 'GLOBAL_ALLIANCE_NAME_NOT_UNIQUE';
                            self::$data['set_action'] = 'change_alliance_name';
                            self::_settings();
                            return;
                        }
                        if (!VD_AllianceName::Validate(self::$data['new_allince_name'], 0, DS_Common::CreateForCurentDB()))
                        {
                            self::$msg = 'WRONG_ALLIANCE_NAME';
                            self::$data['set_action'] = 'change_alliance_name';
                            self::_settings();
                            return;
                        }
                        
                        self::$globalAlliance->setName(self::$data['new_allince_name']);
                    }

                    //Если пользователь ввел не текущее сокрашение
                    if (self::$globalAlliance->getShortName() != self::$data['new_allince_tag'])
                    {
                        //Проверяем, нет ли уже альянса с таким сокращением
                        if (DS_GlobalAlliance::CheckUniqAllianceTag(self::$data['new_allince_tag']))
                        {
                            //self::$data['WORLD_WHERE_EXIST_ALLINCE'] = DS_GlobalAlliance::CheckUniqAllianceName(self::$data['new_allince_name']);
                            //Big Alliance
                            self::$msg = 'GLOBAL_ALLIANCE_NAME_NOT_UNIQUE';
                            self::$data['set_action'] = 'change_alliance_name';
                            self::_settings();
                            return;
                        }

                        if (!VD_AllianceTag::Validate(self::$data['new_allince_tag'], 0, DS_Common::CreateForCurentDB()))
                        {
                            self::$msg = 'WRONG_ALLIANCE_TAG';
                            self::$data['set_action'] = 'change_alliance_name';
                            self::_settings();
                            return;
                        }
                        
                        self::$globalAlliance->setShortName(self::$data['new_allince_tag']);
                    }
                    
                    //Отправляем игрокам сообщение о переименовании альянса
                    $subject = "Переименование альянса";
                    $message = "Альянс $oldName ($oldTag) был переименован в ".self::$data['new_allince_name']." (".self::$data['new_allince_tag'].")." ;
                    self::SendMessageToAllAllianceMembers($subject, $message);
                
                    //Снимаем деньгу
                    //UC_GlobalAllianceMoneyOperations::GetMoneyForOperation(self::$user, 'RENAME');
                
                    //Переименовываем альянс
                    DS_GlobalAlliance::RenameAlliance(self::$data['new_allince_name'], self::$data['new_allince_tag'], self::$globalAlliance->getID());
                    
                    self::$data['ALLIANCE_NAME'] = self::$data['new_allince_name'];
                    
                    self::$msg = 'SUCCESSFULL_CHANGE_NAME';
                    self::$data['MSG_TYPE'] = 'OK';
                }
                break;
                /*___________________________________________________________________*/

            case 'change_avatar':

                if(isset($_FILES['ga_avatar']) && strlen($_FILES['ga_avatar']['name']) > 0)
                {
                    $imageSize = getimagesize($_FILES['ga_avatar']['tmp_name']);
                    
                    if ($imageSize[0] > 100 || $imageSize[1] > 100)
                    {
                        self::$msg = 'WRONG_IMAGE_SIZE';
                        self::$data['set_action'] = 'change_avatar';
                        self::_settings();
                        return;
                    }
                    
                    if(strcmp(self::$data['password'], self::$globalAlliance->getPassword()) != 0)
                    {
                        self::$msg = 'WRONG_PASSWORD';
                        self::$data['set_action'] = 'change_avatar';
                        self::_settings();
                        return;
                    }
                    
                    // Проверяем есть ли денежка для смены аватары
                    /*if(!UC_GlobalAllianceMoneyOperations::CheckMoneyForOperation(self::$user, 'CHANGE_AVATAR'))
                    {
                        self::$msg = 'NOT_ENOUGHT_CREDITS_FOR_CHANGE_AVATAR';
                        self::$data['set_action'] = 'change_avatar';
                        self::_settings();
                        return;
                    }*/

                    $oldFileName = DS_GlobalAlliance::GetAllianceAvatar(self::$globalAlliance->getID());

                    //Загружаем аватарку
                    if(!self::_saveAvatarOnServer())
                    {
                        self::$msg = 'CANT_LOAD_AVATAR_ON_SERVER';
                        self::$data['set_action'] = 'change_avatar';
                        self::_settings();
                        return;
                    }

                    //Снимаем деньгу
                    /*if (!UC_GlobalAllianceMoneyOperations::GetMoneyForOperation(self::$user, 'CHANGE_AVATAR'))
                    {
                        break;
                    }*/

                    //Обновляем сведения об аватаре в бд
                    DS_GlobalAlliance::UpdateAvatar(self::$globalAlliance->getID(), self::$avatar);


                    //Удаляем старую аватарку
                    @unlink($_SERVER['DOCUMENT_ROOT'].$oldFileName);
                    
                    self::$msg = 'AVATAR_WAS_CHANGE';
                    self::$data['MSG_TYPE'] = 'OK';
                }
                break;
        }

        GB_SingletonObjectStorage::CommitAllCommitable();
        
        self::_indexControl();
    }
        
    static private function _saveAvatarOnServer()
    {
        //Если у нас есть временный файл, заливаем его на ftp
        $path = $_SERVER['DOCUMENT_ROOT'].self::$avatarLocation;
        $newFile = $_FILES['ga_avatar']['name'];
        $oldFile = $_FILES['ga_avatar']['name'];

        //Проверяем, нет ли файла с таким же именем
        $i = 1;
        while (file_exists($path.$newFile) AND $i<100)
        {
            //Если есть, то прибавляем к файлу цифру, и делаем проверку заново
            $tempName = explode(".", $oldFile);
            $tempName[0] = $tempName[0].$i;
            $newFile = "";

            foreach ($tempName as $key => $val)
            {
                if ($key != 0 )
                {
                    //Добавляем точуку
                    $newFile .= ".";
                }
                $newFile .= $val;
            }
            $i++;
        }

        if (!move_uploaded_file($_FILES['ga_avatar']['tmp_name'], $path.$newFile)) 
        {
            return false;
        }
        
        self::$avatar = self::$avatarLocation.$newFile;
        return true;
    }
    
    static private function _blackList()
    {   
        $blackList = DS_GlobalAlliance::GetBlackList(self::$globalAlliance->getID());

        self::$data['BLACK_LIST'] = $blackList;

        self::$data['MODE'] = 'BLACK_LIST';
        self::_index();
    }
    
    static private function _delUserFromBlackList()
    {   
        foreach (self::$data['users'] as $key => $userId)
        {
            DS_GlobalAlliance::DeleteUserFromBlackList(self::$globalAlliance->getID(), $userId);
            
            //Порисылаем игроку сообщение, что он занесен в черный список
            //Выбираем все инстансы игрока, которому хотим послать сообщение
            $instancesTo = DS_GlobalAlliance::GetUserInstance((int)$userId);
            $subject = 'Черный список';
            $message = 'Вы удалены из черного списка альянса "'.self::$globalAlliance->getName().'"';
            foreach ($instancesTo as $key => $instance)
            {
                //посылаем игроку сообщение
                DS_GlobalAlliance::SendAllianceMessageToUser(0, $instance['id_userinstance'], $instance['id_world'], $instance['dbname'], $subject, $message, self::$globalAlliance->getID());
            }
        }

        self::_blackList();
    }
    
    static private function _AddUserToBlackList()
    {   
        //Если поле было оставлено пустым
        if (self::$data['user_to_bl'] == '')
        {
            self::$msg = 'EMPTY_USER';
            self::_blackList();
            return;
        }
        
        //Проверяем корректность комментариев
        $arr = array ( "'", '`',  '<', '>', '\n', '\t');
        
        $comments = str_replace ( $arr, "", self::$data['comments']);
        
        if ( strlen ( $comments ) > 600 ) {
            self::$msg = 'COMMENTS_TOO_LONG';
            self::_blackList();
            return;
        }
        
        //Если пользователь пытается занести самого себя
        if (self::$data['user_to_bl'] == self::$user->getName())
        {
            self::$msg = 'CANT_ADD_IN_BL_YOURSELF';
            self::_blackList();
            return;
        }

        //Получаем id игрока по его имени
        $userId = DS_GlobalAlliance::GetUserIdByName(self::$data['user_to_bl']);

        //Если такого игрока не существует
        if (!$userId)
        {
            self::$msg = 'USER_NOT_EXIST';
            self::_blackList();
            return;
        }
        
        //Если игрок уже в черном списке
        if (DS_GlobalAlliance::IsUserInBlackList(self::$globalAlliance->getID(), (int)$userId))
        {
            self::$msg = 'USER_ALREADY_IN_BLACK_LIST';
            self::_blackList();
            return;
        }

        //Смотрим состоит ли игрок, заносимый в черный список, в альянсе
        if (DS_GlobalAlliance::IsUserInAlliance((int)$userId, self::$globalAlliance->getID()))
        {
            //Если состоит, то смотрим, было ли выведено предупреждение 
            if (key_exists('withDelete', self::$data))
            {
                //Если да, то удаляем его из альянса
                self::_expelUser($userId);
            } else {
                //Если нет, то выводим предупреждение
                self::$data['MODE'] = 'WARNING_THAT_USER_WILL_DELETE';
                self::_index();
                return;
            }
        } 

        //Если нет, то просто заносим его в черный список
        DS_GlobalAlliance::InsertUserToBlackList(self::$globalAlliance->getID(), $userId, $comments);
        
        //Порисылаем игроку сообщение, что он занесен в черный список
        //Выбираем все инстансы игрока, которому хотим послать сообщение
        $instancesTo = DS_GlobalAlliance::GetUserInstance((int)$userId);
        $subject = 'Черный список';
        $message = 'Вы занесены в черный список альянса "'.self::$globalAlliance->getName().'"';
        foreach ($instancesTo as $key => $instance)
        {
            //посылаем игроку сообщение
            DS_GlobalAlliance::SendAllianceMessageToUser(0, $instance['id_userinstance'], $instance['id_world'], $instance['dbname'], $subject, $message, self::$globalAlliance->getID());
        }
        
        self::$data['MSG_TYPE'] = 'OK'; 
        self::$msg = 'SUCCESSFULL_ADD_USER_TO_BL';  
        self::_blackList();
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
        
    static private function _sentMessageToUsers()
    {           
        //Если нажали кнопку "отмена"
        if (key_exists('cansel', self::$data))
        {
            self::_indexMembers();
        }

        foreach (self::$data['users'] as $key => $userName)
        {
            if (!self::_validateMessage())
            {
                self::_indexMessage();
                return;
            }

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
        if (!self::_validateMessage())
        {
            self::_indexMessage();
            return;
        }
        
                
        $allianceId = DS_GlobalAlliance::GetUserGlobalAlliance(self::$user);
                
        //Получаем список миров, в которые отправляем сообщение
        foreach (self::$data['worlds'] as $k => $d)
        {
            $tempArr = explode("_", $d);
            $worldList[] = $comma.$tempArr['2'];
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

        UC_OutUserCabinet::Out('GlobalAllianceControlPanel', self::$data);
    }
    
    static private function _getParams(array $data)
    {   
        if(key_exists('ga_action', $data))
        {
            self::$action = $data['ga_action'];
        }
            
        self::$data = $data;
        
    }
    
    static private function SendMessageToAllAllianceMembers($subject, $message)
    {
        assert(!empty($subject));
        assert(!empty($message));
        
        //Выбираем все миры
        $worlds = DS_GlobalAlliance::GetWordList();
        
        foreach ($worlds as $k => $worldData)
        {
            //Получаем инстанс того, от имени которого посылаем сообщение
            $userInstance = DS_GlobalAlliance::GetUserInstaceByUserIdAndWorldId(self::$user->getId(), (int)$worldData['dbid'], (int)$worldData['id_world']);

            //Если у игрока нет инстанса в этом мире, то посылаем сообщение от имени ds
            if (empty($userInstance))
            {
                $userInstance = 0;
            }

            //Отправляем сообщение
            DS_GlobalAlliance::SendMessageToChoosenWorld((int)$userInstance, (int)$worldData['dbid'], (int)$worldData['id_world'], $subject, $message, self::$globalAlliance->getID());
        }
    }
    
    
    static private function _joinUserToAlliance(DE_User $user, DE_GlobalAlliance $globalAlliances)
    {
        $worlds = DS_GlobalAlliance::GetWordList();
        
        //Проходимся по всем мирам
        foreach ($worlds as $key => $world)
        {
            //Выбираем инстанс игрока в этом мире
            $userInstanceId = DS_GlobalAlliance::GetUserInstaceByUserIdAndWorldId($user->getId(), (int)$world['dbid'], (int)$world['id_world']);

            //Если у пользователя есть инстанс в этом мире
            if (!empty($userInstanceId))
            {
                //Проверяем, есть ли посольство у игрока в этом мире
                if (DS_GlobalAlliance::HaseUserEmbassy($userInstanceId, (int)$world['dbId']))
                {
                    $userInstance = DE_UserInstance::CreateFromID((int)$userInstanceId);
                    
                    $id_local_alliance = DS_GlobalAlliance::GetLocalAllianceByGlobalAllianceAndWorldID($globalAlliances->getID(),  (int)$world['dbid'], (int)$world['id_world']);
                    $localAlliance = DE_Alliance::CreateFromID((int)$id_local_alliance);

                    //Добавляем игрока в альянс в этом мире
                    ME_AddUserToAlliance::Add($userInstance, $localAlliance);
                }
            }
        }
    }
    
    static private function _checkIsUserLeader()
    {
        $globalAllianceId = DS_GlobalAlliance::GetUserGlobalAlliance(self::$user);

        if (DS_GlobalAlliance::GetLeaderLeader((int)$globalAllianceId) != self::$user->getID())
        {
            self::$data['ga_action'] = 'index';
            UC_ControlGlobalAllianceRegistration::Exec(self::$user, self::$data);
            return;
        }
    
        //Создаем экземпляг класса Global Alliance
        self::$globalAlliance = DE_GlobalAlliance::CreateFromID($globalAllianceId);

    }
}

?>