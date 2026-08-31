<?
  /**
    Класс для логирования
    
    Формат лог-файла:
    [Date Time] [IP - HOST] [From: ObjectName] [MessageType (Error|Log)] - Message
    
  */
  class Logger {

    /** Имя фала для логирования */
    var $logFileName;
    
    /** Имя класса который заносит запись в лог */
    var $objectName;
    
    /** Путь к логфайлам */
    var $logPath;
    
    /** Тип логирования ошибки (Error) или логирование события (Log) */
    var $logType;
    
    /**
      Конструктор
      Параметры:
        $objectName - Имя класса осуществляющего логирование
        $logFileName - Имя файла для логирования
        $logPath - Путь для сохранения логов
    */
    function Logger ($objectName, $logFileName, $logPath){
      $this->objectName = $objectName;
      $this->logFileName = $logFileName;
      $this->logPath = $logPath;
      $this->logType = "Log";
    }
    
    /**
      private метод для записи в логфайл
      Параметры: $string - Строка для записи в файл
    */
    function log ($string){
      error_log ($string, 3, $this->logPath.$this->logFileName);
    }
    
    /**
      Функция для получения IP
    */
    function getIP (){
      //(проверяем за прокси сервером он или нет.
      //Если за прокси, то пытаемся определиить его реальный IP)
      if (getenv ("HTTP_X_FORWARDED_FOR")){
        $ip = getenv ("HTTP_X_FORWARDED_FOR");
      }else{
        $ip = getenv ("REMOTE_ADDR");
      }
      return $ip;
    }
    
    /**
      Метод для логирования
      Параметры:
        $message - текст для логирования
    */
    function logIt ( $message ){
      //Формируем строку для лога
      $str = "[".date("Y-m-d")." ".date("H-i-s")."]".
             "[".$this->getIP()." - ".gethostbyaddr($this->getIP())."]".
             "[From: ".$this->objectName."]".
             "[".$this->logType."] - ".$message."\n";
      $this->log($str);
    }

  }; //END OF CLASS Logger




?>
