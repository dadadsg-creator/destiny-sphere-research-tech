<?php
//require_once '../Main.php';
require_once './_smarty/libs/Smarty.class.php';

class LocalizedTextHandler extends Smarty
{
	private $smarty_dir;
	private $app_dir;
	//private $action;

	public function __construct()
	{
		$this->app_dir       = '/home/www/htdocs/defo/ds/ifloc/';
		$this->smarty_dir    = $this->app_dir . '_smarty/';
		$this->cache_dir     = $this->smarty_dir . 'cache/';
		$this->compile_dir   = $this->smarty_dir . 'templates_c/';
		$this->template_dir  = $this->app_dir . 'templates/';
		//$this->
	}

	public function handleRequest()
	{
		// better define it
		$perPage = 10;

		if (key_exists('action', $_GET)) {
			$action = $_GET['action'];
		} elseif (key_exists('action', $_POST)) {
			$action = $_POST['action'];
		} else {
			$action = 'view';
		}

		if (key_exists('page', $_GET) && is_numeric($_GET['page'])) {
			$page = intval($_GET['page']);
		} elseif (key_exists('page', $_POST) && is_numeric($_POST['page'])) {
			$page = intval($_POST['page']);
		} else {
			$page = 1;
		}

		if (key_exists('sort', $_GET) && ('id' === $_GET['sort'] || 'name' === $_GET['sort'])) {
			$sort = $_GET['sort'];
		} else {
			$sort = false;
		}

		if (key_exists('search', $_GET) && !empty($_GET['search'])) {
			$searchedText = $_GET['search'];
		} else {
			$searchedText = false;
		}

		if (key_exists('lang', $_GET) && !empty($_GET['lang'])) {
			$searchLanguage = $_GET['lang'];
		} else {
			$searchLanguage = false;
		}

		$pagesCount = LocalizedText::getPagesCount(10, $searchedText);
		//echo "Pages count: $pagesCount<br />\n";

		if (false === $pagesCount) {
			$pagesCount = 0;
		}

		$pages = array();

		for ($p = 1; $p <= $pagesCount; ++$p) {
			$pages[$p] = $p;
		}

		$this->assign('page', $page);
		$this->assign('pages_count', $pagesCount);
		$this->assign('pages', $pages);

		$languages = Language::getLanguages();

		switch ($action) {
			case 'delete':
				if (array_key_exists('id', $_GET) && is_numeric($_GET['id'])) {
					$id = intval($_GET['id']);


					$l = new LocalizedText($id);
					$detail = "";
					foreach ($l->aLocalizations as $lant => $texst)
					{
						$detail .= "Language $lant: ".$texst."<br>";
					}
					$ifName = $l->interfaceName;

					$res = LocalizedText::deleteItem($id);
					$deleteResult = $res;
					
					actionlog ("Delete interface localization", "<b>Interface Name: '$ifName;'</b><br>$detail");

					
				} else {
					$deleteResult = false;
				}

				$this->assign('delete_result', $deleteResult);
				break;
			case 'add':
				break;
			case 'save':
				$values = $_POST['lt'];
				$interfaceName = $_POST['interfacename'];
				$res = LocalizedText::createNewItem($values, $interfaceName);

				if (false !== $res) {
					$this->assign('save_result', true);
				} else {
					$this->assign('save_result', false);
					$this->assign('error_message', LocalizedText::$errorMessage);
				}
				break;
			case 'update':
				$detail = "";
				if (array_key_exists('id', $_POST) && is_numeric($_POST['id'])) {
					$id = intval($_POST['id']);

					$l = new LocalizedText($id);

					foreach ($languages as $short => $language) {
						//$newValues[$short] = $_POST['lt_' . $id][$short];
						$l->setTextByLanguage($short, $_POST['lt'][$short]);
						$detail .= "Language $short:<br>".$_POST['lt'][$short]."<br>";
					}
					$ifName = $l->interfaceName;
					actionlog ("Edit interface localization", "<b>Interface Name: '$ifName;'</b><br>$detail");
				}

				//break;
			default:
				// the same as view
				$action = 'view';

				if (isset($searchedText) && !empty($searchedText)) {
					if (false !== $searchLanguage) {
						$aLocalized = LocalizedText::getLocalizedTextByText($page, $perPage, $searchedText, $searchLanguage);
						$this->assign('lang', $searchLanguage);
					} elseif ('search_by_id' === $_GET['action']) {
						$aLocalized = LocalizedText::getLocalizedTextById($searchedText);
						//print_r($aLocalized);
					} else {
						$aLocalized = LocalizedText::getLocalizedTextByInterfaceName($page, $perPage, $searchedText);
					}
				} else {
					$aLocalized = LocalizedText::getLocalizedText($page, $perPage, $sort);
				}
				//print_r($aLocalized);

				// for Smarty better if it is an array
				foreach ($aLocalized as $item) {
					$localized[$item->id] = array();
					$localized[$item->id]['text'] = array();

					foreach ($languages as $lang) {
						$localized[$item->id]['text'][$lang->getLanguageShort()] = $item->getTextByLanguage($lang->getLanguageShort());
					}

					$localized[$item->id]['interfacename'] = $item->interfaceName;
				}
				//print_r($localized);
				$this->assign('localized', $localized);

				if (false !== $sort) {
					$this->assign('sort', $sort);
				}

				if (false !== $searchedText) {
					$this->assign('search', $searchedText);
				}
		}

		$this->assign('action', $action);

		// pass languages to Smarty:
		$this->assign('languages', $languages);
	}
}


