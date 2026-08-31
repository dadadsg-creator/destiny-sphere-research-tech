<?php $CVS_Version = '$Revision$ ; $Date$';
require_once constant('INCLUDE_PATH')."/GameDown.inc.php";

class RD_RefreshDemon
{
    /**
     * Объект для работы с БД. Уже подключенн к нужной БД.
     *
     * @var DS_Common
     */
    private $common;

    /**
     * Время жизни скрипта максимальное в секундах
     *
     * @var integer
     */
    private $lifeTimeLimit = 3300;

    /**
     * Если нагрузка н ервера слишком велика то время ожидания до следующего теста  нагрузки в секундах
     *
     * @var integer
     */
// 60   
    private $loadWaitQuant = 60;
    
    /**
     * Время ожидания в секундах в случае гейм дауна
     *
     * @var unknown_type
     */
// 600
    private $downWaitQuant = 600;

    /**
     * Максимальная нагрузка для HTTP сервера выше которй не рефрешим никого
     *
     * @var integer
     */
    private $maxHTTPLoad = 16;

    /**
     * Максимальное колличество трназакций в БД выше которго не рефрешим некого.
     *
     * @var integer
     */
    private $maxTransactionsCount = 40;

    /**
     * Путь до скрипта который осуществляет рефреш
     *
     * @var string
     */
    private $urlForRefreshScript = '/home/www/ds';

    /**
     * Имя скрипта для рефреша игрока
     *
     * @var string
     */
    private $scriptNameForRefresh = 'useraction.php';

    /**
     * ID БД с которой работаем
     *
     * @var integer
     */
    private $DBID;

    /**
     * TimeStamp времени когда мы начали работать. что бы понять временное ограниение работы скрипта
     *
     * @var integer
     */
    private $startTime;

    /**
     * Текущее значение загрузки HTTP
     *
     * @var integer
     */
    private $currentLoadHTTP = 0;

    /**
     * Текущее значение колличества транзакций
     *
     * @var integer
     */
    private $currentLoadDB = 0;

    /**
     * Текущий UIID  ск оторым работаем
     *
     * @var integer
     */
    private $UIID;

    private $refreshedUIIDs = array();

    /**
     * Колличество ивентов у текущего UIID на обработку
     *
     * @var integer
     */
    private $eventsForRefreshCount;

    /**
     * Имя текущего пользователя которого рейрешим
     *
     * @var string
     */
    private $userName;

    /**
     * Имя мир аиз которого текущий пользователь
     *
     * @var unknown_type
     */
    private $worldName;
    
    
    /**
     * Счетчик успешно отрефрешенных юзверей
     *
     * @var integer
     */
    private $statUsersOkCount = 0 ;

    /**
     * Счетчик юзверей проблемами
     *
     * @var integer
     */
    private $statUsersBadCount = 0;

    /**
     * примерно еколличестов обработанных ивентов
     *
     * @var integer
     */
    private $statRefreshedEventsCount = 0;

    /**
     * Режи вывода. По умолчанию на экран. Если выводим
     * на эран то в лдог не пишем
     *
     * @var string
     */
    static private $_outPutMode = 'screen';

    /**
     * Врямя исполнения useractiona последнего
     *
     * @var integer
     */
    private $timeDelta;

    /**
     * Разделитель между мессагами
     *
     * @var string
     */
    private $seporator = "-----------------";

    /**
     * Имя лог файла куда логируем
     *
     * @var integer
     */
    private $logFileName = '';

    /**
     * Флаг заблокированности аботы демона.
     * Работает только при работе чере консоль
     *
     * @var boolean
     */
    private $isBlocked = false;

    /**
     * Флаг остановки работы демона
     * Работает только при работе через консоль
     * 
     * @var unknown_type
     */
    private $isStopped = false;
    
    /**
     * Имя файла сигнализирующего о том что демон работет сейчас.
     * Используеться только при запуске из консоли
     *
     * @var string
     */
    private $aliveFileName = '';

    /**
     * Объект для проверки локинга инстанса
     *
     * @var LockUIID
     */
    private $lockUIID;  
    
