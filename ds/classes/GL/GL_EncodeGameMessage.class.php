<?php

/**
 * Кодирование и декодирование почты в клиенте + 2 полезные функции по работе с юникодом. 
 * 	Пока не вышел ПХП 5.3 работа с юникодом не самое сильное место в ПХП
 *
 */
class GL_EncodeGameMessage
{
	static private $hexArray = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
	
	/**
	 * Получить код символа, вариант для кодировки utf-8 (юникод)
	 *
	 * @param string $c
	 * @return int
	 */
	static public function uniord($c) 
	{
   		$ud = 0;
   		
  		if (ord($c{0})>=0 && ord($c{0})<=127)
  		{
   			$ud = ord($c{0});
  		} elseif (ord($c{0})>=192 && ord($c{0})<=223) {
   			$ud = (ord($c{0})-192)*64 + (ord($c{1})-128);
  		} elseif (ord($c{0})>=224 && ord($c{0})<=239) {
   			$ud = (ord($c{0})-224)*4096 + (ord($c{1})-128)*64 + (ord($c{2})-128);
  		} elseif (ord($c{0})>=240 && ord($c{0})<=247) {
   			$ud = (ord($c{0})-240)*262144 + (ord($c{1})-128)*4096 + (ord($c{2})-128)*64 + (ord($c{3})-128);
  		} elseif (ord($c{0})>=248 && ord($c{0})<=251) {
   			$ud = (ord($c{0})-248)*16777216 + (ord($c{1})-128)*262144 + (ord($c{2})-128)*4096 + (ord($c{3})-128)*64 + (ord($c{4})-128);
  		} elseif (ord($c{0})>=252 && ord($c{0})<=253) {
   			$ud = (ord($c{0})-252)*1073741824 + (ord($c{1})-128)*16777216 + (ord($c{2})-128)*262144 + (ord($c{3})-128)*4096 + (ord($c{4})-128)*64 + (ord($c{5})-128);
  		} elseif (ord($c{0})>=254 && ord($c{0})<=255) {
   			$ud = false;
		}
   			
 		return $ud;
	}
	
	/**
	 * Получить символ по его коду, вариант для кодировки utf-8 (юникод)
	 *
	 * @param int $c
	 * @return string
	 */
	static public function unichr($c) 
	{
    	if ($c <= 0x7F) 
    	{
        	return chr($c);
    	} elseif ($c <= 0x7FF) {
        	return chr(0xC0 | $c >> 6).chr(0x80 | $c & 0x3F);
    	} elseif ($c <= 0xFFFF) {
        	return chr(0xE0 | $c >> 12).chr(0x80 | $c >> 6 & 0x3F).chr(0x80 | $c & 0x3F);
    	} elseif ($c <= 0x10FFFF) {
        	return chr(0xF0 | $c >> 18).chr(0x80 | $c >> 12 & 0x3F).chr(0x80 | $c >> 6 & 0x3F).chr(0x80 | $c & 0x3F);
    	} else {
        	return false;
    	}
	}
	
	/**
	 * Зашифровать сообщение
	 *
	 * @param unknown_type $text
	 * @param unknown_type $fromLang
	 * @return unknown
	 */
	static public function Encode($text, $fromLang = null)
	{
		assert(is_null($fromLang) || is_int($fromLang));
		if(is_null($fromLang))
		{
			$fromLang = constant('DEFAULT_LANGUAGE');
		}
		
		$charset = SU_OutputHandler::GetCharsetForLang($fromLang);
		
		$encodedMsg = '';
		
		for($i=0; $i<strlen($text); $i++)
		{
            $num = self::uniord(iconv($charset, 'UTF8', $text[$i]));
            $tnum = $num;
            $str = "";

            $char1 = '';
            $char2 = '';
            $char3 = '';
            
            $k = (int)floor($num/256);
            if(key_exists($k, self::$hexArray))
            {
            	$char1 = self::$hexArray[$k];
            }
            
            $num = $num%256;
            $k = (int)floor($num/16);
            if(key_exists($k, self::$hexArray) && $char1 !== '')
            {
            	$char2 = self::$hexArray[$k];
            }
            
            $k = $num%16;
            if(key_exists($k, self::$hexArray) && $char2 !== '')
            {
            	$char3 = self::$hexArray[$k];
            }
            
            $code = $char1.$char2.$char3;
            $encodedMsg .= $code;
        }
		
		return $encodedMsg;
	}
	
	/**
	 * Расшифровать
	 *
	 * @param unknown_type $text
	 * @return unknown
	 */
	static public function Decode($text)
	{
		$textLen = strlen($text);
		
		$decodedMsg = '';
		
		for ($i=0; $i<$textLen; $i+=3)
		{
			$num = 0;
			
			$c1 = $text[$i+0];
			$c2 = $text[$i+1];
			$c3 = $text[$i+2];
			
			$c1_num = array_keys(self::$hexArray, $c1, true);
			$c2_num = array_keys(self::$hexArray, $c2, true);
			$c3_num = array_keys(self::$hexArray, $c3, true);
			$num = $c3_num[0] + 16 * $c2_num[0] + 256 * $c1_num[0];
						
			$decodedMsg .= self::unichr($num);
		}
		
		return $decodedMsg;
	}
}

?>