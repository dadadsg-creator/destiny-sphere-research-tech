<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit_without_cvs.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";

	$Table = array("Name"=>"bug_trace", "PrimaryKey"=>"ID");

$Table[Template][ElementResult] = "tmpl.tmp.html";


        $Fields["DATETIME"] = array (
                "Type"=>array("Name"=>"Datetime","AllowEmpty"=>"0","Mandatory"=>0),
                "Caption"=>"DATETIME",
                "Edit"=>array("Field"=>"DATETIME"),
                "Show"=>array("Field"=>"DATETIME","Width"=>100),
                "DefaultValue"=>""
        );
        
        $Fields["USERNAME"] = array (
                "Type"=>array("Name"=>"Varchar","Size"=>"120", "Mandatory"=>1),
                "Caption"=>"USERNAME",
                "Edit"=>array("Field"=>"USERNAME", "Width"=>"100%"),
                "Show"=>array("Field"=>"USERNAME", "Width"=>"100%"),
				"Search"=>array("Template"=>"(USERNAME like '%#Value#%' OR USERNAME='#Value#')"),
        );

        $Fields["UIID"] = array (
                "Type"=>array("Name"=>"Varchar","Size"=>"120", "Mandatory"=>1),
                "Caption"=>"UIID",
                "Edit"=>array("Field"=>"UIID", "Width"=>"100%"),
                "Show"=>array("Field"=>"UIID", "Width"=>"100%"),
        );

		$Fields["WORLDID"] = array (
                "Type"=>array("Name"=>"Varchar","Size"=>"120", "Mandatory"=>1),
                "Caption"=>"WORLDID",
                "Edit"=>array("Field"=>"WORLDID", "Width"=>"100%"),
                "Show"=>array("Field"=>"WORLDID", "Width"=>"100%"),
                "Search"=>array("Template"=>"(WORLDID like '%#Value#%' OR WORLDID='#Value#')"),
        );
      
         $Fields["DBNAME"] = array (
                "Type"=>array("Name"=>"Varchar","Size"=>"120", "Mandatory"=>1),
                "Caption"=>"DBNAME",
                "Edit"=>array("Field"=>"DBNAME", "Width"=>"100%"),
                "Show"=>array("Field"=>"DBNAME", "Width"=>"100%"),
                "Search"=>array("Template"=>"(DBNAME like '%#Value#%' OR DBNAME='#Value#')"),
        );
       
        $Fields["ERRORTYPE"] = array (
                "Type"=>array("Name"=>"Varchar","Size"=>"120", "Mandatory"=>1),
                "Caption"=>"ERRORTYPE",
                "Edit"=>array("Field"=>"ERRORTYPE", "Width"=>"100%"),
                "Show"=>array("Field"=>"ERRORTYPE", "Width"=>"100%"),
                "Search"=>array("Template"=>"(ERRORTYPE like '%#Value#%' OR ERRORTYPE='#Value#')"),
        );

        $Fields["ERRORTEXT"] = array (
                "Type"=>array("Name"=>"Varchar","Size"=>"120", "Mandatory"=>1),
                "Caption"=>"ERRORTEXT",
                "Edit"=>array("Field"=>"ERRORTEXT", "Width"=>"100%"),
                "Show"=>array("Field"=>"ERRORTEXT", "Width"=>"100%"),
        );

        $Fields["PHPCODE"] = array (
                "Type"=>array("Name"=>"Text","Cols"=>"120","Rows"=>"15", "Size"=>"120", "Mandatory"=>1),
               "Caption"=>"PHPCODE",
                "Edit"=>array("Field"=>"PHPCODE", "Width"=>"100%"),
                //"Show"=>array("Field"=>"concat('<xmp>',PHPCODE,'</xmp>')", "Width"=>"100%"),
		"ForbidUpdate"=>1
        );

        $Fields["BACKTRACE"] = array (
                "Type"=>array("Name"=>"Text","Cols"=>"120","Rows"=>"15","Size"=>"120", "Mandatory"=>1),
               "Caption"=>"BACKTRACE",
                "Edit"=>array("Field"=>"BACKTRACE", "Width"=>"100%"),
                "Show"=>array("Field"=>"BACKTRACE", "Width"=>"100%"),
		"ForbidUpdate"=>1
        );

        $Fields["FULLTXT"] = array (
                "Type"=>array("Name"=>"Text","Cols"=>"120","Rows"=>"15","Size"=>"120", "Mandatory"=>1),
               "Caption"=>"FULLTXT",
                "Edit"=>array("Field"=>"FULLTXT", "Width"=>"100%"),
                "Show"=>array("Field"=>"concat('<xmp>',FULLTXT,'</xmp>')", "Width"=>"100%"),
                "Show"=>array("Field"=>"FULLTXT", "Width"=>"100%"),
                "Show"=>array("Field"=>"concat('<xmp>', PHPCODE, '</xmp>', '<br><br>', '<xmp>', BACKTRACE, '</xmp>')", "Width"=>"100%"),
        );

        $Fields["XMLDATA"] = array (
                "Type"=>array("Name"=>"Varchar","Size"=>"120", "Mandatory"=>1),
               "Caption"=>"XMLDATA",
                "Edit"=>array("Field"=>"XMLDATA", "Width"=>"100%"),
                "Show"=>array("Field"=>"XMLDATA", "Width"=>"100%"),
        );

        $Fields["OWNER"] = array (
                "Type"=>array("Name"=>"Varchar","Size"=>"120", "Mandatory"=>1),
               "Caption"=>"OWNER",
                "Edit"=>array("Field"=>"OWNER", "Width"=>"100%"),
                "Show"=>array("Field"=>"OWNER", "Width"=>"100%"),
                "Lookup"=>array ("Type"=>"Master","ParentTable"=>"Users","ParentColumn"=>"ID","LookupColumn"=>"Login","Order"=>"Login","AllowEmpty"=>1),
                "Search"=>array("Template"=>"(OWNER like '%#Value#%' OR OWNER='#Value#')"),
        );

  
$Table[Events]["AfterEdit"] = array ("SetFocus"=>"ID");



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
        $Table[Events]["AfterEdit"] = array ("SetFocus"=>"USERNAME");

        $Table[Actions]["delete"] = array (
                "Caption"=>local('ADMIN_DELETE'),
                "Width"=>"20",
                "Confirmation"=>array("Template"=>local('ADMIN_DELETE')." '[#USERNAME#] - [#DBNAME#] _ [#WORLDID#]' ?"),
                "IMG"=>array(
                        "Inactive"=>"/Admin/img/knopka_del.gif",
                        "Active"=>"/Admin/img/knopka_del-over.gif",
                        "Down"=>"/Admin/img/knopka_del-down.gif",
                        "Width"=>20,
                        "Height"=>20)
        );

$Table['Style']['Show']['Table'] = array ("Border"=>0,"Cellpadding"=>0,"Cellspacing"=>0);
$Table['Style']['Show']['Table']['Bgcolor'] = "#040D23";
$Table['Style']['Show']['Table']['Td']['Class'] = "FormText";
$Table['Style']['Show']['Table']['Td']['Background'] = "/Admin/img/table-white-bg.gif";
//$Table['Style']['Show']['Table']['Header']['Bgcolor'] = "#636E88";
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



$Options[Order] = "DATETIME DESC";

$se = new SimpleEdit($Table, $Fields, $Options);

$Params[action] = $action;
$Params[ID] = $ID;





$se->ProcessEvent($Params);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";


?>