    /**
    * ID демона
    *
    * @var int  
    */
    private $demonID;
    
    private $logAccumulator = '';
    
    public  function __construct($DBID, $demonID = 0)
    {
        assert(is_int($DBID) && $DBID >= 0);
        $this->DBID = $DBID;
        
        assert(is_int($demonID) && $demonID >=0);
        $this->demonID = $demonID;

        //Создали объект уже с коннектом к нужной БД
        $this->common = DS_Common::CreateForCurentDB($DBID);

        //Проверяем есть ли наш опанент
        assert(file_exists($this->urlForRefreshScript."/".$this->scriptNameForRefresh));

        //Отметили время когда начали
        $this->startTime = time();

        $sapi = php_sapi_name();
        if(strstr($sapi, "apache") !== false)
        {
            self::$_outPutMode = 'screen';
//$this->logFileName = 'RefreshDemon_'.$GLOBALS['dbWorldAccess'][$this->DBID]['server'].'.log';         
//self::$_outPutMode = 'log';
        }else{
            $this->logFileName = 'RefreshDemon_'.$GLOBALS['dbWorldAccess'][$this->DBID]['server'].'.log';           
            self::$_outPutMode = 'log';
        }
        
        $this->aliveFileName = "./files/RD_".$this->DBID."_ALIVE_".$this->demonID;
        
        //Создаем объект локера
        $this->lockUIID = SU_Locker::Create('USERACTION', $this->DBID);             
        
        $this->_out("SCANER ".$this->demonID." CREATED FOR DBID = ".$DBID." ON HTTP_NAME = [".$_SERVER['SERVER_NAME']."]");
    }

