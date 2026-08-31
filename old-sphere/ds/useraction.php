<?PHP $CVS_Version = '$Revision$ ; $Date$';
/**
 * Скрипт к которому FLASH обращаеться со всеми запросами.
 */
    //Подключаем базовый файл с инклюдом.
    require_once "Main.php";

  
    if ( constant('DEFAULT_LANGUAGE_SHORT') == 'L') 
  {
    require_once($_SERVER['DOCUMENT_ROOT']."/sys/OAuth.php");
    require_once($_SERVER['DOCUMENT_ROOT'].'/ds/config/klasecfg.php' );
  }
/********** НАстройки работы хендлеров **********/
    //Устанавливаем лимит памяти
    ini_set('memory_limit', constant('PHP_MEMORY_LIMIT'));

    //Устанавливаем лимит времени исполнения
    set_time_limit(constant('PHP_TIME_LIMIT'));

    //Устанавливаем лимит времени исполнения
    //set_error_handler("DS_ErrorHandler");

    //Регитрация финализатора
    register_shutdown_function ('finilazer');

/********** END **********/

    //Начинаеться засечка времени работы срикпта
    SU_DebugTools::TimerStart('SCRIPT_START_TIME');

    //Если запускаеться из коммандной строки то производим необходимые действия по адаптации старта
    RD_AdeptUserActionToConsole::Exec();

    //Старт сессии
    if(!SU_GameSessionWork::SessionContinue())
    {
        SU_OutputHandler::Singleton()->addString("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<key valid=\"0\" addr=\"index_login.php\"/>", 'loadkey');
        echo SU_OutputHandler::Singleton()->fetchAll();
        exit;
    }


    //Проверка на пустой запрос
    if(count($_POST) == 0)
    {
        $str = SU_DebugTools::GetInternalDate()." ".SU_DebugTools::FormIndeteficationString()." ".SU_DebugTools::GetFormatedBrouserInfo()."\n";
        SU_DebugTools::WriteToLog($str, 'EmptyRequestError.log');

        ds_error ("Empty request (POST)", false );
    }

/********** Првоерка сесси на корректность **********/
    $correctSassionFlag =
    key_exists('dbId', $_SESSION) &&
    key_exists('world', $_SESSION) &&
    key_exists('lang', $_SESSION) &&
    key_exists('uiid', $_SESSION) &&
    key_exists('windowKey', $_SESSION) &&
    key_exists('race', $_SESSION) &&

    key_exists('ring', $_SESSION) &&
    key_exists('combcomplex', $_SESSION) &&
    key_exists('comb', $_SESSION) &&
    key_exists('celltype', $_SESSION) &&
    key_exists("LOGIN_ADM_ENTER", $_SESSION);

    if(!$correctSassionFlag)
    {
        SU_DebugTools::WriteToLog(print_r($_SESSION, true), 'CheckSession.log');

        //ds_error ("Your session is closed. Please login again.", false, 1, 1, 0, 'SESSION_CLOSE');
        //ds_error ("Your session is closed. Please login again.", false, 1, 1, 0, 'SESSION_CLOSE');        
        die('outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf" title=""><txt><![CDATA[Your session is closed. Please login again.]]></txt></swloader>');
    }

    /********** Проверка закрытости игры **********/
    require_once(constant('INCLUDE_PATH').'GameDown.inc.php');

    if(GameDown($_SESSION['dbId'], $_SESSION['world']))
    {
        ds_error ("Game is closed. Please try later.", false, 1, 1, 0, 'GAME_CLOSED');
    }


    //Засекаем время для отправки клиенту
    SU_ClienServerTimeSync::FixTime();

    //Проверка на соотесвия путя версии
    if (!GL_VersionPathWork::ValidateVersionPath($_SESSION['dbId']))
    {
        SU_OutputHandler::Singleton()->addString("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<key valid=\"0\" addr=\"index_start.php\"/>",'loadkey');
        echo SU_OutputHandler::Singleton()->fetchAll();
        exit;
    }

    if (!key_exists('profi_game', $_SESSION))
    {
        $_SESSION['profi_game'] = false;
    }

    if(!key_exists('worldtype', $_SESSION))
    {
        $_SESSION['worldtype'] = '';
    }

    //Народ только эту затык уне трогайте. Сам непоню зачем, но надо наврено.
    /*if (isset($_SESSION) && key_exists('world_uid_new', $_SESSION))
    {
        $_SESSION['world_uid'] = $_SESSION['world_uid_new'];
    }*/
