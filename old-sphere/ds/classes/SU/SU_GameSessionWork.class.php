<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_GameSessionWork
{	
	static private $instance;
	
	public function __construct()
	{
		switch (CONSTANT('SESSION_METHOD'))
		{
			case 'db':
				SU_SessionDataBase::Singleton();
				break;
			case 'file':
				SU_SessionFile::Singleton();
				break;
			case 'memC':
				SU_SessionMemC::Singleton();
				break;
			default:
				SU_DebugTools::LogAll("ERROR! UNKNOWN SESSION METHOD", 'SessionErrors.log', 0, 0);
		}
	}
	
	static public function Singleton()
	{
		if(!(self::$instance instanceof SU_GameSessionWork))
		{
			self::$instance = new SU_GameSessionWork();
		} 
		
		return self::$instance;
	}		
	
	static public function SessionStart()
	{
		self::Singleton();

		assert(session_start());
		$_SESSION['session_start_ip_full'] = "REMOTE_ADDR: ".@$_SERVER['REMOTE_ADDR']."; X_FORWARDED_FOR: ".@$_SERVER['HTTP_X_FORWARDED_FOR'];
		$_SESSION['session_start_ua'] = md5(@$_SERVER['HTTP_USER_AGENT']);
		$_SESSION['session_start_ua_full'] = @$_SERVER['HTTP_USER_AGENT'];
		$_SESSION['session_start_date'] = SU_DebugTools::GetInternalDate();
		$_SESSION['session_start_sid'] = session_id();
		return true;
	}
	
		
	static public function SessionContinue($sido = NULL)
	{
		self::Singleton();
		
		$sapi = php_sapi_name();
		if(strpos($sapi, "apache") === false)
		{
			return true;
		}

		self::_setDomain();

		if ($_SERVER['REMOTE_ADDR']=='195.182.156.98')
  	  SU_DebugTools::LogAll('sido='.$sido.',	session_name()='.session_name()."\n COOKIE=".var_export($_COOKIE, true)."\n REQUEST=".var_export($_REQUEST, true), "SessionErrors.log");
		if ($sido == NULL)
		{
			if(isset($_COOKIE[session_name()]))
			{
				$sido = $_COOKIE[session_name()];
			} else if(isset($_REQUEST[session_name()])) {
				$sido = $_REQUEST[session_name()];
				if(strpos($sido, ' ') !== false)
				{
					$sido = substr($sido, 0, strpos($sido, ' '));
				}
			} else {

				$str  = "Cant find SID\n";
				$str .= "SESSION_NAME = ".session_name()."\n";
				$str .= "COOKIE = ".@$_COOKIE[session_name()]."\n";
				$str .= "REQUEST = ".@$_REQUEST[session_name()]."\n";
				$str .= "START_DATE = ".@$_SESSION['session_start_date']."\n";
				$str .= "LAS_CONTINUE_DATE = ".@$_SESSION['session_last_continue_date']."\n";

				SU_DebugTools::LogAll($str, "SessionErrors.log");

				return false;
			}
		} else {
			session_id($sido);
		}

		/*
		if($sido == 'deleted')
		{
		}
		*/
		
		if(!preg_match('/^[a-zA-Z0-9]+$/', $sido))
		{
			$str  = "SID == $sido\n";
			$str .= "SESSION_NAME = ".session_name()."\n";
			$str .= "COOKIE = ".@$_COOKIE[session_name()]."\n";
			$str .= "REQUEST = ".@$_REQUEST[session_name()]."\n";
			$str .= "START_DATE = ".@$_SESSION['session_start_date']."\n";
			$str .= "LAS_CONTINUE_DATE = ".@$_SESSION['session_last_continue_date']."\n";

			SU_DebugTools::LogAll($str, "SessionErrors.log");
			
			return false;
		}
		
		switch (CONSTANT('SESSION_METHOD'))
		{
			case 'db':
				$sessionExist = SU_SessionDataBase::sessionExist($sido);
				break;
			case 'file':
				$sessionExist = SU_SessionFile::sessionExist($sido);
				break;
			case 'memC':
				$sessionExist = SU_SessionMemC::sessionExist($sido);
				break;
			default:
				SU_DebugTools::LogAll("ERROR! UNKNOWN SESSION METHOD", 'SessionErrors.log', 0, 0);
		}
		

		if ($sessionExist == true)
		{
			self::SessionStart();
		  if ($_SERVER['REMOTE_ADDR']=='195.182.156.98')
  	    SU_DebugTools::LogAll('sessionExist=TRUE', "SessionErrors.log");		
			
			if(!isset($_SESSION['session_start_ua']) || $_SESSION['session_start_ua'] != md5(@$_SERVER['HTTP_USER_AGENT']))
			{
				$str  = "Mismatch session UA\n";
				$str .= "SESSION_START_IP = ".@$_SESSION['session_start_ip_full']."\n";
				$str .= "START_DATE = ".@$_SESSION['session_start_date']."\n";
				$str .= "LAS_CONTINUE_DATE = ".@$_SESSION['session_last_continue_date']."\n";
				$str .= "START UA = ".@$_SESSION['session_start_ua_full'];
				$str .= "CURRENT UA = ".@$_SERVER['HTTP_USER_AGENT'];
	
				SU_DebugTools::LogAll($str, "SessionErrors.log");
				//DS_Error("Mismatch session UA", false);
				return false;
			}

		} else {
			$str  = "Cant find session\n";
			$str .= "FILE_NAME = ".$sido."\n";
			$str .= "START_DATE = ".@$_SESSION['session_start_date']."\n";
			$str .= "LAS_CONTINUE_DATE = ".@$_SESSION['session_last_continue_date']."\n";
			
			SU_DebugTools::LogAll($str, "SessionErrors.log");
		}
		
		$_SESSION['session_last_continue_date'] = SU_DebugTools::GetInternalDate();
		  if ($_SERVER['REMOTE_ADDR']=='195.182.156.98')
  	    SU_DebugTools::LogAll('return true', "SessionErrors.log");		

		return true;
	}	
	
	static public function getSIDByUsernameAndPassword($username, $password)
	{
		return substr(md5($username).md5($password), 9, 32);
	}
	
	static private function _setDomain()
	{
		if(!isset($_SERVER['HTTP_HOST']))
		{
			return;
		}
		if($_SERVER['HTTP_HOST'] == 'game7.destinysphere.ru'){
			ini_set('session.cookie_domain', "destinysphere.ru");
		}elseif($_SERVER['HTTP_HOST'] == 'www.destinysphere.ru'){
			ini_set('session.cookie_domain', "destinysphere.ru");
		}elseif($_SERVER['HTTP_HOST'] == 'destinysphere.ru'){
			ini_set('session.cookie_domain', "destinysphere.ru");
		}elseif($_SERVER['HTTP_HOST'] == 'ds-game.ru'){
			ini_set('session.cookie_domain', "ds-game.ru");
		}elseif($_SERVER['HTTP_HOST'] == 'www.ds-game.ru'){
			ini_set('session.cookie_domain', "ds-game.ru");
		}elseif($_SERVER['HTTP_HOST'] == 'ds.rambler.ru'){
			ini_set('session.cookie_domain', "ds.rambler.ru");
		}elseif($_SERVER['HTTP_HOST'] == 'portal'){
			ini_set('session.cookie_domain', "portal");
		}elseif($_SERVER['HTTP_HOST'] == 'www.destinysphere.lt'){
			ini_set('session.cookie_domain', "destinysphere.lt");
		}elseif($_SERVER['HTTP_HOST'] == 'destinysphere.lt'){
			ini_set('session.cookie_domain', "destinysphere.lt");
		}elseif($_SERVER['HTTP_HOST'] == 'lt.destinysphere.net'){
			ini_set('session.cookie_domain', "destinysphere.lt");
		}
	}
	
	static public function SessionDestroy()
	{

		$_SESSION = array();

		if(isset($_COOKIE[session_name()])) 
		{
			$sido = $_COOKIE[session_name()];
			setcookie(session_name(), '', time()-42000, '/');
		} else if(isset($_REQUEST[session_name()])) {
			$sido = $_REQUEST[session_name()];
			if(strpos($sido, ' ') !== false)
			{
				$sido = substr($sido, 0, strpos($sido, ' '));
			}
		} else {
			$sido = '';
		}

		session_destroy();

		switch (CONSTANT('SESSION_METHOD'))
		{
			case 'db':
				$count = SU_SessionDataBase::session_delete($sido);
				break;
			case 'file':
				$count = SU_SessionFile::session_delete($sido);
				break;
			case 'memC':
				$count = SU_SessionMemC::session_delete($sido);
				break;
		}
	}
	
	static public function haveSession()
	{
		if(isset($_COOKIE[session_name()]))
		{
			$sido = $_COOKIE[session_name()];
		} else if(isset($_REQUEST[session_name()])) {
			$sido = $_REQUEST[session_name()];
			if(strpos($sido, ' ') !== false)
			{
				$sido = substr($sido, 0, strpos($sido, ' '));
			}
		} else {
			return false;
		}
		
		if(!preg_match('/^[a-f0-9]+$/', $sido))
		{
			return false;
		}
		
		switch (CONSTANT('SESSION_METHOD'))
		{
			case 'db':
				$sessionExist = SU_SessionDataBase::sessionExist($sido);
				break;
			case 'file':
				$sessionExist = SU_SessionFile::sessionExist($sido);
				break;
			case 'memC':
				$sessionExist = SU_SessionMemC::sessionExist($sido);
				break;
			default:
				return false;
		}
		
		if(!$sessionExist)
		{
			return false;
		}

		return self::SessionContinue();
	}
	
	
	static public function GetUsersOnline()
	{
		switch (CONSTANT('SESSION_METHOD'))
		{
			case 'db':
				$count = SU_SessionDataBase::countUsersOnLine();
				break;
			case 'file':
				$count = SU_SessionFile::countUsersOnLine();
				break;
			case 'memC':
				$count = SU_SessionMemC::countUsersOnLine();
				break;
		}

		return $count;
	}
}

?>