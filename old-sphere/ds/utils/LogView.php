<?
 ini_set('memory_limit', '32M');
 session_start();

 require_once '../Main.php';
 include('../classes/SU/SU_LogView.class.php');

assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

$pathes = array(
    1 => $GLOBALS['LOGING_DEBUG_LOGS_PATH']['MAIN'],
    2 => $GLOBALS['LOGING_DEBUG_LOGS_PATH']['SLAVE'],
    3 => $GLOBALS['LOGING_DEBUG_LOGS_PATH']['BACKUP'],
    3 => $GLOBALS['LOGING_DEBUG_LOGS_PATH']['CLASSIC'],
    4 => constant('LOGING_SYSTEM_ERROR_LOGS_PATH'),
    //5 => constant('LOGING_BUG_TRACE_PATH')
);

$logView = new SU_LogView($pathes);
$logView->main($_REQUEST);

?>