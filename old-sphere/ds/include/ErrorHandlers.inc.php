<?PHP

function getCode($file, $line)
{	
	if(!constant('LOGING_SORCE_CODES'))
	{
		return "Sorce codes blocked by config settings";
	}
	
	if(!file_exists($file))
	{
		return'Cant find file\n';
	}
	
	$code = file($file);
	
	$delta = 15;
	$out = array();
	
	for($i=$line-5; $i<=$line+5; $i++)
	{
		if($i>0 && array_key_exists($i, $code))
		{
			if($i+1 == $line)
			{
				$prefix = ">>>";
			}else{
				$prefix = "";
			}
			$out[] = $prefix.$code[$i];
		}		
	}
	
	return implode("", $out);
}

function DS_AssertHandler($file, $line, $code)
{
	$errorText = "Assert failed in $file ($line)";
	$phpCode = getCode($file, $line);
	
	$out  = "ASSERT ERROR\n\n";
	$out .= $errorText."\n\n";	
	
	$out .= "Code:\n";	
	$out .= $phpCode."\n\n";

	if(constant('LOGING_SYSTEM_ERRORS_FLAG'))
	{
		$fullText = SU_DebugTools::LogAll($out, 'SystemErrors.log', 1, 1);
		
		if(constant("LOGING_BUG_TRACE_FLAG"))
		{	
			ExportErrorData('ASSERT', $errorText, $fullText, $phpCode);
		}
	}
	
	if(defined('DEBUG_MODE'))
	{
		$debugMode = constant('DEBUG_MODE');
	}else{
		$debugMode = 0;
	}
	
	
	$file2 = substr($file, strrpos($file, "/")+1);
	$str = date('Y-m-d H:i:s').' ERROR! Type: ASSERT File: '.$file2.' Line: '.$line;
	if(key_exists('client_key', @$_SESSION))
	{	
		$sessionClientKey = $_SESSION['client_key'];
	} else {
		$sessionClientKey = '';
	}
	
	if(key_exists('HI_SECURITY_FLAG', $GLOBALS) && $GLOBALS['HI_SECURITY_FLAG'] == 1)
	{
		GE_Login::execLogout();
	}
	die('&xmlerror=<error y="ASSERT">'.$str.'</error>&ck='.$sessionClientKey."&");		
}


/** Обработчик Ошитбко PHP */
function DS_ErrorHandler($errno, $errmsg, $file, $line) 
{
	//Затыка для того тчо бы пропустиьт кривые ошибки
	if(ereg('assert code', $file) || ereg('Query failed: ERROR:', $errmsg)) 
	{
		return;
	}

	if(key_exists("DS_ERROR_HANDLER_STOP", $GLOBALS))
    {
    	if($GLOBALS["DS_ERROR_HANDLER_STOP"] == true)
    	{
    		$GLOBALS["DS_ERROR_HANDLER_STOP"] = false;
    		return;
    	}
    }
	
	 $errorTypes = array (
                E_ERROR           => "E_ERROR",
                E_WARNING         => "E_WARNING", // --
                E_PARSE           => "E_PARSE",
                E_NOTICE          => "E_NOTICE",
                E_CORE_ERROR      => "E_CORE_ERROR",
                E_CORE_WARNING    => "E_CORE_WARNING",
                E_COMPILE_ERROR   => "E_COMPILE_ERROR",
                E_COMPILE_WARNING => "E_COMPILE_WARNING",
                E_USER_ERROR      => "E_USER_ERROR",
                E_USER_WARNING    => "E_USER_WARNING",
                E_USER_NOTICE     => "E_USER_NOTICE",
                E_STRICT          => "E_STRICT"
    );
	
    $noStopErrorTypes = array("E_WARNING", "E_NOTICE", "E_STRICT");
    
    $errorType = $errorTypes[$errno];    
    
    //Здесь смотрим не включен ли сейчас флаг отключени яобрбаотки ошибок
    if(key_exists("DS_ERROR_HANDLER_STOP_".$errorType, $GLOBALS))
    {
    	if($GLOBALS["DS_ERROR_HANDLER_STOP_".$errorType] == true)
    	{
    		$GLOBALS["DS_ERROR_HANDLER_STOP_".$errorType] = false;
    		return;
    	}
    }
    
    $phpCode = getCode($file, $line);
    
	$str = "[$errorType] $errmsg $file   ($line)";

	$out = "PHP ERROR\n\n";
	$out .= "$str\n\n";			
	
	$out .= "Code:\n";	
	$out .= $phpCode."\n\n";

	if(constant('LOGING_SYSTEM_ERRORS_FLAG'))
	{
		$fullText = SU_DebugTools::LogAll($out, 'SystemErrors.log', 1, 1);

		if(constant("LOGING_BUG_TRACE_FLAG"))
		{	
			ExportErrorData('PHP_ERROR', $str, $fullText, $phpCode);			
		}
	}
	
	if(!in_array($errorType, $noStopErrorTypes))
	{
		if(key_exists('client_key', @$_SESSION))
		{	
			$sessionClientKey = $_SESSION['client_key'];
		} else {
			$sessionClientKey = '';
		}
		
		die('&xmlerror=<error y="0">'.$str.'</error>&ck='.$sessionClientKey."&");
	}	
}//END OF FUNCTION 

/** 
 * Обрабатываем случаи когда по локингу надо прервать выполнение
 * */
