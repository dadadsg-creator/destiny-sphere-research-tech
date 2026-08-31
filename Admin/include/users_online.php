<?php

	require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";


	function getOnlineUsers () {

		$count = 0;
		$dir = constant('SESSION_PATH');
		if (is_dir($dir)) {
    		if ($dh = opendir($dir)) {
        		while (($file = readdir($dh)) !== false) {
        			$count++;
        		}
        		closedir($dh);
        		$count = $count-2;
    		}else{
				$count = 'Error: Cant open dir.';
    			/*Затычка для того чтобы не выдавалась ошибка яваскрипта*///die('Error: Cant open dir.');
			}
		}else{
			$count = 'Error: Is not a dir.';
			/*Затычка для того чтобы не выдавалась ошибка яваскрипта*///die("Error: Is not a dir.");
		}
		
		echo "document.write(\"".$count."\");\n";
		return $count-2;
	}

$usersonline = getOnlineUsers();


?>