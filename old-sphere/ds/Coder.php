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
		
		$this->init();
		
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
		
		$this->down();
		
		return implode($result);
	}//END OF METHOD
	
	
	/**
	 * Кодирование строки
	 */
	function code ( $string ) {
		
		$this->init ();
		
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
		
		$this->down();
		
		return implode(',', $out);
	}//END OF METHOD
	
	function init (){
		
	}
	
	function down(){
		
	}
	
}//END OF CLASS


$c = new Coder;

if( $_REQUEST['action'] == 'code' ){
	echo $c->code($_REQUEST['msg']);
}else{
	echo $c->decode($_REQUEST['msg']);
}



?>