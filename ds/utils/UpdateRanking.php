<?php
ini_set('memory_limit', '128M');
set_time_limit(0);

require_once '../Main.php';
require_once constant('INCLUDE_PATH').'/GameDown.inc.php';


//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');
define('DEBUG_MODE', 1);
/*********** BEGIN WORK AREA **************/

$sapi = php_sapi_name();
if($sapi == "apache2handler")
{
    $action = '';
    if(key_exists('action', $_REQUEST))
    {
        $action = $_REQUEST['action'];  
    }
    
    $DBID = 0;
    if(key_exists('dbid', $_REQUEST))
    {
        $DBID = $_REQUEST['dbid'];  
    }
    
    echo "<h1>UPDATE RANKING</h1>";
    echo "SELECT SERVER TO WORK: <br>"; 
    foreach($GLOBALS['dbWorldAccess'] as $k=>$v)
    {       
        echo '<a href="?action=update&dbid='.$k.'">'.$v['server'].' ('.$k.')</a><br>';  
    }   
    

    if($action == 'update')
    {
        assert($DBID >= 0); 
        echo "<xmp>";
        $u = new SU_UpdateRanking((int)$DBID);
        $u->Update();
        echo "</xmp>";
    }   
}else{
    assert(key_exists('1', $_SERVER['argv']));
    $DBID = (int)$_SERVER['argv'][1];
    assert($DBID >= 0);
    
    $u = new SU_UpdateRanking((int)$DBID);
    $u->Update();
}

?>