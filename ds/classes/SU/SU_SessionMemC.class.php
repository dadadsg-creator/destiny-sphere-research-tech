<?php

class SU_SessionMemC implements SU_SessionInterface
{
	/**
	 * Атрибут для реализации синглетона
	 *
	 * @var 
	 */
	static private $instance;
	
	static public function Singleton()
	{
		if(!(self::$instance instanceof SU_SessionMemC))
		{
			self::$instance = new SU_SessionMemC();
		} 
				
		return self::$instance;
	}
	
	function __construct()
	{
		
	}
	
	public function session_open()
	{
		
	}	
	public function session_close()
	{
		
	}	
	public function session_read($sid)
	{
		
	}	
	public function session_write($sid, $data)
	{
		
	}	
	public function session_gc()
	{
		
	}	
	static function session_delete($id)
	{
		if(file_exists(session_save_path().'/sess_'.session_id()))
		{
			unlink(session_save_path().'/sess_'.session_id());
		}
	}
	
	static function countUsersOnLine()
	{
		
	}
	
	static function sessionExist($sido)
	{
		//$memcache = new Memcache;
		//$memcache->connect('localhost', 11211) or die ("Could not connect");

		if(file_exists($mySessionFile))
		{
			if(!session_id())
			{
				return true;
			}else {
				return false;
			}
		} else {
			$str  = "Cant find session store\n";
			$str .= "FILE_NAME = ".$mySessionFile."\n";
			$str .= "START_DATE = ".@$_SESSION['session_start_date']."\n";
			$str .= "LAS_CONTINUE_DATE = ".@$_SESSION['session_last_continue_date']."\n";

			SU_DebugTools::LogAll($str, "SessionErrors.log");

			return false;
		}
	}
}
?>