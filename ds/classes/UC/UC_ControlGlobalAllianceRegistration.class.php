<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_ControlGlobalAllianceRegistration implements UC_ControlInterface 
{
    static private $action = 'index';
    
    static private $avatarPreview;
    
    static private $msg;
    
    static private $avatar;
    
    static private $avatarLocation = "/ds/allianceavatars/";
    
    static private $user;
    
    static private $data;
    
    static private $joinWorlds;
    
    public static function Exec(DE_User $user, array $data)
    {   
//echo "<pre>";
//print_r($data);
/*exit;*/       
        self::_getParams($data);

        self::$user = $user;
//echo "self::action  = ".self::$action."<br>";
        switch (self::$action)
        {
            case 'index':
                self::_index();
                break;
                
            case 'index_create':
                self::_indexCreate();
                break;
                
            case 'index_join':
                self::_indexJoin();
                break;
                
            case 'join_alliance':
                self::_joinAlliance();
                break;
                    
            case 'confirm_join_alliance':
                self::_confirmJoinAlliance();
                break;
                    
            case 'un_expectation_alliance':
                self::_unExpectationAlliance();
                break;
            
            case 'create_alliance':
                self::_createAlliance();
                break;
                
            case 'leave_alliance_successful':
                self::_leaveAlliancSuccessful();
                break;
                
            case 'upgrade_alliance':
                
                break;
                
            case 'avatar_preview':
                
                break;
        
            default:
                self::_index();
                break;
        }
    }
    
    static private function _getParams(array $data)
    {   
        if(key_exists('ga_action', $data))
        {
            self::$action = $data['ga_action'];
        }

        /*if(key_exists('ga_avatar', $_FILES))
        {
            //self::_getAvatar($_FILES['ga_avatar']);
        }*/
        
        if(key_exists('ga_avatar_preview', $data))
        {
            if($data['ga_avatar_preview'] == 'on')
            {
                self::$avatarPreview = true;
            }
        }
        
        self::$data = $data;
    }
    
    static private function _getAvatar()
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

        if (move_uploaded_file($_FILES['ga_avatar']['tmp_name'], $path.$newFile)) {
            self::$msg = 'SUCCESSFULL';
            self::$data['MSG_TYPE'] = 'OK';     
        } else {
            self::$data['MODE'] = 'CREATE';
            self::$msg = 'CANT_LOAD_AVATAR_ON_SERVER';  
            self::_index();
            return;
        }
        
        self::$avatar = self::$avatarLocation.$newFile;
    }
    
    /**
     * Проверка, не состоит ли пользователь в каком-либо альянсе
     *
     */
    static private function _checkUserAlliances()
    {
        $userAlliances = array();

        foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
        {
            $common = DS_Common::CreateForCurentDB($dbId);
            
            $als = DS_GlobalAlliance::GetUserAlliance(self::$user, $common);
            
            foreach ($als as $k => $al)
            {
                $userAlliances[] = $al;
            }
        }
        return $userAlliances;
    }
    
    static private function _index()
    {   
        if(!key_exists('MODE', self::$data))
        {
            self::$data['MODE'] = 'INDEX';
        }
        
        self::$data['MSG'] = self::$msg;

        UC_OutUserCabinet::Out('GlobalAllianceIndex', self::$data);
    }
    
    static private function _indexJoin()
    {
        //Если у человека нет инстанса ни в одном мире
        if (DS_GlobalAlliance::HasUserInstance(self::$user->getID()) == false)
        {
            self::$msg = 'USER_HAS_NOT_INSTANCE';   
            self::_index();
            return;
        }
        
        $alliances = self::_checkUserAlliances();
        
        self::$data['ALLIANCES'] = $alliances;
        
        //Если человек уже состоит в альянсе
        if(count($alliances) > 0)
        {
            //Если челове состоит в глобальном альянсе
            if ($alliances[0]['id_global_alliance'] > 0 ) {
                self::$data['MODE'] = 'LEAVE_GLOBAL_ALLIANCE';
                self::$data['ALLIANCE_NAME'] = $alliances[0]['alliancename'];
                self::$data['GLOBAL_ALLIANCE_ID'] = $alliances[0]['id_global_alliance'];
                self::_index();
                return;
            } else {
                self::$data['MODE'] = 'USER_IN_ALLIANCE';
                self::_index();
                return;
            }
        }
        
        //Если человек уже подал заявку на вступление
        $userAllianceExpectation = DS_GlobalAlliance::CheckUserInExpectation(self::$user->getID());

        if(!empty($userAllianceExpectation))
        {
            self::$data['USER_IN_EXPECTATION'] = 'yes';
            self::$data['USER_ALLIANCE_EXPECTATION'] = $userAllianceExpectation;
        }else{
            self::$data['USER_IN_EXPECTATION'] = 'no';
        }
        
        //Выбираем все глобальные альянсы
        $globalAlliances = DS_GlobalAlliance::GetGlobalAlliances(self::$user);
        
        self::$data['GLOBAL_ALLIANCES'] = $globalAlliances;
        
            
        self::$data['MODE'] = 'JOIN_INDEX';
        self::_index();
    }
    
    static private function _confirmJoinAlliance()
    {
        assert(self::$data['id_alliance'] >=0 );
        
        //Выбираем данные об альянсе
        $alliance = DS_GlobalAlliance::GetDataById((int)self::$data['id_alliance']);

        
        /*//Проверяем, правилен ли введенный пароль
        if (self::$data['password'] != $alliance['join_password'])
        {
            self::$msg = 'WRONG_JOIN_PASSWORD';         
            self::_indexJoin();
            return;
        } */

        //Проверяем, есть ли игрок в черном списке
        if (DS_GlobalAlliance::IsUserInBlackList(self::$data['id_alliance'], self::$user->getId()))
        {
            self::$msg = 'CANT_JOIN_ALLIANCE';          
            self::_indexJoin();
            return;
        }
        
        // Проверяем есть ли денежка для создания альянса
        /*if(!UC_GlobalAllianceMoneyOperations::CheckMoney(self::$user, $alliance['join_payment'], 'SC'))
        {
            self::$msg = 'NOT_ENOUGHT_CREDITS_FOR_JOIN';
            self::_indexJoin();
            return;
        }*/
        
        $countMembers = DS_GlobalAlliance::GetCountMembers((int)self::$data['id_alliance']);
        
        self::$data['ALLIANCE'] = $alliance;
        self::$data['ALLIANCE']['members_ammount'] = $countMembers;
            
        self::$data['MODE'] = 'CONFIRM_JOIN';
        self::_index();
    }
    
    static private function _unExpectationAlliance()
    {
        DS_GlobalAlliance::UnExpectationAlliance(self::$user->getID());

        self::$msg = 'SUCCESSFULL';         
        self::$data['MSG_TYPE'] = 'OK'; 
        self::$data['MODE'] = 'JOIN_INDEX';
            
        self::_indexJoin();
    }
    
    static private function _indexCreate()
    {
        //Если у человека нет инстанса ни в одном мире
        if (DS_GlobalAlliance::HasUserInstance(self::$user->getID()) == false)
        {
            self::$msg = 'USER_HAS_NOT_INSTANCE';   
            self::_index();
            return;
        }
        
        //Если человек уже подал заявку на вступление
        $userAllianceExpectation = DS_GlobalAlliance::CheckUserInExpectation(self::$user->getID());

        if(!empty($userAllianceExpectation))
        {
            self::$data['MODE'] = 'ALREADY_WEITING_JOIN';
            self::_index();
            return;
        }
        
        $alliances = self::_checkUserAlliances();
        self::$data['ALLIANCES'] = $alliances;

        //Если человек уже состоит в альянсе
        if(count($alliances) > 0)
        {
            //Если челове состоит в глобальном альянсе
            if ($alliances[0]['id_global_alliance'] > 0 ) {
                self::$data['MODE'] = 'LEAVE_GLOBAL_ALLIANCE';
                self::$data['ALLIANCE_NAME'] = $alliances[0]['alliancename'];
                self::$data['GLOBAL_ALLIANCE_ID'] = $alliances[0]['id_global_alliance'];
                self::_index();
                return;
            } else {
                self::$data['MODE'] = 'USER_IN_ALLIANCE';
                self::_index();
                return;
            }
        }
        
        
        $creationCost = (int)DE_GlobalSettings::Get('GLOBAL_ALLIANCE_CREATION_COST');
        $creationCredits = DE_GlobalSettings::Get('GLOBAL_ALLIANCE_CREATION_COST_CREDITS');
        
        self::$data['CREATION_COST'] = $creationCost;
        self::$data['CREATION_CREDITS'] = $creationCredits;
        
        /*На случай, если решим апгрейдить альянсы
        if(count($alliances) == 1 && $alliances[0]['status'] == 'LEADER')
        {
            self::$data['MODE'] = 'UPGRADE';
        } elseif(count($alliances) > 0) {
            self::$data['MODE'] = 'CANT_CREATE';
        } else {
            self::$data['MODE'] = 'CREATE';
        }*/
        
        //А пока
        if(count($alliances) > 0)
        {
            self::$data['MODE'] = 'USER_IN_ALLIANCE';
            self::_index();
            /*if (!empty($alliances[0]['id_global_alliance']) AND $alliances[0]['id_global_alliance'] > 0)
            {
                self::$data['LEAVE_BUTTON'] = 'YES';
                self::$data['ID_GLOBAL_ALLIANCE'] = $alliances[0]['id_global_alliance'];
            }*/
        }
        
        if(key_exists('GA_CREATION_DATA', $_SESSION))
        {
            self::$data['GA_CREATION_DATA'] = $_SESSION['GA_CREATION_DATA'];
        } else {
            self::$data['GA_CREATION_DATA'] = array(
                'alliance_name'     => '',
                'short_name'        => '',
                'join_password'     => '',
                'join_payment'      => '',
                'join_confirmation' => false
            );
        }
        
        //Получаем настройку возможности брать плату за вступление
        $joinPaymentSettings = DE_GlobalSettings::Get('GLOBAL_ALLIANCE_JOIN_PAYMENT');
        self::$data['JOIN_PAYMENT_SETTINGS'] = $joinPaymentSettings;
        
        self::$data['MODE'] = 'CREATE';
        self::_index();
    }
    
    static private function _checkAllianceCreationData()
    {
        if(count(self::_checkUserAlliances()) > 0)
        {
            self::$msg = 'GEN_ERROR';           
            return false;
        }
        
        assert(key_exists('alliance_name', self::$data));
        assert(key_exists('short_name', self::$data));
        assert(key_exists('join_password', self::$data));
        //assert(key_exists('join_payment', self::$data));

        $_SESSION['GA_CREATION_DATA'] = array(
            'alliance_name' => self::$data['alliance_name'],
            'short_name'    => self::$data['short_name'],
            'join_password' => self::$data['join_password'],
            'join_payment'  => self::$data['join_payment'],
            'join_confirmation' => false,
        );

        if(strcmp(self::$data['join_password'], self::$data['confirm_join_password']) != 0)
        {
            self::$msg = 'MISMATCH_PASSWORD';
            return false;
        }

        if(!VD_Alliance::Validate(self::$data['alliance_name'], self::$data['short_name'], self::$data['join_password'], 0, DS_Common::CreateForCurentDB()))
        {
            self::$msg = VD_Alliance::FetchErrorCode();         
            return false;
        }
        
        if (!key_exists('join_payment', self::$data))
        {
            self::$data['join_payment'] = 0;
        }

        if((int)self::$data['join_payment'] < 0 || (int)self::$data['join_payment'] > DE_GlobalSettings::Get('GLOBAL_ALLIANCE_CREATION_COST'))
        {
            self::$msg = 'WRONG_JOIN_PAYMENT';
            return false;
        }

        // Проверяем есть ли денежка для создания альянса
        /*if(!UC_GlobalAllianceMoneyOperations::CheckMoneyForOperation(self::$user, 'CREATION'))
        {
            self::$msg = 'NOT_ENOUGHT_CREDITS_FOR_CREATION';
            return false;
        }*/
        
        // Добавим данные, которых нету в формочке
        if(key_exists('join_confirmation', self::$data) && self::$data['join_confirmation'] == 'on')
        {
            self::$data['join_confirmation'] = 't';
            $_SESSION['GA_CREATION_DATA']['join_confirmation'] = true;
        } else {
            self::$data['join_confirmation'] = 'f';
        }
        
        self::$data['id_leader'] = self::$user->getID();
        
        self::$data['creation_date'] = time();
        
        return true;
    }
    
    static private function _createAlliance()
    {
        if(!self::_checkAllianceCreationData())
        {
            self::_indexCreate();
            return;
        }

        // Бабло снимаем
        //UC_GlobalAllianceMoneyOperations::GetMoneyForOperation(self::$user, 'CREATION');
        
        // создаем альянс
        $alliance = DE_GlobalAlliance::CreateInDB(self::$data);
        
        if(key_exists('ga_avatar', $_FILES) && strlen($_FILES['ga_avatar']['name']) > 0)
        {
            //Проверяем размер картинки
            $imageSize = getimagesize($_FILES['ga_avatar']['tmp_name']);
            
            if ($imageSize[0] != 60 || $imageSize[1] != 60)
            {
                self::$msg = 'WRONG_IMAGE_SIZE';
                self::_indexCreate();
                return;
            }
            
            //Загружаем аватарку
            if(!self::_saveAvatarOnServer())
            {
                self::$msg = 'CANT_LOAD_AVATAR_ON_SERVER';
                self::_indexCreate();
                return;
            }

            self::_addLocalAlliances($alliance);
            
            //Обновляем сведения об аватаре в бд
            DS_GlobalAlliance::AddAvatar($alliance->getID(), self::$avatar);
        } else {
            self::_addLocalAlliances($alliance);
        }
        
        $alliance->commit();
        
        //Добавляем игрока в табличку со списком пользователей глобального альянса
        DS_GlobalAlliance::AddUserToGlobalAlliance(self::$user->getId(), $alliance->getId());
        
        GB_SingletonObjectStorage::CommitAllCommitable();
        
        self::$msg = 'CREATE_ALLIANCE_SUCCESSFULL'  ;
        self::$data['MSG_TYPE'] = 'OK'; 
        self::$data['ga_action'] = 'index_stat';
        
        //Переходим на страничку управления альянсом
        UC_ControlGlobalAllianceControlPanel::Exec(self::$user, self::$data);
        
        //UC_ControlGlobalAlliance::Exec(self::$user, self::$data);
        return;
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
    
    static private function _addLocalAlliances(DE_GlobalAlliance $globalAlliance)
    {
        $worlds = DS_GlobalAlliance::GetWordList();
                        
        foreach ($worlds as $key => $data)      
        {
            $id_local_alliance = DS_GlobalAlliance::InsertLocalAlliance($globalAlliance, (int)$data['dbid'], (int)$data['id_world']);
            
            $IdInstance = DS_GlobalAlliance::GetUserInstaceByUserIdAndWorldId(self::$user->getID(), (int)$data['dbid'], (int)$data['id_world']);

            //Если у пользователя есть инстанс в этом мире, то добавляем его в альянс
            if (!empty($IdInstance))
            {
                $localAlliance = DE_Alliance::CreateFromID($id_local_alliance);

                $userInstance = DE_UserInstance::CreateFromID((int)$IdInstance);
                ME_AddUserToAlliance::Add($userInstance, $localAlliance);
            } 
        }
    }   
    
    static private function _joinAlliance()
    {
        //Если была нажата кнопка ""Отмена
        if (isset(self::$data['cancel']))
        {
            self::_indexJoin();
            return;
        }

        assert(self::$data['id_global_alliance'] >=0 );
        
        //Смотрим, не подал ли игрок уже заявку
        $userExpectation = DS_GlobalAlliance::CheckUserInExpectation(self::$user->getID());
        if (!empty($userExpectation))
        {
            self::_index();
            return;
        }
        
        //Создаем объект глобального альянса
        $globalAlliance = DE_GlobalAlliance::CreateFromID((int)self::$data['id_global_alliance']);

        //Если для вступления требовался пароль
        if (key_exists('password', self::$data))
        {
            //Проверяем его правильность
            if (self::$data['password'] != $globalAlliance->getPassword())
            {
                self::$msg = 'WRONG_JOIN_PASSWORD';         
                self::_indexJoin();
                return;
            }
        }
        
        // Бабло снимаем
        //UC_GlobalAllianceMoneyOperations::GetMoney(self::$user, $globalAlliance->getJoinPayment(), 'SC');
        
        
        /*--------------------------------------------------------------------------------------
        
        Если мы подаем заявку на вступление, то надо не снимать деньги, а класть их в резерв
        !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        
        --------------------------------------------------------------------------------------*/
        
        //Если для вступления в альянс требуется подтверждения лидера, то ставим игрока в ожидание
        if ($globalAlliance->getConfirmation('pg') == 't')
        {
            DS_GlobalAlliance::AddUserToExpectation(self::$user->getID(), $globalAlliance->getID(), self::$data['comments']);
        } elseif ($globalAlliance->getConfirmation('pg') == 'f')
        {
            //Если подтверждения на требуется, заносим игрока в альянс
            self::_joinUserToAlliance(self::$user, $globalAlliance);
            
            //Добавляем игрока в табличку со списком пользователей глобального альянса
            DS_GlobalAlliance::AddUserToGlobalAlliance(self::$user->getId(), $globalAlliance->getId());
            
            //Посылаем сообщение всем игрокам альянса
            //Выбираем все миры
            $worlds = DS_GlobalAlliance::GetWordList();
            $subject = 'Новый участник альнса';
            $message = "Наш альянс приветствует нового участника по имени ".self::$user->getName().".";
            foreach ($worlds as $k => $worldData)
            {
                //Отправляем сообщение
                DS_GlobalAlliance::SendMessageToChoosenWorld(0, (int)$worldData['dbid'], (int)$worldData['id_world'], $subject, $message, $globalAlliance->getId());
            }       

            self::$data['JOIN_WORLDS'] = self::$joinWorlds;
            
            self::$data['ALLIANCE_NAME'] = $globalAlliance->getName();  
            self::$data['ga_action'] = 'join_alliance_successful';  
            UC_ControlGlobalAllianceMemberPanel::Exec(self::$user, self::$data);
            return;
        }
        
        self::$data['MODE'] = 'INDEX';
        self::$msg = 'SUCCESSFULL'; 
        self::$data['MSG_TYPE'] = 'OK';
        self::_index();
    }
    
    static private function _joinUserToAlliance(DE_User $user, DE_GlobalAlliance $globalAlliances)
    {
        $worlds = DS_GlobalAlliance::GetWordList();
        self::$joinWorlds = array();
        
        //Проходимся по всем мирам
        foreach ($worlds as $key => $world)
        {
            //Выбираем инстанс игрока в этом мире
            $userInstanceId = DS_GlobalAlliance::GetUserInstaceByUserIdAndWorldId(self::$user->getId(), (int)$world['dbid'], (int)$world['id_world']);

            //Если у пользователя есть инстанс в этом мире
            if (!empty($userInstanceId))
            {
                //Проверяем, есть ли посольство у игрока в этом мире
                if (DS_GlobalAlliance::HaseUserEmbassy($userInstanceId, (int)$world['dbId']))
                {
                    $userInstance = DE_UserInstance::CreateFromID((int)$userInstanceId);
                    
                    $id_local_alliance = DS_GlobalAlliance::GetLocalAllianceByGlobalAllianceAndWorldID($globalAlliances->getID(),  (int)$world['dbid'], (int)$world['id_world']);
                    $localAlliance = DE_Alliance::CreateFromID((int)$id_local_alliance);
//echo "<br>Вносим игрока с инстансом <b>".$userInstance->getId()."</b> в альянс ".$id_local_alliance." в мире ".$world['id_world']."<br>";

                    //Добавляем игрока в альянс в этом мире
                    ME_AddUserToAlliance::Add($userInstance, $localAlliance);
                    
                    
                    self::$joinWorlds[] = $world['worldname'];
                }
            }
        }
    }
    
    static private function _leaveAlliancSuccessful()
    {
        self::$data['MODE'] = 'LEAVE_ALLIANCE_SUCCESSFUL';
        self::$msg = 'SUCCESSFULL';
        self::$data['MSG_TYPE'] = 'OK'; 
        self::_index();
        return;
    }
    
}

?>