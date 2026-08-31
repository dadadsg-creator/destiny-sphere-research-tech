<?php

function sessionContinue()
{
	//Адаптация для консольного запуска useraction
	$sapi = php_sapi_name();
	if($sapi != "apache")
	{
		return true;
	}

	setDomain();
	if( isset($_COOKIE[session_name()]) ) {
		$sido = $_COOKIE[session_name()];
	}else if(isset($_REQUEST[session_name()])){
		$sido = $_REQUEST[session_name()];
	} else {
		return false;
	}

	$mySessionFile = session_save_path() ."/sess_".$sido;

	if ( file_exists ( $mySessionFile ) )
	{
		if (session_id())
		{
			return true;
		} else {
			if (session_start())
			{
				return true;
			}else {
				return false;
			}
		}
	}else{
		return false;
	}

}//END OF FUNCTION sessionContinue ()

function setDomain()
{
	if(!isset($_SERVER['HTTP_HOST']))
	{
		return;
	}
	if($_SERVER['HTTP_HOST'] == 'game7.destinysphere.ru'){
		ini_set('session.cookie_domain', "destinysphere.ru");
	}elseif($_SERVER['HTTP_HOST'] == 'www.destinysphere.ru'){
		ini_set('session.cookie_domain', "destinysphere.ru");
	}elseif($_SERVER['HTTP_HOST'] == 'destinysphere.ru'){
		ini_set('session.cookie_domain', "destinysphere.ru");
	}elseif($_SERVER['HTTP_HOST'] == 'ds-game.ru'){
		ini_set('session.cookie_domain', "ds-game.ru");
	}elseif($_SERVER['HTTP_HOST'] == 'www.ds-game.ru'){
		ini_set('session.cookie_domain', "ds-game.ru");
	}elseif($_SERVER['HTTP_HOST'] == 'ds.rambler.ru'){
		ini_set('session.cookie_domain', "ds.rambler.ru");
	}elseif($_SERVER['HTTP_HOST'] == 'portal'){
		ini_set('session.cookie_domain', "portal");
	}

}
?>