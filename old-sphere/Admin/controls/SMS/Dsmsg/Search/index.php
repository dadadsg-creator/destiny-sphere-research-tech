<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit_without_cvs.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";


	$Table = array("Name"=>"dsmsg_messages", "PrimaryKey"=>"ID");



        $Fields["MESSAGEID"] = array (
		"Type"=>array("Name"=>"Varchar","Size"=>"11", "Mandatory"=>1),
		"Caption"=>"ID",
		"Edit"=>array("Field"=>"MESSAGEID", "Width"=>"11"),
		"Show"=>array("Field"=>"MESSAGEID", "Width"=>"11"),
		"Unique"=>1,
        );

        $Fields["PHONE"] = array (
		"Type"=>array("Name"=>"Varchar","Size"=>"12", "Mandatory"=>1),
		"Caption"=>"Телефон",
		"Edit"=>array("Field"=>"PHONE", "Width"=>"12"),
		"Show"=>array("Field"=>"PHONE", "Width"=>"12"),
		"Search"=>array("Template"=>"(PHONE like '%#Value#%' OR PHONE='#Value#')"),
        );

        $Fields["TIME"] = array (
                "Type"=>array("Name"=>"Datetime","Size"=>"20", "Mandatory"=>0),
		"Caption"=>local('ADMIN_DATE'),
                "Edit"=>array("Field"=>"TIME", "Width"=>"20"),
                "Show"=>array("Field"=>"DATE_FORMAT(TIME,'%d.%m.%Y %H:%i:%s')", "Width"=>"20"),
//		"ForbidUpdate"=>"1"
        );

        $Fields["TRIGGER"] = array (
		"Type"=>array("Name"=>"Varchar","Size"=>"11", "Mandatory"=>1),
		"Caption"=>local('ADMIN_USER'),
		"Edit"=>array("Field"=>"TRIGGER", "Width"=>"30"),
		"Show"=>array("Field"=>"TRIGGER", "Width"=>"30"),
		"Search"=>array("Template"=>"(TRIGGER like '%#Value#%' OR TRIGGER = '#Value#')"),
        );












        $Fields["USERNAME_FROM"] = array (
		"Type"=>array("Name"=>"Varchar","Size"=>"11", "Mandatory"=>1),
		"Caption"=>local('ADMIN_USER'),
		"Edit"=>array("Field"=>"USERNAME_FROM", "Width"=>"30"),
		"Show"=>array("Field"=>"USERNAME_FROM", "Width"=>"30"),
		"Search"=>array("Template"=>"(USERNAME_FROM like '%#Value#%' OR USERNAME_FROM = '#Value#')"),
        );
        $Fields["WORLD"] = array (
		"Type"=>array("Name"=>"Varchar","Size"=>"11", "Mandatory"=>1),
		"Caption"=>local('ADMIN_WORLD'),
		"Edit"=>array("Field"=>"WORLD", "Width"=>"30"),
		"Show"=>array("Field"=>"WORLD", "Width"=>"30"),
		"Search"=>array("Template"=>"(WORLD like '%#Value#%' OR WORLD = '#Value#')"),
        );

        $Fields["USERNAME_TO"] = array (
		"Type"=>array("Name"=>"Varchar","Size"=>"11", "Mandatory"=>1),
		"Caption"=>local('ADMIN_USER'),
		"Edit"=>array("Field"=>"USERNAME_TO", "Width"=>"30"),
		"Show"=>array("Field"=>"USERNAME_TO", "Width"=>"30"),
		"Search"=>array("Template"=>"(USERNAME_TO like '%#Value#%' OR USERNAME_TO = '#Value#')"),
        );

        $Fields["TEXT"] = array (
		"Type"=>array("Name"=>"Text","Size"=>"11", "Mandatory"=>1),
		"Caption"=>local('ADMIN_USER'),
		"Edit"=>array("Field"=>"TEXT", "Width"=>"30"),
		"Show"=>array("Field"=>"TEXT", "Width"=>"30"),
		"Search"=>array("Template"=>"(TRIGGER like '%#Value#%' OR TRIGGER = '#Value#')"),
        );








$Table[Events]["AfterEdit"] = array ("SetFocus"=>"MESSAGEID");

$Options[Order] = "ID DESC";

$Options[ForbidBindTable] = 1;
$Options[ForbidInsert] = 0;
$Options[ForbidEdit] = 0;



        $Table[Actions]["edit"] = array ("Caption"=>local('ADMIN_EDIT'),"Width"=>"20",
                "IMG"=>array(
                        "Inactive"=>"/Admin/img/knopka_edit.gif",
                        "Active"=>"/Admin/img/knopka_edit-over.gif",
                        "Down"=>"/Admin/img/knopka_edit-down.gif",
                        "Width"=>20,
                        "Height"=>20)
        );
        $Table[Events]["AfterEdit"] = array ("SetFocus"=>"Alert");

        $Table[Actions]["delete"] = array (
                "Caption"=>local('ADMIN_DELETE'),
                "Width"=>"20",
                "Confirmation"=>array("Template"=>local('ADMIN_DELETE')." '[#Alert#]' ?"),
                "IMG"=>array(
                        "Inactive"=>"/Admin/img/knopka_del.gif",
                        "Active"=>"/Admin/img/knopka_del-over.gif",
                        "Down"=>"/Admin/img/knopka_del-down.gif",
                        "Width"=>20,
                        "Height"=>20)
        );

$Table['Style']['Show']['Table'] = array ("Border"=>0,"Cellpadding"=>5,"Cellspacing"=>1);
$Table['Style']['Show']['Table']['Td']['Class'] = "FormText";
$Table['Style']['Show']['Table']['Td']['Background'] = "/Admin/img/table-white-bg.gif";
$Table['Style']['Show']['Table']['Header']['Bgcolor'] = "#636E88";
$Table['Style']['Show']['Table']['Header']['Class'] = "FormText";

$Table['Style']['BindTable'] = array ("Border"=>0,"Cellpadding"=>5,"Cellspacing"=>1);
$Table['Style']['BindTable'][Td]['Class'] = "FormText";
$Table['Style']['BindTable'][Td]['Background'] = "/Admin/img/table-white-bg.gif";
$Table['Style']['BindTable']['Header']['Bgcolor'] = "#636E88";
$Table['Style']['BindTable']['Header']['Class'] = "FormText";

$Table['Style']['Edit']['Table'] = array ("Border"=>0,"Cellpadding"=>5,"Cellspacing"=>1);
$Table['Style']['Edit']['Table']['Td']['Class'] = "FormText";
$Table['Style']['Edit']['Table']['Td']['Background'] = "/Admin/img/table-white-bg.gif";
$Table['Style']['Edit']['Table']['Header']['Bgcolor'] = "#636E88";
$Table['Style']['Edit']['Table']['Header']['Class'] = "FormText";






















































$se = new SimpleEdit($Table, $Fields, $Options);

$Params[action] = $action;
$Params[ID] = $ID;

$se->ProcessEvent($Params);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";


?>