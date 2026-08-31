<?php
	
// включаем убийство сессии в случае assert или ds_error
$GLOBALS['HI_SECURITY_FLAG'] = 0;

require_once ('Main.php');
require_once (constant('INCLUDE_PATH').'GameDown.inc.php');

if(GameDown())
{
	Header("Status: 302 Moved Temporaly");
	Header("Location: index_down.html");
	exit();
}

SU_GameSessionWork::SessionContinue();

GE_DisciplinaryRequest::Exec($_REQUEST);
?>