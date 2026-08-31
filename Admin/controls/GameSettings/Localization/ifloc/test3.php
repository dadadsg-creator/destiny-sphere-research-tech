<?php

ini_set('memory_limit', '16M');
set_time_limit(0);

require_once 'Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

//Регистрация своего обработчикка ошибок
//set_error_handler("DS_ErrorHandler");

//Регитрация финализатора
register_shutdown_function ('finilazer');

define('DEBUG_MODE', 1);

class LocalizedTextManager
{	private $dbHandler;

	public function __construct()
	{		//	}
	function getLocalizedText ($perPage, $page)
	{
		//
	}

	function updateLocalizedText ($localizedTextId, $aLocalizedText)
	{
		//
	}

	function addLocalizedText ($aLocalizedText)
	{
		//

		return $newId;
	}
}
// functions

/*********** BEGIN WORK AREA **************/

$common = DS_Common::CreateForCurentDB();
/*
$common = DS_Common::CreateForCurentDB();

$res = $common->getResultArray('', $sql, '', '', '');
assert(empty($common->errormsg));
*/

$perPage = 30;

if ('show' === $mode) {	if (array_key_exists('page', $_GET) && is_int($_GET['page'])) {		$page = intval($_GET['page']);	} else {		$page = 1;	}

	$sql = '
		select
			ds_interface.id_interface     as id_interface,
			ds_interface.interfacename    as interfacename,
			ds_interface.id_localizedname as id_localizedtext
		from
			ds_interface, ds_localizedtext, ds_language, ds_localizedtextlanguage
		where
			ds_localizedtext.id_localizedtext = ds_interface.id_localizedname and
			ds_localizedtext.id_localizedtext = ds_localizedtextlanguage.id_localizedtext and
			';}

/*********** END WORK AREA **************/
?>