<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * 
 * Depricated!!!! Use  SU_OutputHandler
 * 
 * 
 *
 */

class OutputHandler extends BS_Base
{

	private $data;        //  "буфер" строк

	function __construct()
	{
		ds_error('OutputHandler depricated!!!! Use SU_OutputHandler');
		parent::__construct();
	}


	/*
	* добавление строки в "буфер"
	*/
	function addString($str, $mark='xmldata')
	{

		$this->addReadyString( $mark.'='.$str );
		return ( $mark.'='.$str );
	}

	/*
	** добавление готовой к выводу строки в "буфер"
	*/
	function addReadyString($str)
	{

		if( !empty($this->data) && ($this->data != '&') )
		{
			$this->data .= '&';
		}

		$this->data .= $str;
	}

	static function encodeData($data)
	{
		if(!isset($_SESSION))
		{
			$langID = 3;
		}else{
			if(key_exists('lang', $_SESSION))
			{
				$langID = $_SESSION['lang'];
			}else{
				$langID = 3;
			}
		}
		
		$data = urldecode($data);
		
		switch($langID)
		{
			case 1:
				$enc = 'cp1250';
				break;
			case 2:
				$enc = 'cp1250';
				break;
			case 3:
				$enc = 'cp1251';
				break;
			default:
				$enc = 'cp1251';
				
		}	
		
		$data = iconv($enc, 'utf-8', $data);
		
		return $data;
	}
	
	/*
	* возвращает содержимого из "буфера"
	*/
	function fetchAll()
	{
		return self::encodeData($this->data);
	}

	/**
         * очистка "буфера"
         */
	function clearBuf()
	{
		$this->data = '';
	}

	function __destruct()
	{
		parent::__destruct();
	}

}

?>