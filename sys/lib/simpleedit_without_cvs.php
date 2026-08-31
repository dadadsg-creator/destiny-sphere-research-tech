<?
# Version 1.6   20.09.2002

class CVEdit {

var $SimpleEdit;
var $Table = array();
var $Fields = array();
var $Options = array();
var $Params = array();
var $Permissions = array();
var $ElementType = "";
var $MySQLerror = "";

    function CVEdit (&$Table,&$Fields,&$Options,&$Permissions) {
        global $SERVER_NAME,$_GET,$_POST;
        global $LanguageID;
        global $UserManager, $session;
        global $Element_id,$page,$PartElementTitle;

        $this->ElementType = $Table[Name];
        $this->Table = $Table;
        $this->DataTable = $Table;
        $this->TableFields = $Fields;
        $this->Options = $Options;
        $this->Options[Order] = null;
        $this->DataOrder = $Options[Order];
        $this->Permissions = $Permissions;
        reset($Fields);
        while (list($field,$data)=each($Fields)) {
            $Fields[$field][Show] = null;
            $Fields[$field][Search] = null;
        }
        reset($Fields);

        $this->Params[action]    = ($_POST[action]) ? $_POST[action]    : $_GET[action];
        $this->Params[ID]        = ($_POST[ID])  ? $_POST[ID]       : $_GET[ID];

        $mode = ($_POST[mode]) ? $_POST[mode]   : $_GET[mode];
        switch (strval($this->Params[action])) {
            case "new":
            case "add":
            case "edit":
            case "update":
                if ($mode == "data") {
                    $this->Fields = $Fields;
                    $this->Options[HiddenParams][mode] = "data";
                    $this->Options[HiddenParams][Element_id] = ($Element_id) ? $Element_id : $this->Params[ID];
                    $this->Options[HiddenParams][page] = $page;
                    $this->Options[HiddenParams][PartElementTitle] = $PartElementTitle;
                } else {
                    if (!($this->Options[ForbidInputCVSElementTitle] && in_array($this->Params[action],array("add","new")))) {
                        $this->Table[Events]["AfterEdit"] = array ("SetFocus"=>"ElementTitle");
                        $this->Fields["ElementTitle"] = array (
                            "Type"=>array("Name"=>"Varchar","Size"=>"50","Mandatory"=>1),
                            "Caption"=>local('ADMIN_NAME'),
                            "Edit"=>array("Field"=>"ElementTitle"),
                            "ForbidInsert"=>1,
                        );
                    }
                    if (in_array($this->Params[action],array("new","add"))) {
                        while (list($key,$val)=each($Fields)) $this->Fields[$key] = $val;
                    } else {
                        $this->Table[Name] = "Element";
                        $this->Options[HiddenParams][PartElementTitle] = $PartElementTitle;
                    }

                    if (!$this->Options[ForbidBindTable]) {
                        $this->Table[BindTable][Element_PageCategory] = array(
                            "ChildField"=>"Element_id",
                            "BindField"=>"PageCategory_id",
                            "BindLookupTable"=>"PageCategories",
                            "BindParentColumn"=>"PageCategories.ID",
                            "BindLookupColumn"=>"PageCategories.Title",
                            "Options" => array("BindMethod"=>"CheckBoks",
                                               "Caption"=>"Доступно для категорий",
                                               "BindLookupTableWhere"=>"WHERE PageCategories.lang_id='".$LanguageID."'",
                                               "BindLookupColumnOrder"=>"ORDER BY PageCategories.ShowOrder"),
                            "ForbidInsert"=>1
                        );
                        $this->Table[BindTable][Element_Page] = array(
                            "ChildField"=>"Element_id",
                            "BindField"=>"Page_id",
                            "BindLookupTable"=>"Pages",
                            "BindParentColumn"=>"Pages.ID",
                            "BindLookupColumn"=>"Pages.Title",
                            "Options" => array("BindMethod"=>"CheckBoks",
                                               "Caption"=>"Доступно для страниц, не входящих ни в одну из категорий",
                                               "BindLookupTableWhere"=>"WHERE (Pages.PageCategory_id='' OR Pages.PageCategory_id=0 OR Pages.PageCategory_id is NULL) AND Pages.lang_id='".$LanguageID."'",
                                               "BindLookupColumnOrder"=>"ORDER BY Pages.OrderInParent"),
                            "ForbidInsert"=>1
                        );
                    }
                }
                $this->Options[ForbidApplyButton] = 1;
                //$this->Options[ForbidListTable] = 1;
            break;
            default:
                $this->InitList();
            break;
        }

//      $this->Options[ForbidMessage] = 1;
    }

