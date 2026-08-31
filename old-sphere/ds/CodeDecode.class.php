<?php

/**
 * Клас для кодирования и декодирования трафика.
 */
class Coder {
	
	/** 
	 * Ключь покоторому кодируем.
	 */
	var $c = 26061963;
	
	/**
	 * Сколько добавляеться символов левых спереди.
	 */
	var $first = 10;
	
	/**
	 * Скольк о добовляеться символов в конце.
	 */
	var $last = 10;
	
	/**
	 * Декодирование строки
	 */
	function decode ( $string ) {

		// декодирует данные, полученные от клиента
		$result = array();		

		$code = explode(",", $string);
		$code = array_slice( $code, $this->first );
		$code = array_reverse( $code );
		$code = array_slice( $code, $this->last );
		$result = array();

		foreach ( $code as $k=>$v ) {
			$result[$k] = chr ($v ^ $this->c);
		}

		return implode($result);
	}//END OF METHOD
	
	
	/**
	 * Кодирование строки
	 */
	function code ( $string ) {
		
		$out  = array();		
		
		srand ((double) microtime() * 1000000);
		
		for($i=0; $i < $this->first; $i++){
			$out[] = @rand ( 0, $this->c ); 
		}
		
		for($i = strlen($string)-1; $i>=0; $i--){
			$v = $string[$i];
			$out[] = ord($v) ^ $this->c;
		}
		
		for($i=0; $i < $this->last; $i++){
			$out[] = @rand ( 0, $this->c ); 
		}

		return implode(',', $out);
	}//END OF METHOD

}//END OF CLASS

$a = new Coder;
$str = "FUCK";

echo $a->decode ($a->code($str) );
?>