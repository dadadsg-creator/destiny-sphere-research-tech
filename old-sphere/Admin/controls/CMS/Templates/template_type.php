<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";

$Fields["TypeID"] = array ("Type"=>array("Name"=>"Varchar","Size"=>"20","Mandatory"=>1),
							 "Caption"=>local('ADMIN_LINE_TYPE_ID'),
							 "Edit"=>array("Field"=>"TypeID"),
							 "Show"=>array("Field"=>"TypeID","Width"=>"100"),
							 "Search"=>array("Template"=>"('#Value#'='' OR TypeID LIKE '%#Value#%')")
							);

$Fields["Title"] = array ("Type"=>array("Name"=>"Varchar","Size"=>"40","Mandatory"=>1),
						 "Caption"=>local('ADMIN_NAME'),
						 "Edit"=>array("Field"=>"Title"),
						 "Show"=>array("Field"=>"Title","Width"=>"100%"),
						 "Search"=>array("Template"=>"('#Value#'='' OR Title LIKE '%#Value#%')")
						);

$Options = array("Order"=>"Title");

$Table = array("Name"=>"TemplateType", "PrimaryKey"=>"TypeID");
$Table['Actions']['edit'] = array ("Caption"=>"<img src='/Admin/img/knopka_edit.gif' border='0' alt='".local('ADMIN_EDIT')."'>","Width"=>"20");
$Table['Actions']["delete"] = array ("Caption"=>"<img src='/Admin/img/knopka_del.gif' border='0' alt='".local('ADMIN_DELETE')."'>","Width"=>"20","Confirmation"=>array("Template"=>local('ADMIN_DELETE_MOULD_TYPE')." '[#Title#]' ?"));

$Table['Events']["AfterEdit"] = array ("SetFocus"=>"Title");
$Table['Events']["AfterDelete"] = array ("Query"=>array("DELETE FROM TemplateTypeTree WHERE TypeID='#ID#'",
												      "DELETE FROM TemplateTypeTree WHERE ParentTypeID='#ID#'"));

$Table['BindTable']['TemplateTypeTree'] = array(
	"ChildField"=>"ParentTypeID",
	"BindField"=>"TypeID",
	"BindLookupTable"=>"TemplateType",
	"BindParentColumn"=>"TemplateType.TypeID",
	"BindLookupColumn"=>"TemplateType.Title",
	"Options" => array("BindMethod"=>"CheckBoks",
					   "BindLookupTableWhere"=>"WHERE TemplateType.TypeID<>'$ID'","Caption"=>local('ADMIN_INTERNAL_PATTERNS'),
					   "BindLookupColumnOrder"=>"ORDER BY TemplateType.Title")
);

$se = new SimpleEdit($Table, $Fields, $Options);

if ($action == "delete") {
	$Query = "SELECT count(*) FROM Templates WHERE TypeID='$ID'";
	$result = @mysql_db_query(DBName,$Query,$se->conn_id);
	if ($result) {
		$row = mysql_fetch_row($result);
		if ($row[0] > 0) {
			$action = "";
			echo "<font color=red>".local('ADMIN_DELETE_ERROR_MOULDS_THIS_TYPE')." </font><br><br>";
		}
	} else {
		$action = "";
		echo "<font color=red>".local('ADMIN_ERROR_NEED_MESSAGE_DEV')."</font>";
	}
}

$Params['action'] = $action;
$Params['ID'] = $ID;

$se->ProcessEvent($Params);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>