    function ProcessEvent() {
        global $DB_CONNECTION_ID;
        global $SERVER_NAME,$_GET,$_POST;
        global $LanguageID;
        global $UserManager, $session;

        $Error = 0;
        $mode = ($_POST[mode]) ? $_POST[mode]   : $_GET[mode];
        switch (strval($this->Params[action])) {
            case "delete":
                if ($this->Params[ID]) {
                    if ($UserManager->CheckPermission($this->Permissions["Delete"]["Require"])) {
                        $query = "UPDATE Element SET Status='deleted' WHERE ID=".$this->Params[ID];
                        $result = @mysql_db_query(DBName,$query,$DB_CONNECTION_ID);
                        if ($result) {
                            $Msg = ShowCMSMessage(local('ADMIN_INFO_UPDATE_COMPLITE'), 1);
                            $query = "SELECT ElementTitle FROM Element WHERE ID=".$this->Params[ID];
                            $result = @mysql_db_query(DBName,$query,$DB_CONNECTION_ID);
                            if ($result) {
                                $row = mysql_fetch_row($result);
                                $ElementTitle  = $row[0];
                            } else $this->MySQLerror .= $this->getMysqlError($query);
                            $LogTitle = str_replace("#title#",$ElementTitle,$this->Permissions["Delete"][LogTitle]);
                            $LogBeforeAction = str_replace("#ELEMENT_ID#",$this->Params[ID],$this->Permissions["Delete"][LogUrlBeforeAction])."&change_lang_id=".$LanguageID;
                            actionLog($LogTitle);
                        } else {
                            $ErrMsg = "Ошибкка при обращении к базе данных";
                            $this->MySQLerror .= $this->getMysqlError($query);
                        }
                    } else {
                        $ErrMsg = $this->Permissions["Delete"][ErrMsg];
                    }
                }
                $this->Params[action] = "";
            break;
            case "checkin":
                $this->Checkin($Error, $ErrMsg);
                if (!($Error || $ErrMsg)) $Msg = ShowCMSMessage(local('ADMIN_INFO_UPDATE_COMPLITE'), 1);
            break;
            case "edit":
                if ($this->Params[ID]) {
                    if ($mode == "data") {
                        if ($UserManager->CheckPermission($this->Permissions["Edit"]["Require"])) {
                            $this->Params[ID] = $this->getFieldValue("Element","ElementData_id","ID=".$this->Params[ID]);
                            $this->Params[action] = "edit";
/*
                            if ($this->getFieldValue("Element","CVstatus","ID=".$this->Params[ID])=="checkin") {
                                if ($this->setFieldValue("Element","CVstatus","checkout","ID=".$this->Params[ID])) {
                                    $ListFields = array();
                                    $fields = @mysql_list_fields(DBName, $this->Table[Name], $DB_CONNECTION_ID);
                                    $columns = @mysql_num_fields($fields);
                                    for ($i = 0; $i < $columns; $i++) {
                                        $field = @mysql_field_name($fields, $i);
                                        if ($field != $this->Table[PrimaryKey]) array_push($ListFields,$field);
                                    }
                                    if (sizeof($ListFields)) {
                                        $ElementDataID = $this->getFieldValue("Element","LastVersion_id","ID=".$this->Params[ID]);
                                    //  $ElementDataID = $this->getFieldValue("Element","CurrentVersion_id","ID=".$this->Params[ID]);
                                        $Version = $this->getFieldValue("Element E, Element_Data ED","ED.Version","E.ID=ED.Element_id AND E.LastVersion_id=ED.ElementData_id AND E.ID=".$this->Params[ID]);
                                        $query = "SELECT ".join(",",$ListFields)." FROM ".$this->Table[Name]." WHERE ".$this->Table[PrimaryKey]."='".$ElementDataID."'";
                                        //echo $query."<br>";
                                        $result = @mysql_db_query(DBName,$query,$DB_CONNECTION_ID);
                                        if ($result) {
                                            if ($row = mysql_fetch_row($result)) {
                                                $ListFieldsValues = array();
                                                while (list($key,$val)=each($row)) array_push($ListFieldsValues,"'".addslashes($val)."'");
                                                $query = "INSERT INTO ".$this->Table[Name]."(".join(",",$ListFields).") VALUES (".join(",",$ListFieldsValues).")";
                                                //echo $query."<br>";
                                                $result = @mysql_db_query(DBName,$query,$DB_CONNECTION_ID);
                                                if ($result) {
                                                    $NewElementDataID = mysql_insert_id($DB_CONNECTION_ID);
                                                    //echo "new_id: ".$NewElementDataID;
                                    //              $Approbation = ($UserManager->CheckPermission($this->Permissions["Approbation"]["Require"])) ? "Y" : "N";
                                                    $Approbation = "N";
                                                    $query = "INSERT INTO Element_Data (Element_id,ElementData_id,User_id,Version,CreateDate,Approbation)
                                                                VALUES(".$this->Params[ID].",".$NewElementDataID.",".$session->user_id.",".($Version+1).",now(),'".$Approbation."')";
                                                    //echo $query."<br>";
                                                    $result = @mysql_db_query(DBName,$query,$DB_CONNECTION_ID);
                                                    if ($result) {
                                                        $this->setFieldValue("Element","LastVersion_id",$NewElementDataID,"ID=".$this->Params[ID]);
                                                        // log checkout
                                                        $ElementTitle = $this->getFieldValue("Element","ElementTitle","ID=".$this->Params[ID]);
                                                        $LogTitle = str_replace("#title#",$ElementTitle,$this->Permissions["Edit"][LogCheckout]);
                                                        $LogUrlBeforeAction = str_replace("#DATA_ID#",$ElementDataID,$this->Permissions["Edit"][LogUrlBeforeAction]);

                                                        $this->Params[ID] = $NewElementDataID;
                                                        $this->Params[action] = "edit";

                                                    } else {
                                                        $Error = 1;
                                                        $this->MySQLerror .= $this->getMysqlError($query);
                                                        $this->setFieldValue("Element","CVstatus","checkin","ID=".$this->Params[ID]);
                                                        $query = "DELETE FROM ".$this->Table[Name]." WHERE ".$this->Table[PrimaryKey]."='".$NewElementDataID."'";
                                                        $result = @mysql_db_query(DBName,$query,$DB_CONNECTION_ID);
                                                        if (!$result) $this->MySQLerror .= $this->getMysqlError($query);
                                                    }
                                                } else {
                                                    $Error = 1;
                                                    $this->MySQLerror .= $this->getMysqlError($query);
                                                    $this->setFieldValue("Element","CVstatus","checkin","ID=".$this->Params[ID]);
                                                }
                                            } else $Error = 1;
                                        } else $Error = 1;
                                    } else $Error = 1;
                                } else {
                                    $Error = 1;
                                    $ErrMsg = "Ошибкка при обращении к базе данных";
                                    $this->MySQLerror .= $this->getMysqlError($query);
                                }
                            } else {
                                //echo "already checkout";
                                $LastElementDataID = $this->getFieldValue("Element E, Element_Data ED","ED.ElementData_id","E.ID=ED.Element_id AND E.LastVersion_id=ED.ElementData_id AND E.ID=".$this->Params[ID]);
                                $LastElementUserID = $this->getFieldValue("Element E, Element_Data ED","ED.User_id","E.ID=ED.Element_id AND E.LastVersion_id=ED.ElementData_id AND E.ID=".$this->Params[ID]);
                                if ($LastElementDataID) {
                                    if ($session->user_id == $LastElementUserID) {
                                        //echo "last version: ".$LastElementDataID."<br>";
                                        $this->Params[ID] = $LastElementDataID;
                                        $this->Params[action] = "edit";
                                    } else {
                                        $Error = 1;
                                        $ErrMsg = "Элемент выписан другим пользователем";
                                    }
                                } else {
                                    $Error = 1;
                                }
                            }
*/
                        } else {
                            $Error = 1;
                            $ErrMsg = $this->Permissions["Edit"][ErrMsg];
                        }
                    } else { // edit property
                        if (!$UserManager->CheckPermission($this->Permissions["Edit"]["Require"])) {
                            $Error = 1;
                            $ErrMsg = $this->Permissions["Edit"][ErrMsg];
                        }
                    }
                } else $Error = 1;
            break;
            case "update":
                if ($this->Params[ID]) {
                    if (!$UserManager->CheckPermission($this->Permissions["Edit"]["Require"])) {
                        $Error = 1;
                        $ErrMsg = $this->Permissions["Edit"][ErrMsg];
                    }
                } else $Error = 1;
            break;
            case "new":
            case "add":
                if (!$UserManager->CheckPermission($this->Permissions["Insert"]["Require"])) {
                    $Error = 1;
                    $ErrMsg = $this->Permissions["Insert"][ErrMsg];
                }
            break;
        }

        if ($Error) {
            $this->InitList();
            $this->Params[action] = "";
        }

        $this->Options[ForbidDirectOutput] = 1;
        $this->SimpleEdit = new SimpleEdit($this->Table, $this->Fields, $this->Options);
        $OUTPUT =  $this->SimpleEdit->ProcessEvent($this->Params);

        $Error = 0;
        switch (strval($this->Params[action])) {
            case "add":
                $FieldElementTitle = ($this->Options[ForbidInputCVSElementTitle]) ? $this->ElementType." ".date("d.m.Y H:i") : $_POST[FieldElementTitle];
                if (!($this->SimpleEdit->InsertError || $this->SimpleEdit->Error)) {
                    $Approbation = ($UserManager->CheckPermission($this->Permissions["Approbation"]["Require"])) ? "Y" : "N";
                    $query = "INSERT INTO Element(ElementType,ElementTitle,Lang_id,ElementData_id,User_id,CreateDate,Approbation,Status)
                                 VALUES('".$this->ElementType."','".$FieldElementTitle."','".$LanguageID."',".$this->SimpleEdit->NEW_ID_VALUE.",".$session->user_id.",now(),'".$Approbation."','published')";
                    $result = @mysql_db_query(DBName,$query,$DB_CONNECTION_ID);
                    if ($result) {
                        $NewElementID = mysql_insert_id($DB_CONNECTION_ID);
                        if ($this->Table[BindTable]) {
                            while (list($BindTable,$Data) = each($this->Table[BindTable])) {
                                $BindArray = $_POST["Bind_$BindTable"];
                                if (is_array($BindArray)) while (list($key,$val)=each($BindArray)) {
                                    $query = "INSERT INTO $BindTable (".$Data[ChildField].",".$Data[BindField].") VALUES (".$NewElementID.",$key)";
                                    $result = @mysql_db_query(DBName,$query,$DB_CONNECTION_ID);
                                    if (! $result) {
                                        $Error = 1;
                                        $this->MySQLerror .= $this->getMysqlError($query);
                                    }
                                }
                            }
                        }
                        $LogTitle = str_replace("#title#",$FieldElementTitle,$this->Permissions["Insert"][LogTitle]);
                        $LogUrlAfterInsert = str_replace("#DATA_ID#",$this->SimpleEdit->NEW_ID_VALUE,$this->Permissions[Insert][LogUrlAfterAction])."&change_lang_id=".$LanguageID;
                        actionLog($LogTitle);
                    } else {
                        $Error = 1;
                        $this->MySQLerror .= $this->getMysqlError($query);
                    }

                    if ($Error) {
                        $query = "DELETE FROM ".$this->Table[Name]." WHERE ".$this->Table[PrimaryKey]."='".$this->SimpleEdit->NEW_ID_VALUE."'";
                        $result = @mysql_db_query(DBName,$query,$DB_CONNECTION_ID);
                        if (!$result) $this->MySQLerror .= $this->getMysqlError($query);
                        if ($NewElementID) {
                            $query = "DELETE FROM Element WHERE ID=".$NewElementID;
                            $result = @mysql_db_query(DBName,$query,$DB_CONNECTION_ID);
                            if (!$result) $this->MySQLerror .= $this->getMysqlError($query);
                        }
                        $ErrMsg = "Ошибка при обращении к базе даеных";
                    } else {
                        $Msg = "Элемент добавлен успешно";
                        $this->InitList();
                        $this->SimpleEdit->InitSimpleEdit($this->Table, $this->Fields, $this->Options);
                        $OUTPUT =  $this->SimpleEdit->ProcessEvent();
                    }
                }
            break;
            case "update":
                if (!($this->SimpleEdit->UpdateError || $this->SimpleEdit->Error)) {
                    if ($mode=="data") {
                        $this->Params[ID] = $_POST[Element_id];
                        $this->Checkin($Error,$ErrMsg);
                        if (!($Error || $ErrMsg)) $Msg = ShowCMSMessage(local('ADMIN_INFO_UPDATE_COMPLITE'), 1);
                    }
                    $this->InitList();
                    $this->SimpleEdit->InitSimpleEdit($this->Table, $this->Fields, $this->Options);
                    $OUTPUT =  $this->SimpleEdit->ProcessEvent();
                }
            break;
        }

        if ($Msg || $ErrMsg) echo ($ErrMsg) ? "<font color=red>".$ErrMsg."</font><br><br>" : "<font color=blue>".$Msg."</font><br><br>";
        if ($this->MySQLerror) echo $this->MySQLerror;
        echo $OUTPUT;
        return $result;
    }

