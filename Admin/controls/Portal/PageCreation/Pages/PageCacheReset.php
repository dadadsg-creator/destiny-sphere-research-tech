<?php

function pageCacheReset($pageID){
	$file = file(constant('CACHE_PAGES_INFO_FILE'));
	
	$pageName = '';
	if(is_array($file))
	{
		foreach($file as $k=>$v)
		{
			$page = explode(';', $v);
			if($pageID == $page[0]){								
				$pageName = $page[1];
				
				break;
			}
		}
	}
	
	if(!$pageName){
		$message_clear_cache_error = "Кэширования для страницы <b><i>\"".$pageID."\"</i></b> необнаруженно.<br>";
		ShowCMSMessage($message_clear_cache_error, 2);
//		echo "<br>ВНИМАНИЕ!<br> Кэширования для страницы <b>".$pageID."</b> необнаруженно.<br>";
		return;
	}
	
	$deleted = 0;
	if ($handle = opendir(constant('CACHE_STORE_PATH'))) 
	{
		while (false !== ($file = readdir($handle))) 
		{ 
	    	if ( $file == '.'  || $file == '..')
			{
	    		continue;
	    	}
			
			if ( !file_exists ( constant('CACHE_STORE_PATH').'/'.$file ) ) 
			{
				continue;
			}
			
			if (ereg('PAGE_'.$pageName, $file)) 
			{
				$result = unlink ( constant('CACHE_STORE_PATH').'/'.$file );
				$deleted++;
			}
			
	    }
	    closedir($handle); 
	}
	
	$message_clear_cache = "Кеш для страницы <b><i>\"".$pageName."'\"</i></b> обнавлен. Удалено (".$deleted.") файлов кеша.<br>";
	ShowCMSMessage($message_clear_cache, 1);
//	echo "<br>ВНИМАНИЕ!<br> Кеш для страницы <b>".$pageName."</b> обнавлен. Удалено (".$deleted.") файлов кеша.<br>";
	
}//END OF FUNCTION 

pageCacheReset($_REQUEST['ID']);

?>
