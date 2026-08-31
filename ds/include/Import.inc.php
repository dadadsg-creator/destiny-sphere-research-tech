<?php $CVS_Version = '$Revision: 1.4 $ ; $Date: 2007/11/19 14:32:39 $';

/** 
 * Метод подключает описание класса
 * Параметры: 
 * $class - имя класса
 */
function import ($class)
{
    
    static $summaryTime = 0;
    static $counter = 0;
    
    //$startTime = getMicroTime();
    
    assert(defined('CLASS_PATH'));
    assert(is_dir(constant('CLASS_PATH')));
    assert(!empty($class));
        
    $className = explode('_', $class);

    if ($className[0]=='Smarty') { 
        smartyAutoload ($class);
        return;
    }
    
    if ( array_key_exists(1, $className) ) {
        $fileName = constant ( 'CLASS_PATH' )."/".$className[0]."/".$class.".class.php";
    }else{
        $fileName = constant ( 'CLASS_PATH' )."/".$class.".class.php";
    }
    
    if(!file_exists($fileName))
    {
        ds_error('Cant find file for class '.$class);
    };
    
    require_once $fileName;
    
    /*$endTime = getMicroTime();            
    $time = $endTime-$startTime;
    $summaryTime = $summaryTime + $time;
    
    //GP_SystemStatistic::DeltaParam('IMPORT_FILES', 'IMPORT_FILES_COUNT', 1);
    //GP_SystemStatistic::SetParam('IMPORT_FILES', 'SUMMARY_IMPORT_TIME', $summaryTime);
    
    $msg  = "-------------".date('Y-m-d H:i:s')." ".$_SERVER['PHP_SELF']."-------------\n";
    $msg .= $fileName."\n\n";
    $msg .= "Counter =  ".$counter++."\n";
    $msg .= "ThisTime =  ".$time."\n";
    $msg .= "SummaryTime ".$summaryTime."\n\n";
        
    if(!stristr($_SERVER['PHP_SELF'], 'LogView.php'))
    {
//      error_log($msg, 3, constant('DEBUG_LOGS')."/Import.log");
    }*/
    
}// END OF FUNCTION import ()
    
/**
 * Метод полдключает сразу все опсания из пакета
 * Параметры:
 * $pack - имя пакета
 */
function importPack ( $pack ){
    assert ( defined ( 'CLASS_PATH' ) );
    assert ( is_dir  ( constant ('CLASS_PATH') ) );
    
    if( empty ( $pack ) ){
        trigger_error("Empty pack name for import!", E_USER_ERROR);         
    }
    
    $dir = constant ( 'CLASS_PATH' )."/".$pack;
    
    if( is_dir ( $dir ) ){
        trigger_error("Uncorrect dir ".$dir, E_USER_ERROR);         
    }
    
    if (!($handle = opendir('/path/to/files'))) {
        trigger_error("Cant open dir for pack ".$pack, E_USER_ERROR);           
    }
    
    while (false !== ($file = readdir($handle))) { 
        require_once $file;
    }

    closedir($handle); 
}// END OF FUNCTION importPack ()


function __autoload($className) 
{
    import ($className);
}

?>