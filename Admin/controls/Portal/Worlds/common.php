<?
$IMG_DIR = "/img/news/";
$IMG_UPLOAD_DIR = $DOCUMENT_ROOT . $IMG_DIR;


$Table = array("Name"=>"Worlds", "PrimaryKey"=>"ID");


$Fields["Name"] = array (
"Type"=>array("Name"=>"Varchar","Size"=>"50", "Mandatory"=>1),
"Caption"=>local('ADMIN_Name'),
"Edit"=>array("Field"=>"Name"),
"Show"=>array("Field"=>"Name", "Width"=>"100"),
);

$Fields["Image"] = array (
"Type"=>array("Name"=>"Varchar","Size"=>"50", "Mandatory"=>1),
"Caption"=>local('ADMIN_Image'),
"Edit"=>array("Field"=>"Image"),
"Show"=>array("Field"=>"Image", "Width"=>"100"),
);



$Table[Events]["AfterEdit"] = array ("SetFocus"=>"Name");

$Options[Order] = "Name ASC";

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

?>