<?
$Table = array("Name"=>"DynPHPContent", "PrimaryKey"=>"ID");
$Table[Events]["AfterEdit"] = array ("SetFocus"=>"Title");

$Fields["Title"] = array ("Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
						 "Caption"=>local('ADMIN_NAME'),
						 "Edit"=>array("Field"=>"Title"),
						 "Show"=>array("Field"=>"Title","Width"=>"100%"),
						 "Search"=>array("Template"=>"('#Value#'='' OR Title LIKE '%#Value#%')")
						);

$Fields["Body"] = array (
	"Type"=>array("Name"=>"Text","Cols"=>"75","Rows"=>"20","Mandatory"=>0),
    "Caption"=>local('ADMIN_COMPONENT_TEXT'),
	"Edit"=>array("Field"=>"Body","Description"=>"")
);

$Fields["Lang_id"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"5","Mandatory"=>0),
    "Caption"=>local('ADMIN_LANGUAGE'),
	"Edit"=>array("Field"=>"Lang_id","Description"=>""),
	"Show"=>array("Field"=>"Lang_id","Width"=>70),
	"Lookup"=>array ("Type"=>"Master","ParentTable"=>"Langs","ParentColumn"=>"ID","LookupColumn"=>"Title","Order"=>"Title DESC","AllowEmpty"=>1)
);
?>