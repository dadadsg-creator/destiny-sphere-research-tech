<?php
 require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/IncludeManager.class.php";
 $inc = new IncludeManager();
 
 $db = new DBinterface (	constant('DB_HOST'),
    						constant('DB_USER'),
                            constant('DB_PASS'),
                            constant('DB_NAME'));

 $sql = "SELECT
 			ID,
  			ShortName ,
 			CacheLifeTime
 		 FROM Pages
 		 WHERE 
 			RedirectURL = '' AND
 			CacheLifeTime > 0
 			";
 $rs = $db->query($sql);
 
 if(is_array($rs))
 {
 	foreach($rs as $k=>$v)
 	{
 		$file[] = $v['ID'].";".$v['ShortName'].";".$v['CacheLifeTime'];
 	}
 }
 
 $str = implode ("\r\n",$file);
 $filePath = constant('CACHE_PAGES_INFO_FILE');
 @unlink($filePath);
 error_log($str,3,$filePath);


?>