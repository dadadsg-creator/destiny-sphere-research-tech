<?php $CVS_Version = '$Revision$ ; $Date$';

ini_set('memory_limit', '16M');
set_time_limit(300);


$sapi = php_sapi_name();
if( $sapi != "apache2handler" )
{
    if( isset( $argv[1] ) && !empty( $argv[1] ) )
    {       
        $_REQUEST['action'] = $argv[1];
        $_SERVER['DOCUMENT_ROOT'] = $argv[2];
    }else{

        exit();
    }
}

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

/*********** WORK AREA **************/

if(constant('CACHE_SYSTEM_NAME') == 'DBA')
{
    $cache = new DS_CacheControlDBA(constant('DB_CACHE_PATH')); 
    echo $cache->main($_REQUEST);
}else{
    
    echo "<h1>MemCache statistic</h1>";
    
    $memcache = new Memcache;
    assert($memcache->connect(constant('MEM_CACHE_HOST'), 11211));
    
    if(@$_REQUEST['action'] == 'reset')
    {
        $memcache->flush();
        
        echo "Reset reset complit.<br><br>";
    }
    
    
    $a = $memcache->getStats();
    
    echo "<xmp>";
    print_r($a);
    echo "</xmp>";
    
    echo "<a href=?action=reset>EXEC all cache data reset!</a>";
}

?>