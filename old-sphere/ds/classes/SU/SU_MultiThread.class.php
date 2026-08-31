<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_MultiThread
{	
	static public function Exec($params)
	{
		assert(is_array($params));
		assert(defined('PHP_PATH'));
		
		echo "<xmp>";
		foreach($params as $k => $param)
		{
			echo date("H:i:s")." - THREAD $k STARTED\n";
			flush();
			exec("daemon -Ul --user=root --chdir=/www/htdocs/portal/ds ".constant('PHP_PATH')." -f ./test_thread.php $param");
			echo date("H:i:s")." - THREAD ENDED\n";
			flush();
		}
		echo "</xmp>";
	}
	
	static public function ExecThread()
	{
		$sapi = php_sapi_name();
		if($sapi == "apache")
		{
			return;			
		}
		
		assert(key_exists(1, $_SERVER['argv']));
		$exec = $_SERVER['argv'][1];
		assert(!empty($exec));
		
		if(key_exists(2, $_SERVER['argv']))
		{
			$params = $_SERVER['argv'][2];
		} else {
			$params = '';
		}
		
		exec("deamon ".constant('PHP_PATH')." -f ".$exec." ".$params);
	}
}
?>