<?php $CVS_Version = '$Revision: 1.1 $ ; $Date: 2008/04/23 09:26:27 $';

require_once('Main.php');

/*
if(!SU_GameSessionWork::haveSession())
{
}
*/
$_SESSION['lang'] = 3;

SU_ScienceTree::Get($_REQUEST);

?>