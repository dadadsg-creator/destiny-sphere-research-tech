<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=Unionjournal><font size=3><b>HELP</b></font></a></p>';

$Fields["ts"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
	"Caption"=>local('ADMIN_DATE'),
	"Show"=>array("Field"=>"DATE_FORMAT(ts,'%d.%m.%Y %H:%i')","Width"=>"100"),
);

$Fields["User_id"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"5","Mandatory"=>0),
    "Caption"=>local('ADMIN_USER'),
	"Show"=>array("Field"=>"User_id","Width"=>"150"),
	"Lookup"=>array ("Type"=>"Master","ParentTable"=>"Users","ParentColumn"=>"ID","LookupColumn"=>"Concat(LastName,' ',FirstName)","AllowEmpty"=>0),
	"Search"=>array("Template"=>"('#Value#'='' OR User_id='#Value#')")
);


/*$Fields["AdminMenu_ID"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"5","Mandatory"=>0),
    "Caption"=>local('ADMIN_PART'),
	"Show"=>array("Field"=>"AdminMenu_ID","Width"=>"150"),
	"Lookup"=>array (
		"Type"=>"Master",
		"ParentTable"=>"AdminMenu",
		"ParentColumn"=>"ID",
		"LookupColumn"=>"Title",
		"AllowEmpty"=>0,
		"Where" => "Url <> '#'"
	),
	"Search"=>array("Template"=>"('#Value#'='' OR AdminMenu_ID='#Value#')")
);*/

$Fields["AdminMenuName"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
	"Caption"=>local('ADMIN_PART'),
	"Show"=>array("Field"=>"AdminMenuName","Width"=>"100"),	
	"Search"=>array("Template"=>"('#Value#'='' OR AdminMenuName LIKE '%#Value#%')")
);

$Fields["Action"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
	"Caption"=>local('ADMIN_ACTION'),
	"Show"=>array("Field"=>"Action","Width"=>"250"),
	"Search"=>array("Template"=>"('#Value#'='' OR Action LIKE '%#Value#%')")
);

$Fields["Details"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
	"Caption"=>"Details",
	"Show"=>array("Field"=>"Details","Width"=>"50"),
	"Search"=>array("Template"=>"('#Value#'='' OR Details LIKE '%#Value#%')")
);


$Table = array("Name"=>"ActionsLog", "PrimaryKey"=>"ts");

$Table['Actions']['rules'] = array (
	"Caption"=>"<img src='/Admin/img/knopka_edit.gif' border='0' alt=''>",
	"Show"=>array("Field"=>"Details","Width"=>"250"),
	"Url"=>"1",
	"Width"=>"20");

$Options[ForbidInsert] = 1;
$Options[ForbidEdit] = 1;
$Options[ForbidUpdate] = 1;
$Options[ForbidDelete] = 1;

$Options[ExtensionQuery] = "DATE_FORMAT(ts,'%Y%m%d%H%i%s') as SortDate";
$Options[Order] = "SortDate DESC";

$se = new SimpleEdit($Table, $Fields, $Options);

$Params[action] = $action;
$Params[ID] = $ID;

$se->ProcessEvent($Params);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>