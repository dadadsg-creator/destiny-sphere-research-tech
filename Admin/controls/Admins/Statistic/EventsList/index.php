<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=Eventslist><font size=3><b>HELP</b></font></a></p>';

$Fields["User"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"5","Mandatory"=>0),
    "Caption"=>local('ADMIN_USER'),
	"Show"=>array("Field"=>"User_id"),
	"Lookup"=>array ("Type"=>"Master","ParentTable"=>"Users","ParentColumn"=>"ID","LookupColumn"=>"Concat(LastName,' ',FirstName)","AllowEmpty"=>0)
);

$Fields["Connections"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
	"Caption"=>local('ADMIN_CONNECTIONS_NUMBER'),
	"Show"=>array("Field"=>"count(SID)","Width"=>"150"),
);


$Fields["Duration"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
	"Caption"=>local('ADMIN_DURATION'),
	"Show"=>array("Field"=>"IF(SUM(UNIX_TIMESTAMP(LastTime)-UNIX_TIMESTAMP(StartTime))>3600,CONCAT(FLOOR(SUM(UNIX_TIMESTAMP(LastTime)-UNIX_TIMESTAMP(StartTime))/60/60),' ".local('ADMIN_HOUR').".', ROUND(MOD(SUM(UNIX_TIMESTAMP(LastTime)-UNIX_TIMESTAMP(StartTime)),3600)/60) ,' ".local('ADMIN_MINUTE').".'),CONCAT(ROUND(SUM(UNIX_TIMESTAMP(LastTime)-UNIX_TIMESTAMP(StartTime))/60),' ".local('ADMIN_MINUTE').".'))","Width"=>"50"),
);

$Table = array("Name"=>"Sessions", "PrimaryKey"=>"SID");

$Options[ForbidInsert] = 1;
$Options[ForbidEdit] = 1;
$Options[ForbidUpdate] = 1;
$Options[ForbidDelete] = 1;

$Options[Group] = "User_id";
$Options[Order] = "Connections DESC";

$se = new SimpleEdit($Table, $Fields, $Options);

$Params[action] = $action;
$Params[ID] = $ID;

$se->ProcessEvent($Params);


require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>