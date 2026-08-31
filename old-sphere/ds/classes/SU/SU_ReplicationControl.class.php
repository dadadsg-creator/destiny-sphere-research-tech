<?php $CVS_Version = '$Revision: 1.1 $ ; $Date: 2007/06/26 10:58:51 $';

/**
 * Самопальная система репликации. В базе для своих нужд использует таблицы 
 * в базе мастера (ds_replication, ds_replication_tables и ds_replication_slaves).
 * 
 * Описание алгоритма: 
 *  в базе на тригеры повешено логирование изменений в таблицах
 *  эти изменения заносятся в виде ТАБЛИЦА, НАЗВАНИЕ_ПОЛЕЙ, ЗНАЧЕНИЯ_ПОЛЕЙ, 
 *  ДЕЙСТВИЕ (I = INSERT, U = UPDATE и D = DELETE), ИД_СЛЕЙВА
 *  берется пачка таких записей и нужные данные переносятся на подчиненные базы
 *  
 */
class SU_ReplicationControl
{
    /**
     * Ограничение по времени исполнения скрипта (в секундах)
     *
     * @var int
     */
    private $execTime = 900;
    
    /**
     * Сколько времени ждать после каждого шага репликации (в секундах)
     *
     * @var int
     */
    private $waitTime = 5;
    
    /**
     * Сколько времени ждать в случае ошибки (в секундах)
     *
     * @var int
     */
    private $waitErrorTime = 30;
    
    /**
     * Имя файла локинга
     *
     * @var string
     */
    private $lockFile = './files/Replication.lock';
    
    /**
     * Имя файла для остановки репликации
     *
     * @var string
     */
    private $stopFile = './files/Replication.stop';
    
    /**
     * Имя файла для паузы
     *
     * @var string
     */
    private $pauseFile = './files/Replication.pause';
    
    /**
     * Через сколько времени лок файл считается устаревшим
     *
     * @var int
     */
    private $lockTimeOut = 30;
    
    /**
     * По сколько записей брать для репликации за один проход
     *
     * @var int
     */
    private $dataQuant = 30;
    
    /**
     * Время начала работы
     *
     * @var int
     */
    private $startTime;
    
    /**
     * Лог файл
     *
     * @var string
     */
    private $logFileName = 'Replication.log';
    
    /**
     * ИД экземпляра
     *
     * @var unknown_type
     */
    private $id;
    
    /**
     * Объект для работы с базой
     *
     * @var DS_Common
     */
    private $common;
    
    /**
     * ИД мастера
     *
     * @var int
     */
    private $masterDbId = 0;
    
    /**
     * Список слейвов
     *
     * @var array
     */
    private $slaveDbId = array();
    
    /**
     * Сокет для управления и проверки статуса снаружи
     *
     * @var SOCKET
     */
    private $socket = false;
    
    /**
     * Флаг паузы
     *
     * @var bool
     */
    private $pause = false;
    
    /**
     * Флаг остановки
     *
     * @var bool
     */
    private $stop = false;
    
    /**
     * Записи об ошибках
     *
     * @var string
     */
    private $errorLog = '';
    
    /**
     * Счетчик кол-ва скопированных записей
     *
     * @var array
     */
    private $recordsCounter = array('I' => 0, 'U' => 0, 'D' => 0);
    
    /**
     * протокол служебного соединения
     * поддерживается кроме 'tcp' еще и 'udp', но он только для экспериментов
     * @var string
     */
    static public $controlConnectionProtocol = 'tcp'; 
    
    /**
     * Хост, к которому привязывается соединение
     *
     * @var string
     */
    static public $controlConnectionHost = '127.0.0.1';
    
    /**
     * Порт на котором будут слушаться соединения
     *
     * @var int
     */
    static public $controlConnectionPort = 7375;
    
    public function __construct()
    {
        $this->id = rand(1, 100000);
        
        // отключаем автоматический старт транзакций
        DS_Common::SetConnectWithTransaction(false);
        
        $this->common = DS_Common::Singleton();
        
        // отключаем блокировку исполнения sql, в случае возникновения ошибки
        $this->common->stopErrorBlocking();
    }
    
