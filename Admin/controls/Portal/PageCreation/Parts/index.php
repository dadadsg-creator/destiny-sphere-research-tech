<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";



$IMG_DIR = "/img/";
$IMG_UPLOAD_DIR = $DOCUMENT_ROOT . $IMG_DIR;


$Fields["ShowOrder"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"3","Mandatory"=>0),
	"Caption"=>local('ADMIN_SORTING_ORDER'),
	"Edit"=>array("Field"=>"ShowOrder"),
	"Show"=>array("Field"=>"ShowOrder","Width"=>"20"),
	"DefaultValue"=>""
);

$Fields["Title"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
	"Caption"=>local('ADMIN_NAME'),
	"Edit"=>array("Field"=>"Title"),
	"Show"=>array("Field"=>"Title","Width"=>"200"),
	"DefaultValue"=>"",
	"Search"=>array("Template"=>"('#Value#'='' OR Title LIKE '%#Value#%')"),
);

$Fields["MenuImgAct"] = array (
	"Type"=>array("Name"=>"UploadFile",
				  "UploadDir"=> $IMG_UPLOAD_DIR,
				  "ReplaceDuplicate"=>1,
				  "MimeType"=>array("image/pjpeg","image/gif"),
				  "MaxFileSize"=>"900000",
				  "Mandatory"=>0),
	"Caption"=>local('ADMIN_ACTIVE_PICTURE'),
	"Show"=>array("Field"=>"IF(ISNULL(MenuImgAct) OR MenuImgAct='','".local('ADMIN_NO')."',Concat('".$IMG_DIR."',MenuImgAct,'<br><img  src=\'".$IMG_DIR."',MenuImgAct,'\'>'))","Width"=>"150"),
	"Edit"=>array("Field"=>"IF(ISNULL(MenuImgAct) OR MenuImgAct='','".local('ADMIN_NO')."',Concat('".$IMG_DIR."',MenuImgAct,'<br><img  src=\'".$IMG_DIR."',MenuImgAct,'\'>'))","Width"=>"150"),
	"DefaultValue"=>"",
	"SaveImageSize"=>array("Width"=>"MenuImgActWidth","Height"=>"MenuImgActHeight"),
);

$Fields["MenuImgInact2"] = array (
	"Type"=>array("Name"=>"UploadFile",
				  "UploadDir"=> $IMG_UPLOAD_DIR,
				  "ReplaceDuplicate"=>1,
				  "MimeType"=>array("image/pjpeg","image/gif"),
				  "MaxFileSize"=>"900000",
				  "Mandatory"=>0),
	"Caption"=>local('ADMIN_PASSIVE_PICTURE'),
	"Show"=>array("Field"=>"IF(ISNULL(MenuImgInact2) OR MenuImgInact2='','".local('ADMIN_NO')."',Concat('".$IMG_DIR."',MenuImgInact2,'<br><img  src=\'".$IMG_DIR."',MenuImgInact2,'\'>'))","Width"=>"200"),
	"Edit"=>array("Field"=>"IF(ISNULL(MenuImgInact2) OR MenuImgInact2='','".local('ADMIN_NO')."',Concat('".$IMG_DIR."',MenuImgInact2,'<br><img  src=\'".$IMG_DIR."',MenuImgInact2,'\'>'))","Width"=>"200"),
	"DefaultValue"=>"",
	"SaveImageSize"=>array("Width"=>"MenuImgInact2Width","Height"=>"MenuImgInact2Height"),
);

$Fields["RedirectURL"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>0),
	"Caption"=>local('ADMIN_URL'),
	"Edit"=>array("Field"=>"RedirectURL","Description"=>local('ADMIN_AUTO_TRANSFER')),
	"Show"=>array("Field"=>"RedirectURL","Width"=>"100"),
	"DefaultValue"=>"?p="
);

$Fields["Lang_ID"] = array ("Hidden"=>1,"DefaultValue"=>"$LanguageID");

$Options = array("Where"=>"Lang_id='$LanguageID'","Order"=>"SHOWORDER");

$Table = array("Name"=>"PageCategories", "PrimaryKey"=>"ID");
$Table[Actions][edit] = array ("Caption"=>"<img src='/Admin/img/knopka_edit.gif' border='0' alt='".local('ADMIN_EDIT')."'>","Width"=>"20");
$Table[Actions]["delete"] = array ("Caption"=>"<img src='/Admin/img/knopka_del.gif' border='0' alt='".local('ADMIN_DELETE')."'>","Width"=>"20");

$Table[Events]["AfterEdit"] = array ("SetFocus"=>"Title");
$Table[Events]["AfterDelete"] = array ("Query"=>array("DELETE FROM Pages WHERE PageCategory_ID=#ID#"));

$se = new SimpleEdit($Table, $Fields, $Options);

$Params[action] = $action;
$Params[ID] = $ID;

$se->ProcessEvent($Params);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>