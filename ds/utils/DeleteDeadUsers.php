<?
ini_set('memory_limit', '128M');
set_time_limit(0);

require_once '../Main.php';
if (VERSION_NAME=="LIMITED") exit;

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
        $DBID = (int)$_REQUEST['dbid']; 
    }
    
    echo "<h1>DELETE DEAD USERS</h1>";
    echo "SELECT SERVER TO WORK: <br>"; 
    foreach($GLOBALS['dbWorldAccess'] as $k=>$v)
    {       
        echo '<a href="?action=scan&dbid='.$k.'">'.$v['server'].' ('.$k.')</a><br>';    
    }
    
    
    if($action == 'scan')
    {
        assert($DBID >= 0);
            
        echo "<xmp>";
        $delete = new SU_DeleteDeadUsers($DBID);
        $delete->Scan();    
        echo "</xmp>";
    }   
}else{
    assert(key_exists('1', $_SERVER['argv']));
    $DBID = (int)$_SERVER['argv'][1];
    assert($DBID >= 0);
    
    $delete = new SU_DeleteDeadUsers($DBID);
    $delete->Scan();    
}

?>