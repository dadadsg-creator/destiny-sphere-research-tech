<?php $CVS_Version = '$Revision: 1.18 $ ; $Date: 2009/07/03 08:32:55 $';
/**
 * Класс осуществляющий сбор и передачю данных шаблону index_start.php
 *
 */
class GE_ExecStartWorldsList
{
    
    /**
     * Объект для работы с базой данных
     *
     * @var DS_Common
     */
    static private $common;
    
    /**
     * Объект в который помещаются данные, необходимые для шаблона
     *
     * @var GE_DataStartWorldsList
     */
    static private $worldsListData;
    
    /**
     * Объект с данными пользователя
     *
     * @var DE_User
     */
    static private $user;
    
    /**
     * ID пользователя
     *
     * @var int
     */
    static private $uid;
    
    /**
     * Информация о премиуме
     *
     * @var bool
     */
    static private $profi;
    
    static private $msg;
    
    static private $userBlock = false;
    
    static private $userBanReason;
    
    static public function Exec($msg = '', $idQuestion = false)
    {
        assert(isset($_SESSION) && key_exists('uid', $_SESSION));
        self::$uid = (int)$_SESSION['uid'];
        assert(self::$uid > 0);     
        
        self::$msg = $msg; 
        
        assert(key_exists('profi_game', $_SESSION));
        self::$profi = $_SESSION['profi_game'];
        
        $_SESSION['dbId'] = 0;

        // если тестовый премиум то не учитываем
        if(key_exists('after_reg_trial', $_SESSION))
        {       
            if(self::$profi && $_SESSION['after_reg_trial'])
            {
                self::$profi = false;
            }
        }
        
        // объект для передачи данных теплейту
        self::$worldsListData = new GE_DataStartWorldsList();

        self::$user = DE_User::CreateFromID(self::$uid);
        
        self::$worldsListData->SetLanguage(self::$user->getLangShortName());
        
        self::$common = DS_Common::CreateForCurentDB(0);    
        GE_SearchStart::SetCommon(self::$common);
        
        // временная 
        self::_checkPasswordChange();       
            
        // порверяем не забанен ли юзер
        self::_checkUserBan();
        
        // проверяем активацию мыла
        self::_checkMailActivation();
        
        // получаем информацию о пользователе
        self::_getUserInfo();
        
        // получаем список миров в которых пользователь зарегистрирован
        self::_getWorldsToEnter();

        // получаем список миров в которых пользователь может зарегистрироваться
        self::_getWorldsToReg();
        
        // получаем список всех рас
        self::_getRaceList();
        
        // получаем список всех миров
        self::_getAllWorlds();
        
        // информация о премиуме игрока
        self::_getProfiData();
        
        // информация о подарке
        self::_getPresentData();
        
        // смотрим нет ли иформации о предыдущей попытки регистрации
        self::_getPrevRegInfo();
        
        // ключ защиты
        self::_getClientKey();
        
        self::_getChangeMailInfo();
        
        //смотрим, может ли игрок заходить  в кабинет
        self::_checkAllowUserCabinet();

        // показ хелпа для новых пользователей
        self::_checkNewUserHelp();

        // если опросник не заблокирован
        if(!key_exists('disable_poll', $_SESSION))
        {
            //Смотрим, надо ли выводить результаты опроса
            if ($idQuestion !== false) {
                //Поучаем результаты ответа на вопрос
                self::_checkPollResult($idQuestion);
            } else {
                // получаем текущий опросник
                self::_checkCurrentPoll();
            }
        }
        
        self::_getBannerData();
        
        // выводим страницу
        echo GE_OutStartWorldsList::FormOut(self::$worldsListData, self::$msg);
    }
    
    static private function _checkPasswordChange()
    {
//echo "<a href='pass_change.php'>>>></a>";
        
        /*if(!GE_SearchStart::CheckUserPasswordChange(self::$uid))
        {
            header("Location: pass_change.php?".session_name().'='.session_id());
            exit();
        }*/
    }
    
    static private function _getUserInfo()
    {
        // передаем ID пользователя
        self::$worldsListData->SetUserId(self::$uid);
        
        // передаем логин игрока 
        self::$worldsListData->SetUserName(self::$user->getName());

        // передаем e-mail игрока 
        self::$worldsListData->SetUserEmail(self::$user->getEmail());
    }
    
