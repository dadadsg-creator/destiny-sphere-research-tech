<?php


class GK_Logger
{
	static public function Error(Exception $e, $withExit = true)
	{
		$msg = "\n===================================";
		$msg .= "\n".date("Y-d-m H:i:s")." - EXCEPTION";
		$msg .= "\n===================================";
		$msg .= "\nFILE: ".substr($e->getFile(), -40)." LINE: ".$e->getLine();
		$msg .= "\n\nMESSAGE: ".$e->getMessage();
		$msg .= "\n\nTRACE: \n".$e->getTraceAsString();
		
		if(!file_exists(constant('GATE_KEY_LOG_FILE')))
		{
			touch(constant('GATE_KEY_LOG_FILE'));
			chmod(constant('GATE_KEY_LOG_FILE'), 0777);
		}

		error_log($msg, 3, constant('GATE_KEY_LOG_FILE'));
		
		if($withExit)
		{
			if(constant('GATE_KEY_DEBUG'))
			{
				die("<pre>".$msg."</pre>");
			}
			
			die('EXCEPTION CAUGHT');
		}
	}
}

?>