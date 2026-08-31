<?
  /**
   * Класс для получени ятекстов собщений ошибок
   */
  class ErrorMessages extends NativeMessages{
     function ErrorMessages($db,$lang){
       $this->Object("ErrorMessages");
       $this->error = new Error ($this->objectType, constant("ERROR_LOG_NAME"),  constant("LOG_PATH"));
       
       $this->mainTblName = "ErrorMsg";
       $this->secondTblName = "ErrorMsgTexts";
       $this->mainKeyField = "ErrorMsg_id";
       $this->secondKeyField = "ErrorMsgText_id";
       
       $this->NativeMessages($db,$lang);
     }
  }
?>