/********** END **********/

      
/********** Инициализации объектов нужных и переменных ***********/
    //Инициализируем аккумулятор протокола
    SU_ProtocolAccumulator::RequestAccumulator();

    if(!empty($_SESSION["s_xmldata"]) && !$_SESSION['profi_game'])
        $protocol = decode($_SESSION["s_xmldata"]);
    else if(!empty($_POST["xmldata"]))
        $protocol = decode($_POST["xmldata"]);
  if (in_array($_SESSION['s_login'], $GLOBALS['USERS_FOR_LOG_PROTOCOL']))
        SU_DebugTools::LogAll("protocol=".$protocol, 'protocol.log');
            
    $protocol = stripcslashes ( $protocol );
    $protocol = str_replace ( "&", "", $protocol );

  
    $gameenter = 0;
    if (strpos($protocol,'<load locations') !== false) //вход в игру
        $gameenter = 1;
   
    
    $usebot  = DE_WorldSettings::CreateForCurentWorld()->get('ENABLE_ANTIBOT');

    if ($usebot && preg_match("/^apache/", $sapi))
    {
        if ($gameenter) //вход в игру
        {
            //при входе в игру delta не апдейтится
            if (!empty($_SESSION['uid']) && !empty($_SESSION['world']))
            {
                list($qdelta,$att_status) = DS_Question::GetTimeBeforeQuestion($_SESSION['uid'],
                $_SESSION['world'], false);
                if (!$att_status) //в случае, если в предыдущую сессию неправильно ответили на вопрос,
                //принудительно задаём его снова
                {
                    $qdelta = 0;
                }
            } else
            {
                $qdelta = DS_Question::$qperiod*1000;
                $att_status = 1;
            }
        } else if ($protocol != 'logout') //не вход в игру и не логаут - обычное действие
        {
            if (!empty($_SESSION['uid']) && !empty($_SESSION['world']))
            list($qdelta,$att_status) = DS_Question::GetTimeBeforeQuestion($_SESSION['uid'], $_SESSION['world']);
            else
            {
                $qdelta = DS_Question::$qperiod*1000;
                $att_status = 1;
            }

            //В user_action не может приходить запросов, пока не дан правильный ответ на контрольный вопрос
            //поэтому если запрос пришёл, то человек - бот
            if (!$gameenter && (($qdelta < -300000) || !$att_status ))
            {
                //блокируем все действия.
                //SU_DebugTools::LogAll('blocked! qdelta='.$qdelta.', REQUEST='.var_export($_REQUEST, true), 'daniil.log');
                DS_Botlist::AddBot($_SESSION['uid']);
                //$_SESSION[] = array();
                //header("Location: index_start.php");
                exit;
            }
        }
    }
/****** Проверка ключа клиента ******/

    if(!SU_ClientKey::isCheckGetVarsSet() && !RD_AdeptUserActionToConsole::$commandLineFlag)
    {
        SU_ClientKey::checkKey();
    }

