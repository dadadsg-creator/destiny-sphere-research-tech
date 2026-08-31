<?
# Version 2.0  20.09.2002

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
                $query = "SELECT E.ID, E.ElementTitle, E.Status, E.Approbation, Concat(U.LastName,' ',U.FirstName), U.ID, DATE_FORMAT(ts,'%d.%m.%Y %H:%i')
                            FROM  Element E, Users U
                            WHERE E.User_id=U.ID AND
                                  E.ElementType='".$Table[Name]."' AND
                                  E.ElementData_id='".$this->data_id."'";
                $result = @mysql_db_query(DBName,$query,$DB_CONNECTION_ID);
                if ($result) {
                    if ($row = mysql_fetch_row($result)) {
                        $this->element_id = $row[0];
                        $this->Element[ElementTitle] = $row[1];
                        $this->Element[Status] = $row[2];
                        $this->Element[Approbation] = $row[3];
                        $this->Element[Author] = $row[4];
                        $this->Element[Author_id] = $row[5];
                        $this->Element[Version] = $row[6];
                    } else {
                        $this->ErrMsg = "Не найден блок данных с  идентификатором  \"<b>".$this->data_id."</b>\"";
                    }
                } else {
                    $this->ErrMsg = "Ошибка при обращении к базе данных";
                    $this->MySQLerror .= $this->getMysqlError($query);
                }
            } else {
                $query = "SELECT E.ElementData_id, E.ElementTitle, E.Status, E.Approbation, Concat(U.LastName,' ',U.FirstName), U.ID, DATE_FORMAT(ts,'%d.%m.%Y %H:%i')
                            FROM  Element E, Users U
                            WHERE E.User_id=U.ID AND
                                  E.ID='".$this->element_id."' AND ElementType='".$Table[Name]."'";
                $result = @mysql_db_query(DBName,$query,$DB_CONNECTION_ID);
                if ($result) {
                    if ($row = mysql_fetch_row($result)) {
                        $this->data_id = $row[0];
                        $this->Element[ElementTitle] = $row[1];
                        $this->Element[Status] = $row[2];
                        $this->Element[Approbation] = $row[3];
                        $this->Element[Author] = $row[4];
                        $this->Element[Author_id] = $row[5];
                        $this->Element[Version] = $row[6];
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
                    if ($this->setFieldValue("Element","Approbation","N","ID=".$this->element_id)) {
                        $this->Element[Approbation] = "N";
                        // reject log
                        $LogTitle = "Запрещено к пубдикации: \"".$this->Element[ElementTitle]."\"";
                        $LogUrlAfterAction = $PHP_SELF."?data_id=".$this->data_id."&change_lang_id=".$LanguageID;

                        actionLog($LogTitle);

                    } else $SqlEerror = 1;
                break;
                case "approbation":
                    if ($UserManager->CheckPermission($this->Permissions["Approbation"]["Require"])) {
                        if ($this->setFieldValue("Element","Approbation","Y","ID=".$this->element_id)) {
                            $this->Element[Approbation] = "Y";
                            // approbation log
                            $LogTitle = "Разрешено к публикации: \"".$this->Element[ElementTitle]."\"";
                            $LogUrlAfterAction = $PHP_SELF."?data_id=".$this->data_id."&change_lang_id=".$LanguageID;

                            actionLog($LogTitle);

                        } else $SqlEerror = 1;
                    }
                break;
                case "restore":
                    if ($UserManager->CheckPermission($this->Permissions["Approbation"]["Require"])) {
                        if ($this->setFieldValue("Element","Status","published","ID=".$this->element_id)) {
                            $this->Element[Status] = "published";
                            // restore log
                            $LogTitle = "Восстановлен элемент \"".$this->Element[ElementTitle]."\"";
                            $LogUrlAfterAction = $PHP_SELF."?element_id=".$this->element_id."&change_lang_id=".$LanguageID;

                            actionLog($LogTitle);

                        } else $SqlEerror = 1;
                    }
                break;
            }
            if ($SqlEerror) echo "<font color=red>В процессе обновления информации произошли ошибки при обращении к базе данных</font><br><br>";
            //echo $this->MySQLerror."<br>";

            //se 1
            $Options[ForbidListTable] = 1;
            $Options[ForbidUpdate] = 1;
            $Options[ForbidDirectOutput] = 1;
            $this->SimpleEdit = new SimpleEdit($Table, $Fields, $Options);
        }

    }

    function ProcessEvent() {
        global $PHP_SELF;
        global $DB_CONNECTION_ID;
        global $_GET,$_POST;
        global $LanguageID;
        global $UserManager, $session;

        if ($this->Msg || $this->ErrMsg) {
            echo ($this->ErrMsg) ? "<font color=red>".$this->ErrMsg."</font><br><br>" : "<font color=#FBCF00>".$this->Msg."</font><br><br>";
            if ($this->MySQLerror) echo $this->MySQLerror;
        } else {
            echo "<table>";
            echo "  <tr><td width=100><b>".local('ADMIN_NAME').":</b></td><td>".$this->Element[ElementTitle]." &nbsp;&nbsp; (<i>версия</i> <b>".$this->Element[Version]."</b>)</td></tr>";
            echo "  <tr><td width=100><b>".local('ADMIN_AUTHOR').":</b></td><td>".$this->Element[Author]."</td></tr>";
            echo "  <tr><td width=100><b>".local('ADMIN_STATUS').":</b></td><td>";
            if ($this->Element[Approbation]=="Y") {
                echo "<font color=#FBCF00>Разрешено к публикации на сайте</font>";
                if ($UserManager->CheckPermission($this->Permissions["Approbation"]["Require"])) {
                    if (!(($this->Element[CVstatus]=="checkout") && ($this->Element[LastVersion_id]==$this->data_id))) {
                        echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"".$PHP_SELF."?action=reject&data_id=".$this->data_id."\">Запретить</a>";
                    }
                }
            } else {
                echo "<font color=red>Публикация на сайте запрещена</font>";
                if ($UserManager->CheckPermission($this->Permissions["Approbation"]["Require"])) {
                    if (!(($this->Element[CVstatus]=="checkout") && ($this->Element[LastVersion_id]==$this->data_id))) {
                        echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"".$PHP_SELF."?action=approbation&data_id=".$this->data_id."\">Резрешить</a>";
                    }
                }
            }
            echo "</td></tr>";
            if ($this->Element[Status]=="deleted") {
                echo "  <tr><td width=100><b>Состояние:</b></td><td>Документ удален &nbsp;&nbsp;&nbsp;";
                if ($UserManager->CheckPermission($this->Permissions["Approbation"]["Require"])) {
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"".$PHP_SELF."?action=restore&data_id=".$this->data_id."\">Восстановить</a>";
                }
                echo "</td></tr>";
            } else {
                if ($this->Element[CVstatus]=="checkout" && $this->data_id==$this->Element[LastVersion_id]) {
                    echo "  <tr><td width=100><b>Состояние:</b></td><td><font color=#D368EE>".local('ADMIN_EDITING')."</font>";
                    if ($session->user_id==$this->Element[Author_id]) {
                        echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"index.html?action=checkin&ID=".$this->element_id."\">".local('ADMIN_APPLY_CHANGES')."</a>";
                    }
                    echo "</td></tr>";
                }
            }
            echo "</table>";
            echo "<br>";

            $Params = array("ID"=>$this->data_id,"action"=>"edit");
            echo $this->SimpleEdit->ProcessEvent($Params);
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
        $ret = "<table><tr><td colspan=2><font color=red><b>Error:</b></font></td></tr>
                           <tr><td valign=top><b>Query:</b></td><td>".$query."</td></tr>
                           <tr><td valign=top><b>Mysql:</b></td><td>".mysql_error($DB_CONNECTION_ID)."</td></tr></table>";
        return $ret;
    }

}

?>