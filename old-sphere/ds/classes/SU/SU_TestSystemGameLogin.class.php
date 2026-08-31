<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_TestSystemGameLogin extends SU_TestSystemBase
{
    public function exec()
    {
        $string = "Start login test";
        $this->_outString($string, 4);
        
        //Проверяем, не закрыта ли игра
        if(GameDown(0))
        {
            $result = "Data Base closed by GameDown";
            $this->_outString($result, 8);
            return;
        } 
        
        //определяем параметры
        $host = constant('GAME_HOST');  //Хост
        $password = "cvDNS128";         //Пароль
        $login = "Annita";              //Логин

        //Данные о мире в зависимости от версии игры
        switch ($host)
        {
            case 'http://ds.rambler.ru':
                
                if(GameDown(11, 2))
                {
                    $result = "World closed by GameDown";
                    $this->_outString($result, 8);
                    return;
                } 
                
                $worldData = "20427_11_2";  //Классик (водолей)
                break;
                
            case 'http://ds-game.ru':
                
                //Проверяем, не закрыт ли мир
                if(GameDown(2, 1))
                {
                    $result = "World closed by GameDown";
                    $this->_outString($result, 8);
                    return;
                } 
                $worldData = "1932_2_1";    //Анлимитед (Сфера Дракона 2-й уровень)
                break;
                
            case 'http://test.destinysphere.ru':
                $worldData = "7768_0_31";   //Тест (Гиппопотам)
                break;
                
            case 'http://unlim':
                //$worldData = "1_0_1";         //Локаль анлима
                $worldData = "2_0_1";       //Локаль анлима
                $password = "zzzzzzzz";     //Пароль
                break;
                
            case 'http://classic':
                $worldData = "3_0_2";       //Локаль классика
                break;
                
            case 'http://ds.destinysphere.net':
                $result = "Test not available!";
                $this->_outString($result, 8);
                return;
                
            default:
                $result = "Undefided Game Host!!! <<<<<<<<<<<<< ERROR!";
                $this->_writeError('GL', "Undefided Game Host");
                $this->_outString($result, 8);
                return;
        }
        
        //Делаем три попытки логина в игру
        for ($i = 0; $i <3; $i++)
        {
            $gameTestResult = $this->testGameLogin($host, $password, $login, $worldData);
            if ($gameTestResult == 'OK')
            {
                $this->_outString('OK!', 8);    
                return true;
            } 
        }
        
        //Если попытки входа в игру неудачные, записываем ошибку
        $result = "$gameTestResult!!! <<<<<<<<<<<<< ERROR!";
        $this->_writeError('GL', "$gameTestResult");
        $this->_outString($result, 8);
        
        return false;
    }

    static private function testGameLogin($host, $password, $login, $worldData)
    {
        //Логинимся в игру
        $request = $host."/ds/index_login.php";
        $getContent = new SU_HTTPGetContent();
        $getContent->setUrl($request);
        $getContent->setMethod("POST");
        $getContent->setPostVar("enter", "Login");
        $getContent->setPostVar("login", $login);
        $getContent->setPostVar("pass", $password);

        $getContent->Start();
        $headers = $getContent->GetResponseHeaders();
        
        //Нас редиректит на index_start, идем туда
        //Разбиваем заголовок в массив
        $tmp = explode("\r\n", $headers);

        foreach($tmp as $key => $data)
        {
            if (stripos($data, ':') !== false){
                $dataTmp = explode(': ', $data);
                $headerArr[$dataTmp[0]] = $dataTmp[1];
            }
        }
        
        $tmp = explode('?', $headerArr['Location']);

        $request = $host."/ds/".$tmp[0];
        $getContent = new SU_HTTPGetContent();
        $getContent->setUrl($request);
        $getContent->setMethod("POST");
        //Устанавливаем параметры
        self::SetParams($getContent, $tmp[1]);
        
        //Получаем содержимое index_start (странички с выбором миров)
        $getContent->Start();
        $content = $getContent->GetResponseContent();
        
        //Если в данных нет клиент кея
        if (!ereg ('<input type="hidden" name="ck" value=', $content)) 
        //if (!ereg ('<input type="hidden" name="ck" value=', $content) || !ereg ('<input type="hidden" name="SIDIX" value=', $content)) 
        {
            return "Can't login in game";
        }

        //вытаскиваем из данных клиент кей
        $ck = self::GetParams($content, '<input type="hidden" name="ck" value="');
        
        //вытасиваем SIDIX
        //$SIDIX = self::GetParams($content, '<input type="hidden" name="SIDIX" value="');
        
        //Выбираем мир
        $request = $host."/ds/index_start.php";
        $getContent = new SU_HTTPGetContent();
        $getContent->setUrl($request);
        $getContent->setMethod("POST");
        $getContent->setPostVar("ck", $ck);
        //$getContent->setPostVar("SIDIX", $SIDIX);
        $getContent->setPostVar("start", "1");
        $getContent->setPostVar("uiid", "$worldData");
        
        //Устанавливаем параметры
        self::SetParams($getContent, $tmp[1]);

        $getContent->Start();
        $headers = $getContent->GetResponseHeaders();
        $headers = $getContent->GetResponseHeaders();
        
        //Разбиваем заголовок в массив
        $tmp = explode("\r\n", $headers);
        foreach ($tmp as $key => $data)
        {
            if (stripos($data, ':') !== false)
            {
                $dataTmp = explode(': ', $data);
                $headerArr[$dataTmp[0]] = $dataTmp[1];
            }
        }
        
        $tmp = explode('?', $headerArr['Location']);
        $reguestTmp = explode('./', $tmp[0]);
                
        $request = $host."/".$reguestTmp[1];
        $getContent = new SU_HTTPGetContent();
        $getContent->setUrl($request);
        $getContent->setMethod("POST");
        
        //Устанавливаем параметры
        self::SetParams($getContent, $tmp[1]);

        $getContent->Start();
        $content = $getContent->GetResponseContent();
        /*          
        // Если у нас включена проверка плеера
        //if(!constant("DISABLE_DETECT_PLAYER"))
        /*
        //Проходим проверку плеера
        $request = $host."/".$reguestTmp[1];
        $getContent = new SU_HTTPGetContent();
        $getContent->setUrl($request);
        $getContent->setMethod("POST");
        
        //Устанавливаем параметры
        self::SetParams($getContent, $tmp[1]);

        $getContent->Start();
        $content = $getContent->GetResponseContent();

        if (!ereg ('meta http-equiv="Refresh"', $content))
        {
            return "Can't pass fash check";
        }*/

        //$address = self::GetParams($content, '<meta http-equiv="Refresh" content="10; URL=');
        //$pos = strpos($content, '<meta http-equiv="Refresh"');
        /*
        $Temp1 = explode('meta http-equiv="Refresh"', $content);
        $Temp2 = explode('URL', $Temp1[1]);
        $address = self::GetParams($Temp2[1], '=');
    
            
        $tmp = explode('?', $address);
        $request = $host."/ds/".$tmp[0];*/

        /*$getContent = new SU_HTTPGetContent();
        $getContent->setUrl($request);
        $getContent->setMethod("POST");
        
        //Устанавливаем параметры
        self::SetParams($getContent, $tmp[1]);
        $getContent->Start();
        
        $content = $getContent->GetResponseContent();*/

        if (!ereg ('<PARAM NAME="FlashVars" VALUE="', $content))
        {
            return "Can't get FlashVars";
        }
        
        //вытаскиваем из данных клиент кей
        $params = self::GetParams($content, '<PARAM NAME="FlashVars" VALUE="');
    
        $request = $host."/ds/useraction.php";

        $getContent = new SU_HTTPGetContent();
        $getContent->setUrl($request);
        $getContent->setMethod("POST");
        $getContent->setReferer("http://unlim/ds/flash/ds3-31.swf");

        //Устанавливаем параметры
        self::SetParams($getContent, $tmp[1]);      
        
    
        $xmldata = code('<refresh/>');

        //Достаем версию флеша
        $tmpArr = explode(',', $xmldata);
        
        if (!self::getVersions($host))
        {
            return "Can't get flash version";
        }
        
        $tmpArr[9] = $_SESSION['FLASH_VERSIONS']['ds3.swf'];
        $elementNamber = count($tmpArr)-10;
        $tmpArr[$elementNamber] = $_SESSION['FLASH_VERSIONS']['menu.lib.swf'];

        $xmldata = implode(',', $tmpArr);

        $getContent->setPostVar('xmldata', $xmldata);
        $getContent->Start();
        $content = $getContent->GetResponseContent();   

        if (!strpos($content, 'refresh='))
        {
            return "Can't refresh comb";
        }
        
        return 'OK';
    }
    
    static private function SetParams($getContent, $paramsString)
    {
        $varTmp = explode('&', $paramsString);
        foreach ($varTmp as $key => $value)
        {
            $var = explode('=', $value);
            $getContent->setPostVar($var[0], $var[1]);
        }
    }

    static private function GetParams($content, $string)
    {
        $pos = strpos($content, $string);
        $paramsTemp = explode('"', substr($content, $pos+strlen($string)));
        return $paramsTemp[0];
    }
    
    static private function getVersions($host)
    {
        /*// Затычка для локали!!
        $_SESSION['FLASH_VERSIONS']['ds3.swf'] = 696;           
        $_SESSION['FLASH_VERSIONS']['menu.lib.swf'] = 753;          
        return true;*/
        
        
        $versionsForControl = array
                (
                'ds3.swf' => array('FROM_BEGIN' => 10, 'FROM_END' => null), 
                'menu.lib.swf' =>  array('FROM_BEGIN' => null, 'FROM_END' => 10)
                );
            
        $path = $host.'/ds/flash/version.ini';
        
        $versions = parse_ini_file($path, true);
        
        foreach($versions AS $fileName =>$fileData)
        {
            if(!key_exists($fileName, $versionsForControl))
            {
                continue;
            }
            
            assert(key_exists('version', $fileData));
            $version = (int)$fileData['version'];
            assert($version > 0);           
            
            $_SESSION['FLASH_VERSIONS'][$fileName] = $version;          
        }
        
        return true;    
    }
}

?>