    // получаем список миров в которых пользователь зарегистрирован
    static private function _getWorldsToEnter()
    {
        $worlds = array();
        $worldsDown = array();
        $hasWorldToEnter = false;
            
        $hasInstanceInWorldUnion = false;
        $hasInstanceInWorldWithFreeTransfer = false;
        
        $worldsToEnter = GE_LogicWorldsList::GetWorldsToEnter(self::$user, self::$profi);               
        
        foreach($worldsToEnter as $k => $world)
        {
            if($world['game_down'] == 0)
            {               
                $hasWorldToEnter = true;
            }
            $worldsDown[$world['id_userinstance']."_".$world['dbId']."_".$world['id_world']] = $world['game_down'];
            $worlds[$world['id_userinstance']."_".$world['dbId']."_".$world['id_world']] = $world['worldname'];
            
            /* Инфа об объединении миров */
            $worldId = $world['dbname']."_".$world['id_world'];         
            if(in_array($worldId, $GLOBALS['WORLDS_UNION']))
            {
                $hasInstanceInWorldUnion = true;
            }
            
            if($world['dbId'] == constant('FREE_WORLD_TRANSFER_DB_FROM') && $world['id_world'] == constant('FREE_WORLD_TRANSFER_ID'))
            {
                $hasInstanceInWorldWithFreeTransfer = true;
            }
        }

        self::$worldsListData->SetWorldsToEnter($worlds);
        // информация о закрытых мирах
        self::$worldsListData->SetWorldsDown($worldsDown);
        // есть ли в списке миров для входа открытые
        // чтобы не давать ломиться в закрытые миры
        self::$worldsListData->SetHasWorldToEnter($hasWorldToEnter);
        
        self::$worldsListData->SetInstanceInWorldUnion($hasInstanceInWorldUnion);
        
        self::$worldsListData->SetInstanceInWorldWithFreeTransfer($hasInstanceInWorldWithFreeTransfer);
    }
    
    // получаем список миров в которых пользователь может зарегистрироваться
    static private function _getWorldsToReg()
    {
        $worlds = array();
        
        // есть ли у игрока инстансы в непремиумных мирах
        $instances = GE_SearchStart::GetUserNonProfiInstances(self::$user);
        if($instances)
        {
            $userHasInstances = true;
        } else {
            $userHasInstances = false;
        }
        
        $worldsToReg = GE_LogicWorldsList::GetWorldsToReg(self::$user, self::$profi, $userHasInstances);
        foreach($worldsToReg as $k => $world)
        {
            // не даем регистрироваться в закрытых мирах
            if($world['game_down'])
            {
                continue;
            }
            $worlds[$world['id_world']."_".$world['dbId']] = $world['worldname'];
        }
        
        if(count($worlds) > 0)
        {
            $emptyWorld = array('-' => '&nbsp;-&nbsp;');
            $worlds = array_merge($emptyWorld, $worlds);
        }
        
        self::$worldsListData->SetWorldsToReg($worlds);
    }
    
    // список локализованных названий всех рас 
    static private function _getRaceList()
    {
        $races = GE_SearchStart::GetRaceList($_SESSION['lang']);
        foreach($races AS $id_race => $race)
        {
            $allRaces[$race['id_race']] = $race['text'];
        }

        self::$worldsListData->SetAllRaces($allRaces);
    }
    
    // список всех существующих миров
    static private function _getAllWorlds()
    {
        $allWorlds = GE_SearchStart::GetWorldCollection($_SESSION['lang']); 

        self::$worldsListData->SetAllWorlds($allWorlds);
    }
    
    // информация о кол-ве оставшихся дней премиума
    static private function _getProfiData()
    {   
        if(self::$profi)
        {
            $premiumDays = GE_SearchStart::GetPremiumDaysLeft(self::$uid);
            self::$worldsListData->SetPremiumDays($premiumDays);            
        }
    }
    
    static private function _getPresentData()
    {
        $premiumPresent = !GE_LogicGetPremiumPresent::CheckIfPresentIsReceived(self::$user);
        self::$worldsListData->SetPremiumPresent($premiumPresent);
    }
    
    static private function _checkMailActivation()
    {
        if(!self::$user->isMailActivated() && constant('ACTIVATION_EMAIL_ENABLED'))
        {
            $regDate = strtotime(self::$user->getRegDate()->getPgFormat());
            $timeAfterReg = $regDate + constant('CAN_ENTER_WITHOUT_ACTIVATION')*86400;//time();
            $canEnterTill = strftime("%d.%m.%Y", $timeAfterReg);
            if($timeAfterReg < time())
            {
                self::$worldsListData->SetMailActivationRequire(true);
            } else {
                self::$worldsListData->SetMailActivationRequire(false);
            }
            self::$worldsListData->SetMailActivationDaysLeft($canEnterTill);
            self::$worldsListData->SetMailActivation(false);
        } else {
            self::$worldsListData->SetMailActivation(true);
            self::$worldsListData->SetMailActivationRequire(false);
        }
    }
    
    static private function _checkUserBan()
    {
        if(!self::$user->isActive())
        {           
            self::$userBlock = true;
            self::$worldsListData->SetUserBan(true);
        } else {
            self::$userBanReason = self::$user->getBanReason();
            if(count(self::$userBanReason))
            {
                self::$userBlock = true;
                self::$worldsListData->SetUserBan(true);
                $banEndDate = GB_TypeFactory::CreateBase('GB_Date', self::$userBanReason['enddate']);
                self::$userBanReason['enddate'] = $banEndDate->getFormatedDate(self::$user->getTimeDelta());
                self::$worldsListData->SetUserBanReason(self::$userBanReason);
            } else {
                self::$worldsListData->SetUserBan(false);
            }
        }
    }   
    
