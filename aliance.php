<?php
if( count( $_POST ) > 2 && isset($_POST['Email']) &&  isset($_POST['User_Nick']) && !empty( $_POST['Email']) && !empty( $_POST['User_Nick'] ) ) {

	$mailto = "sheller@destinysphere.com";
	$location = "index.php?p=aliance_form";
	
	switch( @$_POST['sphere'] ){
		case '1':
		default:
			$sphere = "Сфера Андромеды";
		break;
		case '2':
			$sphere = "Сфера Кассиопеи";
		break;
		case '3':
			$sphere = "Сфера Ориона";
		break;
		case '4':
			$sphere = "Сфера Ворона";
		break;
		
/*		case '5':
			$sphere = "Сфера Скорпиона";
		break;
*/
		case '6':
			$sphere = "Сфера Феникса";
		break;
		case '7':
			$sphere = "Сфера Водолея";
		break;
		case '8':
			$sphere = "Сфера Пегаса";
		break;
		case '9':
			$sphere = "Сфера Эридана";
		break;
		case '10':
			$sphere = "Сфера Змееносца";
		break;
	}

	$from 	= "From: ".@$_POST['Email']; 	// billing@ru.destinysphere.com";
	$reply	= "Reply-To: ".@$_POST['Email']; 	// billing@ru.destinysphere.com";

	$mailHeaders =
			$from." \r\n".
			$reply." \r\n".
			"Content-Type: text/plain; charset=Windows-1251 \r\n".
			"X-Mailer: DestinySphere-Portal";

	$subj = "alliance";

	$mailMess =
			"Название альянса (полное): ".@$_POST['aliance_name_full']."\n\n".
			"Сокращенное название альянса: ".@$_POST['aliance_name_small']."\n\n".
			"NickName: ".@$_POST['User_Nick']."\n\n".
			"E-Mail: ".@$_POST['Email']."\n\n".
			"Ссылка на сайт альянса: ".@$_POST['site_url']."\n\n".
			"Сфера: ".$sphere."\n\n".
			"Дополнительные данные: \n".@$_POST['add_data']."\n\n";

	mail( $mailto, $subj, $mailMess, $mailHeaders);
	
	/*отправка юзеру подтверждения*/
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
}
Header( "Location: ".$location );

?>