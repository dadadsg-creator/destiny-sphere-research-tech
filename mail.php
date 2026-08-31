<?php

switch( @$_POST['Country'] ){

		case 'tad':

			$mailto = "didado@destinysphere.com";

			$location = "index.php?p=serv_tad";

		break;

		case 'kazahst':

			$mailto = "aoe@mail.kz";

			$location = "index.php?p=serv_kaz";

		break;

		case 'azer':

			$mailto = "orik@destinysphere.com";

			$location = "index.php?p=serv_az";

		break;

		case 'ua_1':

			$mailto = "psich@destinysphere.com";

			$location = "index.php?p=serv_ua";

		break;

		case 'ua_2':

			$mailto = "mira@destinysphere.com";

			$location = "index.php?p=serv_ua";

		break;

		case 'ua_3':

			$mailto = "anv@destinysphere.com";

			$location = "index.php?p=serv_ua";

		break;

		case 'ua_4':

			$mailto = "tannas@destinysphere.com";

			$location = "index.php?p=serv_ua";

		break;

		case 'ua_5':

			$mailto = "wmzpua@destinysphere.com";

			$location = "index.php?p=serv_ua";

		break;

		case 'ua_6':

			$mailto = "Saydat@destinysphere.com";

			$location = "index.php?p=serv_ua";

		break;

		case 'bel_1':

			$mailto = "evd@destinysphere.com";

			$location = "index.php?p=serv_bel";

		break;

		case 'bel_2':

			$mailto = "solo@destinysphere.com";

			$location = "index.php?p=serv_bel";

		break;

		case 'bel_3':

			$mailto = "progressiveage@destinysphere.com";

			$location = "index.php?p=serv_bel";

		break;

		case 'baltic_1':

			$mailto = "kirillica@destinysphere.com";

			$location = "index.php?p=serv_balt";

		break;

		case 'baltic_2':

			$mailto = "esomus@destinysphere.com";

			$location = "index.php?p=serv_balt";

		break;

		case 'isr':

			$mailto = "locb1980@destinysphere.com";

			$location = "index.php?p=serv_iz";

		break;

		case 'euro':

			$mailto = "netnet@destinysphere.com";

			$location = "index.php?p=serv_euro";

		break;

		case 'moscow':

			$mailto = "rainmaster@destinysphere.com";

			$location = "index.php?p=serv_rus";

		break;

		case 'ru':

		default:

			$mailto = "billing@ru.destinysphere.com";

			$location = "index.php?p=serv_rus";

		break;

	}





