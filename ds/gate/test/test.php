<?
define('INI_STORE_PATH', 'ds/gate/test');


require_once('../classes/Properties.class.php');
require_once('../classes/getContent.class.php');

$delimiter = array('FROM'=>' ', 'TO'=>'[space]');
$arrayServers = array(
//'http://portal_release/ds/gate/dsmessage.php',
'http://www.destinysphere.ru/ds2/gate/dsmessage.php'
);


$getFileArray = new Properties("xls2");
$getContent = new GetContent();
$testServerNumber = 1;


$arrayShortCodes = $getFileArray->getAllSections();



echo "Комплексная Эмуляция сообщений, которые по каким либо причинам, не дошли до нашего скрипта";
foreach ($arrayServers as $server)
{
	echo "<br><br><hr>";
	echo "<h2>Тест Сервера ".$server."</h2>";
	$testNumber = 1;


//print_r($arrayShortCodes);
	foreach ($arrayShortCodes as $shortcode)
	{
		$arrayMessages = $getFileArray->get($shortcode);

		//print_r($arrayMessages);
		//die();

		foreach ($arrayMessages as $key => $value)
		{
			sleep(1);
			$arrayMessage = explode(";", $value);
			assert(count($arrayMessage) == 2);

			$message = $arrayMessage[1];
			$phone = $arrayMessage[0];

			echo "<h3>Тест ".$testServerNumber.".".$testNumber."</h3>";
			echo "Текст СМС сообщения: ".str_replace($delimiter['FROM'],$delimiter['TO'],$message)."<br>\n";

			$q = array();
			$q[] = "body=".$message;
			$q[] = "shortcode=".'+'.$shortcode;
			$q[] = "group=".'MTS_Moscow_new';
			$q[] = "trans_id=".'';
			$q[] = "emulator=".'TRUE';
			$q[] = "phone=".'+'.$phone;
			$q[] = "debug_mode=1";

			$url = $server.'?'.implode("&", $q);

			$getContent->setUrl($url);
			$getContent->setMethod("GET");
			$getContent->Start();
			$content = $getContent->GetResponseContent();

			//echo $url."<br>";

			if (is_null($content))
			{
				echo "ERROR!!!!! ".$getContent->GetError()."";
			}

			echo "<table><tr><td bgcolor=#cccccc>".var_export($content,1)."</td></tr></table>";

			//var_export($content);






			$testNumber++;
		}


	}
	$testServerNumber++;
}






?>