    function InitList() {
        global $UserManager, $session;
        global $LanguageID;
        global $WorkPageCategoryID;
        global $page,$PartElementTitle;

        $DataTable = $this->DataTable;
        $DataFields = $this->TableFields;
        $DataOptions = $this->Options;

//      $this->Table = array("Name"=>"Element E, Element_Data ED","PrimaryKey"=>"ID");
        $this->Table = array("PrimaryKey"=>"E.ID");
        $this->Fields = array();
        if ($WorkPageCategoryID == "all") {
            $this->Table[SelectFrom] = "Element E, Users U, ".$DataTable["Name"];
        } else {
            $this->Table[SelectFrom] = "Element E, Users U, ".$DataTable["Name"]." LEFT JOIN Element_PageCategory EPC ON E.ID=EPC.Element_id";
        }
        $this->Options[Where]  = "(E.Status='published' AND E.ElementType='".$this->ElementType."' AND E.Lang_id='".$LanguageID."')";
        $this->Options[Where] .= " AND (E.User_id=U.ID)";
        if ($WorkPageCategoryID == "null") {
            $this->Options[Where] .= " AND (EPC.PageCategory_id is NULL)";
        } elseif ($WorkPageCategoryID != "all") {
            $this->Options[Where] .= " AND (EPC.PageCategory_id='".$WorkPageCategoryID."')";
        }
        $this->Options[Where] .= " AND ".$DataTable["Name"].".".$DataTable[PrimaryKey]."=E.ElementData_id";


        $this->Options[Order] = ($this->DataOrder) ? $this->DataOrder : "E.ElementTitle";


        //<!--
        $i = 0;
        reset($DataFields);
        while (list($field,$data)=each($DataFields)) {
            if ($data[Show]) {
                if ($i==0) $ConfirmationField = "Data_".$field;
                $this->Fields["Data_".$field] = array (
                    "Caption"=>$data[Caption],
                    "Show"=> $data[Show],
                    "Search"=>$data[Search],
                );
                $i++;
            }
        }
        if ($i==0) {
            $ConfirmationField = "ElementTitle";
            $this->Fields["ElementTitle"] = array (
                "Caption"=>local('ADMIN_NAME'),
                "Show"=>array("Field"=>"E.ElementTitle","Width"=>"100%"),
                "Search"=>array("Template"=>"('#Value#'='' OR ElementTitle LIKE '%#Value#%')"),
            );
        }
        //-->

        $this->Fields["Version"] = array (
            "Caption"=>local('ADMIN_VERSION'),
            "Show"=>array("Field"=>"CONCAT('<font color=',IF(E.Approbation='Y','black','red'),'>',DATE_FORMAT(E.ts,'%d.%m.%Y'),'<br>',U.LastName,'&nbsp;',U.FirstName,'</font>')"),
        );

        if ($UserManager->CheckPermission($this->Permissions["Edit"]["Require"])) {
            $this->Table[Actions][edit1] = array (
                "Caption"=>local('ADMIN_EDIT'),
                "URL"=>"?action=edit&mode=data&ID=#ID#&page=$page&PartElementTitle=$PartElementTitle",
                "Width"=>"20",
                "IMG"=>array(
                    "Inactive"=>"/Admin/img/knopka_edit.gif",
                    "Active"=>"/Admin/img/knopka_edit-over.gif",
                    "Down"=>"/Admin/img/knopka_edit-down.gif",
                    "Width"=>20,
                    "Height"=>20)
            );
            if (!($DataOptions[ForbidBindTable] && $DataOptions[ForbidInputCVSElementTitle])) {
                $this->Options[HiddenParams][mode] = "";
                $this->Table[Actions][edit] = array (
                    "Caption"=>locval('Admin', 'PROPERTIES'),"Width"=>"20",
                    "IMG"=>array(
                        "Inactive"=>"/Admin/img/knopka_prop.gif",
                        "Active"=>"/Admin/img/knopka_prop-over.gif",
                        "Down"=>"/Admin/img/knopka_prop-down.gif",
                        "Width"=>20,
                        "Height"=>20)
                );
            }
        }

        if ($UserManager->CheckPermission($this->Permissions["Delete"]["Require"])) {
            $this->Table[Actions]["delete"] = array (
                "Caption"=>local('Admin', 'DELETE'),
                "Width"=>"20","Confirmation"=>array("Template"=>"Удалить '[#".$ConfirmationField."#]' ?"),
                "IMG"=>array(
                    "Inactive"=>"/Admin/img/knopka_del.gif",
                    "Active"=>"/Admin/img/knopka_del-over.gif",
                    "Down"=>"/Admin/img/knopka_del-down.gif",
                    "Width"=>20,
                    "Height"=>20)
            );
        }

        $this->Options[ForbidListTable] = null;
//      $this->Options[ForbidMessage] = null;
        if (!$UserManager->CheckPermission($this->Permissions["Insert"]["Require"])) {
            $this->Options[ForbidInsert] = 1;
        }
        if (!$UserManager->CheckPermission($this->Permissions["Edit"]["Require"])) {
            $this->Options[ForbidUpdate] = 1;
        }

        $this->Table[Style][Show][Table] = array ("Border"=>0,"Cellpadding"=>5,"Cellspacing"=>0);
        $this->Table[Style][Show][Table][Td]['Class'] = "FormText";
        $this->Table[Style][Show][Table][Td]['Background'] = "/Admin/img/table-white-bg.gif";
        $this->Table[Style][Show][Table]['Header']['Bgcolor'] = "#636E88";
        $this->Table[Style][Show][Table]['Header']['Class'] = "FormText";

    }