/**
 * Class LocalizedText is an ActiveRecord class
 * */
class LocalizedText
{
	//private $dbh;
	public $id; // for smarty, very bad
	public $aLocalizations; // for Smarty, very bad
	public $interfaceName;
	public static $errorMessage;

	public function __construct($id)
	{
		$dbh = DS_Common::CreateForCurentDB();
		//SU_DebugTools::WriteToLog('LocalizedText::__construct() BEGIN: $id=' . $id . ' (' . date('Y-m-d H:i:s') . ")\n", 'LocalizedText.log');
		$this->id = $id;

		$languages = Language::getLanguages();
		//print_r($languages);

		foreach ($languages as $k => $lang) {
			//$langDump = print_r($lang, true);
			//SU_DebugTools::WriteToLog('LocalizedText::__construct(): $lang=' . $langDump . ' (' . date('Y-m-d H:i:s') . ")\n", 'LocalizedText.log');
			$sql = 'select text from ds_localizedtextlanguage where id_localizedtext = ' . $id . ' and id_language = ' . $lang->getId();
			$res = $dbh->getResultArray('', $sql, '', '', '');
			//$resDump = print_r($res, true);
			assert(empty($res->errormsg));
			$this->aLocalizations[$k] = $res[0]['text'];
			//SU_DebugTools::WriteToLog('LocalizedText::__construct(): res=' . $res[0]['text'] . "\n", 'LocalizedText.log');
		}
		//SU_DebugTools::WriteToLog('LocalizedText::__construct() END: $id=' . $id . ' (' . date('Y-m-d H:i:s') . ")\n", 'LocalizedText.log');

		$sql = 'select interfacename from ds_interface where id_localizedname = ' . $id;
		$res = $dbh->getResultArray('', $sql, '', '', '', '');

		assert(empty($res->errormsg));

		$this->interfaceName = $res[0]['interfacename'];
	}

	/**
	 * Parameter $lang is the Language::languageShort
	 * */
	public function getTextByLanguage($lang)
	{
		return $this->aLocalizations[$lang];
	}

	public function setTextByLanguage($lang, $value)
	{
		$dbh = DS_Common::CreateForCurentDB();;

		$languages =& Language::getLanguages();
		$language = $languages[$lang];

		assert(!empty($language));

		$sql = 'select count(*) as cnt from ds_localizedtextlanguage where id_language = ' . $language->getId() . ' and id_localizedtext = ' . $this->id;
		$res = $dbh->getResultArray('', $sql, '', '', '', '');
		assert(empty($res->errormsg));

		if ($res[0]['cnt'] == 0) {
			$sql = 'insert into ds_localizedtextlanguage (id_language, id_localizedtext, text) values (\'' . $language->getId() . '\', \'' . $this->id . '\', E\'' . $value . '\')';
		} else {
			$sql = 'update ds_localizedtextlanguage set text = E\'' . $value . '\' where id_language =' . $language->getId() . ' and id_localizedtext = ' . $this->id;
		}

		$result = $dbh->executeQuery($sql);
		assert(empty($result->errormsg));
		//$resultDump = print_r($result, true);
		//SU_DebugTools::WriteToLog('setTextByLanguage: sql: ' . $sql . "\n" . $resultDump . "\n", 'LocalizedText.log');
		//SU_DebugTools::WriteToLog('setTextByLanguage: sql: ' . $sql . "\n" . $resultDump . "\n", 'LocalizedText.log');
		
		//Логируем
		//actionlog ("Edit interface localization", "$value");

		//assert(empty($value));
		$this->aLocalizations['$lang'] = $value;
	}

