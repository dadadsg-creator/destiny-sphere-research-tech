<?php
require_once($_SERVER['DOCUMENT_ROOT']."/ds/Main.php");

function getEmail()
{
	if(key_exists('nick', $_POST) && key_exists('pass', $_POST))
	{
		$userName = trim($_POST['nick']);
		$password = trim($_POST['pass']);
	
		if(empty($userName) || empty($password))
		{
			return '';
		}
	
		$common = DS_Common::CreateForCurentDB(0);
	
		$uid = VD_User::Validate($userName, $password, $common);
		if(!$uid)
		{
			return '';
		}
	
		$user = DE_User::CreateFromID($uid);
	
		$email = $user->getEmail();
		
		return $email;
	}
	
	return '';
}

$email = getEmail();

if( count( $_POST ) > 2 && !empty( $_POST['nick']) && !empty( $_POST['pass']) && !empty($email)) {

	$mailto = "bono@destinysphere.com";
//	$location = "index.php?p=aliance_form";
	
	switch( @$_POST['race'] ){
		case '0':
		default:
			$race = "Воранер";
		break;
		case '1':
			$race = "Лиенсу";
		break;
		case '2':
			$race = "Псолао";
		break;
	}

	$from 	= "From: billing@ru.destinysphere.com";
	$reply	= "Reply-To: billing@ru.destinysphere.com";
	
//	$from 	= "From: ".@$_POST['Email']; 	// billing@ru.destinysphere.com";
//	$reply	= "Reply-To: ".@$_POST['Email']; 	// billing@ru.destinysphere.com";

	$mailHeaders =
			$from." \r\n".
			$reply." \r\n".
			"Content-Type: text/plain; charset=Windows-1251 \r\n".
			"X-Mailer: DestinySphere-Portal";

	$subj = "Gamma";

	$mailMess =
			"NickName: ".@$_POST['nick']."\n\n".
			"E-Mail: ".$email."\n\n".
			"Для расы: ".$race."\n\n".
			"offset [R:G:B] - ".@$_POST['ro'].":".@$_POST['go'].":".@$_POST['bo']." \n\n".
			"multiply [R:G:B] - ".@$_POST['rm'].":".@$_POST['gm'].":".@$_POST['bm']." \n\n".
			"Font color invert: ".@$_POST['fnt']."\n\n";

	mail( $mailto, $subj, $mailMess, $mailHeaders);
	
	/*отправка юзеру подтверждения*/
/*	
	$from_u = "From: noreply@destinysphere.com";
	$reply_u	= "Reply-To: noreply@destinysphere.com";
	$mailHeaders_u =
			$from_u." \r\n".
			$reply_u." \r\n".
			"Content-Type: text/plain; charset=Windows-1251 \r\n".
			"X-Mailer: DestinySphere-Portal";
	$subj_u = "Добавление альянса";
	$mailto_u 	= @$_POST['Email'];
	
	$mailMess_u =
			"Здравствуйте ".@$_POST['User_Nick']."! \n\n".
			"Вы подали заявку на добавление ваших данных в раздел \"Страницы альянсов\". \n\n".
			"При заполнении формы вы указали следующие данные: \n".
			
			"Название альянса (полное): ".@$_POST['aliance_name_full']."\n\n".
			"Сокращенное название альянса: ".@$_POST['aliance_name_small']."\n\n".
			"Ваш NickName в игре: ".@$_POST['User_Nick']."\n\n".
			"E-Mail: ".@$_POST['Email']."\n\n".
			"Ссылка на сайт альянса: ".@$_POST['site_url']."\n\n".
			"Сфера: ".$sphere."\n\n".
			"Дополнительные данные: \n".@$_POST['add_data']."\n\n";
			
			"Команда поддержки Destiny Sphere.";
			
	mail( $mailto_u, $subj_u, $mailMess_u, $mailHeaders_u);
	*/
	echo "Mail Sending! =)";
}
else {
?>	
	<form method='post' action='index_gamma.php'>
		<input type=hidden name=nick value="bono">
		<input type=hidden name=pass value="test">
		<input type=hidden name=fnt value="0">
		<input type=submit name=nick value="send">
	</form>
<?
}
//Header( "Location: ".$location );


?>