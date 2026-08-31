<?
$Table = array("Name"=>"NewsCompany", "PrimaryKey"=>"ID");

        $Fields["NewsDate"] = array (
                "Type"=>array("Name"=>"Datetime","AllowEmpty"=>"0","Mandatory"=>0),
                "Caption"=>local('ADMIN_DATE'),
                "Edit"=>array("Field"=>"NewsDate"),
                "Show"=>array("Field"=>"NewsDate","Width"=>100),
                "DefaultValue"=>""
        );

        $Fields["Placer"] = array (
        	"Type"=>array("Name"=>"Varchar","Size"=>"99","Mandatory"=>0),
        	"Caption"=>local('ADMIN_TYPE_MESSAGE'),
        	"Edit"=>array("Field"=>"Placer","Description"=>""),
        	"Lookup"=>array ("Type"=>"Local","Map"=>array("addservbal"=>local('ADMIN_NEWS')),"AllowEmpty"=>0));


        $Fields["Alert"] = array (
                "Type"=>array("Name"=>"Varchar","Size"=>"99", "Mandatory"=>1),
               "Caption"=>"Service Name",
                "Edit"=>array("Field"=>"Alert", "Width"=>"100%"),
                "Show"=>array("Field"=>"Alert", "Width"=>"100%"),
        );
        $Fields["Descript"] = array (
                "Type"=>array("Name"=>"Varchar","Size"=>"50", "Mandatory"=>1),
                "Caption"=>"Price",
                "Edit"=>array("Field"=>"Descript"),
        );

        $Fields["Url"] = array (
                "Type"=>array("Name"=>"Varchar","Size"=>"50", "Mandatory"=>0),
                "Caption"=>"Key",
                "Edit"=>array("Field"=>"Url", "Width"=>"600"),
                "Show"=>array("Field"=>"Url", "Width"=>"150"),
        );
        
        $Fields["Body"] = array (
                "Type"=>array("Name"=>"HTML","Cols"=>"50","Rows"=>"10"),
                "Caption"=>local('ADMIN_FULL_TEXT'),
                "Edit"=>array("Field"=>"Body"),
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

$Table['Style']['Show']['Table'] = array ("Border"=>0,"Cellpadding"=>5,"Cellspacing"=>0);
$Table['Style']['Show']['Table']['Td']['Class'] = "FormText";
$Table['Style']['Show']['Table']['Td']['Background'] = "/Admin/img/table-white-bg.gif";
$Table['Style']['Show']['Table']['Header']['Bgcolor'] = "#636E88";
$Table['Style']['Show']['Table']['Header']['Class'] = "FormText";

$Table['Style']['BindTable'] = array ("Border"=>0,"Cellpadding"=>5,"Cellspacing"=>0);
$Table['Style']['BindTable'][Td]['Class'] = "FormText";
$Table['Style']['BindTable'][Td]['Background'] = "/Admin/img/table-white-bg.gif";
$Table['Style']['BindTable']['Header']['Bgcolor'] = "#636E88";
$Table['Style']['BindTable']['Header']['Class'] = "FormText";

$Table['Style']['Edit']['Table'] = array ("Border"=>0,"Cellpadding"=>5,"Cellspacing"=>0);
$Table['Style']['Edit']['Table']['Td']['Class'] = "FormText";
$Table['Style']['Edit']['Table']['Td']['Background'] = "/Admin/img/table-white-bg.gif";
$Table['Style']['Edit']['Table']['Header']['Bgcolor'] = "#636E88";
$Table['Style']['Edit']['Table']['Header']['Class'] = "FormText";

?>