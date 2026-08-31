<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2007/06/07 08:45:57 $';

ini_set('memory_limit', '16M');
set_time_limit(1200);

require_once './include/Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

$_SESSION["dbId"] = 0;

SU_DebugTools::TimerStart('TEST');
/*********** BEGIN WORK AREA **************/
require_once('./classes/Search.class.php');

$common = DS_Common::CreateForCurentDB();

$search = new Search($common);


$messages = array(
	1 => array('subject' => 'TEST SUBJECT1', 'body' => 'TEST BODY1', 'headers' => ""),
);

$recipients = array(
	6 => 'fedchin@destinysphere.com',
	1 => '40Min@destinysphere.com',
	5 => 'zya@destinysphere.com',
	4 => 'oleg.t@destinysphere.com',
	8 => 'enzo@destinysphere.com',
);

$disabled = array(
	6 => 'fedchin@destinysphere.com'
);

$common->executeQuery('BEGIN');

//Вставляем сеччаги
foreach($messages as $k=>$message)
{
	echo "INSERT MESSAGE ".$message['subject']."<br>";
	$messageID = $search->insertMessage($message['subject'], $message['body'], $message['headers']);	
	
	foreach($recipients as $id_user=>$email)
	{		
		$search->insertRecipient((int)$messageID, (int)$id_user, $email);
		echo "INSERT PARTICIPANT ".$email."<br>";
	}
}

//Вставляем баны
foreach($disabled as $id_user=>$email)
{
	echo "INSERT DISABLED MAIL ".$email."<br>";
	$search->disableUser((int)$id_user, $email);
}

$common->executeQuery('COMMIT');

/*********** END WORK AREA **************/
echo "<hr>WORK AREA TIME: ".SU_DebugTools::TimerStop('TEST');
?>