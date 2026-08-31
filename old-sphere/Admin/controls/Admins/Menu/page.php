<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/IncludeManager.class.php";

if(!$_REQUEST['action']){
    headerLocation ( 'index.php' );
}

//require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";

$Fields["ShowOrder"] = array (
    "Type"=>array("Name"=>"Varchar","Size"=>"3","Mandatory"=>0),
    "Caption"=>local('ADMIN_SORTING_ORDER'),
    "Edit"=>array("Field"=>"ShowOrder"),
    "Show"=>array("Field"=>"ShowOrder","Width"=>"20"),
    "DefaultValue"=>""
);



    $Fields["Title"] = array ("Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
                         "Caption"=>local('ADMIN_MENU_ITEM'),
                         "Show"=>array("Field"=>"Title","Width"=>"100%"),
                         "Edit"=>array("Field"=>"Title","Description"=>local('ADMIN_LIMIT_255_SYMBOLS')),
                        );

    $Fields["Url"] = array (
    "Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>0),
    "Caption"=>"Url",
    "Edit"=>array("Field"=>"Url", "Description"=>local('ADMIN_PATH_DIR').". ".local('ADMIN_EXEMPLE').": /Admin/controls/cms_pages/")
);

if($_REQUEST['Parent_ID'] || $_REQUEST['FieldParent_ID']){
$Fields["Parent_ID"] = array (
        "Type"=>array("Name"=>"Varchar","Size"=>"5","Mandatory"=>0),
        "Caption"=>local('ADMIN_CATEGORY'),
        "Edit"=>array("Field"=>"Parent_ID","Description"=>""),
        "Lookup"=>array ("Type"=>"Master","ParentTable"=>"AdminMenu","ParentColumn"=>"ID","LookupColumn"=>"Title","Where"=>"","Order"=>"BINARY Title","AllowEmpty"=>0),
        "DefaultValue"=>$_REQUEST['Parent_ID']
);
}

$Fields["Active"] = array (
    "Type"=>array("Name"=>"Checkbox","Size"=>"50"),
    "Caption"=>local('ADMIN_ACTIVE'),
    "Edit"=>array("Field"=>"Active"),
    "Show"=>array("Field"=>"IF(Active='Y','<font color=#FBCF00>".local('ADMIN_YES')."</font>','<font color=#D368EE>".local('ADMIN_NO')."</font>')"),
    "Values"=>array("Checked"=>"Y","UnChecked"=>"N"),
    "DefaultValue"=>"Y",
);

    $Fields["Description"] = array ("Type"=>array("Name"=>"Text","Size"=>"50","Mandatory"=>0),
                         "Caption"=>local('ADMIN_DESCRIPTION'),
                         "Show"=>array("Field"=>"Description","Width"=>"100%"),
                         "Edit"=>array("Field"=>"Description","Description"=>""),
                        );




$Options['Order'] = "ShowOrder";
//$Options['Where'] = " part_id  = ".$part_id." and Parent_ID=".$parent_id;

//
$Options['DoubleDataProtection_simple'] = 1;

$Table = array("Name"=>"AdminMenu", "PrimaryKey"=>"ID");


/*$Table['BindTable']['AdminMenu_Permission'] = array(
    "ChildField"=>"AdminMenuID",
    "BindField"=>"PermissionID",
    "BindLookupTable"=>"Permissions P, PermissionGroups PG",
    "BindParentColumn"=>"P.ID",
    "BindLookupColumn"=>"Concat('<b>',PG.Title,'</b> / ',P.Title)",
    "Options" => array("BindMethod"=>"CheckBoks",
                       "BindLookupTableWhere"=>"WHERE P.PermissionGroupID=PG.ID",
                       "Caption"=>local('ADMIN_ACCESS_RIGHTS'),
                       "Description"=>local('ADMIN_ANY_RIGHT_ACCESS'),
                       "BindLookupColumnOrder"=>"ORDER BY PG.Title, P.Title")
);*/



$Table['Actions']['edit'] = array ("Caption"=>"<img src='/Admin/img/knopka_edit.gif' border='0' alt='".local('ADMIN_EDIT')."'>","Width"=>"20");
$Table['Actions']["delete"] = array ("Caption"=>"<img src='/Admin/img/knopka_del.gif' border='0' alt='".local('ADMIN_DELETE')."'>","Width"=>"20","Confirmation"=>array("Template"=>local('ADMIN_DELETE')." '[#Title#]' ?"));

$Table['Events']["AfterEdit"] = array ("SetFocus"=>"Title");

//$Fields["Parent_ID"] = array ("Hidden"=>1,"DefaultValue"=>"$PageParentID");

$se = new SimpleEdit($Table, $Fields, $Options);

$Params = $_REQUEST;

$se->ProcessEvent($Params);

if($_REQUEST['action'] == 'update'){
    headerLocation ( 'index.php' );
}

if($_REQUEST['action'] == 'add'){
    headerLocation ( 'index.php' );
}

if($_REQUEST['action'] == 'delete'){
    headerLocation ( 'index.php' );
}
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>
