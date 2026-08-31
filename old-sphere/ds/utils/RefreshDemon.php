<?php $CVS_Version = '$Revision$ ; $Date$';
ini_set('memory_limit', '1024M');
set_time_limit(3540);

$sapi = php_sapi_name();

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

//Регистрация своего обработчикка ошибок
//set_error_handler("DS_ErrorHandler");

//Регитрация финализатора
register_shutdown_function ('finilazer');

define('DEBUG_MODE', 1);

$_SESSION["dbId"] = 0;
$_SESSION["lang"] = 3;
$_SESSION["world"] = 2;

SU_DebugTools::TimerStart('TEST');
/*********** BEGIN WORK AREA **************/

define('MAX_DB_DEMONS', 10);

function StartPatch($DBID, $demonID)
{   
    $scaner = new RD_RefreshDemon((int)$DBID, $demonID);
    $scaner->startScan();
}

function AddRDActionFile($DBID, $action, $demonID = 0)
{
    if($demonID > 0)
    {
        $demonIDPart = '_'.$demonID;
    } else {
        $demonIDPart = '';
    }
    $fp = fopen("./files/RD_".$DBID."_".$action.$demonIDPart, "w+");
    fclose($fp);
}

function ClearRDActionFile($DBID, $action, $demonID = 0)
{
    if($demonID)
    {
        $demonIDPart = '_'.$demonID;
    } else {
        $demonIDPart = '';
    }
    
    $fName = "./files/RD_".$DBID."_".$action.$demonIDPart;
    if(file_exists($fName))
    {
        unlink($fName);
    }
}

function FirstScreen()
{
    echo "</xmp> <h1>REFRESH DEMON</h1>";
    
    echo "Select server for work:<br>";
    
    echo '<TABLE cellspacing=0 celpadding=0 border=1 width="70%">';
    echo '<TR><TD align="center">db name</TD><TD align="center">db id</TD><TD align="center">stop file</TD><TD align="center">block file</TD><TD>&nbsp;</TD><TD align="center">alive</TD></TR>';
    
    foreach($GLOBALS['dbWorldAccess'] as $k=>$v)
    {           
        $aliveDemon = array();
        $blockFiles = array();
        $stopFiles = array();
        
        for($i=0; $i<constant('MAX_DB_DEMONS'); $i++)
        {
            if(file_exists("./files/RD_".$k."_ALIVE_".$i))
            {
                if(filemtime("./files/RD_".$k."_ALIVE_".$i) > (time() - 3*60))
                {
                    $aliveDemon[$i] = true;
                } else {
                    $aliveDemon[$i] = false;
                }
            }
            
            if(file_exists("./files/RD_".$k."_BLOCK_".$i))
            {
                $blockFiles[$i] = true;
            }
            
            if(file_exists("./files/RD_".$k."_STOP_".$i))
            {
                $stopFiles[$i] = true;
            }
        }
        
        echo '<TR><TD align="center">'.$v['server'].'</TD><TD align="center">'.$k.'</TD></TD><TD align="center">';
        //$stopped =  ? "1" : "0";
        if(file_exists("./files/RD_".$k."_STOP"))
        {
            $stopped = 'ALL DEMONS : <FONT color="red"><B>yes</B></FONT>';
            $stopControl = '<A href="?action=clearstop&dbid='.$k.'&demonid=0">delete</A>';
        } else {
            $stopped = "ALL DEMONS : no";
            $stopControl = '<A href="?action=addstop&dbid='.$k.'&demonid=0">add</A>';
        }
        $stopControl = $stopped." : ".$stopControl."<br>";
        
        foreach($aliveDemon as $demonID => $alive)
        {
            if($demonID == 0)
            {
                continue;
            }
            if(key_exists($demonID, $stopFiles) && $alive)
            {   
                $stopControl .= 'demon '.$demonID.' : <FONT color="red"><B>yes</B></FONT>';
                $stopControl .= ' : <A href="?action=clearstop&dbid='.$k.'&demonid='.$demonID.'">delete</A><br>';
            } else {
                $stopControl .= 'demon '.$demonID." no";
                $stopControl .= ' : <A href="?action=addstop&dbid='.$k.'&demonid='.$demonID.'">add</A><br>';
            }
        }
        
        echo $stopControl;
        
        echo '</TD><TD align="center">';
        //$blocked =  ? "1" : "0";
        if(file_exists("./files/RD_".$k."_BLOCK"))
        {
            $blocked = 'ALL DEMONS : <FONT color="red"><B>yes</B></FONT>';
            $blockControl = '<A href="?action=clearblock&dbid='.$k.'&demonid=0">delete</A><br>';
        } else {
            $blocked = "ALL DEMONS : no";
            $blockControl = '<A href="?action=addblock&dbid='.$k.'&demonid=0">add</A><br>';
        }       
    
        echo $blocked." : ".$blockControl;
        
        $blockControl = '';
        
        foreach($aliveDemon as $demonID => $alive)
        {
            if($demonID == 0)
            {
                continue;
            }
            if(key_exists($demonID, $blockFiles) && $alive)
            {   
                $blockControl .= 'demon '.$demonID.' : <FONT color="red"><B>yes</B></FONT>';
                $blockControl .= ' : <A href="?action=clearblock&dbid='.$k.'&demonid='.$demonID.'">delete</A><br>';
            } else {
                $blockControl .= 'demon '.$demonID." no";
                $blockControl .= ' : <A href="?action=addblock&dbid='.$k.'&demonid='.$demonID.'">add</A><br>';
            }
        }
        
        echo $blockControl;
        
        $isAlive = '';
        if(count($aliveDemon) > 0)
        {   
            foreach($aliveDemon as $id => $working)
            {
                if($working)
                {
                    $isAlive .= 'Demon '.$id.' <FONT color="green"><B>working</B></FONT><br>';
                } else {
                    $isAlive .= 'Demon '.$id.' <FONT color="red"><B>inactive</B></FONT><br>';
                }
            }
        } else {
            $isAlive .= '<B>stopped</B>';
        }
        echo '</TD><TD align="center"><a href="?action=start&dbid='.$k.'" target="_blank">start demon</a></TD><TD align="center">'.$isAlive.'</TD></TR>';
        //echo '<a href="?action=start&dbid='.$k.'">'.$v['server'].'('.$k.')</a><br>';
    }
    
    echo "</TABLE>"; 
    
}



