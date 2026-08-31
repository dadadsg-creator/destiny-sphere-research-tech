<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";

if (isset($PartPageCategory_ID)) $PagesTreeWorkPageCategoryID = $PartPageCategory_ID;
$PartPageCategory_ID = $PagesTreeWorkPageCategoryID;
$_GET['PartPageCategory_ID'] = $PagesTreeWorkPageCategoryID;

if (isset($change_mode)) {
    $PagesTreeWorkMode = $change_mode;
    $page_parent_id = 0;
}
if (!isset($PagesTreeWorkMode)) $PagesTreeWorkMode = 2;
if (isset($page_parent_id)) $PageParentID = $page_parent_id;
if (!isset($PageParentID)) $PageParentID = 0;

if ($PageParentID) {
    $conn = getMysqlConnection();
    $query = "SELECT Parent_ID, PageCategory_ID FROM Pages WHERE id=$PageParentID";
    $result = @mysql_db_query(DBName,$query,$conn);
    if ($result) {
        $row = mysql_fetch_row($result);
        $PagesTreeWorkPageCategoryID = $row[1];
    } else $PagesTreeWorkPageCategoryID = 0;
}



?>



<form name="ChangeMode" action="<?=$PHP_SELF?>" method="post">
  <select name="change_mode" onchange="javascrip: document.ChangeMode.submit()">
        <option value="1" <?if ($PagesTreeWorkMode==1) echo "selected";?>><?=local('ADMIN_NON_CATEGORY_PAGES')?></option>
        <option value="2" <?if ($PagesTreeWorkMode==2) echo "selected";?>><?=local('ADMIN_CATEGORY_PAGES')?></option>
  </select>
</form>
<?



$Fields["OrderInParent"] = array (
    "Type"=>array("Name"=>"Varchar","Size"=>"3","Mandatory"=>0),
    "Caption"=>"№",
    "Edit"=>array("Field"=>"OrderInParent","Description"=>local('ADMIN_SORTING_ORDER')),
    "Show"=>array("Field"=>"OrderInParent","Width"=>"25"),
    "DefaultValue"=>""
);

$Fields["Title"] = array (
    "Type"=>array("Name"=>"Varchar","Size"=>"60","Mandatory"=>1),
    "Caption"=>local('ADMIN_NAME'),
    "Edit"=>array("Field"=>"Title","Description"=>local('ADMIN_LESS_60_SYMBOLS')),
    "Show"=>array("Field"=>"Concat('<a class=sitemapa href=\'?page_parent_id=',ID,'\'>',Title,'</a>')","Width"=>"150"),
    "DefaultValue"=>"",
    "Search"=>array("Template"=>"('#Value#'='' OR Title LIKE '%#Value#%')"),
);
$Fields["Title2"] = array (
    "Type"=>array("Name"=>"Varchar","Size"=>"60","Mandatory"=>0),
    "Caption"=>local('ADMIN_TITLE_NAME'),
    "Edit"=>array("Field"=>"Title2","Description"=>local('ADMIN_LESS_60_SYMBOLS')),
    "DefaultValue"=>""
);

if ($PageParentID !=0) {
    $Fields["PageCategory_ID"] = array ("Hidden"=>1,"DefaultValue"=>"$PagesTreeWorkPageCategoryID");
} elseif ($PagesTreeWorkMode == 1) {
    $Fields["PageCategory_ID"] = array ("Hidden"=>1,"DefaultValue"=>"null");
} else {
    $Fields["PageCategory_ID"] = array (
        "Type"=>array("Name"=>"Varchar","Size"=>"5","Mandatory"=>1),
        "Caption"=>local('ADMIN_CATEGORY'),
        "Edit"=>array("Field"=>"PageCategory_ID","Description"=>""),
        "Lookup"=>array ("Type"=>"Master","ParentTable"=>"PageCategories","ParentColumn"=>"ID","LookupColumn"=>"Title","Where"=>"Lang_id='$LanguageID'","Order"=>"ShowOrder","AllowEmpty"=>0),
        "DefaultValue"=>"$PagesTreeWorkPageCategoryID"
    );
    if ($PagesTreeWorkMode==2) $Fields["PageCategory_ID"]["Search"] = array("Template"=>"('#Value#'='' OR PageCategory_ID='#Value#')","NotEmpty"=>1);
    //"Show"=>array("Field"=>"PageCategory_ID","Width"=>"150"),
}

$Fields["AssemblyTemplate_ID"] = array (
    "Type"=>array("Name"=>"Varchar","Size"=>"5","Mandatory"=>0),
    "Caption"=>local('ADMIN_MODEL'),
    "Edit"=>array("Field"=>"AssemblyTemplate_ID","Description"=>""),
    "Show"=>array("Field"=>"AssemblyTemplate_ID","Width"=>"150"),
    "Lookup"=>array ("Type"=>"Master","ParentTable"=>"AssemblyTemplate","ParentColumn"=>"ID","LookupColumn"=>"Title","Order"=>"Title","AllowEmpty"=>1)
);

