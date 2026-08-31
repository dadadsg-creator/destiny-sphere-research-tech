<?
	require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
	require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
	require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/tree.class.php";

	echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=Menu><font size=3><b>HELP</b></font></a></p>';

	$db_conn_id = getMysqlConnection();

	// –екрсивна€ функци€ посторени€ дерева страниц
	//  - tree - объект "дерево". явл€етс€ входным и выходным параметром
	//  - $start_page_id - идентификатор страницы. Ѕудет вершиной дерева
	// ==================================================================================
	function create_pages_tree(&$tree,$start_page_id,$ref="") {
	// ==================================================================================
		global $db_conn_id;
		global $LanguageID;
		$query = "SELECT * FROM AdminMenu  WHERE Parent_ID = '$start_page_id' ORDER BY ShowOrder";
		$result = mysql_db_query(DBName,$query,$db_conn_id);
		while ($res = mysql_fetch_array($result))	{
			if ($ref) {
				$href = $ref.$res['ID'];
			}
			$parent_id = ($res['Parent_ID']) ? $res['Parent_ID'] : "root";
			$title = "(".$res['ShowOrder'].") ".$res['Title'];
			$title = str_replace("<br>","",$title);
			$title = str_replace("&ndash;","",$title);
			$title = str_replace("<img src=\"/img/tree/doc.gif\" border=0>","",$title);
			
			$str = '&nbsp;&nbsp;<a href="#">+</a> <a href="#"><img src="" border=0>+</a>';
			$tree->addnode($parent_id,$res['ID'],$title,$href, $str);
			create_pages_tree($tree,$res['ID'],$ref);
		}
	}

	$PagesTree = new tree("root","");

	/*$query = "SELECT * FROM AdminMenuParts ORDER BY ShowOrder";
	$result = mysql_db_query(DBName,$query,$db_conn_id);
	while ($res = mysql_fetch_array($result))	{
		$PagesTree->addnode("root","category_".$res['part_id'],$res['Title']);
	}*/

	$str = "?ID=";
	create_pages_tree($PagesTree,0,$str);
	//echo $PagesTree->showtree();
	//echo $PagesTree->show_checkboxes('test');
	echo $PagesTree->showTreeEdit();

	require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>

