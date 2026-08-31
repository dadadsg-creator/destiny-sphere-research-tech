<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_TestSystemController extends SU_TestSystemBase
{
    /**
     * Enter description here...
     *
     * @var SU_Locker
     */
    private $locker;
    
    private $extension;

    private $logFileName = 'TestSystem.log';

    private $logErrorFileName = 'TestSystemError.log';

    private $sapi = '';

    private $isLocked = false;
    
    public function __construct()
    {
        try {
            $this->locker = SU_Locker::Create('TEST_SYSTEM_FUNCTION', 111);
        } catch (Exception $e) {
            $this->locker = '';
            $this->extension = $e;
        }
        $this->sapi = php_sapi_name();
    }

    private $tests  = array
    (
        'Ping' => 'SU_TestSystemPing', //Пингуем сервера
        'Porlal' => 'SU_TestSystemPortal', //Проверяем работоспособность портала
        'DBConnectionAndReplication' => 'SU_TestSystemDBConnectionAndReplication', //Проверяем возможность коннекта к БД
        'MySQLConnection' => 'SU_TestSystemMySQLConnection', //Проверяем возможность коннекта к мускульной БД
        'NFSFreeSpace' => 'SU_TestSystemNFSFreeSpace', //Проверка свободного места на свере
        'ParalelSystem' => 'SU_TestSystemParalelSystem', //Проверка, отзывается ли параллелная сиситема: для руских - это немцы, для немцев -это русские
        'GameLogin' => 'SU_TestSystemGameLogin', //Проверка, можно ли залогиниться в игру
        'MemCache' => 'SU_TestSystemMemCache', //Проверка работы Mem Cashe
        'ServerTimes' => 'SU_TestSystemServerTimes', //Проверка совпадения времени на всех серверах
    );

    public function exec()
    {
        //Если скрипт выполняется из командной строки
        if(strpos($this->sapi, "apache"))
        {
            $this->test();
            return;
        }

        if(key_exists('action',$_REQUEST))
        {
            $action = $_REQUEST['action'];
        }else{
            $action = 'menu';
        }

        switch ($action)
        {
            case 'menu': //Менюшка для работы из папки utils
            $this->menu();
            break;
            case 'local_test': //Тест свой системы
            echo "<xmp>";
            $this->test();
            echo "</xmp>";
            break;

            assert(false);
        }
    }

    private function menu()
    {
        echo "</xmp>
            <H1>Test of system functionality. Server [".$_SERVER['SERVER_NAME']."].</H1>
            Exec system test? <a href=".$_SERVER['PHP_SELF']."?action=local_test>YES</a><xmp>";
    }

    public function responsToExternalStatusRequest()
    {
        $testRes = $this->_cantDoSystemTest(2400);

        if(empty($testRes) && false)
        {
            echo 'TO_OLD_LAST_WORK';
        }else{
            echo 'OK';
        }
    }

    private function _cantDoSystemTest($leftTimeDelta = 1680)
    {
        assert(is_int($leftTimeDelta) && $leftTimeDelta > 0);
        
        if($this->locker->isLocked('TEST_SYS_FUN'))
        {
            return 'Testting is locked';
        }
        
        //Смотрим не было ли отчетов за послднйи час
        assert(key_exists('LOGING_DEBUG_LOGS_PATH', $GLOBALS) && key_exists('MAIN', $GLOBALS['LOGING_DEBUG_LOGS_PATH']));
        $fileName = $GLOBALS['LOGING_DEBUG_LOGS_PATH']['MAIN'].$this->logFileName;
        
        if(file_exists($fileName))
        {
            clearstatcache();
            $fileStat = @stat($fileName);

            $fileLastUpdate = $fileStat['mtime'];

            if($fileLastUpdate > (time() - $leftTimeDelta))
            {
                return 'Have not old reports';
            }
        }
        return '';
    }

    private function test()
    {
        //проверяем не залочен ли уже сей процес
        if(strpos($this->sapi, "apache"))
        {
            $testDisableResult = $this->_cantDoSystemTest();

            if($testDisableResult != '')
            {
                $this->_outString('Cant do test. Reason: '.$testDisableResult);
                return;
            }else{
                $this->locker->lock('TEST_SYS_FUN');                
                $this->isLocked = true;
            }
        }
        
        $testDisableResult = $this->_cantDoSystemTest();

        $msg = "-------===== TEST GAME VERSION ".constant('GAME_HOST')." =====-----";
        $this->_outString($msg);

        if (!empty($this->extension)) {
            $result = "Unable To Lock!! <<<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR!";
            $this->_outString($result, 2);
            $result = "Reason: ".$this->extension;
            $this->_outString($result, 2);
            
            $this->_writeError('MySQL', "Unable To Lock");
        }
        
        //Раз сюда дошли ведать пора тест делать
        foreach($this->tests as $testName => $testClassName)
        {
            $testObject = new $testClassName;

            $this->_outString("START TEST: ".$testName);        

            $testObject->exec();        
        }
    
        $msg = "-----------------------===== END OF TEST =====---------------------";
        $this->_outString($msg);

        //Логируем отчет в журнал
        SU_DebugTools::LogAll(self::$fullReportText, $this->logFileName);

        //Логируем ошибки
        $this->_logNewError();
        
        //Проверка game modifier
        SU_TestTableData::Exec();
        
        SU_TestSorceVersions::Exec();
    }

    private function _logNewError()
    {
        $newErrors = false;

        assert(key_exists('LOGING_DEBUG_LOGS_PATH', $GLOBALS) && key_exists('MAIN', $GLOBALS['LOGING_DEBUG_LOGS_PATH']));
        $fileName = $GLOBALS['LOGING_DEBUG_LOGS_PATH']['MAIN'].$this->logErrorFileName;

        //Если у нас есть ошибки
        if(count(self::$haveErrors) > 0)
        {
            //Если файл с ошибками существует то перезаписываем его
            if (file_exists($fileName))
            {
                //Считываем содержимое файла с ошибками в массив
                $oldErrors = file("$fileName");

                //Ищем совпадения текущих ошибок и ошибок, уже записанных в журнал
                foreach(self::$haveErrors as $type=>$errorArrey)
                {
                    foreach ($errorArrey as $key => $error)
                    {
                        //Если текушей ошибки нет в старых ошибках
                        if (!in_array("$type/$error;\n", $oldErrors))
                        {
                            //Тогда новые ошибки есть
                            $newErrors = true;
                        }
                    }
                }
                //Удаляем файл с ошбками
                unlink ($fileName);
            }
            //Если файла с ошибками не существует
            else {
                //Тогда навые ошибки есть
                $newErrors = true;
            }

            //Логируем их
            $errors = $this->_writeErrorLog();

            //Если есть новые ошибки
            if ($newErrors == true)
            {
                //Шлем письмо на мыло
                $this->_sendLatterWithErrors($errors);
            }

        }
        //Если ошибок нет, а лог со старыми ошибками есть, то удаляем его
        else
        {
            if (file_exists($fileName))
            {
                unlink ($fileName);
            }
        }
    }

    private function _sendLatterWithErrors($errors)
    {
        //Данные о мире в зависимости от версии игры
        switch (constant('GAME_HOST'))
        {
            case 'http://ds.rambler.ru':
                $version = 'CL';            //Классик 
                break;
                
            case 'http://ds-game.ru':
                $version = 'UN';            //Unlimited 
                break;
                
            case 'http://test.destinysphere.ru':
                $version = 'TS';            //Test 
                break;
                
            case 'http://unlim':
                $version = 'LC';            //Loclal 
                break;
                
            case 'http://ds.destinysphere.net':
                $version = 'GE';            //German 
                break;
                
            default:
                $version = '?';             //Undefined 
                break;
        }
        
        //Шлем письма
        $subject = 'System Errors ['.$version.'] ';
        $comma = "; ";

        foreach($errors as $type=>$countError)
        {
            $subject .= "$type/$countError; ";
        }

        foreach($GLOBALS['TEST_SYSTEM_REPORT_EMAILS'] as $k=>$email)
        {
            $eol = "\r\n";
            $headers = 'From: TestSysData'.$eol;
            $headers .= "Message-ID: <".time()." TheSystem@".$_SERVER['SERVER_NAME'].">".$eol;
            $headers .= "X-Mailer: PHP v".phpversion().$eol;
            $headers .= "Content-Type: text/plain; charset=windows-1251".$eol;
            
            mail($email, $subject, self::$fullReportText, $headers);        
        }
        
        /*//Шлем смс
        $sms = '['.$version.'] ';
        $spase = '';
        
        foreach(self::$ErrorsByServer as $server => $errors)
        {
            $sms .= $spase."$server:";
            $comma = '';
            
            foreach ($errors as $keyError => $errorType)
            {
                $sms .= $comma."$errorType";
                $comma = ',';
            }
            
            $spase = ' ';
        }
        
        $sms = substr($sms, 0, 163);

        //$url = 'http://sms.ro.ru/r-sendsms/destiny?phone=+79213787571&msg='.urlencode($sms);
        //$contents = file_get_contents($url, false, stream_context_create(self::$options), 0, 100000);
        
        $url = 'http://sms.ro.ru/r-sendsms/destiny?phone=+79213690940&msg='.urlencode($sms);
        $contents = file_get_contents($url, false, stream_context_create(self::$options), 0, 100000);*/
    }

    public function __destruct()
    {
        if($this->isLocked)
        {
            $this->locker->unlock('TEST_SYS_FUN');
        } 
    }

    private function _writeErrorLog()
    {
        $countErrors = 0;

        foreach(self::$haveErrors as $type=>$errorArr)
        {
            foreach ($errorArr as $key => $error)
            {
                $errormsg = $type."/".$error.";"."\n";
                SU_DebugTools::WriteToLog($errormsg, $this->logErrorFileName);

                //Увеличиваем счетчик ошибок
                $countErrors ++;
            }

            $errors[$type] = $countErrors;

            //Обнуляем счетчик ошибок
            $countErrors = 0;
        }

        return $errors;
    }
}


?>