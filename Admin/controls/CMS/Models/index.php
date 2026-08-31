<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";

$Fields["Title"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
	"Caption"=>local('ADMIN_NAME'),
	"Edit"=>array("Field"=>"Title","Description"=>"50"),
	"Show"=>array("Field"=>"Concat('<a class=sitemapa href=\'edit.php?ID=',ID,'\'>',Title,'</a>')","Width"=>"100%"),
	"DefaultValue"=>"",
	"Search"=>array("Template"=>"('#Value#'='' OR Title LIKE '%#Value#%')"),
);

if (in_array($action,array("new","add"))) {
	$Fields["MainTemplate_id"] = array (
		"Type"=>array("Name"=>"Varchar","Size"=>"5","Mandatory"=>1),
	    "Caption"=>local('ADMIN_MAIN_PATTERN'),
		"Edit"=>array("Field"=>"MainTemplate_id","Description"=>""),
		"Lookup"=>array ("Type"=>"Master","ParentTable"=>"Templates","ParentColumn"=>"ID","LookupColumn"=>"Title","Where"=>"TypeID='main' AND (Lang_id='$LanguageID' OR Lang_id='' OR Lang_id is NULL)","Order"=>"Title","AllowEmpty"=>0)
	);
}

$Fields["Lang_id"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"5","Mandatory"=>0),
    "Caption"=>local('ADMIN_LANGUAGE'),
	"Edit"=>array("Field"=>"Lang_id","Description"=>""),
	"Show"=>array("Field"=>"Lang_id","Width"=>20),
	"Lookup"=>array ("Type"=>"Master","ParentTable"=>"Langs","ParentColumn"=>"ID","LookupColumn"=>"Title","Order"=>"Title DESC","AllowEmpty"=>1)
);


$Options = array("Where"=>"(Lang_id='$LanguageID' OR Lang_id='' OR Lang_id is NULL)","Order"=>"Title");

$Table = array("Name"=>"AssemblyTemplate", "PrimaryKey"=>"ID");
$Table['Actions']['edit'] = array ("Caption"=>"<img src='/Admin/img/knopka_edit.gif' border='0' alt='".local('ADMIN_EDIT')."'>","Width"=>"20");
$Table['Actions']["delete"] = array ("Caption"=>"<img src='/Admin/img/knopka_del.gif' border='0' alt='".local('ADMIN_DELETE')."'>","Width"=>"20","Confirmation"=>array("Template"=>local('ADMIN_DELETE_MAKET', '[#Title#]')));

$Table['Events']["AfterEdit"] = array ("SetFocus"=>"Title");
$Table['Events']["AfterInsert"] = array ("Query"=>array("INSERT INTO PageTemplate (AssemblyTemplate_id,ApplyTemplate_id,OrderInTemplate) VALUES (#NEW_ID#,$FieldMainTemplate_id,1)"));
$Table['Events']["AfterDelete"] = array ("Query"=>array("DELETE FROM PageTemplate WHERE AssemblyTemplate_id=#ID#"));

$se = new SimpleEdit($Table, $Fields, $Options);

$Params['action'] = $action;
$Params['ID'] = $ID;

$se->ProcessEvent($Params);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>