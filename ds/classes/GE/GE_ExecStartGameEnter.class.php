<?php $CVS_Version = '$Revision: 1.8 $ ; $Date: 2010/03/10 09:20:36 $';

/**
 * Вход
 *
 */

class GE_ExecStartGameEnter
{
    /**
     * ID инстанса игрока
     *
     * @var integer
     */
    static private $UIID;
    
    /**
     * ID базы вкаторой мир игрока куда он коннектиться
     *
     * @var integer
     */
    static private $DBID;
    
    /**
     * ID пользователя
     *
     * @var integer
     */
    static private $UID;
    
    /**
     * Флаг профи режима
     *
     * @var boolean
     */
    static private $isProfi = false;
    
    /**
     * ОБъект доля работы с БД
     *
     * @var DS_Common 
     */
    static private $common;
    
    /**
     * Сообщение об ошибке
     *
     * @var string
     */
    static private $errorMsg;
    
    /**
     * Объект с данными пользователя
     *
     * @var DE_User
     */
    static private $user;
    
    static private $idWorld;
    
    static public function Exec($data)
    {
        assert(is_array($data) && count($data > 0));
        
        // проверяем защитный код
        if($GLOBALS['INDEX_START_BLOCKS']['PROTECTION_CODE'])
        {
            assert(key_exists('protection', $data));
            if(!$data['protection'] || !GE_ImageProtection::Validate($data['protection']))
            {
                self::$errorMsg = 'PROTECTION_CODE_ERROR';
                return false;
            }
        }
    
        //Получем все необходимые входные данные
        assert(key_exists('uid', $_SESSION));
        self::$UID = (int)$_SESSION['uid'];
        assert(self::$UID > 0);
        
        assert(key_exists('uiid', $data));      
        $arrayTmp = explode('_', $data['uiid']);
        
        assert(key_exists(0, $arrayTmp));
        self::$UIID = (int)$arrayTmp[0];
        assert(self::$UIID > 0);
        
        assert(key_exists(1, $arrayTmp));
        self::$DBID = (int)$arrayTmp[1];
        assert(self::$DBID >= 0);
        
        assert(key_exists(2, $arrayTmp));
        self::$idWorld = (int)$arrayTmp[2];
        assert(self::$idWorld > 0);
        
        assert(key_exists('profi_game', $_SESSION));
        self::$isProfi = (bool)$_SESSION['profi_game'];
        
        assert(key_exists('s_login', $_SESSION));
        assert(key_exists('s_pass', $_SESSION));
        
        // Проверяем не закрыта ли игра
        if(GameDown(self::$DBID))
        {
            Header("Location: index_down.html");
        }
        
        //Подкжчемся к необходимуому серверу БД
        $_SESSION['dbId'] = self::$DBID;
        self::$common = DS_Common::CreateForCurentDB(self::$DBID);
            
        GE_SearchStart::SetCommon(self::$common);
        
        //Проверяем инстанс на корректность
        if(!VD_UserInstance::Validate(self::$UIID, self::$UID, self::$common))
        {
            ds_error("Validate instance error: ".VD_UserInstance::FetchErrorCode());
        }
        
        //Засовываем UIID в сессию
        $_SESSION["uiid"] = self::$UIID;
        
        //Делаем блокировку паралельной работы
        self::_updateWindowKey();
                    
        //Ставим масштабированеи какое-то ..кстати зачем оно?
        if(self::$isProfi == 1)
        {
            self::_setScaling();
        }       
        
        //Проверяем возможность входа в данный мир пользователя
        self::$user = DE_User::CreateFromID(self::$UID);
        $data = DS_User::getByID(self::$UID);
        assert(key_exists('password', $data));
        
        // Проверяем не слетела ли репликация
        /*if($data['password'] != $_SESSION['s_pass'])
        {
            self::$errorMsg = 'REPLICATION_PROBLEMS';
            return false;
        }*/
        
        //Извлекаем из БД сведения по пользователям
        self::_getUserInfo();
        
        self::$common->setDbId(0);
        if(!GE_LogicCanEnterWorld::Test(self::$user, self::$isProfi, self::$DBID, self::$idWorld))
        {           
            ds_error("Can't enter this world");
        }
        self::$common->setDbId(self::$DBID);
        
        ME_LoginHistory::Exec(self::$UID, self::$common);
        $ui = DE_UserInstance::CreateFromID(self::$UIID);
        TMP_NewYear::givePresent($ui);
        
        $path = GL_VersionPathWork::GetVersionPath(self::$DBID);
        
        if(constant("DISABLE_DETECT_PLAYER"))
        {
            $script = 'index.php';
        } else {
            $script = 'detect_player.php';
        }
        
        if (self::$isProfi) 
        {                           
            $url = "Location: ..".$path.$script."?ck=".$_SESSION['client_key']; 
        } else {                    
            $url = "Location: ..".$path."index_queue.php?ck=".$_SESSION['client_key'];
        }       
        
        // Версия игры
        $url .= "&VERS=".constant('VERSION_NAME');
        
        // Смотрим, включен ли в мире чат
        if (DE_WorldSettings::CreateForCurentWorld()->get('CHAT_ENABLE') == 1) {
            // Выбираем номер порта, по которому будем коннектиться
            $port = DS_World::GetPortByWorldId(self::$idWorld);
            assert(is_int($port) && $port >= 0);
            $_SESSION['sport'] = $port; 
            $url .= "&sport=".$port;
        } else {
            $_SESSION['sport'] = -1;    
        }
        
        if(session_id())
        {
            $url = $url."&".session_name()."=".session_id();
        }
        
        Header($url);
        return true;
    }
    
    static private function _setScaling()
    {       
        $scaling = GE_SearchStart::GetScaling(self::$UID);  

        if($scaling)
        {
            $_SESSION["scaling"] = $scaling;
        }
    }
    
    static private function _updateWindowKey()
    {
        $windowKey = mt_rand(10, 99);
        $_SESSION['windowKey'] = $windowKey;
        
        GE_SearchStart::UpdateWindowKey(self::$UIID, $windowKey);
    }
    
    static private function _getUserInfo()
    {
        $userInfo = GE_SearchStart::GetUserInfo(self::$UIID, $_SESSION['s_login'], $_SESSION['s_pass']);
        
        $_SESSION["shortname"]   = $userInfo['shortname'];
        $_SESSION["lang"]        = (int)$userInfo['id_language'];
        $_SESSION["race"]        = (int)$userInfo['id_race'];
        $_SESSION["world"]       = (int)$userInfo['id_world'];
        $_SESSION["ring"]        = (int)$userInfo['id_ring'];
        $_SESSION["combcomplex"] = (int)$userInfo['id_combcomplex'];
        $_SESSION["comb"]        = (int)$userInfo['id_comb'];
        $_SESSION["celltype"]    = 1;
        $_SESSION["combnumber"]  = (int)$userInfo['combnumber'];
        $_SESSION["userscore"]   = (int)$userInfo['combscore'];
        $_SESSION["worldtype"]   = $userInfo['worldtype'];
        
        self::$idWorld = (int)$userInfo['id_world'];
    }
    
    static public function FetchErrorCode()
    {
        return self::$errorMsg;
    }
}

?>