/*************** END ****************/

    //Создаем экземпляр сиситемных сообщений для данного пользователя
    DE_SystemMessages::CreateForCurrentUserInstance();

    //Создаем экземпляр глобюального контенера с утилитами.
    $GLOBALS['GC'] = new GB_GlobalContainer ($_SESSION['dbId'], $_SESSION['world'], $_SESSION["lang"]);

    //ДЛя совместимости с 5PHP сделан собственны объект domXML эмулирующйи нтерфейс 4 PHP
    if (version_compare(PHP_VERSION,'5','>='))
    {
        import ('domXML');
    }

    // Проверка окончания  ускорения тренировки юнитов
    if (strpos(decode($_POST["xmldata"]), 'refresh') || strpos(decode($_POST["xmldata"]), 'load')) {
        DS_UserInstance::CheckEndTrainSpeedup($_SESSION['uiid']);
    }

    //Подключаемся к БД
    $GLOBALS['common']->startTrans(); //Стартуем транзакцию.
    $GLOBALS['common']->startErrorBlocking(); //Блокируем ипольнение завпрос в случае возникновения ошибок
    DS_Common::$slowQueryTimeLimit = constant('DB_SLOW_QUERY_TIMELIMIT');

    // Проверяем не закрыт ли мир
    GE_SearchBase::SetCommon($GLOBALS['common']);
    $worldStatus = GE_SearchStart::GetWorldStatus((int)$_SESSION['world'], $_SESSION['dbId']);
    if($worldStatus == 'CLOSE' && !$_SESSION['LOGIN_ADM_ENTER'] && !RD_AdeptUserActionToConsole::$commandLineFlag)
    {
        ds_error ("Game is closed. Please try later.", false, 1, 1, 0, 'GAME_CLOSED');
    }

    //Выбираем текущего пользователя и его инстанс
    $userInstance = DE_UserInstance::CreateFromID((int)$_SESSION['uiid']);

    if($userInstance->haveNewbDefend())
    {
        SU_OutputHandler::Singleton()->addString(1, "newbee");
    } else {
        SU_OutputHandler::Singleton()->addString(0, "newbee");
    }

     $user = $userInstance->getUser();
   $alertStr = '';
   if ($gameenter)
   {
     $checkDate = GB_TypeFactory::CreateBase('GB_Date', time());
     $premBan = $user->getPremiumBan();
     if ($premBan->dateInPeriod($checkDate))
     {
       $banstr = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PREMIUM_BAN_ALERT', array(), null, null, false);
       $titlestr = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('START_ALERT', array(), null, null, false);
       $banstr = str_replace('%DATE%', $premBan->getEndDate()->getPgFormat(), $banstr);
       $alertStr = iconv("cp1251", "UTF-8", '&outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf" title="'.$titlestr.'"><txt><![CDATA['.$banstr.']]></txt></swloader>');
     }
   }

    //Проверка забанин игрок иили нет.
    if($user->getAccountActive() == 'f' && !RD_AdeptUserActionToConsole::$commandLineFlag && !$_SESSION["LOGIN_ADM_ENTER"])
    {
        ds_error('Account deactivated by administration', 1, 1, 1, 0, 'USER_BAN');
    }


    $_SESSION['USERNAME'] = $user->getName(); //Используем для логирования ошибок
    $_SESSION['uid'] = $user->getID(); //Нужно для работы профиля игрока

    $_SESSION['LAST_USER_ACTIONTYPE_ID'] = array();

    //Проверяем тайм дельту игрока и если надо то утснаавливаем (Иногда куда-то дееться хотя и оочень редко)

    //Временно выключаем, пока не придумаем как хранить сессии в одном месте, во избежании рассинхронизации
    //таймдельты между сессией и ds_user
    if(!key_exists('time_delta', $_SESSION) && false)
    {
        $_SESSION['time_delta'] = $user->getTimeDelta() + constant('TIME_DELTA_DELTA');
    }
/********** END **********/


/********** Проверка на синхронизауию по времени HTTP и DB ********/
    $timeDB = GB_TypeFactory::CreateBase('GB_Date', DS_Misc::GetNow());
    $timeHTTP = GB_TypeFactory::CreateBase('GB_Date', time());
    $serversTimeDelta = abs($timeDB->value - $timeHTTP->value);

    if($serversTimeDelta > 4)
    {
        //ds_error('Servers time synchronization error! Time delta = '.$serversTimeDelta.". Server: ".SU_DebugTools::GetServerName(), 1, 1, 1, 0, 'SERVER_SYNC_ERROR');
        // Высылаем письма
    }
/*********** END **********/

/*********** Блокировкаи на одно исполнение для одного UIID **************/
    //Проверка на несоклько открытых окон и попутно логитруем обращения для мира для отлова ботов
    if(!RD_AdeptUserActionToConsole::$commandLineFlag)
    {
        $sql = "SELECT
                *
            FROM
                ds_userinstancewindowkey as wk
            WHERE
                wk.windowkey = ".$_SESSION['windowKey']." AND
                wk.id_userinstance = ".$_SESSION["uiid"];
        $user = $common->getResultArray ( "", $sql, "", "", "" );

        if(count($user) == 0)
        {
            SU_OutputHandler::Singleton()->addString("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<key valid=\"0\" addr=\"index_start.php\"/>",'loadkey');
            echo SU_OutputHandler::Singleton()->fetchAll();
            exit;
        }
    }
