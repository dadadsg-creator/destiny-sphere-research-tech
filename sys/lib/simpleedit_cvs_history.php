<?
# Version 1.2  10.07.2002

class CVHistory {
var $element_id;
var $data_id;
var $Element = array();

var $Permissions = array();
var $ErrMsg = "";
var $MySQLerror = "";

var $SimpleEdit;
var $SimpleEdit2;

    function CVHistory (&$Table,&$Fields,&$Options,&$Permissions) {
        global $DB_CONNECTION_ID;
        global $_GET,$_POST,$PHP_SELF;
        global $LanguageID;
        global $UserManager, $session;

        $this->Permissions = $Permissions;

        $this->element_id = ($_POST[element_id]) ? $_POST[element_id] : $_GET[element_id];
        $this->data_id    = ($_POST[data_id])     ? $_POST[data_id] : $_GET[data_id];

        if ($this->element_id || $this->data_id) {
            if ($this->data_id) {
                $query = "SELECT E.ID, E.ElementTitle, E.Status, E.CVstatus, E.CurrentVersion_id, E.LastVersion_id, ED.Approbation, Concat(U.LastName,' ',U.FirstName), U.ID, ED.Version
                            FROM  Element E, Element_Data ED, Users U
                            WHERE E.ID=ED.Element_id AND
                                  ED.User_id=U.ID AND
                                  E.ElementType='".$Table[Name]."' AND
                                  ED.ElementData_id='".$this->data_id."'";
                $result = mysql_db_query(DBName,$query,$DB_CONNECTION_ID);
                if ($result) {
                    if ($row = mysql_fetch_row($result)) {
                        $this->element_id = $row[0];
                        $this->Element[ElementTitle] = $row[1];
                        $this->Element[Status] = $row[2];
                        $this->Element[CVstatus] = $row[3];
                        $this->Element[CurrentVersion_id] = $row[4];
                        $this->Element[LastVersion_id] = $row[5];
                        $this->Element[Approbation] = $row[6];
                        $this->Element[Author] = $row[7];
                        $this->Element[Author_id] = $row[8];
                        $this->Element[Version] = $row[9];
                    } else {
                        $this->ErrMsg = "Не найден блок данных с  идентификатором  \"<b>".$this->data_id."</b>\"";
                    }
                } else {
                    $this->ErrMsg = "Ошибка при обращении к базе данных";
                    $this->MySQLerror .= $this->getMysqlError($query);
                }
            } else {
                $query = "SELECT E.ElementTitle, E.Status, E.CVstatus, E.CurrentVersion_id, E.LastVersion_id, ED.Approbation, Concat(U.LastName,' ',U.FirstName), U.ID, ED.Version
                            FROM  Element E, Element_Data ED, Users U
                            WHERE E.ID=ED.Element_id AND E.CurrentVersion_id=ED.ElementData_id AND
                                  ED.User_id=U.ID AND
                                  E.ID='".$this->element_id."' AND ElementType='".$Table[Name]."'";
                $result = @mysql_db_query(DBName,$query,$DB_CONNECTION_ID);
                if ($result) {
                    if ($row = mysql_fetch_row($result)) {
                        $this->Element[ElementTitle] = $row[0];
                        $this->Element[Status] = $row[1];
                        $this->Element[CVstatus] = $row[2];
                        $this->Element[CurrentVersion_id] = $row[3];
                        $this->Element[LastVersion_id] = $row[4];
                        $this->Element[Approbation] = $row[5];
                        $this->Element[Author] = $row[6];
                        $this->Element[Author_id] = $row[7];
                        $this->Element[Version] = $row[8];
                        $this->data_id = $this->Element[CurrentVersion_id];
                    } else {
                        $this->ErrMsg = "Не найден элемент с идентификатором \"<b>".$this->element_id."</b>\"";
                    }
                } else {
                    $this->ErrMsg = "Ошибка при обращении к базе данных";
                    $this->MySQLerror .= $this->getMysqlError($query);
                }
            }
        } else {
            $this->ErrMsg = "Не задан идентификатор элемента";
        }

        if (!$this->ErrMsg) {

            $action = ($_POST[action]) ? $_POST[action] : $_GET[action];
            switch(strval($action)) {
                case "reject":
                    if ($this->setFieldValue("Element_Data","Approbation","N","Element_id=".$this->element_id." AND ElementData_id=".$this->data_id)) {
                        $NewActiveDataID = $this->getFieldValue("Element_Data","ElementData_id","Element_id=".$this->element_id." AND Approbation='Y' ORDER BY Version DESC LIMIT 0,1");
                        if ($NewActiveDataID) {
                            $this->setFieldValue("Element","CurrentVersion_id",$NewActiveDataID,"ID=".$this->element_id);
                        }
                        $this->Element[Approbation] = "N";
                        $this->Element[CurrentVersion_id] = $NewActiveDataID;
                        // reject log
                        $LogTitle = "Отклонена версия <b>".$this->Element[Version]."</b> \"".$this->Element[ElementTitle]."\"";
                        $LogUrlAfterAction = $PHP_SELF."?data_id=".$this->data_id;

                        actionLog($LogTitle);

                    } else $SqlEerror = 1;
                break;
                case "approbation":
                    if (true) {
                    //if ($UserManager->CheckPermission($this->Permissions["Approbation"]["Require"])) {
                        if (!(($this->Element[CVstatus]=="checkout") && ($this->Element[LastVersion_id]==$this->data_id))) {
                            if ($this->setFieldValue("Element_Data","Approbation","Y","Element_id=".$this->element_id." AND ElementData_id=".$this->data_id)) {
                                $NewActiveDataID = $this->getFieldValue("Element_Data","ElementData_id","Element_id=".$this->element_id." AND Approbation='Y' ORDER BY Version DESC LIMIT 0,1");
                                if ($NewActiveDataID) {
                                    if ($this->Element[CurrentVersion_id]!=$NewActiveDataID) {
                                        $this->setFieldValue("Element","CurrentVersion_id",$NewActiveDataID,"ID=".$this->element_id);
                                        $this->Element[CurrentVersion_id] = $NewActiveDataID;
                                    }
                                }
                                $this->Element[Approbation] = "Y";
                                // approbation log
                                $LogTitle = "Утверждена версия <b>".$this->Element[Version]."</b> \"".$this->Element[ElementTitle]."\"";
                                $LogUrlAfterAction = $PHP_SELF."?data_id=".$this->data_id;

                                actionLog($LogTitle);

                            } else $SqlEerror = 1;
                        } // редактируется
                    }
                break;
                case "restore":
                                        if (true) {
                    //if ($UserManager->CheckPermission($this->Permissions["Approbation"]["Require"])) {
                        if ($this->setFieldValue("Element","Status","published","ID=".$this->element_id)) {
                            $this->Element[Status] = "published";
                            // restore log
                            $LogTitle = "Восстановлен элемент \"".$this->Element[ElementTitle]."\"";
                            $LogUrlAfterAction = $PHP_SELF."?element_id=".$this->element_id;

                            actionLog($LogTitle);

                        } else $SqlEerror = 1;
                    }
                break;
            }
            if ($SqlEerror) echo "<font color=red>В процессе обновления информации произошли ошибки при обращении к базе данных</font><br><br>";

            //se 1
            $Options[ForbidListTable] = 1;
            $Options[ForbidUpdate] = 1;
            $Options2[ForbidDelete] = 1;
            $Options[ForbidDirectOutput] = 1;
            $this->SimpleEdit = new SimpleEdit($Table, $Fields, $Options);
            //se 2
            $Table2 = array("PrimaryKey"=>"Element_id");
            $Table2[SelectFrom] = "Element_Data ED, Users U";
            $Table2[Style][Show][Table][Border] = 0;

            $Fields2["Version"] = array (
                "Caption"=>local('ADMIN_VERSIONS').":",
                "Show"=>array("Field"=>"CONCAT('<font size=+1>',IF(ElementData_id=".$this->data_id.",CONCAT('<b>',Version,'</b>'),CONCAT('<a href=\"?data_id=',ElementData_id,'\">',Version,'</a>')),'</font>&nbsp;от&nbsp;<font size=-1 color=',IF(Approbation='Y' AND ElementData_id=".$this->Element[CurrentVersion_id].",'green',IF(Approbation='Y','blue','red')),'>',DATE_FORMAT(CreateDate,'%d.%m.%Y'),'</font>')")
            );

            $Options2[Where] = "ED.User_id=U.ID AND ED.Element_id=".$this->element_id;
            $Options2[Order] = "ED.Version DESC";

            $Options2[ForbidInsert] = 1;
            $Options2[ForbidEdit] = 1;
            $Options2[ForbidUpdate] = 1;
            $Options2[ForbidDelete] = 1;
            $Options2[ForbidDirectOutput] = 1;
            $this->SimpleEdit2 = new SimpleEdit($Table2, $Fields2, $Options2);
            $this->SimpleEdit2->RecordOnPage = 100;
        }

    }