    /**
     * Инизиализация настроек
     *
     */
    private function _initSettings()
    {
        // нет конекта к мастеру, делать нечего
        if(!$this->_connectToMaster())
        {
            $this->_log('<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< WARNING: Unable to init settings', 1);
            return;
        }
        
        // пытаемся собрать все настройки
        $execTime       = (int)DE_GlobalSettings::Get( 'REPLICATION_EXEC_TIME_LIMIT', true );   
        $waitTime       = (int)DE_GlobalSettings::Get( 'REPLICATION_WAIT_TIME',       true );
        $waitErrorTime  = (int)DE_GlobalSettings::Get( 'REPLICATION_ERROR_WAIT_TIME', true );
        $lockTimeOut    = (int)DE_GlobalSettings::Get( 'REPLICATION_LOCK_TIMEOUT',    true );
        $dataQuant      = (int)DE_GlobalSettings::Get( 'REPLICATION_DATA_QUANT',      true );
        
        // если какого-то параметра не нашлось, то будет дефолтное значение использовано
        if($execTime > 0)
        {
            $this->execTime = $execTime;
        }
        
        if($waitTime > 0)
        {
            $this->waitTime = $waitTime;
        }
        
        if($waitErrorTime > 0)
        {
            $this->waitErrorTime = $waitErrorTime;
        }
        
        if($lockTimeOut > 0)
        {
            $this->lockTimeOut = $lockTimeOut;
        }
        
        if($dataQuant > 0)
        {
            $this->dataQuant = $dataQuant;
        }
    }
    
    /**
     * Запуск цикла репликации, здесь все и делается
     *
     */
    public function Exec()
    {
        $this->startTime = time();
        
        $this->_log('=========================================');
        $this->_log('REPLICATION PROCESS '.$this->id.' STARTED');
        $this->_log('=========================================');
        
        // получаем настройки, если получится
        $this->_initSettings();
        
        // инициализируем слжебное соединение, если оно не создастся, то скрипт продолжит работу без него
        $this->_initControlConnection();
        
        while(true)
        {
            // проверяем, не пора ли умереть
            if($this->_checkTimeLimit())
            {
                $this->_log('TIME TO DIE :\'( ');
                $this->_log();
                break;
            }
            
            // проверяем, не пора ли умереть
            if($this->_checkStopFile())
            {
                $this->_log('STOP FILE DETECTED');
                $this->_log();
                break;
            }
            
            // проверяем, не пора ли остановиться
            if($this->_checkPauseFile())
            {
                $this->_log('PAUSE FILE DETECTED');
                $this->_log();
                $this->_wait($this->waitTime);
                continue;
            }
            
            // закрылись, делается в цикле, чтобы не прерывать работу в случае ошибки
            if(!$this->_lock())
            {
                $this->_log('<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< WARNING: Unable to lock', 1);
                $this->_wait($this->waitErrorTime);
                continue;
            }
            
            // конектимся к мастеру
            if(!$this->_connectToMaster())
            {
                $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR: Unable to connect to master DB", 1);
                $this->_wait($this->waitErrorTime);
                continue;
            }
            
            // получаем список рабов
            if(!$this->_prepareSlaveList())
            {
                $this->_wait($this->waitErrorTime);
                continue;
            }
            
            // берем партию данных
            $data = $this->_getReplicationData();
            if($data === false)
            {
                $this->_wait($this->waitErrorTime);
                continue;
            }
            
            // реплицируем их
            $this->_log('Replicating '.count($data).' records ...');
            foreach($data as $k => $rec)
            {
                $this->_replicate($rec);
            }
            $this->_log('DONE');
            $this->_log();
            
            // немного отдыхаем и снова на работу
            $this->_wait($this->waitTime);
            
            // открылись
            if(!$this->_unlock())
            {
                $this->_log('<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< WARNING: Unable to unlock', 1);
                $this->_wait($this->waitErrorTime);
                continue;
            }
        }
        
        // закрываем соединение
        $this->_closeControlConnection();
    }
    