/*********** END **************/

/********** Проверяем залочен ли данный инстанс **********/
    if(!SU_Locker::CerateForCurrentDB('USERACTION')->lockWithTest($_SESSION["uiid"]))
    {
        DS_ClientLockHandler('USER_LOCK_DELAY', constant('USER_LOCK_DELAY'), $_SESSION["uiid"], 'USERACTION');
    }

/*********** END **************/

/***********************************************************************
 *  Данный блок используется при переезде, для подготовки пользователя
 *      отменяются все торги
 *      все евенты доставки товаров и оплаты завершаются
 *      армии возвращаются на соту
 */

    if(RD_AdeptUserActionToConsole::$prepareTransfer)
    {
        SU_DebugTools::LogAll('Prepare for transfer ...', 'MassTransfer.log');
        SI_PrepareUserForTransfer::Exec($_SESSION["uiid"]);
    }

/********************************* END *********************************/

/*********** Исполныем запрос пользователя **************/

    // Выставляем клиент кей
    SU_ClientKey::setKey();
    
    //Создаем экземпляр ообработчика запросов
    $ua = new UserAction;

    //Исполняем запрос из сесии елси это не премиум
    if(!empty($_SESSION["s_xmldata"]) && !$_SESSION['profi_game'])
    {
        assert($ua->getRequest($_SESSION["s_xmldata"]));
        $_SESSION["s_xmldata"] = "";
    }

    //Испольнили запрос на пярмую если это премиум
    if(!empty($_POST["xmldata"]))
    {
        assert($ua->getRequest($_POST["xmldata"]));
    }

/*********** END **************/

/*********** ЗАвершающие действия **************/

    //Компилирыем и отправляем сборне сиситемные сообщения
    MC_CompileAndSendMessages::Exec(GB_TypeFactory::CreateBase('GB_Date', time()));

    //ВЫводим все иситемные сообщения
    DE_SystemMessages::SendForCorrentUser();

    //Отслеживаем изменение атибутов соты для выбранных пользователей
    GP_CombAttributeChange::Flush();

    //Коммитем все изменения в объектах сущностей
    GB_SingletonObjectStorage::CommitAllCommitable();

    //ЗАлогировали что накопилось по иситемынм логам
    DE_BattleSystemLog::FlashAll();

    //sleep(2); // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< УБРАТЬ ДЛЯ РЕЛИЗА

    //Завершаем транзакцию
    try{
        ME_EndDBTransactions::Exec(true);
    }catch(Exception $e){
        die("&overload=USER_LOCK_DELAY|".constant('USER_LOCK_DELAY')."&");
    }

    foreach (@$_SESSION['LAST_USER_ACTIONTYPE_ID'] as $k => $value)
    {
        SU_DebugTestTools::LogAction((int)$_SESSION["uiid"], (int)$value[0], $value[1], SU_DebugTools::TimerStop('SCRIPT_START_TIME'));
    }

    // закрываем все соединения
    ME_EndDBTransactions::Disconnect();

    //Если работаем в режиме коммандлнйо строки то выводим наружу сведения о том сколько ивентов реально бьыло обработанно
    if(RD_AdeptUserActionToConsole::$commandLineFlag)
    {
        SU_OutputHandler::Singleton()->addString(ME_EventMonitor::$eventNumber, 'ce');
    }

    GP_SystemStatistic::DeltaParam('USERACTION', 'HANDLED_EVENTS', ME_EventMonitor::$eventNumber);

    // Выставляем клиент кей
    //SU_ClientKey::setKey();

    //Отправялем клиенту время засеченное в начале скрипта
    SU_ClienServerTimeSync::OutTime();

    //Выводим все что подготовленно к выходу
    $xmlstr =  SU_OutputHandler::Singleton()->fetchAll($out);

    if ($usebot && $gameenter)
    $xmlstr .= '&delta='.$qdelta;
  
    if ($gameenter) 
      $xmlstr .= $alertStr;
      
    echo $xmlstr;
     
    //В аакумултор протокола ответ кидаем
    SU_ProtocolAccumulator::ResponsAccumulator($out);

    //Логируем протокол для кого надо
    GP_ProtocolLog::Exec($out);
?>