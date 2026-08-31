<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс хранит протокол вз.д. клиента с сервером.
 *
 */
class SU_ProtocolAccumulator
{
    
    /**
     * Массив с данными протокола, которые логируем
     *
     * @var array
     */
    static private $protocolAccumulator = array();
    
    /**
     * Колличество шакгов которые логируем
     *
     * @var integer
     */
    static private $maxAccumulatorLength = 5;
    
    /**
     * Текущее время в аккумуляторе
     *
     * @var string
     */
    static private $currentAccumulatorTime;
    
    /**
     * Переменный в ответе сервера, которые логируем
     *
     * @var array
     */
    static private $forLogResponsKeys = array('xmldata', 'helper', 'combres', 'refresh', 'loaddata', 'ck');
    
    /**
     * Переменный в запросе сервера, которые логируем
     *
     * @var array
     */
    static private $forLogRequestKeys = array('ck', 'xmldata_post', 'xmldata_sess');
    
    /**
     * Переменные в ответе сервера у которых логируеться не содежимое, а только его длина в символах.
     *
     * @var array
     */
    static private $forExistsKeys = array(/*'refresh',*/ 'loaddata');
    
    /**
     * Теги в ответе сервера которые уризаються до состония просто налияи или нет
     *
     * @var array
     */
    static private $forCutTags = array('<messageoverview', '<table', '<battlinfo', '<battleoverview', '<load'/*, '<armyoverview'*/);    
    
    static public function RequestAccumulator()
    {
        assert(is_null(self::$currentAccumulatorTime));
        assert(count(self::$protocolAccumulator) == 0);
    
        self::$currentAccumulatorTime = SU_DebugTools::GetInternalDate();
    
        $record = array(
            'REQUEST' => self::_formOutString(self::_getRequestData()),
            'RESPONS' => ''
        );

        if(key_exists('PROTOCOL_ACCUMULATOR', $_SESSION))
        {
            self::$protocolAccumulator = $_SESSION['PROTOCOL_ACCUMULATOR'];
        }
    
        self::$protocolAccumulator[self::$currentAccumulatorTime] = $record;
    
        self::_cutAccumulator();
    
        $_SESSION['PROTOCOL_ACCUMULATOR'] = self::$protocolAccumulator;
    }
    
    static private function _cutAccumulator()
    {
        if(count(self::$protocolAccumulator) > self::$maxAccumulatorLength)
        {
            self::$protocolAccumulator = array_slice(self::$protocolAccumulator, count(self::$protocolAccumulator) - self::$maxAccumulatorLength - 1);
        }
    }
        
    static private function _preProcessRequest()
    {
        $out = $_REQUEST;
        
        if(key_exists('s_xmldata', $_SESSION))
        {
            $sessionXmlData = self::_decodeXmlData($_SESSION['s_xmldata']);
        } else {
            $sessionXmlData = 'N/A';
        }

        if(key_exists('xmldata', $out))
        {
            $postXmlData = self::_decodeXmlData($out['xmldata']);
        } else {
            $postXmlData = 'N/A';
        }
        
        $out['xmldata_post'] = $postXmlData;        
        $out['xmldata_sess'] = $sessionXmlData;
        
        return $out;
    }
    
    static private function _getRequestData()
    {
        $out = array();
        
        $request = self::_preProcessRequest();
        
        foreach(self::$forLogRequestKeys as $k=>$keyName)
        {
            if(key_exists($keyName, $request))
            {
                $out[$keyName] = $request[$keyName];
            }
        }
        
        return $out;
    }
        
    static private function _decodeXmlData($xmlData)
    {
        if(function_exists('decode'))
        {
            return decode($xmlData);
        }else{
            return $xmlData;
        }
    }
    
    static public function ResponsAccumulator($response)
    {
        assert(!(is_null(self::$currentAccumulatorTime)));
        assert(!(count(self::$protocolAccumulator) == 0));      

        $respons = array();

        $tmpArray = explode('&', $response);

        foreach($tmpArray as $k=>$varString)
        {
            $seporatePosition = strpos ($varString, '=');
            if ($seporatePosition <= 0) continue;
            // assert($seporatePosition > 0);
            
            $name = substr($varString, 0, $seporatePosition);
            
            //Если это ключь не логируем то переходим длаьше
            if(!(in_array($name, self::$forLogResponsKeys)))
            {
                continue;
            }
            
            $value = substr($varString, $seporatePosition+1);           

            //Ключи которые логируем только на прдемет существования
            if(in_array($name, self::$forExistsKeys))
            {
                $value = "length ".strlen($value);
            }else{
                
                //Вырезаем полностью тег если надо
                $isCated = false;
                foreach(self::$forCutTags as $k => $cutTag)
                {
                    if(strstr($value, $cutTag))
                    {
                        $value = $cutTag.">";
                        $isCated = true;
                        break;
                    }
                }
                
                if(!$isCated)
                {
                    //Убираем заголовок XML
                    $value = str_replace('<?xml version="1.0" encoding="utf-8"?>'."\n", "", $value);
                    $value = str_replace('<?xml version="1.0"?>'."\n", "", $value);     
                    
                    
                    //Обрезаем переводы корретки в конце
                    if(strlen($value) > 0)
                    {
                        $lastPos = strlen($value)-1;
                        if($value[$lastPos] == "\n" || $value[$lastPos] == "\r\n")
                        {
                            $value = substr($value, 0, $lastPos);
                        }
                    }
                }
            
            }
            
                    
            $respons[$name] = $value;
        }
        
        $out = array();     
        foreach(self::$forLogResponsKeys as $k => $keyName)
        {                       
            if(!(key_exists($keyName, $respons)))
            {
                continue;
            }
            
            $out[$keyName] = $respons[$keyName];
        }
        
        if(count($out) > 0)
        {
            assert(key_exists('RESPONS', self::$protocolAccumulator[self::$currentAccumulatorTime]));
            self::$protocolAccumulator[self::$currentAccumulatorTime]['RESPONS'] = gzcompress(self::_formOutString($out), 9);
        }

        $_SESSION['PROTOCOL_ACCUMULATOR'] = self::$protocolAccumulator;
    }
    
    static public function GetFormatedProtocolHistory()
    {
        $out = '';      
        foreach(self::$protocolAccumulator as $time=>$record)
        {
            $gz = @gzuncompress($record['RESPONS']);
            $gz = iconv('utf-8', 'cp1251', $gz);
            $out .= "##############################> ".$time." <################################### \n";
            $out .= $record['REQUEST'];
            $out .= "----------------\n";
            $out .= $gz;
        }

        return $out;
    }
    
    static private function _formOutString(array $data)
    {
        $out = '';
        
        foreach($data AS $k=>$v)
        {
            $out .= "[$k] = $v\n";  
        }
        
        return $out;
    }
    
    /**
     * Уже не используеться в этом классе, но используеться с наружи. Потому не удаляем
     *
     * @return unknown
     */
    static public function GetXmlData()
    {
        if(key_exists('s_xmldata', $_SESSION))
        {
            $sessionXmlData = $_SESSION['s_xmldata'];
        } else {
            $sessionXmlData = '';
        }

        if(key_exists('xmldata', $_REQUEST))
        {
            $postXmlData = $_REQUEST['xmldata'];
        } else {
            $postXmlData = '';
        }
        
        
        if(key_exists('ck', $_REQUEST))
        {
            $ck = $_REQUEST['ck'];
        }else{
            $ck = 'N/A';
        }

        return "Post(".self::_decodeXmlData($postXmlData).") Session(".self::_decodeXmlData($sessionXmlData).") CK[$ck]";
    }
    
}

?>