    /**
     * Функция реплицирования одной записи
     *
     * @param SU_ReplicationEntityData $rec
     * @return bool
     */
    private function _replicate(SU_ReplicationEntityData $rec)
    {
        $rec->validate();
        
        // конектимся к рабу
        if(!$this->_connectToSlave($rec->slaveID))
        {
            return false;
        }
        
        // реплицируем
        SU_ReplicationSearch::ReplicateData($this->common, $rec);
        if(!empty($this->common->errormsg))
        {
            $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR: Can't replicate data in slave ".$slave." : ".$this->common->errormsg, 1);
            $this->common->errormsg = '';
            return false;
        }
        
        // конектимся к мастеру
        $this->_connectToMaster();
        
        // удаляем запись на мастере
        SU_ReplicationSearch::DeleteReplicationData($this->common, $rec->replicationID);
        if(!empty($this->common->errormsg))
        {
            $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR: Can't delete replication data : ".$this->common->errormsg, 1);
            $this->common->errormsg = '';
            return false;
        }
        
        // увеличиваем счетчик, обработанных записей
        $this->recordsCounter[$rec->action]++;
        
        //$this->_log('ok..');      
        
        return true;
    }
    
    /**
     * Подключение к мастеру
     *
     * @return bool
     */
    private function _connectToMaster()
    {
        if(GameDown($this->masterDbId))
        {
            $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< INFO: Master GameDown", 1);
            return false;
        }
        
        return $this->common->setDbId($this->masterDbId);
    }
    
    /**
     * Подключение к рабу
     *
     * @param int $id
     * @return bool
     */
    private function _connectToSlave($id)
    {
        assert(is_int($id) && $id > 0);
        assert(key_exists($id, $this->slaveDbId));
        
        if(GameDown($this->slaveDbId[$id]))
        {
            $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< INFO: Slave DB[".$this->slaveDbId[$id]."] GameDown", 1);
            return false;
        }
        
        if(!$this->common->setDbId($this->slaveDbId[$id]))
        {
            $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR: Unable to connect to slave [$id]", 1);
            return false;
        }
        
        return true;
    }
    
