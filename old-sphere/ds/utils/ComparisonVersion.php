<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2007/10/26 11:27:22 $';

ini_set('memory_limit', '16M');
set_time_limit(0);

session_start();

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);
/*********** BEGIN WORK AREA **************/

echo "<head>
	<META HTTP-EQUIV='Content-Type' CONTENT='text/html; charset=windows-1251'>
	<title>Сравнение версий</title>
	</head>";

if ($_REQUEST['submit']=='Back' || $_REQUEST['submit']=='Create list')
{
	SU_CompareVersion::ShowFirst();
}
elseif (key_exists('ShowDifference', $_REQUEST) )
{
	SU_CompareVersion::ShowDifference($_REQUEST);	
} 
elseif ($_REQUEST['submit']=='Show' ||  key_exists('addresses', $_REQUEST))
{
	SU_CompareVersion::Exec($_POST);
} 
else 
{
	SU_CompareVersion::ShowFirst();
}
	?>	
	</form>
	
	</body>