    function ProcessEvent() {
        global $PHP_SELF;
        global $DB_CONNECTION_ID;
        global $_GET,$_POST;
        global $LanguageID;
        global $UserManager, $session;

        if ($this->Msg || $this->ErrMsg) {
            echo ($this->ErrMsg) ? "<font color=red>".$this->ErrMsg."</font><br><br>" : "<font color=blue>".$this->Msg."</font><br><br>";
            if ($this->MySQLerror) echo $this->MySQLerror;
        } else {
            echo "<table border=0 cellpadding=5 cellspacing=0>";
            echo "  <tr><td width=100 CLASS=td><b>".local('ADMIN_NAME').":</b></td><td CLASS=td>".$this->Element[ElementTitle]." &nbsp;&nbsp; (<i>версия</i> <b>".$this->Element[Version]."</b>)</td></tr>";
            echo "  <tr><td CLASS=td width=100><b>".local('ADMIN_AUTHOR').":</b></td><td CLASS=td>".$this->Element[Author]."</td></tr>";
            echo "  <tr><td CLASS=td width=100><b>".local('ADMIN_STATUS').":</b></td><td CLASS=td>";
            if ($this->Element[Approbation]=="Y") {
                echo "Версия утверждена";
                if (true) {
                //if ($UserManager->CheckPermission($this->Permissions["Approbation"]["Require"])) {
                    if (!(($this->Element[CVstatus]=="checkout") && ($this->Element[LastVersion_id]==$this->data_id))) {
                        echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"".$PHP_SELF."?action=reject&data_id=".$this->data_id."\">Отменить</a>";
                    }
                }
            } else {
                echo "<font color=red>Версия не утверждена</font>";
                                if (true) {
                //if ($UserManager->CheckPermission($this->Permissions["Approbation"]["Require"])) {
                    if (!(($this->Element[CVstatus]=="checkout") && ($this->Element[LastVersion_id]==$this->data_id))) {
                        echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"".$PHP_SELF."?action=approbation&data_id=".$this->data_id."\">Утвердить</a>";
                    }
                }
            }
            echo "</td></tr>";
            if ($this->Element[Status]=="deleted") {
                echo "  <tr><td CLASS=td width=100><b>Состояние:</b></td><td CLASS=td>Документ удален &nbsp;&nbsp;&nbsp;";
                                if (true) {
                //if ($UserManager->CheckPermission($this->Permissions["Approbation"]["Require"])) {
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"".$PHP_SELF."?action=restore&data_id=".$this->data_id."\">Восстановить</a>";
                }
                echo "</td></tr>";
            } else {
                if ($this->Element[CVstatus]=="checkout" && $this->data_id==$this->Element[LastVersion_id]) {
                    echo "  <tr><td CLASS=td width=100><b>Состояние:</b></td><td CLASS=td><font color=#D368EE>".local('ADMIN_EDITING')."</font>";
                    if ($session->user_id==$this->Element[Author_id]) {
                        echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"index.php?action=checkin&ID=".$this->element_id."\">".local('ADMIN_APPLY_CHANGES')."</a>";
                    }
                    echo "</td></tr>";
                }
            }
            echo "</table>";
            echo "<br>";

            $Params = array("ID"=>$this->data_id,"action"=>"edit");
            echo "<table border=0 width=100%><tr>";
            echo "  <td width=100% valign=top>".$this->SimpleEdit->ProcessEvent($Params)."</td>";
            echo "  <td width=200 valign=top>".$this->SimpleEdit2->ProcessEvent(array("Page"=>1))."</td>";
            echo "</tr></table>";
        }
        return $result;
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
        $ret = "<table><tr><td CLASS=td colspan=2><font color=red><b>Error:</b></font></td></tr>
                           <tr><td CLASS=td valign=top><b>Query:</b></td><td>".$query."</td></tr>
                           <tr><td CLASS=td valign=top><b>Mysql:</b></td><td>".mysql_error($DB_CONNECTION_ID)."</td></tr></table>";
        return $ret;
    }

}

?>