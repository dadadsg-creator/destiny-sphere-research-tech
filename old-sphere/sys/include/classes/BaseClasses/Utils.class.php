<?

  class Utils {
  /**
    Содержит в себе сл. методы:
     dump($Var) - Вывод скольк угодно сложной пременной
     noCache() - вывод заголовком запрещающих кэширование
     isEmail($string) - Порверка на валидность мыла
     rendGen($string) - Генерация случайной последовательности из букв и цифр длиной до 25 символов
	 getIp() - Возвращает IP клинета.
  */

    function randGen ($length) {
      return substr(md5(uniqid(rand(2, 6500))), 0,$length );
    }

/***************************************************************************/

    function noCache () {
      Header ( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
      Header ( "Cache-Control: no-cache, must-revalidate" );
      Header ( "Pragma: no-cache" );
      Header ( "Last-Modified: ".gmdate ( "D, d M Y H:i:s")."GMT");
    }//END OF METHOD no_cache

/****************************************************************************/

    function textDump (&$Var, $Level=0) {
      if(is_array($Var)) $Type="Array[".count($Var)."]";
      else if(is_object($Var)) $Type="Object";
      else $Type="";

      if($Type)
      {
        echo "$Type\n";
        for (Reset($Var), $Level++; list($k,$v)=each($Var);)
        {
          if(is_array($v) && $k==="GLOBALS") continue;
          for ( $i=0; $i<$Level*3; $i++) echo " ";
                echo "<b>".HtmlSpecialChars($k)."</b> => ", utils::TextDump($v,$Level);
              }//end FOR
            }
            else echo '"', HtmlSpecialChars($Var),'"'."\n";
    }//end function TEXTDUMP

    function dump (&$Var) {
      if ((is_array($Var) || is_object($Var)) && count($Var))
       echo "<pre>\n",utils::textDump ($Var),"</pre>\n";
            else echo "<tt>",utils::textDump($Var),"</tt>\n";
    }//end of function DUMP

/***********************************************************************************/

    function getIp () {
      global $HTTP_X_FORWARDED_FOR;
      global $REMOTE_ADDR;
      if ( $HTTP_X_FORWARDED_FOR ) { $IP = $HTTP_X_FORWARDED_FOR; }
      else { $IP = $REMOTE_ADDR; }
      return $IP;
    }
	
	function is_email($string)
    /*
    *      bool is_email(string string[, int min_length[, int max_length]])
    *      Check if a string is a syntactically valid mail address. Optionally
    *      check if it has a minimum length of min_length characters and/or a
    *      maximum length of max_length characters.
    */
    {

      // Remove whitespace
      $string = trim($string);
      $ret = ereg('^([a-zA-Z0-9_]|\\-|\\.)+'.'@'.'(([a-zA-Z0-9_]|\\-)+\\.)+'.'[a-zA-Z]{2,4}$',$string);
      return($ret);

    }//end of function IS_EMAIL
  };

?>