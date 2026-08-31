<?
foreach ($_REQUEST as $key=>$val) $$key=$val;

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";

if($_REQUEST['action'] == 'rules'){
    require_once('rules.php');
    
}

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
    "Edit"=>array("Field"=>"Password","Description"=>""),
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

$Table = array("Name"=>"Users", "PrimaryKey"=>"ID");


$Table["BindTable"]["User_UserGroup"] = array(
    "ChildField"=>"UserID",
    "BindField"=>"UserGroupID",
    "BindLookupTable"=>"UserGroups UG",
    "BindParentColumn"=>"UG.ID",
    "BindLookupColumn"=>"UG.Title",
    "Options" => array("BindMethod"=>"CheckBoks",
                       "BindLookupTableWhere"=>"","Caption"=>local('ADMIN_INCLUDE_IN_GROUPS'),
                       "BindLookupColumnOrder"=>"ORDER BY UG.Title")
);

/*
$Table["BindTable"]["User"] = array(
    "ChildField"=>"ID",
    "BindField"=>"ID",
    "BindLookupTable"=>"UserAllowedDB UDB, GameDB GDB",
    "BindParentColumn"=>"UDB.id_user",
    "BindLookupColumn"=>"GDB.dbname",
    "Options" => array("BindMethod"=>"CheckBoks",
                       "BindLookupTableWhere"=>" AND UDB.id_database = GDB.id_database","Caption"=>"Allowed DB",
                       "BindLookupColumnOrder"=>"ORDER BY UDB.id_user")
);
/*
$Table[BindTable][User_Permission] = array(
    "ChildField"=>"UserID",
    "BindField"=>"PermissionID",
    "BindLookupTable"=>"Permissions P, PermissionGroups PG",
    "BindParentColumn"=>"P.ID",
    "BindLookupColumn"=>"Concat('<b>',PG.Title,'</b> / ',P.Title)",
    "Options" => array("BindMethod"=>"CheckBoks",
                       "BindLookupTableWhere"=>"WHERE P.PermissionGroupID=PG.ID","Caption"=>local('ADMIN_ADDITIONAL_RIGHTS'),
                       "BindLookupColumnOrder"=>"ORDER BY PG.Title, P.Title")
);
*/

$Table['Actions']['edit'] = array ("Caption"=>"<img src='/Admin/img/knopka_edit.gif' border='0' alt='".local('ADMIN_EDIT')."'>","Width"=>"20");
$Table['Actions']['rules'] = array ("Caption"=>"Permissions","Width"=>"20", "Url"=>"ID=#ID#" );

$Table['Events']["AfterEdit"] = array ("SetFocus"=>"Login");

$Options = array("Where"=>"isSuperAdmin='N'");
$Options['ForbidDelete'] = 1;

$se = new SimpleEdit($Table, $Fields, $Options);

if ($action=="add") {
    $err = false;
    $query = "SELECT count(*) FROM Users WHERE Login='".$_POST["FieldLogin"]."'";
    $resut = @mysql_db_query(DBName,$query,$se->conn_id);
    if ($resut) {
        $row = mysql_fetch_row($resut);
        if ($row[0]>0) {
            $err = local('ADMIN_LOGIN_UNUNICK', $_POST["FieldLogin"]);
        }
    } else $err =local('ADMIN_DATABASE_ACCESS_ERROR');
    if ($err) {
        $se->InsertError .= "<li>$err</li>";
    }
}

$Params['action'] = $action;
$Params['ID'] = $ID;

if(!$GLOBALS['NO_OUTPUT_FLAG']){
    $se->ProcessEvent($Params);
}


require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>