function DS_ClientLockHandler($lockReason, $lockTime, $mark, $lockObjectName)
{
	assert(!empty($lockReason));	
	assert(!empty($mark));
	assert(!empty($lockObjectName));
	assert(is_int($lockTime) && $lockTime>0);
	
	$locker = SU_Locker::CerateForCurrentDB($lockObjectName);
	
	$out  = "$lockReason => $lockTime\n";
	$out .= var_export($locker->getLockInfo($mark), 1);
	
	SU_DebugTools::LogAll($out, 'UserLockingErrors.log', 1, 1);
	
	if(key_exists('client_key', @$_SESSION))
	{	
		$sessionClientKey = $_SESSION['client_key'];
	} else {
		$sessionClientKey = '';
	}
	
	// скрипт уже умрет через строчку и все транзакции накроются, поэтому текущая транзакция на мастер, 
	// если она есть, откатывается
	DS_Misc::LogLockerError($lockReason, $lockTime, $info);
	
	die("&overload=$lockReason|$lockTime&ck=".$sessionClientKey."&");	
}

/** 
 * 
 * */
function DS_DBTimeOutHandler()
{		
	if(key_exists('client_key', @$_SESSION))
	{	
		$sessionClientKey = $_SESSION['client_key'];
	} else {
		$sessionClientKey = '';
	}
	
	die("&overload=DB_TIME_OUT|30&ck=".$sessionClientKey."&");	
}


/** Функция обработчик ошибок в игре */
function DS_Error($str, $logIt = true, $witExit = true, $withOutput = true, $isUserMessage = false)
{
	
	$out = "SYSTEM ERROR\n\n";
	$out .= "$str\n\n";			
	
	if(constant('LOGING_SYSTEM_ERRORS_FLAG') && $logIt)
	{
		$fullText = SU_DebugTools::LogAll($out, 'SystemErrors.log', 1, 1);
		
		if(constant("LOGING_BUG_TRACE_FLAG"))
		{	
			ExportErrorData('DS_ERROR', $str, $fullText, '');
		}
	}
	
	if(key_exists('HI_SECURITY_FLAG', $GLOBALS) && $GLOBALS['HI_SECURITY_FLAG'] == 1)
	{
		GE_Login::execLogout();
	}
	
	if($withOutput)
	{
		if(key_exists('client_key', @$_SESSION))
		{	
			$sessionClientKey = $_SESSION['client_key'];
		} else {
			$sessionClientKey = '';
		}
		
		if($isUserMessage)
		{
			die('&xmlerror=<error y="MESSAGE">'.$str.'</error>&ck='.$sessionClientKey."&");
		}else{
			if($witExit)
			{
				die('&xmlerror=<error y="ERROR">'.$str.'</error>&ck='.$sessionClientKey."&");
			}else{
				echo('&xmlerror=<error y="WARNING">'.$str.'</error>&');
			}	
		}
	}	
}

function ExportErrorData($errorType, $errorText, $fullText, $phpCode)
{
	//ЗАкрылди это дело так как оно вызывает обрушение ПХП периодически
	return; 
	
	if(!isset($_SESSION))
	{
		$_SESSION = array();
	}
	
	if(!isset($_REQUEST))
	{
		$_REQUEST = array();
	}
	
	if(!key_exists('dbWorldAccess', $GLOBALS))
	{
		$GLOBALS['dbWorldAccess'] = array();
	}
	
	if(key_exists("dbId", $_SESSION))
	{
		$dbId = $_SESSION["dbId"];
	}else{
		$dbId = 0;
	}	
		
	
	//Дата ошибки
	$out[] = date('Y-m-d H:i:s');
	
	//Имя пользователя
	if(key_exists('USERNAME', $_SESSION))
	{
		$out[] = $_SESSION['USERNAME'];
	}else{
		$out[] = 'EMPTY_USERNAME';
	}
		
	//UIID
	if(key_exists('uiid', $_SESSION))
	{
		$out[] = $_SESSION["uiid"];
	}else{
		$out[] = 'EMPTY_UIID';
	}

	//WORLD ID
	if(key_exists('world', $_SESSION))
	{
		$out[] = $_SESSION["world"];
	}else{
		$out[] = 'EMPTY_WORLD_ID';
	}
		
	//DB Server Name
	if(key_exists($dbId, $GLOBALS['dbWorldAccess']))
	{
		if(key_exists('server', $GLOBALS['dbWorldAccess'][$dbId]))
		{			
			$dbName = $GLOBALS['dbWorldAccess'][$dbId]['server'];
		}else{
			$dbName = 'EMPTY_DB_NAME';
		}
	}else{
		$dbName = 'EMPTY_DB_NAME';
	}
	
	$out[] = $dbName;
	
	//Error type
	$out[] = $errorType;
	
	//Error text
	$out[] = $errorText;
	
	//PHP Code
	$out[] = $phpCode;
	
	//BackTrace
	$out[] = SU_DebugTools::BackTrace(2);
	
	if(function_exists('decode') && key_exists('xmldata', $_REQUEST))
	{
		//XML Data
		$out[] = decode($_REQUEST['xmldata']);
	}else{
		//XML Data
		$out[] = '';
	}
		
	//FULL text
	$out[] = $fullText;
	
	$outString = implode('|&&&&&|', $out);
	$outString = str_replace("\r", "", $outString);
	$outString = str_replace("\n", "<br>", $outString);
	
	$outString .= "\r\n";
	
	error_log($outString, 3, constant("LOGING_BUG_TRACE_PATH")."SystemErrors.csv");
}

?>