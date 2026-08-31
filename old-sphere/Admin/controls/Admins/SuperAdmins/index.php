<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";

$Fields["Login"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"20","Mandatory"=>1,"Class"=>"formtxt"),
	"Caption"=>local('ADMIN_LOGIN'),
	"Edit"=>array("Field"=>"Login"),
	"Show"=>array("Field"=>"Login"),
	"ForbidUpdate"=>0
);

$Fields["Password"] = array (
	"Type"=>array("Name"=>"Password","Size"=>"20","Mandatory"=>0,"Class"=>"formtxt"),
    "Caption"=>local('ADMIN_PASSWORD'),
	"Edit"=>array("Field"=>"Password"),
);

$Fields["FIO"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"20","Mandatory"=>0,"Class"=>"formtxt"),
    "Caption"=>local('ADMIN_FULL_NAME'),
	"Show"=>array("Field"=>"Concat(LastName,' ',FirstName,' ',SecondName)","Description"=>""),
);

$Fields["LastName"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"20","Mandatory"=>1,"Class"=>"formtxt"),
    "Caption"=>local('ADMIN_SURNAME'),
	"Edit"=>array("Field"=>"LastName","Description"=>""),
);
$Fields["FirstName"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"20","Mandatory"=>1,"Class"=>"formtxt"),
    "Caption"=>local('ADMIN_FIRST_NAME'),
	"Edit"=>array("Field"=>"FirstName","Description"=>""),
);
$Fields["SecondName"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"20","Mandatory"=>0,"Class"=>"formtxt"),
    "Caption"=>local('ADMIN_FATHERS_NAME'),
	"Edit"=>array("Field"=>"SecondName","Description"=>""),
);

$Fields["Email"] = array (
	"Type"=>array("Name"=>"Email","Size"=>"30","Mandatory"=>0,"Class"=>"formtxt"),
    "Caption"=>"Email",
	"Edit"=>array("Field"=>"Email","Description"=>""),
	"Show"=>array("Field"=>"Email"),
);

$Fields["Active"] = array (
	"Type"=>array("Name"=>"Checkbox","Size"=>"50"),
	"Caption"=>local('ADMIN_ACTIVE'),
	"Show"=>array("Field"=>"IF(Active='Y','<font color=#FBCF00>".local('ADMIN_YES')."</font>','<font color=#D368EE>".local('ADMIN_NO')."</font>')","Width"=>"50"),
	"Edit"=>array("Field"=>"Active","Description"=>local('ADMIN_ACCESS_ENABLED')),
	"Values"=>array("Checked"=>"Y","UnChecked"=>"N"),
	"DefaultValue"=>"Y",
);

$Fields["isSuperAdmin"] = array ("Hidden"=>1,"DefaultValue"=>"Y");


$Table = array("Name"=>"Users", "PrimaryKey"=>"ID");


$Table['Actions']['edit'] = array ("Caption"=>"<img src='/Admin/img/knopka_edit.gif' border=0 alt='".local('ADMIN_EDIT')."'>","Width"=>"20");
//$Table['Actions']['delete'] = array ("Caption"=>"<img src='/Admin/img/knopka_sent.gif' border='0' alt='".local('ADMIN_DELETE')."'>","Width"=>"20");



$Table['Events']["AfterEdit"] = array ("SetFocus"=>"Login");

$Options = array("Where"=>"isSuperAdmin='Y'","Order"=>"LastName, FirstName");
$Options['ForbidDelete'] = 1;

/*
$Table["BindTable"]["UserAllowedDB"] = array(
	"ChildField"=>"id_user",
	"BindField"=>"id_database",
	"BindLookupTable"=>"GameDB GDB",
	"BindParentColumn"=>"GDB.id_database",
	"BindLookupColumn"=>"GDB.dbname",
	"Options" => array("BindMethod"=>"CheckBoks",
					   "BindLookupTableWhere"=>"","Caption"=>"Allowed DB",
					   "BindLookupColumnOrder"=>"ORDER BY id_database")
);
*/

$se = new SimpleEdit($Table, $Fields, $Options);

switch ($action)
{
	case 'add':
		$err = false;
		$query = "SELECT count(*) FROM Users WHERE Login='".$_POST["FieldLogin"]."'";
		$resut = @mysql_db_query(DBName,$query,$se->conn_id);
	
		if($resut) 
		{
			$row = mysql_fetch_row($resut);
			if($row[0] > 0) 
			{
				$err = local('ADMIN_LOGIN_UNUNICK', $_POST["FieldLogin"]);
			}
		} else {
			$err = local('ADMIN_DATABASE_ACCESS_ERROR');
		}
	
		if($err) 
		{
			$se->InsertError .= "<li>$err</li>";
		} else {
			actionLog('Added new super admin: <b>'.$_POST["FieldLogin"].'</b>');
		}
		break;
		
	case 'update':
	case 'apply':
		$sql = "SELECT * FROM Users WHERE ID = ".(int)$_GET['ID'];
		$res = @mysql_db_query(DBName,$sql,$se->conn_id);
		$rs = mysql_fetch_assoc($res);
		
		$details = '';
		
		if($rs['Login'] != $_POST["FieldLogin"])
		{
			$details .= "Login changed: ".$rs['Login'].' -> '.$_POST["FieldLogin"]."<br>\n";
		}
		
		if(!empty($_POST["FieldPassword"]) && $rs['Password'] != md5($_POST["FieldPassword"]))
		{
			$details .= "Password changed<br>\n";
		}
						
		if($rs['Active'] != $_POST["FieldActive"])
		{
			if($_POST["FieldActive"] == 'Y')
			{
				$details .= "Account activated<br>\n";
			} else {
				$details .= "Account deactivated<br>\n";
			}
		}
		
		actionLog('Update super admin data: <b>'.$_POST["FieldLogin"].'</b>', $details);
		break;

	default:
		break;
}

$Params['action'] = $action;
$Params['ID'] = $ID;

$se->ProcessEvent($Params);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>