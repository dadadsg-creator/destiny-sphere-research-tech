<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * ????? ????? ??????? ???? ???? ????? ? ????????.
 *
 */
class SU_OutputHandler extends BS_Base
{

	/**
	 * ????????? ????
	 *
	 * @var SU_OutputHandler
	 */
	static private $instance;
	                 
	/**
	 * "?????" ?????
	 *
	 * @var string
	 */
	private $data = '';

	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Enter description here...
	 *
	 * @return SU_OutputHandler
	 */
	static public function Singleton()
	{
		if(!(self::$instance instanceof SU_OutputHandler))
		{
			self::$instance = new SU_OutputHandler;
		}
		
		return self::$instance;
	}
	
	/*
	* ?????????? ?????? ? "?????"
	*/
	function addString($str, $mark='xmldata')
	{
		$this->addReadyString( $mark.'='.$str );
		return ( $mark.'='.$str );
	}

	/*
	** ?????????? ??????? ? ?????? ?????? ? "?????"
	*/
	function addReadyString($str)
	{
		if( !empty($this->data) && ($this->data != '&') )
		{
			$this->data .= '&';
		}

		$this->data .= $str;
	}
	
	static public function GetCharsetForLang($langID)
	{
		assert(is_int($langID) && $langID > 0);
		
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
			case 4:
				$enc = 'ISO-8859-1';
				break;
			case 5:
				$enc = 'cp1257';
				break;
			default:
				$enc = 'cp1251';
		}	
		
		return $enc;
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
		$data = html_entity_decode($data);
		//$data = stripslashes($data);
		//$data = str_replace("'", '`', $data);
		
		//$data = str_replace("&#", "?", $data);	

		if ($langID == 5) {
			$data = str_replace("&#260;", "À", $data);
			$data = str_replace("&#261;", "à", $data);
			$data = str_replace("&#280;", "Æ", $data);
			$data = str_replace("&#281;", "æ", $data);
			$data = str_replace("&#278;", "Ë", $data);
			$data = str_replace("&#279;", "ë", $data);
			$data = str_replace("&#302;", "Á", $data);
			$data = str_replace("&#303;", "á", $data);
			$data = str_replace("&#370;", "Ø", $data);
			$data = str_replace("&#371;", "ø", $data);
			$data = str_replace("&#362;", "Û", $data);
			$data = str_replace("&#363;", "û", $data);
			$data = str_replace("&#268;", "È", $data);
			$data = str_replace("&#269;", "è", $data);
			$data = str_replace("&#352;", "Ð", $data);
			$data = str_replace("&#353;", "ð", $data);
			$data = str_replace("&#381;", "Þ", $data);
			$data = str_replace("&#382;", "Þ", $data);	
		}
		
		$data = str_replace("&apos;", "'", $data);			
		
		$enc = self::GetCharSetForLang((int)$langID);
		$data = iconv($enc, 'utf-8', $data);
		
		return $data;
	}
		
	function fetchAll(&$forLog = '')
	{
		$forLog = self::encodeData($this->data);
		$this->data = '';

		return $forLog;
	}	
	
	function __destruct()
	{
		parent::__destruct();
	}
}

?>