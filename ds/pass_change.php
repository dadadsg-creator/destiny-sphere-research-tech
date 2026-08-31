<?php $CVS_Version = '$Revision: 1.1 $ ; $Date: 2007/12/28 16:18:03 $';
// включаем убийство сессии в случае assert или ds_error
$GLOBALS['HI_SECURITY_FLAG'] = 0;

require_once 'Main.php';
require_once constant('INCLUDE_PATH').'GameDown.inc.php';
require_once constant('INCLUDE_PATH').'Smarty/Smarty.class.php';

if(GameDown())
{
	Header("Status: 302 Moved Temporaly");
	Header("Location: index_down.html");
	exit();
}

if(!SU_GameSessionWork::SessionContinue())
{
	Header("Status: 302 Moved Temporaly");
	Header("Location: index_login.php");
	exit();
}

GE_PasswordChange::Exec($_REQUEST);

?>