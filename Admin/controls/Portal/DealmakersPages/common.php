<?
$IMG_DIR = "/img/news/";
$IMG_UPLOAD_DIR = $DOCUMENT_ROOT . $IMG_DIR;


$Table = array("Name"=>"NewsCompany", "PrimaryKey"=>"ID");
/*
        $Fields["NewsDate"] = array (
                "Type"=>array("Name"=>"Datetime","AllowEmpty"=>"0","Mandatory"=>0),
                "Caption"=>local('ADMIN_DATE'),
                "Edit"=>array("Field"=>"NewsDate"),
                "Show"=>array("Field"=>"NewsDate","Width"=>"15%"),
                "DefaultValue"=>""
        );
*/
       $Fields["Placer"] = array (
        	"Type"=>array("Name"=>"Varchar","Size"=>"10","Mandatory"=>0),
        	"Caption"=>local('ADMIN_TYPE_MESSAGE'),
        	"Edit"=>array("Field"=>"Placer","Description"=>""),
        	"Lookup"=>array ("Type"=>"Local","Map"=>array("dealpages"=>local('ADMIN_NEWS')/*, "article"=>local('ADMIN_REFERENCES_ABOUT_COMPANY')*/),"AllowEmpty"=>0),
        	"DefaultValue"=>"dealpages" );

/*
if ($PageParentID !=0) {
	$Fields["PageCategory_ID"] = array ("Hidden"=>1,"DefaultValue"=>"$PagesTreeWorkPageCategoryID");
} elseif ($PagesTreeWorkMode == 1) {
	$Fields["PageCategory_ID"] = array ("Hidden"=>1,"DefaultValue"=>"null");
} else {
	$Fields["PageCategory_ID"] = array (
		"Type"=>array("Name"=>"Varchar","Size"=>"5","Mandatory"=>0),
	    "Caption"=>local('ADMIN_CATEGORY'),
		"Edit"=>array("Field"=>"PageCategory_ID","Description"=>""),
		"Lookup"=>array ("Type"=>"Master","ParentTable"=>"NewsCategories","ParentColumn"=>"ID","LookupColumn"=>"Title","Where"=>"Lang_id='$LanguageID'","Order"=>"ShowOrder","AllowEmpty"=>0),
		"DefaultValue"=>"$PagesTreeWorkPageCategoryID"
	);
	if ($PagesTreeWorkMode==2) $Fields["PageCategory_ID"]["Search"] = array("Template"=>"('#Value#'='' OR PageCategory_ID='#Value#')","NotEmpty"=>1);
	//"Show"=>array("Field"=>"PageCategory_ID","Width"=>"150"),
}
*/



/*
$Fields["MenuImgAct"] = array (
	"Type"=>array("Name"=>"UploadFile",
				  "UploadDir"=> $IMG_UPLOAD_DIR,
				  "ReplaceDuplicate"=>1,
				  "MimeType"=>array("image/pjpeg","image/gif"),
				  "MaxFileSize"=>"900000",
				  "Mandatory"=>0),
	"Caption"=>local('ADMIN_THEME_PIC'),
//	"Show"=>array("Field"=>"IF(MenuImgAct='','".local('ADMIN_NO')."', '".local('ADMIN_YES')."')","Width"=>"150"),
	"Edit"=>array("Field"=>"MenuImgAct","Width"=>"100"),
	"DefaultValue"=>"",
	"SaveImageSize"=>array("Width"=>"MenuImgActWidth","Height"=>"MenuImgActHeight"),
);
*/

        $Fields["Alert"] = array (
                "Type"=>array("Name"=>"Varchar","Size"=>"50", "Mandatory"=>1),
               "Caption"=>local('ADMIN_FULL_NAME'),
                "Edit"=>array("Field"=>"Alert", "Width"=>"600"),
                "Show"=>array("Field"=>"Alert", "Width"=>"300"),
        );
        
        $Fields["authors"] = array (
                "Type"=>array("Name"=>"Varchar","Size"=>"50", "Mandatory"=>1),
                "Caption"=>local('DS_NICK_NAME'),
                "Edit"=>array("Field"=>"authors"),
                "Show"=>array("Field"=>"authors", "Width"=>"150"),
        );

        $Fields["descript"] = array (
                "Type"=>array("Name"=>"Varchar","Size"=>"50", "Mandatory"=>1),
                "Caption"=>local('DS_USER_COUNTRY'),
                "Edit"=>array("Field"=>"descript"),
                "Show"=>array("Field"=>"descript", "Width"=>"150"),
        );        

        $Fields["Body"] = array (
                "Type"=>array("Name"=>"HTML","Cols"=>"100","Rows"=>"15", "Mandatory"=>1),
                "Caption"=>local('ADMIN_DESCRIPTION_URL'),
                "Edit"=>array("Field"=>"Body"),
                //"Show"=>array("Field"=>"Body", "Width"=>"600"),
        );

        $Fields["Url"] = array (
                "Type"=>array("Name"=>"Varchar","Size"=>"50", "Mandatory"=>0),
                "Caption"=>local('ADMIN_URL'),
                "Edit"=>array("Field"=>"Url", "Width"=>"600"),
                "Show"=>array("Field"=>"Url", "Width"=>"150"),
        );
        
$Table[Events]["AfterEdit"] = array ("SetFocus"=>"Alert");

$Options[Order] = "NewsDate DESC";

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

$Table['Style']['Show']['Table'] = array ("Border"=>0,"Cellpadding"=>3,"Cellspacing"=>1);
$Table['Style']['Show']['Table']['Td']['Class'] = "FormText";
$Table['Style']['Show']['Table']['Td']['Background'] = "/Admin/img/table-white-bg.gif";
$Table['Style']['Show']['Table']['Header']['Bgcolor'] = "#636E88";
$Table['Style']['Show']['Table']['Header']['Class'] = "FormText";

$Table['Style']['BindTable'] = array ("Border"=>0,"Cellpadding"=>3,"Cellspacing"=>1);
$Table['Style']['BindTable'][Td]['Class'] = "FormText";
$Table['Style']['BindTable'][Td]['Background'] = "/Admin/img/table-white-bg.gif";
$Table['Style']['BindTable']['Header']['Bgcolor'] = "#636E88";
$Table['Style']['BindTable']['Header']['Class'] = "FormText";

$Table['Style']['Edit']['Table'] = array ("Border"=>0,"Cellpadding"=>3,"Cellspacing"=>1);
$Table['Style']['Edit']['Table']['Td']['Class'] = "FormText";
$Table['Style']['Edit']['Table']['Td']['Background'] = "/Admin/img/table-white-bg.gif";
$Table['Style']['Edit']['Table']['Header']['Bgcolor'] = "#636E88";
$Table['Style']['Edit']['Table']['Header']['Class'] = "FormText";

?>