<?
require_once ('Main.php');
require_once 'include/Smarty/Smarty.class.php';
include ("language/".constant('DEFAULT_LANGUAGE_SHORT')."/world_registered.lang");

		SU_GameSessionWork::SessionContinue();

		/* ТУТ Собственно место вызова темплейта */
		$tpl = new Smarty;
		$tpl->template_dir = "templates/";
		$tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
		$tpl->compile_check = true;
		$tpl->debugging = false;

		/* блок переменных */
		$tpl->assign("lang",$lang);
		$str = "index_start.php?ck=".$_SESSION['client_key'];
		if(session_id()) 
		{
			$str .= "&".session_name()."=".session_id();
		}
		$tpl->assign("URL_REDIRECT", $str);
		$tpl->assign("REFRESH", 5);
		
		/* Показать сформированный темплейт */
		$tpl->display('world_registered.tpl');
		
?>