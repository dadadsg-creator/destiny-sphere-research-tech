<?
$Table = array("Name"=>"TextWHeader", "PrimaryKey"=>"ID");

$Fields["inner_title"] = array (
    "Type"=>array("Name"=>"Varchar","Size"=>"60","Mandatory"=>1),
    "Caption"=>'ÇÀÃÎËÎÂÎÊ ÁËÎÊÀ',
    "Edit"=>array("Field"=>"inner_title","Description"=>''),    
    "DefaultValue"=>""  
);

$Fields["Body"] = array (
    "Type"=>array("Name"=>"HTML","Width"=>"100%","Height"=>"450","Mandatory"=>0),
    "Caption"=>"<span class=maintxt>".local('ADMIN_BLOCK_CONTENT')."</span>",
    "Edit"=>array("Field"=>"Body","Description"=>"")
);

$Options[ForbidBindTable] = 0;

$Table[Style][Show][Table] = array ("Border"=>0,"Cellpadding"=>5,"Cellspacing"=>0);
$Table[Style][Show][Table][Td]['Class'] = "FormText";
$Table[Style][Show][Table][Td]['Background'] = "";
$Table[Style][Show][Table]['Header']['Bgcolor'] = "#636E88";
$Table[Style][Show][Table]['Header']['Class'] = "th_2";

$Table[Style][Edit][Table] = array ("Border"=>0,"Cellpadding"=>5,"Cellspacing"=>0);
$Table[Style][Edit][Table][Td]['Class'] = "FormText";
$Table[Style][Edit][Table][Td]['Background'] = "/Admin/img/table-white-bg.gif";
$Table[Style][Edit][Table]['Header']['Bgcolor'] = "#636E88";
$Table[Style][Edit][Table]['Header']['Class'] = "th_2";

?>