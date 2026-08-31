<?
if (isset($change_cat_id)) $WorkPageCategoryID = $change_cat_id;
if (!isset($WorkPageCategoryID)) $WorkPageCategoryID = "all";

if (!in_array($WorkPageCategoryID,array("all","null")))
{
    $query = "SELECT count(*) FROM PageCategories WHERE Lang_id='$LanguageID' AND ID='".$WorkPageCategoryID."'";
    $result = @mysql_db_query(DBName,$query,$DB_CONNECTION_ID);
    if ($result) {
        $res = mysql_fetch_array($result);
        if ($res[0] == 0) $WorkPageCategoryID = "all";
    } else $WorkPageCategoryID = "all";
}



?>