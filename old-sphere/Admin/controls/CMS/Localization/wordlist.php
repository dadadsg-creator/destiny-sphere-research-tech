<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";

?>
<a href="index.php" class=maintxt><img src="/Admin/img/cms_btn/lang_non.gif" border="0"></a>
<img src="/Admin/img/cms_btn/words_vis.gif" border="0">
<a href="dictionary.php" class=maintxt><img src="/Admin/img/cms_btn/trans_non.gif" border="0"></a><br>
<br> 
<?


$Fields["Word"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
	"Caption"=>local('ADMIN_KEY'),
	"Show"=>array("Field"=>"Word","Width"=>"100%"),
	"Edit"=>array("Field"=>"Word","Description"=>local('ADMIN_UNIQUE_ALL_SITE')),
	"Unique"=>1
);
$Options[Order] = "Word";

$Table = array("PrimaryKey"=>"ID","Name"=>"Words");
$Table[Actions][edit] = array ("Caption"=>"<img src='/Admin/img/knopka_edit.gif' border=0 alt='".local('ADMIN_EDIT')."'>","Width"=>"20");
$Table[Actions]["delete"] = array ("Caption"=>"<img src='/Admin/img/knopka_del.gif' border=0 alt='".local('ADMIN_DELETE')."'>","Width"=>"20","Confirmation"=>array("Template"=>local('ADMIN_DELETE')." '[#Word#]' ?"));

$Table[Events]["AfterDelete"] = array (
	"Query"=>array(
		"DELETE FROM Dictionary WHERE Word_id=#ID#",
	)
);

$Params[action] = $action;
$Params[ID] = $ID;

$se = new SimpleEdit($Table, $Fields, $Options);
$se->ProcessEvent($Params);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>
