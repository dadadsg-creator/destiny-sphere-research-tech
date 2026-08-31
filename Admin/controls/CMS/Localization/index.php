<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";

?>
<img src="/Admin/img/cms_btn/lang_vis.gif" border="0">
<a href="wordlist.php" class="maintxt"><img src="/Admin/img/cms_btn/words_non.gif" border="0"></a>
<a href="dictionary.php" class="maintxt"><img src="/Admin/img/cms_btn/trans_non.gif" border="0"></a><br>
<br> 
<?


$Fields["ID"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"3","Mandatory"=>0),
	"Caption"=>"ID",
	"Show"=>array("Field"=>"ID","Width"=>"50"),
	"Edit"=>array("Field"=>"Concat('<b>',ID,'</b>')","Description"=>"(".local('ADMIN_ID_STRING_3x_SYNBOLS').")"),
	"ForbidUpdate"=>1
);

$Fields["Title"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
	"Caption"=>local('ADMIN_NAME'),
	"Show"=>array("Field"=>"Title","Width"=>"100%"),
	"Edit"=>array("Field"=>"Title"),
);

$Fields["Url"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
	"Caption"=>"Url",
	"Show"=>array("Field"=>"Url","Width"=>"50"),
	"Edit"=>array("Field"=>"Url"),
);

$Fields["Charset"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>0),
	"Caption"=>"Charset",
	"Show"=>array("Field"=>"Charset","Width"=>"50"),
	"Edit"=>array("Field"=>"Charset","Description"=>local('ADMIN_OUTPUT_IN_TEG_META').".<br>".local('ADMIN_EXEMPLE').": &lt;meta  http-equiv=\"Content-Type\" content=\"text/html; charset=<b>windows-1251</b>\"&gt;"),
);

$Fields["Active"] = array (
	"Type"=>array("Name"=>"Checkbox","Size"=>"50"),
	"Caption"=>local('ADMIN_ACTIVE'),
	"Show"=>array("Field"=>"IF(Active='Y','<font color=#FBCF00>".local('ADMIN_YES')."</font>','<font color=#D368EE>".local('ADMIN_NO')."</font>')","Width"=>"50"),
	"Edit"=>array("Field"=>"Active","Description"=>""),
	"Values"=>array("Checked"=>"Y","UnChecked"=>"N"),
	"DefaultValue"=>"Y",
);

$Table = array("Name"=>"Langs", "PrimaryKey"=>"ID");

$Table[Actions][edit] = array ("Caption"=>"<img src='/Admin/img/knopka_edit.gif' border='0' alt='".local('ADMIN_EDIT')."'>","Width"=>"20");

$Options[Order] = "ID";
$Options[ForbidInsert] = 1;

$se = new SimpleEdit($Table, $Fields, $Options);

$Params[action] = $action;
$Params[ID] = $ID;

$se->ProcessEvent($Params);


require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>