$Fields["ImgInact"] = array (
    "Type"=>array("Name"=>"Varchar","Size"=>"60","Mandatory"=>0),
    "Caption"=>local('ADMIN_PASSIVE_PICTURE'),
    "Edit"=>array("Field"=>"ImgInact"),
    "DefaultValue"=>""
);
//"Show"=>array("Field"=>"IF(ImgInact<>'',Concat(ImgInact,'<br><img  src=\'',ImgInact,'\'>'),'нет')","Width"=>"150"),

$Fields["ImgAct"] = array (
    "Type"=>array("Name"=>"Varchar","Size"=>"60","Mandatory"=>0),
    "Caption"=>local('ADMIN_ACTIVE_PICTURE'),
    "Edit"=>array("Field"=>"ImgAct"),
    "DefaultValue"=>""
);

$Fields["ShortName"] = array (
    "Type"=>array("Name"=>"Varchar","Size"=>"20","Mandatory"=>0),
    "Caption"=>local('ADMIN_LINE_ID'),
    "Edit"=>array("Field"=>"ShortName","Description"=>local('ADMIN_MUST_BE_UNIQUE').".<br>".local('ADMIN_CHOOSE_ELEMENT')),
    "Unique"=>1
);

$Fields["Descr"] = array (
    "Type"=>array("Name"=>"Text","Cols"=>"45","Rows"=>"3"),
    "Caption"=>local('ADMIN_DESCRIPTION'),
    "Edit"=>array("Field"=>"Descr")
);

$Fields["MetaKeywords"] = array (
    "Type"=>array("Name"=>"Text","Cols"=>"45","Rows"=>"3"),
    "Caption"=>local('ADMIN_KEYWORDS'),
    "Edit"=>array("Field"=>"MetaKeywords")
);


$Fields["RedirectURL"] = array (
    "Type"=>array("Name"=>"Varchar","Size"=>"60","Mandatory"=>0),
    "Caption"=>'URL',
    "Edit"=>array("Field"=>"RedirectURL","Description"=>local('ADMIN_AUTO_TRANSFER')),
    "Show"=>array("Field"=>"IF(ISNULL(RedirectURL) OR RedirectURL='',IF(ISNULL(ShortName) OR ShortName='',Concat('?p=',ID,''),Concat('?p=',ShortName,'')),Concat('<font color=#D368EE>',RedirectURL,'</font>'))","Width"=>"100")
);

/*$Fields["Auth"] = array (
    "Type"=>array("Name"=>"Checkbox","Size"=>"50"),
    "Caption"=>local('ADMIN_NEED_AUTHORISATION'),
    "Edit"=>array("Field"=>"Auth"),
    "Values"=>array("Checked"=>"Y","UnChecked"=>"N"),
    "DefaultValue"=>"N",
);*/

$Fields["CacheLifeTime"] = array (
    "Type"=>array("Name"=>"Varchar","Size"=>"6","Mandatory"=>0),
    "Caption"=>'Cache Time',
    "Edit"=>array("Field"=>"CacheLifeTime","Description"=>''),
    "Show"=>array("Field"=>"CacheLifeTime","Width"=>"75"),
    "DefaultValue"=>""
);

$Fields["inNewWindow"] = array (
    "Type"=>array("Name"=>"Checkbox","Size"=>"50"),
    "Caption"=>"_BLANK",
    "Edit"=>array("Field"=>"inNewWindow"),
    "Show"=>array("Field"=>"IF(inNewWindow='1','<font color=#FBCF00><b>".local('ADMIN_YES')."</b></font>','<font color=#D368EE>".local('ADMIN_NO')."</font>')"),
    "Values"=>array("Checked"=>"1","UnChecked"=>"0"),
    "DefaultValue"=>"0",
);

$Fields["notInMenu"] = array (
    "Type"=>array("Name"=>"Checkbox","Size"=>"50"),
    "Caption"=>"Not in main menu",
    "Edit"=>array("Field"=>"notInMenu "),
    "Show"=>array("Field"=>"IF(notInMenu ='1','<font color=#FBCF00><b>".local('ADMIN_YES')."</b></font>','<font color=#D368EE>".local('ADMIN_NO')."</font>')"),
    "Values"=>array("Checked"=>"1","UnChecked"=>"0"),
    "DefaultValue"=>"0",
);


$Fields["Parent_ID"] = array ("Hidden"=>1,"DefaultValue"=>"$PageParentID");
$Fields["Lang_ID"] = array ("Hidden"=>1,"DefaultValue"=>"$LanguageID");