if( count( $_POST ) > 2 && isset($_POST['Email']) &&  isset($_POST['User_Nick']) && !empty( $_POST['Email']) && !empty( $_POST['User_Nick'] ) ) {

	

				if (isset($_SERVER['HTTP_CLIENT_IP']))

					{

						$ip_proxy = $_SERVER['HTTP_CLIENT_IP'];

						$ip = $_SERVER['REMOTE_ADDR'];

					} else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))

					{

						$ip = $_SERVER['REMOTE_ADDR'];

						$ip_proxy = $_SERVER['HTTP_X_FORWARDED_FOR'];

					} else {

						$ip_proxy = 'none';

						$ip = $_SERVER['REMOTE_ADDR'];

					}



	$from 	= "From: ".@$_POST['Email']; 	// billing@ru.destinysphere.com";

	$reply	= "Reply-To: ".@$_POST['Email']; 	// billing@ru.destinysphere.com";



	$mailHeaders =

			$from." \r\n".

			$reply." \r\n".

			"Content-Type: text/plain; charset=Windows-1251 \r\n".

			"X-Mailer: DestinySphere-Portal";



	$actions = array(

			"GOLD_CREDIT"				=> "Золотой кредит",

			"black_shirt"				=> "Футболка DestinySphere (черная)",

			"color_shirt"				=> "Футболка DestinySphere с картинкой",

			"ds_kepka"					=> "Бейсболка DestinySphere",

			"black_hole"				=> "Восстановление соты из мира Чёрная дыра",

			"certificate_login_trade" 	=> "Предоставление справки о заходах на ваш аккаунт и торговых сделках",

			"freeze_akkaunt_randome"	=> "Предоставление услуги заморозки аккаунта на срок до 12 месяцев со случайным предоставлением места вашего будущего поселения.",

			"freeze_akkaunt_select"		=> "Предоставление услуги заморозки аккаунта на срок до 12 месяцев (с выбором координат соты после разморозки)",

			"backup_akkaunt" 			=> "Предоставление услуги восстановления вашего аккаунта из бекапа",

			"On_leave" 					=> "Предоставление возможности строительства Дома Отдыха - включает режим отпуска",

			"Change_Nick" 				=> "Смена игрового ника",

			"Change_Wabename" 			=> "Смена названия соты",

			"Change_Nick_Fuck" 			=> "Смена нецензурного игрового ника",

			"Change_Wabename_Fuck" 		=> "Смена нецензурного названия соты",

			"Delete_Unique_Buildings" 	=> "Удаление уникального здания",

			"Delete_Wabe" 				=> "Уничтожение соты по собственному желанию",

			"Delete_Colony" 			=> "Удаление колонии с сохранением основной соты и других колоний",

			"Change_User" 				=> "Передача аккаунта другому лицу",

			"Complex_Reservation" 		=> "Резервирование места в определенном комплексе",

			"Move_world" 				=> "Переезд в другой игровой мир",

			"Move_world_group" 			=> "Переезд в другой игровой мир групп игроков одного альянса",

			"Move_world_sote" 			=> "Переезд соты по кольцу на свободное место в сочетании с услугой переезда между игровыми мирами",

    		"Move_sote" 				=> "Переезд соты по кольцу на свободное место",

    		"Replace_sote" 				=> "Обмен местами между сотами двух игроков",

    		"Move_sote_melsion" 		=> "Переезд соты по кольцу на свободное место (Мелсион)",

    		"Replace_sote_melsion" 		=> "Обмен местами между сотами двух игроков (Мелсион)",

			//"Activisation_user" 		=> "Внеочередная активация в игре",

			"Connection_one_user" 		=> "Слияние всех существующих игровых аккаунтов в один",

			"Activisation_new_world" 	=> "Включение возможности дополнительно играть в Сфере Стрельца",

			"Multiple_Worlds" 			=> "Включение возможности играть во всех игровых мирах",

			"ds_tourn" 					=> "Участие в турнире Destiny Sphere",

			"PREMIUM1" 					=> "ПРЕМИУМ-аккаунт на 1 месяц",

			"PREMIUM3" 					=> "ПРЕМИУМ-аккаунт на 3 месяца",

			"PREMIUM6" 					=> "ПРЕМИУМ-аккаунт на 6 месяцев",

			"PREMIUM12" 				=> "ПРЕМИУМ-аккаунт на 12 месяцев"

		);



	

	require_once("./sys_dyn_components/add_serv/add_serv_worlds.inc.php");

		

	$subj = "Service Form - ".@$_POST['Service'];



	$mailMess =

			"ФИО: ".@$_POST['User_Name']."\n\n".

			"Nickname: ".@$_POST['User_Nick']."\n\n".

			"Услуга: ". /*@$_POST['Service']." - ".*/ @$actions[ $_POST['Service'] ]."\n\n".

			"Мир: ". /*@$_POST['World']." - ".*/ @$worlds[ $_POST['World'] ]."\n\n".

			"№ комплекса: ".@$_POST['user_ring'].":".@$_POST['user_complex'].":".@$_POST['user_sota']."\n\n".

			"Коментарии: \n".@$_POST['Comments']."\n\n".

			"Обратный E-Mail: ".@$_POST['Email']."\n\n".

			"IP - ".$ip." / proxy IP - ".$ip_proxy;



//$mailto = "nikita@destinysphere.com";

			

	mail( $mailto, $subj, $mailMess, $mailHeaders);

}

Header( "Location: ".$location );



?>