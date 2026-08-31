<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/tree.class.php";

	$db_conn_id = getMysqlConnection();

	// –екрсивна€ функци€ посторени€ дерева страниц
	//  - tree - объект "дерево". явл€етс€ входным и выходным параметром
	//  - $start_page_id - идентификатор страницы. Ѕудет вершиной дерева
	// ==================================================================================
	function create_pages_tree(&$tree,$start_page_id,$ref="") {
	// ==================================================================================
		global $db_conn_id;
		global $LanguageID;
		$query = "SELECT ID, Parent_ID, Title, PageCategory_ID FROM Pages WHERE parent_id='$start_page_id' AND NOT (PageCategory_ID=0 OR PageCategory_ID='' OR PageCategory_ID is NULL) AND Lang_id='$LanguageID' ORDER BY OrderInParent";
		$result = mysql_db_query(DBName,$query,$db_conn_id);
		while ($res = mysql_fetch_array($result))	{
			if ($ref) {
				$href = $ref.$res[ID];
			}
			$parent_id = ($res[Parent_ID]) ? $res[Parent_ID] : "category_".$res[PageCategory_ID];
			$title = $res[Title];
			$title = str_replace("<br>","",$title);
			$title = str_replace("&ndash;","",$title);
			$title = str_replace("Ц","",$title);
			$tree->addnode($parent_id,$res[ID],$title,$href);
			create_pages_tree($tree,$res[ID],$ref);
		}
	}

	$PagesTree = new tree("root","");

	$PagesTree->addnode("root","FreePages",local('ADMIN_NON_CATEGORY_PAGES'));
	$query = "SELECT ID, Title FROM Pages WHERE (PageCategory_ID=0 OR PageCategory_ID='' OR PageCategory_ID is NULL) AND Lang_id='$LanguageID' ORDER BY OrderInParent";
	$result = mysql_db_query(DBName,$query,$db_conn_id);
	while ($res = mysql_fetch_array($result))	{
		$PagesTree->addnode("FreePages",$res[ID],$res[Title],"/Admin/controls/Portal/visedit.php?ID=".$res[ID]);
	}

	$query = "SELECT ID, Title FROM PageCategories WHERE Lang_id='$LanguageID' ORDER BY ShowOrder";
	$result = mysql_db_query(DBName,$query,$db_conn_id);
	while ($res = mysql_fetch_array($result))	{
		$PagesTree->addnode("root","category_".$res[ID],$res[Title]);
	}

	create_pages_tree($PagesTree,0,"/Admin/controls/Portal/PagesTree/visedit.php?ID=");
	echo $PagesTree->showtree();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>

