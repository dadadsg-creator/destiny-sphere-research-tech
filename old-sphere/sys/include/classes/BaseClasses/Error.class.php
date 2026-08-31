<?
  /**
    Вызывается при возникновении ошибок:
    интерпретатора, вызывается из "нашего" обработчика ошибок
    времени исполнения. Вызывается из обьекта, обнаружевшего ошибку.
  */
  class Error extends Logger {

    /**
      Список мест для вывода полного сообщения об ошибке.
      (log, browser, mail)
    */
    var $errorTarget;

    /**
      Адрес почты для отправки оповещений об ошибках и для отсылке туда
      пользователей.
    */
    var $adminMail;

    /**
      Что будет написанно в теме письма админу с оповещением об ошибке
    */
    var $sendFrom;

	/** Имя обькта для которого обрабатываем ошибки */
	var $objestName;

    /**
      Конструктор
      Параметры:
        $objectName - Имя класса осуществляющего логирование
        $logFileName - Имя файла для логирования
        $logPath - Путь для сохранения логов
    */
    function Error ($objectName, $logFileName, $logPath){
      $this->Logger ($objectName, $logFileName, $logPath);
      $this->adminMail = "support@ru.destinysphere.com";
	  $this->objectName = $objectName;
      $this->sendFrom = "Тестовая ошибка";
      $this->logType = "Error";
      $this->errorTarget = "log and browser and mail";
    }

    /**
      Метод для вывода у браузер полного сообщения об ошибке
        Параметры:
        $msg - Сообщение об ошибке
    */
    function outTrueError ($msg){
      echo "
        <h1>ERROR!</h1>
        <b>Message:</b> $msg<br>
        <br>
        Please write to admin (".$this->adminMail .")";
    }

    /**
      Метод для вывода у браузер сокращенного сообщения об ошибке
      Параметры:
        $msg - Сообщение об ошибке
    */
    function outSimpleError ($msg){
      echo "
        <h1>ATTENTION!</h1>
        Censored!.";
    }

    /**
      Отсылает по почте админу сообщение об ошибке
      Параметры:
        $msg - Сообщение об ошибке
    */
    function sendMail ($msg)
    {
      //@mail($this->adminMail, $this->sendFrom, $msg);
    }

    /**
      Вызывается при обнаружении ошибки.
        Параметры:
        $msg - Сообщение об ошибке
    */
    function processError ($msg)
    {

	  $msg = "From object <b>".$this->objectName."</b> - ".$msg;
      //Если установлен вывод ошибки в log
      if (ereg("log", $this->errorTarget)){
        $this->logIt($msg);
      };

      //Смотрим какой текст ошибки выводить на монитор
      if (ereg("browser", $this->errorTarget)){
        $this->outTrueError($msg);
      }else{
        $this->outSimpleError($msg);
      };

      //Если устанволенна отсылка по мылу
      if (ereg("mail", $this->errorTarget)){
        $this->sendMail($msg);
      };

      exit;
    }

  }; //END OF CLASS Error
?>