	public static function getLocalizedText($page, $perPage, $sort = false)
	{
		//SU_DebugTools::WriteToLog('getLocalizedText started' . "\n", 'LocalizedText.log');
		if ('name' === $sort) {
			$sortAd = ' order by i.interfacename ';
		} elseif ('id' === $sort) {
			$sortAd = ' order by i.id_interface ';
		} else {
			$sortAd = '';
		}

		$dbh = DS_Common::CreateForCurentDB();
		$result = array();

		// first get localized_text object
		$sql = '
			select
				t.id_localizedtext
			from
				ds_localizedtext t, ds_interface i
			where
				-- t.id_localizedtext > 30000 and
				-- t.id_localizedtext < 60000 and
				t.id_localizedtext = i.id_localizedname
				' . $sortAd . '
			limit ' . $perPage . ' offset ' . (($page - 1) * $perPage);
		$res = $dbh->getResultArray('', $sql, '', '', '', '');
		//echo 'res: '; print_r($res);
		assert(empty($res->errormsg));

		foreach ($res as $item) {
			//print_r($item);
			$lt = new LocalizedText($item['id_localizedtext']);
			$result[] = $lt;
		}

		//$dumpRes = print_r($result, true);
		//SU_DebugTools::WriteToLog(date('Y-m-d H:i:s') . 'LocalizedText::getLocalizedText(), res: ' . $dumpRes . "\n", 'LocalizedText.log');

		return $result;
	}

	public static function getLocalizedTextByInterfaceName($page, $perPage, $text)
	{
		if (empty($text) || 0 === preg_match('/[-a-zA-Z0-9_]+/', $text)) {
			return array();
		}
		//SU_DebugTools::WriteToLog('getLocalizedText started' . "\n", 'LocalizedText.log');
		$dbh = DS_Common::CreateForCurentDB();
		$result = array();

		// first get localized_text object
		$sql = '
			select
				t.id_localizedtext
			from
				ds_localizedtext t, ds_interface i
			where
				-- t.id_localizedtext > 30000 and
				-- t.id_localizedtext < 60000 and
				t.id_localizedtext = i.id_localizedname and
				lower(i.interfacename) like \'%' . strtolower($text) . '%\'
			limit ' . $perPage . ' offset ' . (($page - 1) * $perPage);
		$res = $dbh->getResultArray('', $sql, '', '', '', '');
		//echo 'res: '; print_r($res);
		assert(empty($dbh->errormsg));

		foreach ($res as $item) {
			//print_r($item);
			$lt = new LocalizedText($item['id_localizedtext']);
			$result[] = $lt;
		}

		//$dumpRes = print_r($result, true);
		//SU_DebugTools::WriteToLog(date('Y-m-d H:i:s') . 'LocalizedText::getLocalizedText(), res: ' . $dumpRes . "\n", 'LocalizedText.log');

		return $result;
	}

	public static function getLocalizedTextById($id)
	{
		if (empty($id) || !is_numeric($id)) {
			//echo __METHOD__ . ', id: ' . $id;
			return array();
		}

//		$dbh = DS_Common::CreateForCurentDB();
//		$result = array();
//
//		$sql = '
//			select
//				i.id_localizedname as id_localizedtext
//			from
//				ds_interface i
//			where
//				i.id_interface = ' . $id;
//
//		$res = $dbh->getResultArray('', $sql, '', '', '', '');
//		assert(empty($res->errormsg));

//		foreach ($res as $item) {
//			$lt = new LocalizedText($item['id_localizedtext']);
//			$result[] = $lt;
//		}

		$result[] = new LocalizedText($id);

		return $result;
	}

	public static function getLocalizedTextByText($page, $perPage, $text, $lang)
	{
		if (empty($text) || empty($lang)) {
			return array();
		}

		$dbh = DS_Common::CreateForCurentDB();
		$result = array();

		// first get localized_text object
		$sql = '
			select
				t.id_localizedtext
			from
				ds_localizedtext t,
				ds_localizedtextlanguage tl,
				ds_language l,
				ds_interface i
			where
				-- t.id_localizedtext > 30000 and
				-- t.id_localizedtext < 60000 and
				t.id_localizedtext = tl.id_localizedtext and
				tl.id_language = l.id_language and
				l.shortname = \''.$lang.'\' and
				t.id_localizedtext = i.id_localizedname and
				lower(tl.text) like \'%' . strtolower($text) . '%\'
			limit ' . $perPage . ' offset ' . (($page - 1) * $perPage);
		$res = $dbh->getResultArray('', $sql, '', '', '', '');
		assert(empty($res->errormsg));

		foreach ($res as $item) {
			$lt = new LocalizedText($item['id_localizedtext']);
			$result[] = $lt;
		}

		return $result;
	}

