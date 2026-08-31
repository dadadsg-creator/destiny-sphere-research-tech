<?php $CVS_Version = '$Revision: 1.1 $ ; $Date: 2007/06/18 08:08:25 $';

require_once "Main.php";

if(GameDown())
{
	Header("Status: 302 Moved Temporaly");
	Header("Location: index_down.html");
	exit();
}

SU_RemaindPassword::Remaind(array_merge($_POST, $_GET));

?>