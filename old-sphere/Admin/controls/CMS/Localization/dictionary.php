<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";

?>
<a href="index.php" class=maintxt><img src="/Admin/img/cms_btn/lang_non.gif" border="0"></a>
<a href="wordlist.php" class=maintxt><img src="/Admin/img/cms_btn/words_non.gif" border="0"></a>
<img src="/Admin/img/cms_btn/trans_vis.gif" border="0">
<br> 


<form name="ChangeLanguage" action="<?=str_replace("/controls/pages/edit.html","/controls/pages/",$PHP_SELF)?>" method="post">															
<a nohref class="MMenuLink"><? echo local('ADMIN_LANGUAGE'); ?>:</a> 
<select class="dd" name="change_lang_id" onchange="javascrip: document.ChangeLanguage.submit()" style="width:150px">
<?
	$LANGS = getLanguages();

	while (list($key,$LangData) = each($LANGS)) {
			echo "<option value=\"".$key."\" ".(($LanguageID == $key) ? "selected" : "").">".$LangData[Title]."</option>\n";
	}
?>
</select>
</form>

<?
$OUTPUT = "";
switch (strval($action)) {
	case "new":
	case "add":
		$OUTPUT .= ShowWordDescr($Word_id);
		$Fields["Translation"] = array (
			"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
			"Caption"=>local('ADMIN_VALUE'),
			"Edit"=>array("Field"=>"Translation"),
		);
		$Fields["Lang_id"] = array ("Hidden"=>1,"DefaultValue"=>$LanguageID);
		$Fields["Word_id"] = array ("Hidden"=>1,"DefaultValue"=>$Word_id);

		$Options[HiddenParams][Word_id] = $Word_id;
		$Options[HiddenParams][PartWord] = $PartWord;
		$Options[DoubleDataProtection_simple] = 1;

		$Table = array("PrimaryKey"=>"ID","Name"=>"Dictionary");
		$Params[action] = $action;
		break;
	case "edit":
	case "update":
		$OUTPUT .= ShowWordDescr($Word_id);
		$Fields["Translation"] = array (
			"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
			"Caption"=>local('ADMIN_VALUE'),
			"Edit"=>array("Field"=>"Translation"),
		);
		$Options = array();
		$Options[HiddenParams][Word_id] = $Word_id;
		$Options[HiddenParams][PartWord] = $PartWord;
		$Options[ForbidApplyButton] = 1;
		$Table = array("PrimaryKey"=>"ID","Name"=>"Dictionary");
		$Params[action] = $action;
		$Params[ID] = $ID;
		break;
	case "delete":
		$Fields = array();
		$Options = array();
		$Table = array("PrimaryKey"=>"ID","Name"=>"Dictionary");
		$Params[action] = $action;
		$Params[ID] = $ID;
		break;
	default:
	InitList();
}

$Options[ForbidDirectOutput] = 1;
$se = new SimpleEdit($Table, $Fields, $Options);
$OUTPUT .= $se->ProcessEvent($Params);

switch (strval($action)) {
	case "add":
			if (!($se->InsertError || $se->Error)) {
				if ($se->NEW_ID_VALUE) $Msg = local('ADMIN_INFO_UPDATED');
				//$this->error = $Msg;
				//require_once $_SERVER['DOCUMENT_ROOT']."/templates/message/message_template.php";

				InitList();
				$se->InitSimpleEdit($Table, $Fields, $Options);
				$OUTPUT =  $se->ProcessEvent();
			} else {
				$ErrMsg = local('ADMIN_ERROR_INFO_UPDATED');
			}
		break;
	case "update":
			if (!($se->UpdateError || $se->Error)) {
				$Msg = local('ADMIN_INFO_UPDATED');
				//$this->error = $Msg;
				//require_once $_SERVER['DOCUMENT_ROOT']."/templates/message/message_template.php";
				
				InitList();
				$se->InitSimpleEdit($Table, $Fields, $Options);
				$OUTPUT =  $se->ProcessEvent();
			} else {
				$ErrMsg = local('ADMIN_ERROR_INFO_UPDATED');
			}
		break;
	case "delete":
			if (!($se->Error)) {
				$Msg = local('ADMIN_ELEMENT_DELETE_COMPLITED');
				InitList();
				$se->InitSimpleEdit($Table, $Fields, $Options);
				$OUTPUT =  $se->ProcessEvent();
			} else {
				$ErrMsg = local('ADMIN_ERROR_DELETED_ELEMENT');
			}
		break;
	default:
	InitList();
}
//echo $Msg;
//echo $ErrMsg;
//echo $MySQLerror;
if ( !empty ($Msg) ) {$errormsg = $Msg;} elseif
( !empty ($ErrMsg) ) {$errormsg = $ErrMsg;} elseif
( !empty ($MySQLerror) ) {$errormsg = $MySQLerror;} 

