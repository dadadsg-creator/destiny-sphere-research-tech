<?/*
$Fields["uid"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
	"Caption"=>"UID",
	"Edit"=>array("Field"=>"uid"),
	"Show"=>array("Field"=>"uid","Width"=>"1"),
	"DefaultValue"=>"",
);*/
$Fields["Title"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
	"Caption"=>local('ADMIN_PATTERN_NAME'),
	"Edit"=>array("Field"=>"Title"),
	"Show"=>array("Field"=>"Title","Width"=>"100%"),
	"DefaultValue"=>"",
);

$Fields["TypeID"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"5","Mandatory"=>0),
    "Caption"=>local('ADMIN_CATEGORY'),
	"Edit"=>array("Field"=>"TypeID","Description"=>""),
	"Lookup"=>array ("Type"=>"Master","ParentTable"=>"TemplateType","ParentColumn"=>"TypeID","LookupColumn"=>"Title","Order"=>"Title","AllowEmpty"=>0),
	"Search"=>array("Template"=>"('#Value#'='' OR TypeID='#Value#')","NotEmpty"=>1,"DefaultValue"=>"main"),
	"DefaultValue"=>"$PartTypeID"
);

$Fields["Body"] = array (
	"Type"=>array("Name"=>"Text","Cols"=>"75","Rows"=>"25","Mandatory"=>0),
    "Caption"=>local('ADMIN_MOULD'),
	"Edit"=>array("Field"=>"Body",
		"Description"=>"
			<b>&lt;? template( int N [,string Type] ) ?&gt;</b> - применить шаблон<br>
			&nbsp;&nbsp;&nbsp;&nbsp; N - порядковый номер в рамках текущего шаблона<br>
			&nbsp;&nbsp;&nbsp;&nbsp; Type - тип применяемого шаблона<br><br>
			<b>&lt;? title() ?&gt;</b> - вывод названия страницы<br>
			<b>&lt;? short_title() ?&gt;</b> - вывод короткого названия страницы<br>
			<b>&lt;? category_header_img() ?&gt;</b> - вывод картинки с названием текущего раздела<br>
			<b>&lt;? category_content_img() ?&gt;</b> - вывод картинки, закрепленной за текущим разделом<br>
			<b>&lt;? meta_description() ?&gt;</b>, <b>&lt;? meta_keywords() ?&gt;</b> - текст для соотв. тегов<br>
			<br>
			В шаблоне допускается использование конструкций: &lt;!-- #<b>Sentense</b># //--&gt;<br>Значение <b>Sentense</b> может быть определено отдельно для каждой языковой версии сайта<br><br>
		")
);

$Fields["Lang_id"] = array (
	"Type"=>array("Name"=>"Varchar","Size"=>"5","Mandatory"=>0),
    "Caption"=>local('ADMIN_LANGUAGE'),
	"Edit"=>array("Field"=>"Lang_id","Description"=>local('ADMIN_NO_LANGUAGE')),
	"Show"=>array("Field"=>"Lang_id","Width"=>"100%"),
	"Lookup"=>array ("Type"=>"Master","ParentTable"=>"Langs","ParentColumn"=>"ID","LookupColumn"=>"Title","Order"=>"Title DESC","AllowEmpty"=>1)
);

$Options = array("Where"=>"(Lang_id='$LanguageID' OR Lang_id='' OR Lang_id is NULL)","Order"=>"Title");

$Table = array("Name"=>"Templates", "PrimaryKey"=>"ID");

$Table['Actions']['sentence'] = array ("Caption"=>"<img src='/Admin/img/knopka_sent.gif' border='0' alt='Sentence'>","Width"=>"20","URL"=>"sentence.php?ID=#ID#&PartTypeID=$PartTypeID");
$Table['Actions']['edit'] = array ("Caption"=>"<img src='/Admin/img/knopka_edit.gif' border='0' alt='".local('ADMIN_EDIT')."'>","Width"=>"20");
$Table['Actions']["delete"] = array ("Caption"=>"<img src='/Admin/img/knopka_del.gif' border='0' alt='".local('ADMIN_DELETE')."'>","Width"=>"20","Confirmation"=>array("Template"=>local('ADMIN_DELETE')." '[#Title#]' ?"));

$Table['Events']["AfterEdit"] = array ("SetFocus"=>"Title");
?>