	public static function createNewItem($values, $interfaceName)
	{
		$dbh = DS_Common::CreateForCurentDB();
		$languages = Language::getLanguages();

		// check if given interface name already exists
		$sql = 'select count(*) from ds_interface where interfacename =\'' . $interfaceName . '\'';
		$res = $dbh->getResultArray('', $sql, '', '', 'NO_CACHE');
		assert(empty($dbh->errormsg));

		$count = $res[0]['count'];

		if (!is_numeric($count)) {
			self::$errorMessage = 'Ошибка БД';
			return false;
		} elseif ((int)$count > 0) {
			self::$errorMessage = 'Интерфейс с именем &laquo;' . $interfaceName . '&raquo; уже существует';
			return false;
		}

		// creating new localized text record
		$sql = 'select max(id_localizedtext) + 1 as max_id from ds_localizedtext where id_localizedtext > 30000 and id_localizedtext < 60000';
		$res = $dbh->getResultArray('', $sql, '', '', '', '');
		assert(empty($dbh->errormsg));

		$newLtId = $res[0]['max_id'];

		if (!is_numeric($newLtId)) {
			return false;
		}

		$sql = 'insert into ds_localizedtext values (' . $newLtId . ')';
		$res = $dbh->executeQuery($sql);
		assert(empty($dbh->errormsg));

		// creating new interface record
		//$sql = 'select max(id_interface) + 1 as max_id from ds_interface';
		//$res = $dbh->getResultArray('', $sql, '', '', '', '');
		//assert(empty($res->errormsg));

		//$newInterfaceId = $res[0]['max_id'];

		//if (!is_numeric($newInterfaceId)) {
		//	return false;
		//}

		$sql = 'insert into ds_interface (interfacename, id_localizedname) values (\'' . $interfaceName . '\', ' . $newLtId . ')';
		$res = $dbh->executeQuery($sql);
		assert(empty($dbh->errormsg));

		$detail = "";
		
		foreach ($languages as $short => $language) {
			$sql = 'insert into ds_localizedtextlanguage (id_language, id_localizedtext, text) values (\'' . $language->getId() . '\', ' . $newLtId . ', E\'' . $values[$short] .  '\')';
			$res = $dbh->executeQuery($sql);
			assert(empty($dbh->errormsg));
			$detail .= "Language $short: ".$values[$short]."<br>";
		}

		
		actionlog ("Edd interface localization", "<b>Interface Name: '$interfaceName'</b><br>$detail");
		
		return 1;
	}

	public static function deleteItem($id)
	{
		$dbh = DS_Common::CreateForCurentDB();

		$sql = 'delete from ds_interface where id_localizedname = ' . $id;
		$res = $dbh->executeQuery($sql);
		//assert(empty($res->errormsg));

		if (!empty($res->errormsg)) {
			return false;
		}

		$sql = 'delete from ds_localizedtextlanguage where id_localizedtext = ' . $id;
		$res = $dbh->executeQuery($sql);
		//assert(empty($res->errormsg));

		if (!empty($res->errormsg)) {
			return false;
		}

		$sql = 'delete from ds_localizedtext where id_localizedtext = ' . $id;
		$res = $dbh->executeQuery($sql);
		//assert(empty($res->errormsg));

		if (!empty($res->errormsg)) {
			return false;
		}

		return true;
	}

	public static function getPagesCount($perPage = 10, $text = false)
	{
		$dbh = DS_Common::CreateForCurentDB();
		
		$sql = 'select count(*) from ds_localizedtext t, ds_interface i where t.id_localizedtext > 30000 and t.id_localizedtext < 60000 and t.id_localizedtext = i.id_localizedname';

		if (false !== $text && !empty($text)) {
			$sql .= ' and lower(i.interfacename) like \'%' . strtolower($text) . '%\'';
		}
		//echo 'query: ' . $sql . "<br />\n";

		$res = $dbh->getResultArray('', $sql, '', '', '', '');

		assert(empty($res->errormsg));

		//print_r($res);

		if (is_array($res) && is_array($res[0]) && !empty($res[0]['count'])) {
			$total = intval($res[0]['count']);
		} else {
			return false;
		}

		return intval($total / $perPage) + (0 === ($total % $perPage) ? 0 : 1);
	}
}

class Language
{
	public $id;
	public $lang;
	public $langShort;

	public function __construct($id, $lang, $langShort)
	{
		$this->id = $id;
		$this->lang = $lang;
		$this->langShort = $langShort;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getLanguage()
	{
		return $this->lang;
	}

	public function getLanguageShort()
	{
		return $this->langShort;
	}

	private static $aLanguages = array();

	public static function getLanguages()
	{
		global $dbh;

		if (0 === count(Language::$aLanguages)) {
			$sql = 'select id_language as id, language, shortname from ds_language';
			$res = $dbh->getResultArray('', $sql, '', '', '');

			assert(empty($res->errormsg));

			foreach ($res as $item) {
				Language::$aLanguages[$item['shortname']] = new Language($item['id'], $item['language'], $item['shortname']);
			}
		}

		// Here better return constant link, if it is possible in PHP5
		return Language::$aLanguages;
	}
}


?>