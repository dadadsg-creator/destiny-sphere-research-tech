<?
require_once ('Main.php');
require_once 'include/Smarty/Smarty.class.php';
include ("language/".constant('DEFAULT_LANGUAGE_SHORT')."/index_video1.lang");

		/* ТУТ Собственно место вызова темплейта */
		$tpl = new Smarty;
		$tpl->template_dir = "templates/";
		$tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
		$tpl->compile_check = true;
		$tpl->debugging = false;

		/* блок переменных */
		$tpl->assign("lang", $lang);
		
		/* Показать сформированный темплейт */
		$tpl->display('index_video1.tpl');
		
?>