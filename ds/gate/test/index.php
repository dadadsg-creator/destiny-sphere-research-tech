<?



require_once('../classes/getContent.class.php');

$delimiter = array('FROM'=>' ', 'TO'=>'[space]');
$arrayServers = array(
//'http://portal/ds/gate/dsmessage.php',
//'http://alpha.destinysphere.ru/ds/gate/dsmessage.php',
'http://www.destinysphere.ru/ds/gate/dsmessage.php'
);

$arrayMessages = array(
//'DS AINF Stariy StarPass EDR',
//'DS ARCH H Stariy StarPass EDR 19000 12',
//'DS AINF oleg123oleg zzzzzzzz EDR',
//'DS ARCH H oleg123oleg zzzzzzzz EDR 2000 1',
//'DS AINF AntonFedchin zzzzzzzz EDR',
'DS AINF oleg123oleg zxzxzxzx ORN',
'DS ARCH H oleg123oleg zxzxzxzx ORN 2000 12',
//'DS AINF oleg123oleg zxzxzxzx ORN',
//'DSMSG Zya 111 EDR 40min test',
//'DS AINF Anna 111 EDR',
//'DS ARCH H Anna 111 EDR 942 10',
//'DSMSGA Zya 111 EDR 40min test',
//'DSMSGR Zya 111 EDR 40min test',
//'ON DSREG Zya 111',
//'DSPREMIUM Zya',
//'DS RING ORN 1 1',
//'DSPLAY Zya',
//'DS ARMY Stariy StarPass EDR',
//'DS OTVET Zya test',
//'DS VOTE'
);

$getContent = new GetContent();

echo "Проверка на работоспособность скриптов, а так же эмуляция исключительных ситуаций, не используя скрипты компании SMX.COM<br\n>";

$testServerNumber = 1;

foreach ($arrayServers as $server)
{
	echo "<br><br><hr>";
	echo "<h2>Тест Сервера ".$server."</h2>";
	$testNumber = 1;

	foreach ($arrayMessages as $message)
	{
		echo "<h3>Тест ".$testServerNumber.".".$testNumber."</h3>";
		echo "Текст СМС сообщения: ".str_replace($delimiter['FROM'],$delimiter['TO'],$message)."<br>\n";

		$q = array();
		$q[] = "body=".$message;
		$q[] = "shortcode=".'+7171';
		$q[] = "group=".'MTS_Moscow_new';
		$q[] = "trans_id=".'';
		$q[] = "emulator=".'TRUE';
		$q[] = "phone=".'+79119722936';
		$q[] = "debug_mode=1";

		$url = $server.'?'.implode("&", $q);

		$getContent->setUrl($url);
		$getContent->setMethod("GET");

		$getContent->Start();

		//$getContent->showHeaders();
		flush();


		$content = $getContent->GetResponseContent();

		if (is_null($content))
		{
			echo "ERROR!!!!! ";
			//.$getContent->GetError()."";
		}

		echo "<table><tr><td bgcolor=gray>".var_export($content,1)."</td></tr></table>";

		//var_export($content);






	$testNumber++;
	}
	$testServerNumber++;
}






?>