    function Checkin(&$Error,&$ErrMsg) {
        global $SERVER_NAME;
        global $DB_CONNECTION_ID;
        global $UserManager, $session;
        global $LanguageID;

        if ($this->Params[ID]) {
            if ($UserManager->CheckPermission($this->Permissions["Edit"]["Require"])) {
                $this->setFieldValue("Element","User_id",$session->user_id,"ID=".$this->Params[ID]);
                $CurrentElementDataID = $this->getFieldValue("Element","ElementData_id","ID=".$this->Params[ID]);

                            // log checkin
                            $ElementTitle = $this->getFieldValue("Element","ElementTitle","ID=".$this->Params[ID]);
                            $LogTitle = str_replace("#title#",$ElementTitle,$this->Permissions["Edit"][LogCheckin]);
                            $LogUrlAfterAction = str_replace("#DATA_ID#",$CurrentElementDataID,$this->Permissions["Edit"][LogUrlAfterAction])."&change_lang_id=".$LanguageID;
                            actionLog($LogTitle);
                            if ($UserManager->CheckPermission($this->Permissions["Approbation"]["Require"])) {
                                $this->setFieldValue("Element","Approbation","Y","ID=".$this->Params[ID]);
                            }
            } else {
                if (!$UserManager->CheckPermission($this->Permissions["Edit"]["Require"])) {
                    $Error = 1;
                    $ErrMsg = $this->Permissions["Edit"][ErrMsg];
                }
            }
        } else $Error = 1;
    }

