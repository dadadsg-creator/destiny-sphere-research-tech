<?php $CVS_Version = '$Revision: 1.3 $ ; $Date: 2007/09/11 08:12:42 $';

ini_set('memory_limit', '512M');
set_time_limit(0);

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

SU_DebugTools::TimerStart('TEST');
echo "<xmp>";
/*********** BEGIN WORK AREA **************/

function StartPatch($DBID, $onlyValidate)
{
    assert(is_bool($onlyValidate));
    
    assert($DBID == 'ALL' || (int)$DBID >= 0);  
        
    assert(key_exists('dbWorldAccess', $GLOBALS));

    if($DBID != 'ALL')
    {
        assert(key_exists($DBID, $GLOBALS['dbWorldAccess']));
    }
    
    if($DBID == 'ALL')
    {
        foreach ($GLOBALS['dbWorldAccess'] as $k=>$v)
        {       
            Putch($k, $onlyValidate);
        }       
    }else{
        Putch($DBID, $onlyValidate);
    }
}

function Putch($DBID, $onlyValidate)
{
    assert(is_bool($onlyValidate)); 
    assert($DBID == 'ALL' || (int)$DBID >= 0);
    
    $common = DS_Common::CreateForCurentDB($DBID);
    $patchDB = new MP_PatchDB($common);
    MP_Patch::$onlyValidate = $onlyValidate;
    $patchDB->patch();  
}


function FirstScreen()
{
    echo '</xmp>';
    echo '<h1>MegaPatch</h1>';
    echo '<table width=300 border=1>';
    
    echo '<tr>';
    echo '<th>Server</th><th>Mode</th>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td>&nbsp;All servers</td><td align=center> <a href="?action=validate&dbid=ALL">VALIDATE</a> | <a href="?action=correct&dbid=ALL">CORRECT</a></td>';
    echo '</tr>';
    
    foreach($GLOBALS['dbWorldAccess'] as $k=>$v)
    {
        echo '<tr>';
        echo '<td>&nbsp;'.$v['server'].'</td><td align=center> <a href="?action=validate&dbid='.$k.'">VALIDATE</a> | <a href="?action=correct&dbid='.$k.'">CORRECT</a></td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</xmp>';
}


$sapi = php_sapi_name();
if($sapi == "apache")
{
    if(isset($_REQUEST['action']))
    {
        $action = $_REQUEST['action'];
    }else{
        $action = '';
    }

    if(isset($_REQUEST['dbid']))
    {
        $dbid = (int)$_REQUEST['dbid'];
    }else{
        $dbid = 0;
    }
    
    switch ($action)
    {
        case '':
            FirstScreen();
            break;
        
        case 'validate':
            StartPatch($dbid, true);
            break;
    
        case 'correct':
            StartPatch($dbid, false);
            break;
    
        default: 
            FirstScreen();
    }   
}else{
    
    assert(key_exists('1', $_SERVER['argv']));
    $DBID = $_SERVER['argv'][1];
    
    StartPatch($DBID, false);
}


/*********** END WORK AREA **************/
echo "</xmp>";
echo "<hr>WORK AREA TIME: ".SU_DebugTools::TimerStop('TEST');

?>