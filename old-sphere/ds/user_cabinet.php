<?php $CVS_Version = '$Revision$ ; $Date$';

// включаем убийство сессии в случае assert или ds_error
$GLOBALS['HI_SECURITY_FLAG'] = 0;

require_once ('Main.php');
require_once (constant('INCLUDE_PATH').'GameDown.inc.php');
require_once (constant('INCLUDE_PATH').'/Smarty/Smarty.class.php');

if(GameDown())
{
    Header("Status: 302 Moved Temporaly");
    Header("Location: index_down.html");
    exit();
}

SU_GameSessionWork::SessionContinue();

if (!isset($_SESSION['cab_uid'])) {
    UC_ExecLogin::execLogin(NULL);
}

DS_Common::SetConnectWithTransaction(true);

$cab = new UC_ControlUserCabinet(array_merge($_GET, $_POST));

ME_EndDBTransactions::Exec();
?>