<?
$IMG_DIR = "/img/news/";
$IMG_UPLOAD_DIR = $DOCUMENT_ROOT . $IMG_DIR;

			$Table = array("Name"=>"NewsCompany", "PrimaryKey"=>"ID");

        	$Fields["NewsDate"] = array (
				"Type"=>array("Name"=>"Datetime","AllowEmpty"=>"0","Mandatory"=>0),
            "Caption"=>local('ADMIN_DATE'),
            "Edit"=>array("Field"=>"NewsDate"),
            "Show"=>array("Field"=>"NewsDate","Width"=>100),
            "DefaultValue"=>""
        	);

        	$Fields["Placer"] = array (
        		"Type"=>array("Name"=>"Varchar","Size"=>"10","Mandatory"=>0),
        		"Caption"=>local('ADMIN_TYPE_MESSAGE'),
        		"Edit"=>array("Field"=>"Placer","Description"=>""),
        		"Lookup"=>array ("Type"=>"Local","Map"=>array("linkus"=>local('ADMIN_NEWS'), "article"=>local('ADMIN_REFERENCES_ABOUT_COMPANY')),"AllowEmpty"=>0),
        		"DefaultValue"=>"linkus" );

			$Fields["Alert"] = array (
				"Type"=>array("Name"=>"Varchar","Size"=>"35", "Mandatory"=>1),
            "Caption"=>local('ADMIN_TITLE'),
            "Edit"=>array("Field"=>"Alert", "Width"=>"35"),
            "Show"=>array("Field"=>"Alert", "Width"=>"100%"),
        	);

        	$Fields["Descript"] = array (
            "Type"=>array("Name"=>"Text","Cols"=>"120","Rows"=>"10", "Mandatory"=>1),
            "Caption"=>"Код баннера",
            "Edit"=>array("Field"=>"Descript"),
        	);

        	$Fields["Body"] = array (
				"Type"=>array("Name"=>"HTML","Cols"=>"50","Rows"=>"10"),
            "Caption"=>"Вызовы методов копирования кода баннера в буфер обмена. Usage: &lt;a href='#' onclick='copy(text, inA);'&gt;Скопировать код баннера&lt;/a&gt; , где text -- код баннера inA = [0,1] в зависимости от нужно или нет код засовывать в &lt;a&gt;",
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