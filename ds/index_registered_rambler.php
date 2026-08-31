<?
require_once ('Main.php');
require_once 'include/Smarty/Smarty.class.php';
include ("language/".constant('DEFAULT_LANGUAGE_SHORT')."/index_registered.lang");

		SU_GameSessionWork::SessionContinue();
		
		/* ТУТ Собственно место вызова темплейта */
		$tpl = new Smarty;
		$tpl->template_dir = "templates/";
		$tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
		$tpl->compile_check = true;
		$tpl->debugging = false;

		/* блок переменных */
		$tpl->assign("lang",$lang);
		
		/*$str = "index_start.php?rambler_id_login=1&".session_name()."=".session_id();
		if(session_id()) 
		{
			$str .= "&".session_name()."=".session_id();
		}*/
		
		$ramblerLogin = new GE_RamblerLogin();
		if($ramblerLogin->hasRSID())
		{
			if($ramblerLogin->execLogin())
			{
				//$str = "index_start.php";
				$str = "index_login.php?rambler_id_login=1&".session_name()."=".session_id();
			}
		} else {
			SU_DebugTools::LogAll('НЕ залогинилось', 'Annita.log');
		}
		
		/*$ramblerLogin = new GE_RamblerLogin();
		if($ramblerLogin->hasRSID())
		{
			if($ramblerLogin->execLogin())
			{
				$str = "index_login.php?rambler_id_login=1&".session_name()."=".session_id();
			}
		} else {
			SU_DebugTools::LogAll('НЕ залогинилось', 'Annita.log');
		}*/

		$tpl->assign("URL_REDIRECT", $str);
		$tpl->assign("REFRESH", 5);				

		/* Показать сформированный темплейт */
		$tpl->display('index_registered_rambler.tpl');
		
?>