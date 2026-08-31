<?php
session_start();

$count = 0;

$dir = session_save_path();

$data_arr = array();

if(isset($_REQUEST['get_sid']))
{
	$sid = $_REQUEST['get_sid'];
	
	$sess_data = file_get_contents($dir.'/sess_'.$sid);
	
	$_SESSION = array();
   					
   	session_decode($sess_data);
	
   	echo "<xmp>";
   	print_r($_SESSION);
   	echo "</xmp>";   	
} elseif(is_dir($dir)) {
	if($dh = opendir($dir))
	{
   		while(false !== ($file = readdir($dh)))
   		{
   			if(!is_dir($dir.'/'.$file))
   			{
   				if(ereg('sess_', $file))
   				{
   					$sess_data = file_get_contents($dir.'/'.$file);
  					
   					$_SESSION = array();
   					
   					$arr = explode('_', $file);
   					
   					$sid = $arr[1];
   					
   					session_decode($sess_data);
   										
   					if(key_exists('session_start_date', $_SESSION))
					{
						$user = '';
						if(key_exists('s_login', $_SESSION))
						{
							$user = $_SESSION['s_login'];
						} elseif(key_exists('USERNAME', $_SESSION)) {
							$user = $_SESSION['USERNAME'];
						} 
						
						if(key_exists('uid', $_SESSION)) {
							$user .= ' ('.$_SESSION['uid'].')';
						}

						$startTime = strtotime($_SESSION['session_start_date']);
						
						if((time() - $startTime) > 5*3600)
						{
							$data_arr[$startTime] = $_SESSION['session_start_date'].' | '.$user.' | <a href="sess_start.php?get_sid='.$sid.'">check</a>';
						}
   					}
   					$count++;
   				}
   			}
		}
		closedir($dh);
        $count = $count-2;
    } else {
		$count = 'Error: Cant open dir.';    			
	}
	
	asort($data_arr);

	echo "<pre>";
	print_r($data_arr);
	echo "</pre>";
	echo 'Count: '.$count;
}


?>