//Решаем что делать
if(preg_match("/^apache/", $sapi)) {
    echo "<xmp>";
    
    if(key_exists('dbid', $_REQUEST))
    {
        $DBID = (int)$_REQUEST['dbid'];
    }else{
        $DBID = 0;
    }   
    
    if(key_exists('demonid', $_REQUEST))
    {
        $DemonID = (int)$_REQUEST['demonid'];
    }else{
        for($i=0; $i<constant('MAX_DB_DEMONS'); $i++)
        {
            if(!file_exists("./files/RD_".$DBID."_ALIVE_".$i))
            {
                $DemonID = $i;
                break;
            }
        }
    }
    
    if(isset($_REQUEST['action']))
    {
        $action = $_REQUEST['action'];
    }else{
        $action = "";
    }

    switch ($action)
    {
        case '':
            FirstScreen();
            break;
        case 'addstop':
            AddRDActionFile($DBID, "STOP", $DemonID);
            FirstScreen();
            break;      
        case 'clearstop':
            ClearRDActionFile($DBID, "STOP", $DemonID);
            FirstScreen();
            break;
        case 'addblock':
            AddRDActionFile($DBID, "BLOCK", $DemonID);
            FirstScreen();
            break;
        case 'clearblock':
            ClearRDActionFile($DBID, "BLOCK", $DemonID);
            FirstScreen();
            break;
        case 'start':
            StartPatch($DBID ,$DemonID);
            break;      
        default: 
            FirstScreen();
    }   
    
    echo "</xmp>";
    echo "<hr>WORK AREA TIME: ".SU_DebugTools::TimerStop('TEST');
}else{
    if(key_exists(1, $_SERVER['argv']))
    {
        $DBID = (int)$_SERVER['argv'][1];
    } else {
        $DBID = 0;
    }

    if(key_exists(2, $_SERVER['argv']))
    {
        $DemonID = (int)$_SERVER['argv'][2];
    } else {
        $DemonID = 0;
    }
    
    StartPatch($DBID, $DemonID);
}

/*********** END WORK AREA **************/


$GLOBALS['CorrectExit'] = 1;
$GLOBALS['HaveErrors'] = 0;

GB_SingletonObjectStorage::CommitAllCommitable();
?>