    static private function _checkAllowUserCabinet()
    {
        //self::$worldsListData->SetAllowUserCabinet(1);
        //return;
        
        //проходимся по всем мирам, где игрок зареган
        foreach (self::$worldsListData->GetWorldsToEnter() as $worldData => $worldName)
        {
            $worldInfo = explode('_', $worldData);
            
            $dbId = (int)$worldInfo[1];
            $worldId = (int)$worldInfo[2];
            
            //Смотрим, не закрыта ли игра на GameDown
            if (GameDown($dbId, $worldId)) {
                continue;
            }
                
            $_SESSION['dbId'] = $dbId;
            $_SESSION['world'] = $worldId;
            
            //Смотрим, открыт ли в этом мире кабинет
            $allowCabinet = (int)DE_WorldSettings::CreateForCurentWorld()->get('ALLOW_USER_CABINET');

            //Если да
            if ($allowCabinet == 1) 
            {
                self::$worldsListData->SetAllowUserCabinet(1);
                return;
            }
        }
    
        self::$worldsListData->SetAllowUserCabinet(0);
    }
    
    static private function _getPrevRegInfo()
    {
        if(key_exists('reg_world', $_SESSION))
        {
            self::$worldsListData->SetRegWorld($_SESSION['reg_world']);
        } else {
            self::$worldsListData->SetRegWorld('-');
        }
        
        if(key_exists('reg_race', $_SESSION))
        {
            self::$worldsListData->SetRegRace($_SESSION['reg_race']);
        } else {
            self::$worldsListData->SetRegRace(3);
        }
        
        if(key_exists('reg_combname', $_SESSION))
        {
            self::$worldsListData->SetRegCombname($_SESSION['reg_combname']);
        }
    }
    
    static private function _getClientKey()
    {
        self::$worldsListData->SetClientKey($_SESSION['client_key']);
    }
    
    static private function _getChangeMailInfo()
    {
        if(GE_SearchStart::GetCountEmailChanges(self::$uid) > 0)
        {
            self::$worldsListData->SetChangeMail(false);
        } else {
            self::$worldsListData->SetChangeMail(true);
        }
    }
    
    static private function _checkNewUserHelp()
    {
        if(GE_SearchStart::GetUserHelp(self::$user))
        {
            self::$worldsListData->SetShowUserHelp(true);
            GE_SearchStart::DecHelpShowCounter(self::$user);
        } else {
            self::$worldsListData->SetShowUserHelp(false);
        }
    }
    
    static private function _checkCurrentPoll()
    {
        //Получаем активные вопросы вопрос
        $currentPolls = GE_SearchStart::_getPollCurrentQuestoins(self::$worldsListData->GetWorldsToEnter());

        //Если активных вопросов нет
        if (empty($currentPolls)) {
            return;         
        }
        
        //Сортируем в случайном порядке
        shuffle($currentPolls);
        
        $questionKey = self::_GetNotAnsweredQuestion($currentPolls);
        
        //Если у игрока нет вопросов, на которые он еще не отвечал
        if ($questionKey === false) {
            return;
        }
        
        //Если ключ массива некорректен (перестраховка)
        if ((int)$currentPolls[$questionKey]['id_question'] <= 0) {
            return;         
        }
        
        //Получаем варианты ответов
        $answers = GE_SearchStart::_getPollAnswers((int)$currentPolls[$questionKey]['id_question']);
        
        //Если вариантов твета нет
        if (empty($answers)) {
            return;         
        }
        
        // передаем вопрос
        self::$worldsListData->SetPollQuestion($currentPolls[$questionKey]);
        
        // передаем варианты ответов 
        self::$worldsListData->SetPollAnswers($answers);
    }
        
    static private function _checkPollResult($idQuestion)
    {
        if ($idQuestion <= 0) {
            return;
        }
        
        //Получаем активные вопросы вопрос
        $pollResult = GE_SearchStart::_getPollResult(self::$user->getID(), $idQuestion);

        //Если активных вопросов нет
        if (empty($pollResult)) {
            return;         
        }
        
        // передаем статистику
        self::$worldsListData->SetPollResult($pollResult);
    }
        
    static private function _GetNotAnsweredQuestion($currentPolls)
    {
        foreach ($currentPolls as $key => $data)
        {
            //Смотрим, отвечал ли игрок уже на этот вопрос
            $isAnswered = GE_SearchStart::IsUserAnswered(self::$user->getID(), (int)$data['id_question']);

            if ($isAnswered === false) 
            {
                return $key;
            }
        }
        return false;
    }
    
    static private function _getBannerData()
    {
        self::$worldsListData->SetBannerData('');
        switch (constant('VERSION_NAME')) {
            case 'LOCAL':
            case 'ITALY':
                if(self::$user->getPartnerID() == 2)
                {
                    self::$worldsListData->SetBannerData('<iframe src="ads_frame.php?b=2&r=20&bg=ffffff"  style="border: 0px;" width="740" height="100" frameborder="0" marginheight="0" marginwidth="0" scrolling="No"></iframe>');
                }
                break;
        
            default:
                break;
        }
    }
}
?>