$Options = array("Where"=>"Lang_id='$LanguageID'","Order"=>"OrderInParent");
$Options['Where'] .= ($PagesTreeWorkMode==1) ? " AND Parent_ID=$PageParentID AND (PageCategory_ID=0 OR PageCategory_ID='' OR PageCategory_ID is NULL)" : " AND Parent_ID=$PageParentID AND NOT (PageCategory_ID=0 OR PageCategory_ID='' OR PageCategory_ID is NULL)";

$Table = array("Name"=>"Pages", "PrimaryKey"=>"ID");
$Table['Actions']['content'] = array ("Caption"=>"<img src='/Admin/img/knopka_sent.gif' border='0' alt='".local('ADMIN_CONTENT')."'>","Width"=>"20","URL"=>"page.php?ID=#ID#");
$Table['Actions']['edit'] = array ("Caption"=>"<img src='/Admin/img/knopka_edit.gif' border='0' alt='".local('ADMIN_EDIT')."'>","Width"=>"20");
$Table['Actions']["delete"] = array ("Caption"=>"<img src='/Admin/img/knopka_del.gif' border='0' alt='".local('ADMIN_DELETE')."'>","Width"=>"20","Confirmation"=>array("Template"=>"".local('ADMIN_DELETE_PAGE')." '[#Title#]' ?"));
$Table['Actions']['cache_reset'] = array ("Caption"=>"<img src='/Admin/img/knopka_del.gif' border='0' alt='".local('ADMIN_PAGE_CACHE_RESET')."'>", "Width"=>"20","URL"=>"");

$Table['Events']["AfterEdit"] = array ("SetFocus"=>"OrderInParent");
$Table['Events']["AfterDelete"] = array ("Query"=>array("DELETE FROM PageTemplate_PageData WHERE Page_id=#ID#"));

$se = new SimpleEdit($Table, $Fields, $Options);

// Возвращает идентификатор родительской категории
// ==================================================================================
function get_parent_id($category_id) {
// ==================================================================================
    global $se;
    $result = mysql_db_query(DBName,"SELECT parent_id FROM Pages WHERE id=$category_id",$se->conn_id);
    $row = mysql_fetch_row($result);
    if (empty($row[0])) $row[0] = 0;
    return $row[0];
}

// Возвращает название категории
// ==================================================================================
function get_category_name($category_id) {
// ==================================================================================
    global $se;
    if ($category_id) {
        $result = mysql_db_query(DBName,"SELECT Title FROM Pages WHERE id=$category_id",$se->conn_id);
        $row = mysql_fetch_row($result);
        $ret = $row[0];
    } else {$ret = "";};
    return $ret;
}
// Возвращает название категории включая названия родительских категорий
// ==================================================================================
function get_category_full_name($category_id,$str="") {
// ==================================================================================
    $category_name =  get_category_name($category_id);
    $category_parent_id = get_parent_id($category_id);
    if (empty($str)) {$str = "<b>$category_name</b>";} else  {$str = "<a class=sitemapa href=\"?page_parent_id=$category_id\">$category_name</a> &nbsp;/&nbsp; ".$str;}
    if ($category_parent_id) { $str = get_category_full_name($category_parent_id,$str); }
    else { if ($category_id) $str = "<a href=\"?page_parent_id=0\">".get_category_name(0)."</a> &nbsp;/&nbsp; ".$str;   }
return $str;
}

function sub_category_exist($cat_id) {
    global $shpDB;
    $q = new DBquery($shpDB);
    $q->query("SELECT count(*) as catnum FROM Pages WHERE parent_id=$cat_id");
    $q->fetch();
    if ($q->val(catnum)) {$result = 1;} else {$result = 0;}
    $q->free();
    return $result;
}

echo get_category_full_name($PageParentID) . "<br>";



if ($action == "delete") {
    $result = @mysql_db_query(DBName,"SELECT count(*) as catnum FROM Pages WHERE parent_id=$ID",$se->conn_id);
    if ($result) {
        $row = mysql_fetch_row($result);
        if ($row[0] > 0) {
            $action = "";
            echo "<br><font color=red>".local('ADMIN_DELETE_PAGE_HAVE_INCLUDED', get_category_name($ID))."</font><br><br>";
        }
    } else {
        $action = "";
        echo "<br><font color=red>".local('ADMIN_DELETE_PAGE_ERROR')."</font>";
    }
}

$syncActionslist = array('add', 'delete', 'update');
if(in_array($_REQUEST['action'], $syncActionslist)){
    require_once 'PageCacheDataSync.php';
}

if($_REQUEST['action']=='cache_reset'){
    require_once 'PageCacheReset.php';
}

$se->ProcessEvent($_REQUEST);



require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>