    /**
     * Подготовка спаска рабов
     *
     * @return bool
     */
    private function _prepareSlaveList()
    {
        if(count($this->slaveDbId) > 0)
        {
            return true;
        }
        
        $slaveList = SU_ReplicationSearch::GetSlaveList($this->common);
        
        if(!empty($this->common->errormsg))
        {
            $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR: Can't get slave list: ".$this->common->errormsg, 1);
            return false;
        }
        
        foreach($slaveList as $k => $slave) 
        {
            foreach ($GLOBALS['dbWorldAccess'] as $dbId => $db)
            {
                if($db['server'] == $slave['server'])
                {
                    $this->slaveDbId[$slave['id_slave']] = $dbId;
                    break;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Получение порции данных для репликации
     *
     * @return array
     */
    private function _getReplicationData()
    {
        $this->_connectToMaster();
        
        $data = SU_ReplicationSearch::GetReplicationData($this->common, (int)$this->dataQuant);
        
        if(!empty($this->common->errormsg))
        {
            $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR: Can't get replication data: ".$this->common->errormsg, 1);
            return false;
        }
        
        $repData = array();
        foreach ($data as $k => $rec)
        {
            $repData[$k] = new SU_ReplicationEntityData($rec);
            
            if($repData[$k]->action == 'D')
            {
                continue;
            }
            
            // получаем измененные данные
            $replicationTableData = SU_ReplicationSearch::GetReplicationTableData($this->common, $repData[$k]);
            if(!empty($this->common->errormsg))
            {
                $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR: Can't get replication table data: ".$this->common->errormsg, 1);
                return false;
            }
            
            if(count($replicationTableData) != 1)
            {
                $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR: Can't get replication table data ", 1);
                return false;
            }
            
            $repData[$k]->setReplicationData($replicationTableData[0]);
        }
        
        return $repData;
    }
    
    static public function GetFullControlConnectionAddress()
    {
        return self::$controlConnectionProtocol."://".self::$controlConnectionHost.":".self::$controlConnectionPort;
    }
    
    /**
     * Инициализируем сервисное соединение
     *
     */
    private function _initControlConnection()
    {
        switch (self::$controlConnectionProtocol) 
        {
            case 'udp':
                $flags = STREAM_SERVER_BIND;
                break;
         
            case 'tcp':
                $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;
                break;
                
            default:
                $flags = 0;
                break;
        } 
        
        // виден только с локали, но можно чтоб и с инета видно было, если никакой файрвол мешать не будет
        $this->socket = stream_socket_server(self::GetFullControlConnectionAddress(), $errno, $errstr, $flags);
        if(!$this->socket)
        {
            $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< WARNING: Can't create server socket: ".$errstr, 1);
            return false;
        }
        
        stream_set_blocking($this->socket, 0);
        
        return true;
    }
    
    private function _closeControlConnection()
    {
        if(!$this->socket)
        {
            return;
        }
        
        fclose($this->socket);
    }
    
    /**
     * Функция нечегонеделанья
     *
     * @param (int) сколько секунд ничего не делать $sec
     */
    private function _wait($sec)
    {
        assert(is_int($sec) && $sec > 0);
        
        switch (self::$controlConnectionProtocol)
        {
            case 'tcp':
                $this->_wait_tcp($sec);
                break;
            
            case 'udp':
                $this->_wait_udp($sec);
                break;
                
            default:
                sleep($sec);
                break;
        }
    }
    
    /**
     * Функция нечегонеделанья в варианте tcp
     *
     * @param (int) сколько секунд ничего не делать $sec
     */
    private function _wait_tcp($sec)
    {
        if(!$this->socket)
        {
            sleep($sec);
            return;
        }
        
        $endWaitTime = time() + $sec;
        $msg = '';
        
        while(time() < $endWaitTime && $msg != 'W')
        {   
            $tmpSocket = @stream_socket_accept($this->socket, $sec, $peer);
            if(!$tmpSocket)
            {
                sleep(1);
                continue;
            }
            
            $msg = fread($tmpSocket, 1);
            if(empty($msg))
            {
                sleep(1);
                continue;
            }
            
            $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< INFO: Incoming connection from ".$peer);
            $answer = $this->_processExternalMessage($msg);
            $this->_log();
            
            if($msg != 'T')
            {
                $answer = date("Y-M-d H:i:s | \n").$answer;
            } 
            fwrite($tmpSocket, $answer."\r\n");
            fclose($tmpSocket);
            //stream_socket_sendto($this->socket, date("Y-M-d H:i:s |\n").$answer."|\r\n", 0, $peer); 
        }
    }
    
    /**
     * Функция нечегонеделанья в варианте udp
     *
     * @param (int) сколько секунд ничего не делать $sec
     */
    private function _wait_udp($sec)
    {
        if(!$this->socket)
        {
            sleep($sec);
            return;
        }
        
        $endWaitTime = time() + $sec;
        $msg = '';
        
        while(time() < $endWaitTime && $msg != 'W')
        {   
            $msg = stream_socket_recvfrom($this->socket, 1, 0, $peer);
            if(empty($msg))
            {
                sleep(1);
                continue;
            }
            
            $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< INFO: Incoming connection from ".$peer);
            $answer = $this->_processExternalMessage($msg);
            $this->_log();
            
            stream_socket_sendto($this->socket, date("Y-m-d H:i:s |\n").$answer."|\r\n", 0, $peer); 
        }
    }
    
    private function _processExternalMessage($msg)
    {
        $answer = '';
        
        switch ($msg) 
        {
            // Завершение работы
            case 'Q':
                $answer = $this->_stop();
                break;
                
            // Пауза
            case 'P':
                $answer = $this->_pause();
                break;
                
            // Информация о текущем состоянии системы
            case 'I':
                $answer = $this->_information();
                break;
                
            // Продолжить работу, выйти из паузы
            case 'R':
                $answer = $this->_resume();
                break;
                
            // Тестовый запрос
            case 'T':
                $answer = $this->_testSystem();
                break;
                
            // Тестовый запрос
            case 'D':
                $answer = $this->_informationRow();
                break;
        
            default:
                $answer = 'Unknown command!';
                break;
        }
        
        return $answer;
    }
    
    private function _information()
    {
        $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< INFO: Info send");
        
        $status  = "REPLICATION DEAMON ID: ".$this->id."\n";
        $status .= "STARTED: ".date('Y-d-m H:i:s', $this->startTime)."\n";
        $status .= "DATA REPLICATED: \n";
        $status .= "        insert: ".$this->recordsCounter['I']."\n";
        $status .= "        update: ".$this->recordsCounter['U']."\n";
        $status .= "        delete: ".$this->recordsCounter['D']."\n";
        $status .= "ERRORS IN CURRENT SESSION: ".$this->errorLog."\n";
        
        return $status;
    }
    
    private function _testSystem()
    {
        $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< INFO: Test request");
        
        if($this->_checkPauseFile())
        {
            return 'PAUSE';
        }
        
        if($this->_checkStopFile())
        {
            return 'STOP';
        }
        
        return 'OK';
    }
    
    private function _informationRow()
    {
        $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< INFO: Info row send");
        
        $status  = $this->id."|";
        $status .= $this->startTime."|";
        $status .= $this->recordsCounter['I']."|";
        $status .= $this->recordsCounter['U']."|";
        $status .= $this->recordsCounter['D']."|";
        $status .= $this->errorLog;
        
        return $status;
    }
    
    private function _stop()
    {
        $this->stop = true;
        $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< INFO: Stop command recieved");
        
        return 'STOPED';
    }
    
    private function _pause()
    {
        $this->pause = true;
        $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< INFO: Pause command recieved");
        
        return 'PAUSED';
    }
    
    private function _resume()
    {
        $this->pause = false;
        $this->_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< INFO: Resume command recieved");
        
        return 'RESUMED';
    }
    
    /**
     * Проверка, заперлись ли мы на засов
     *
     * @return unknown
     */
    private function _isLocked()
    {
        if(!file_exists($this->lockFile))
        {
            return false;
        }
        
        if(filemtime($this->lockFile) < time() - $this->lockTimeOut)
        {
            return false;
        }
        
        return true;
    }
    
    /**
     * проверяем этот ли скрипт положил лок
     *
     * @return bool
     */
    private function _checkLockId()
    {
        $lockData = file($this->lockFile);
        if(count($lockData) != 1)
        {
            return false;
        }
        
        if(trim($lockData[0]) != $this->id)
        {
            return false;
        }
        
        return true;
    }
    
    /**
     * Проверка ограничения времени работы скрипта
     *
     * @return bool
     */
    private function _checkTimeLimit()
    {
        if((time() - $this->startTime) > $this->execTime)
        {
            return true;
        }
        
        return false;
    }
    
    /**
     * Проверка наличия СТОП-файла
     *
     * @return bool
     */
    private function _checkStopFile()
    {
        $res = file_exists($this->stopFile) || $this->stop;
        return $res;
    }
    
    /**
     * Проверка наличия файла ПАУЗА
     *
     * @return bool
     */
    private function _checkPauseFile()
    {
        $res = file_exists($this->pauseFile) || $this->pause;
        return $res;
    }
    
    /**
     * Enter description here...
     *
     * @return bool
     */
    private function _lock()
    {
        /*
        if($this->_isLocked() && !$this->_checkLockId())
        {
            return false;
        }
        
        $fp = fopen($this->lockFile, "w+");
        if($fp === false)
        {
            return false;
        }
        
        fwrite($fp, $this->id);
        fclose($fp);
        touch($this->lockFile);
        */
        return true;
    }
    
    private function _unlock()
    {
        /*
        if(!$this->_isLocked() || !$this->_checkLockId())
        {
            return false;
        }
        
        unlink($this->lockFile);
        */
        return true;
    }
    
    private function _log($str = '', $err = false)
    {
        if(!empty($str))
        {
            $str = SU_DebugTools::GetInternalDate()." - ".$this->id." - ".$str."\n";
        }
        
        // заносим во внутренний лог ошибок
        if($err)
        {
            $this->errorLog .= $str;
        }
        
        SU_DebugTools::WriteToLog($str, $this->logFileName);
    }
}

?>