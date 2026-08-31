<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_LocalizationStat
{
	static private $lang = array();
	
	static public function Get($text)
	{
		self::_getLang();
		
		if(key_exists($text, self::$lang))
		{
			return self::$lang[$text];
		} else {
			return '';
		}
	}
	
	static private function _getLang()
	{
		if(count(self::$lang) > 0)
		{
			return;
		}
		
		if(isset($_SESSION) && key_exists('lang', $_SESSION))
		{
			$l = $_SESSION['language'];
		} else {
			$l = constant('DEFAULT_LANGUAGE_SHORT');
		}
		
		$lang = array();
		
		if(file_exists($_SERVER['DOCUMENT_ROOT']."/ds/language/".$l."/user_cabinet_stat_out.lang"))
		{
			include_once $_SERVER['DOCUMENT_ROOT']."/ds/language/".$l."/user_cabinet_stat_out.lang";
		}
		
		self::$lang = $lang;
	}
}

?>