if ( !empty ($errormsg) ) {
	ShowCMSMessage( $errormsg, 1 );
	//require_once $_SERVER['DOCUMENT_ROOT']."/templates/message/message_template.php";
} 




//if (($Msg || $ErrMsg) ? "<font color=red>".$ErrMsg."</font><br><br>" : "<font class=\"messages\">".$Msg."</font><br><br>");
//if ($MySQLerror) echo $MySQLerror;
echo $OUTPUT;

//    functions

function ShowWordDescr($word_id) {
	$query = "SELECT W.Word, D.Translation FROM Words W LEFT JOIN Dictionary D ON (W.ID=D.Word_id AND ((D.id is Null) OR (D.Lang_id='ru'))) WHERE W.id='".$word_id."'";
	if ($result = mysql_query($query)) {
		if ($row = mysql_fetch_row($result)) {
			$ret = "<span class=maintxt >".local('ADMIN_KEY').": <b>".$row[0]."</b>&nbsp;&nbsp;".(($row[1]) ? "(".$row[1].")</span>": "");
		} else $ret = local('ADMIN_ID_KEY_NOT_FIND').": ".$word_id;
	} else $ret = "Error: ".mysql_error();
	$ret .= "<br><br>";
	return $ret;
}

function InitList() {
	global $page,$PartWord;
	global $LANGS,$LanguageID;
	global $Fields,$Table,$Options,$Params;

	$Table = array();
	$Fields = array();
	$Options = array();
	$Params = array();

	$Fields["Word"] = array (
		"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
		"Caption"=>local('ADMIN_KEY'),
		"Show"=>array("Field"=>"W.Word","Width"=>"100"),
		"Search"=>array("Template"=>"('#Value#'='' OR W.Word LIKE '%#Value#%')")
	);
	if ($LanguageID!="ru") {
		$Fields["TranslationRU"] = array (
			"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
			"Caption"=>$LANGS[ru][Title],
			"Show"=>array("Field"=>"IF(ISNULL(D_RU.Translation) OR D_RU.Translation='','<font color=red>".local('ADMIN_NOT_DETERMINED')."</font>',D_RU.Translation)","Width"=>"100"),
			"Search"=>array("Template"=>"('#Value#'='' OR D_RU.Translation LIKE '%#Value#%')")
		);
	}
	$Fields["Translation"] = array (
		"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
		"Caption"=>$LANGS[$LanguageID][Title],
		"Show"=>array("Field"=>"IF(ISNULL(D.Translation) OR D.Translation='','<font color=red>".local('ADMIN_NOT_DETERMINED')."</font>',D.Translation)","Width"=>"70%"),
		"Search"=>array("Template"=>"('#Value#'='' OR D.Translation LIKE '%#Value#%')")
	);
	$Fields["Act1"] = array (
		"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
		"Caption"=>"&nbsp;",
		"Show"=>array("Field"=>"CONCAT('<a class=maintxt href=\"".$PHP_SELF."?page=".$page."&PartWord=".$PartWord."&',IF(ISNULL(D.id),'action=new',CONCAT('action=edit&ID=',D.id)),'&Word_id=',W.id,'\"><img src=\"/Admin/img/knopka_edit.gif\" border=0 alt=\"".local('ADMIN_EDIT')."\"></a>')","Width"=>"20"),
	);
	$Fields["Act2"] = array (
		"Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
		"Caption"=>"&nbsp;",
		"Show"=>array("Field"=>"IF(ISNULL(D.id),'".local('ADMIN_DELETE')."',CONCAT('<a class=maintxt href=\"".$PHP_SELF."?action=delete&page=".$page."&PartWord=".$PartWord."&ID=',D.id,'\"><img src=\"/Admin/img/knopka_del.gif\" border=0 alt=\"".local('ADMIN_DELETE')."\"></a>'))","Width"=>"20"),
	);
	
	$Table = array("PrimaryKey"=>"W.ID");
	$Table[SelectFrom] = "Words W LEFT JOIN Dictionary D ON (W.ID=D.Word_id AND ((D.id is Null) OR (D.Lang_id='".$LanguageID."')) )";
	if ($LanguageID!="ru") {
		$Table[SelectFrom] .= " LEFT JOIN Dictionary D_RU ON (W.ID=D_RU.Word_id AND ((D_RU.id is Null) OR (D_RU.Lang_id='ru'))) ";
	}
	if ($LanguageID!="en") {
		$Table[SelectFrom] .= " LEFT JOIN Dictionary D_EN ON (W.ID=D_EN.Word_id AND ((D_EN.id is Null) OR (D_EN.Lang_id='en'))) ";
	}

	$Options[Order] = "W.Word";
	$Options[ForbidInsert] = 1;
	$Options[ForbidDirectOutput] = 1;

}



require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>