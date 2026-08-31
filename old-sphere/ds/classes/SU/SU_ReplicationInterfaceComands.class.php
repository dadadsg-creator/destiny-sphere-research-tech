<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Общение с процессом репликации
 *
 */
class SU_ReplicationInterfaceComands
{
	static private $socket;
	
	static public $error;
	
	static private function _init()
	{
		if(!is_resource(self::$socket))
		{
			self::$socket = @stream_socket_client(SU_ReplicationControl::GetFullControlConnectionAddress(), $errno, $errstr);
			if(!self::$socket) 
			{
				self::$error = "ERROR: $errno - $errstr<br />\n";
				return false;
			}
		}
		
		return true;
	}
	
	static public function SendCommandToReplication($command)
	{
		if(!self::_init())
		{
			return false;
		}
		
		// команды пока из одной буквы
		assert(strlen($command) == 1);
		
		fwrite(self::$socket, $command);
		
		$res = self::_getAnswer();
		
		self::CloseConnection();
		
		return $res;
	}
	
	static private function _getAnswer()
	{
		$res = '';
		
		while(!feof(self::$socket)) 
		{
			$buf = fread(self::$socket, 1024);
			$res .= $buf;
		}
		
		return $res;
	}
	
	static public function CloseConnection()
	{
		fclose(self::$socket);
	}
}
?>