    public function startScan()
    {
        $this->_out("Start scanning");
        $this->_out($this->seporator);
        $this->_flushToLog();
        if(self::$_outPutMode == 'log')
        {
            $fp = fopen($this->aliveFileName, "w+");
            fclose($fp);
        }

        //Цикл обеспечивающй жисть демона
        while(true)
        {
            //проверяем не пришло ли время померать
            if($this->_isTimeToDeath())
            {
                if($this->isStopped)
                {
                    $this->_out("Refresh demon is stopped");
                } else {
                    $this->_out("Life time period ended. Time to stop;");
                }
                $this->_flushToLog();
                if(self::$_outPutMode == 'screen')
                {
                    $this->_out("Refresh will follow...");
                    sleep(3);
                    $this->_out("</xmp><!--функция рефреша-->
                        <script language=\"JavaScript\">
                        function reloadDemon(){
                            window.location.reload();
                        }
                        function test(){
                            window.setTimeout('reloadDemon()', 10);
                        }
                        test();
                        </script><xmp>");
                }
                break;
            }
            
            // Проверяем не закрыта ли игра
            if(GameDown($this->DBID) && self::$_outPutMode == 'log')
            {
                $this->_out("Game down. Wait $this->downWaitQuant sec.");
                $this->_out($this->seporator);                              
                $this->_flushToLog();
                $this->common->closeConnection($this->DBID);            
                
                sleep($this->downWaitQuant);                
                continue;
            }else{
                //Елси не геймдайн то проверяем подключенны ли мы к БД.             
                if(!is_resource($this->common->conn))
                {
                    $this->common->setDBID($this->DBID);
                }
            }

            //Смотрим можем ли мы рефрешить кого-то по нагрузке
            if($this->_canRefreshUser())
            {
                $this->_selectUIIDToRefresh();

                if(is_null($this->UIID))
                {
                    $this->_out("No user with ready events. Wait $this->loadWaitQuant sec.");
                    $this->_out($this->seporator);
                    $this->_flushToLog();
                    sleep($this->loadWaitQuant);

                }else{
                    $this->_out("Select for refresh '".$this->userName."'(".$this->UIID.") from ".$this->worldName." EVENTS_COUNT = $this->eventsForRefreshCount");
                    //$this->_flushToLog();
                    $this->_refreshUIID();
                }
            }else{
                if($this->isBlocked)
                {
                    $this->_out("Refresh demon blocked. Wait $this->loadWaitQuant sec.");
                    $this->_out($this->seporator);
                } else {
                    $this->_out("Servers to busy ($this->currentLoadHTTP : $this->currentLoadDB). Wait $this->loadWaitQuant sec.");
                    $this->_out($this->seporator);                  
                }
                $this->_flushToLog();
                $this->UIID = null;
                $this->eventsForRefreshCount = null;
                $this->worldName = null;
                $this->userName;

                sleep($this->loadWaitQuant);
            }
            if(file_exists($this->aliveFileName) && self::$_outPutMode == 'log')
            {
                touch($this->aliveFileName);
            }
        }
        
        if(file_exists($this->aliveFileName) && self::$_outPutMode == 'log')
        {
            unlink($this->aliveFileName);
        }
        $this->_out("End scanning");
        $this->_flushToLog();
    }

    /**
     * Рефреш одного конкретного пользователя
     *
     * @param integer $UIID
     * 
     * @param integer $DBID
     */
    public function refreshUser($UIID)
    {
        assert(is_int($UIID));
        
        $this->UIID = $UIID;
        
        $this->_refreshUIID();
    }
    
    private function _refreshUIID()
    {
        assert(is_int($this->UIID));
        assert(defined('PHP_PATH'));

        //Првоерям пользователя на UNCOMMITABLE_MODE
        if(in_array($this->userName, $GLOBALS['USERS_FOR_ROLLBACK']))
        {
            $this->_out("User is on UNCOMMITABLE MODE! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< WARNING!!!!!!!!");
            $this->_out($this->seporator);
            $this->_flushToLog();
            $this->refreshedUIIDs[] = $this->UIID;
            return;
        }

        $out = '';
        $command = "cd ".$this->urlForRefreshScript."; ".constant('PHP_PATH')." -f ".$this->scriptNameForRefresh." ".$this->DBID." ".$this->UIID;
        SU_DebugTools::TimerStart('YA');
        $str = exec($command, $out);

        $this->timeDelta = SU_DebugTools::TimerStop('YA');

        $outString = implode("\n", $out);

        if(strpos($outString, 'refresh=') && strpos($outString, 'combres='))
        {
            $this->_out("Result: OK! Time: ".$this->timeDelta);
            $this->statUsersOkCount++;
            
            $ceValueStart = strpos($outString, 'ce=');
            if($ceValueStart !== false)
            {
                $ceValueEnd = strpos($outString, '&', $ceValueStart);
                $ce = substr($outString, $ceValueStart + 3, $ceValueStart - $ceValueEnd - 3);
                $this->_out("CE = ".$ce);
            } else {
                $ce = $this->eventsForRefreshCount;
            }
            
            $this->statRefreshedEventsCount +=  $ce;

            $averEventTime = round($this->timeDelta / $this->eventsForRefreshCount, 5);
            $this->_out("OK_USERS: ".$this->statUsersOkCount.";  BAD_USERS: ".$this->statUsersBadCount."; REFRESHED_EVENTS: ".$this->statRefreshedEventsCount."; AVER_EVENT_TIME: ".$averEventTime."; LOAD: ".$this->currentLoadHTTP." : ".$this->currentLoadDB);
            $this->_out($this->seporator);
        }else{
            if(strpos($outString, 'USER_LOCK_DELAY'))
            {
                $this->_out("Result: User locked. <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< LOCK!!!!!!!!!");
                $this->_writeLockError($outString);
            } else {
                $this->_out("Result: Have problems Time: ".$this->timeDelta." <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR!!!!!!!!!");
                $this->statUsersBadCount++;
                $this->_writeError($outString);
            }   
            $this->_out($outString);
            $this->_out($this->seporator);
            
            $this->refreshedUIIDs[] = $this->UIID;
        }
        $this->_flushToLog();
    }

    /**
     * Выбераем UIID с максимальным колличеством ивентов для рефреша
     *
     */
    private function _selectUIIDToRefresh()
    {
        //Получили список  топов по колличеству готовых ивентов
        $rs = RD_RefreshDemonSearch::GetUIIDWithMaxEndEventsCount($this->common, $this->refreshedUIIDs);
        
        //Очистили значение UIID
        $this->UIID = null;
        
        //Если никого нет то выходим
        if(!$rs)
        {           
            return;
        }
        
        //Если кто-то есть то идем по списку и находим первого незалоченного наданный момент
        $havePerson = false;
        foreach($rs as $k=>$dataForRefresh)
        {
            //Если нашли незалоченного то выходим из цикла
            if(!$this->lockUIID->isLocked($dataForRefresh['id_userinstance']))
            {
                $havePerson = true;
                break;
            }
        }           
        
        //если нкого ненашли то вываливаемся отсюда
        if(!$havePerson)
        {
            return;
        }
        
        //Раз нашли кого-то то заносим его под рефреш.  
        assert(key_exists('id_userinstance', $dataForRefresh));
        $this->UIID = (int)$dataForRefresh['id_userinstance'];
        
        assert(key_exists('colvo', $dataForRefresh));
        $this->eventsForRefreshCount = (int)$dataForRefresh['colvo'];
        
        $userData = RD_RefreshDemonSearch::GetUIIDData($this->common, $this->UIID);
        
        assert(key_exists('username', $userData));
        $this->userName = $userData['username'];
        assert(!empty($this->userName));
        
        assert(key_exists('worldname', $userData));
        $this->worldName = $userData['worldname'];
        assert(!empty($this->worldName));
    }

    /**
     * Пришло ли время выключаться для следующего щзапуска себя.
     */
    private function _isTimeToDeath()
    {
        if(time() >= ($this->startTime + $this->lifeTimeLimit))
        {
            return true;
        }else{
            if(file_exists("./files/RD_".$this->DBID."_STOP") || file_exists("./files/RD_".$this->DBID."_STOP_".$this->demonID) && (self::$_outPutMode == 'log'))
            {
                $this->isStopped = true;
                return true;
            } else {
                $this->isStopped = false;
            }
            
            return false;
        }
    }

    /**
     * Проверяем позваляет ли сейча снагрузка на сервер нам рефрешить игроков
     *
     * @return boolean
     */
    private function _canRefreshUser()
    {
        $this->currentLoadHTTP = SU_HttpServerLoad::Get(0);
        $this->currentLoadDB = RD_RefreshDemonSearch::GetTransactionsCount($this->common, $this->DBID);

        if($this->currentLoadHTTP > $this->maxHTTPLoad || $this->currentLoadDB > $this->maxTransactionsCount)
        {
            return false;
        }else{
            if(file_exists("./files/RD_".$this->DBID."_BLOCK") || file_exists("./files/RD_".$this->DBID."_BLOCK_".$this->demonID) && (self::$_outPutMode == 'log'))
            {
                $this->isBlocked = true;
                return false;
            } else {
                $this->isBlocked = false;
            }
            return true;
        }
    }

    private function _out($str)
    {
        $str = SU_DebugTools::GetInternalDate()." - ".$this->demonID." - ".$str."\n";

        if(self::$_outPutMode == 'screen')
        {
            echo $str;
            flush();
        }else{
            $this->logAccumulator .= $str;
            //SU_DebugTools::WriteToLog($str, $this->logFileName);
        }
    }

    private function _writeError($msg)
    {
        $str  = "---- ".SU_DebugTools::GetInternalDate()." | World: ".$this->worldName."(".$this->DBID.") User: ".$this->userName."(".$this->UIID.") ----\n";
        $str .=  $msg."\n\n";

        SU_DebugTools::WriteToLog($str, 'RefreshDemonErrors.log');
    }
    
    private function _writeLockError($msg)
    {
        $str  = "---- ".SU_DebugTools::GetInternalDate()." | World: ".$this->worldName."(".$this->DBID.") User: ".$this->userName."(".$this->UIID.") ----\n";
        $str .=  $msg."\n\n";

        SU_DebugTools::WriteToLog($str, 'RefreshDemonLockErrors.log');
    }
    
    private function _flushToLog()
    {
        if(self::$_outPutMode == 'log')
        {
            SU_DebugTools::WriteToLog($this->logAccumulator, $this->logFileName);
            $this->logAccumulator = '';
        }
    }
}
?>