    function getFieldValue($TableName,$FieldName,$Where) {
        global $DB_CONNECTION_ID;
        $ret = 0;
        $query = "SELECT $FieldName FROM $TableName WHERE $Where";
        $result = @mysql_db_query(DBName,$query,$DB_CONNECTION_ID);
        if ($result) {
            if ($row = mysql_fetch_row($result)) $ret = $row[0];
        } else $this->MySQLerror .= $this->getMysqlError($query);
        return $ret;
    }

    function setFieldValue($TableName,$FieldName,$FieldValue,$Where) {
        global $DB_CONNECTION_ID;
        $ret = false;
        $query = "UPDATE $TableName SET $FieldName='$FieldValue' WHERE $Where";
        $result = @mysql_db_query(DBName,$query,$DB_CONNECTION_ID);
        if ($result) {
            $ret = true;
        } else $this->MySQLerror .= $this->getMysqlError($query);
        return $ret;
    }


    function getMysqlError($query) {
        global $DB_CONNECTION_ID;
        $ret = "<table><tr><td colspan=2><font color=red><b>Error:</b></font></td></tr>
                           <tr><td valign=top><b>Query:</b></td><td>".$query."</td></tr>
                           <tr><td valign=top><b>Mysql:</b></td><td>".mysql_error($DB_CONNECTION_ID)."</td></tr></table>";
        return $ret;
    }
}

?>