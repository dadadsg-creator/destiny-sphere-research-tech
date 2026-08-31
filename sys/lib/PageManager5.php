<?
# Version 4.3   09.07.2002

class PageManager extends PageManager_BASE {

    var $MaxNestingLevel = 10;
    var $Templates = array();
    var $AssemblyTemplateID;
    var $Error;
    var $DefaultLanguage = "ru";

    function PageManager ($Data) {
        global $_GET,$_POST;

        $this->setMysqlConnection();

        if ($Data['Mode'] == "SuperAdmin") {
            $this->isAdmin = "SuperAdmin";
            if ($Data[PageID]) {
                if (!$this->setPageID($Data[PageID])) $this->Error = 1;
            } else {
                if (!$this->setAssemblyTemplate($Data[AssemblyTemplateID])) $this->Error = 1;
            }
        } elseif ($Data['Mode'] == "EasyAdmin") {
            $this->isAdmin = "EasyAdmin";
            if (!$this->setPageID($Data[PageID])) $this->Error = 1;
        } else {
            $this->isAdmin = false;
            if (!$this->setPageID($Data[PageID])) $this->Error = 1;
        }

        if (!$this->Error) {
            $this->PageTemplateParentID = 0;
            $this->level = 0;
            if (!$this->Page[lang_id]) $this->Page[lang_id] = $this->DefaultLanguage;

            $action = ($_POST[action]) ? $_POST[action] : $_GET[action];
            if ($this->isAdmin) $this->ProcessAction($action);
            if (!$this->InitTemplates())  $this->Error = 1;
        } else {
            //$this = null;
            return;
        }
    }

    function setAssemblyTemplate($ID) {
        $ret = false;
        if ($ID) {
            $query = "SELECT count(*) FROM AssemblyTemplate WHERE ID=$ID";
            $result = @mysql_query($query, $this->conn_id);
            if ($result) {
                $row = mysql_fetch_row($result);
                if ($row[0] > 0) {
                    $this->AssemblyTemplateID = $ID;
                    $ret = true;
                } else {
                    $this->ShowError("Указан не существующий макет с идентивикатором: $ID");
                }
            } else $this->ShowError($this->getMysqlError($query));
        } else $this->ShowError("Ошибочный url");
        return $ret;
    }

    function setPageID($ID) {
        $ret = false;
        if ($ID) {
            $query = "SELECT AT.ID, AT.Title, P.ID, P.Parent_id, P.Title, P.Title2, P.RedirectURL, P.ShortName, P.PageCategory_ID, P.lang_id, P.Descr, P.MetaKeywords FROM Pages P LEFT JOIN AssemblyTemplate AT on P.AssemblyTemplate_id=AT.id";
            if (strval(intval($ID)) == strval($ID)) {
                $query .= " WHERE P.ID='$ID'";
            } else {
                $query .= " WHERE P.ShortName='$ID'";
            }
            $result = @mysql_query($query, $this->conn_id);
            if ($result) {
                if ($row = mysql_fetch_row($result)) {
                    if ($row[1]) {
                        $this->AssemblyTemplateID = $row[0];
                        $this->Page[id] = $row[2];
                        $this->Page[parent_id] = $row[3];
                        $this->Page[title] = $row[4];
                        $this->Page[title2] = $row[5];
                        $this->Page[redirect_url] = $row[6];
                        $this->Page[url] = ($row[7]) ? "?p=".$row[7] : "?p=".$row[2];
                        $this->Page[category_id] = $row[8];
                        $this->Page[lang_id] = $row[9];
                        $this->Page[description] = $row[10];
                        $this->Page[meta_keywords] = $row[11];
                        $ret = true;
                        if ($this->Page[redirect_url]) {
                            // Redirect !
                            headerLocation($this->Page[redirect_url]);
                        }
                    } else {
                        if ($this->isAdmin) $this->ShowError("Не задан макет для страницы с идентификатором: $ID");
                    }
                } else {
                    if ($this->isAdmin) $this->ShowError("Не найдена страница с идентификатором: $ID");
                }
            } else $this->ShowError($this->getMysqlError($query));
        } else if ($this->isAdmin) $this->ShowError("Не указан идентификатор страницы");
        return $ret;
    }

    function InitTemplates() {
        $ret = false;
        $query = "SELECT distinct T.ID, T.Title, T.Body, T.TypeID FROM Templates T, PageTemplate PT WHERE T.id=PT.ApplyTemplate_id AND PT.AssemblyTemplate_id=".$this->AssemblyTemplateID;
        $result = @mysql_query($query, $this->conn_id);
        if ($result) {
            while ($row = mysql_fetch_row($result)) {
                //$this->Templates[$row[0]] = file($row[0]);
                $this->Templates[$row[0]][title] = $row[1];
                $this->Templates[$row[0]][body] = $row[2];
                $this->Templates[$row[0]][type] = $row[3];
                $ret = true;
            }
        } else $this->ShowError($this->getMysqlError($query));
        $query = "SELECT Template_id, Lang_id, Sentense, SentenseValue FROM Sentense";
        $result = @mysql_query($query, $this->conn_id);
        if ($result) {
            while ($row = mysql_fetch_row($result)) {
                $this->Templates[$row[0]][sentense][$row[1]][$row[2]] = $row[3];
            }
        } else $this->ShowError($this->getMysqlError($query));
        return $ret;
    }

    function deleteTemplateNode($PageTemplateID) {
        $query = "SELECT id FROM PageTemplate WHERE Parent_id=".$PageTemplateID;
        $result = @mysql_query($query, $this->conn_id);
        if ($result) {
            while ($row = mysql_fetch_row($result)) {
                $this->deleteTemplateNode($row[0]);
            }
            $query = "DELETE FROM PageTemplate WHERE id=".$PageTemplateID;
            $result = @mysql_query($query, $this->conn_id);
            if ($result) {
                $query = "DELETE FROM PageTemplate_PageData WHERE PageTemplate_id=".$PageTemplateID;
                $result = @mysql_query($query, $this->conn_id);
                if (!$result) $this->ShowError($this->getMysqlError($query));
            } else {
                $this->ShowError($this->getMysqlError($query));
            }
        } else $this->ShowError($this->getMysqlError($query));
    }
    function ProcessAction($action) {
        global $_GET,$_POST;
        switch (strval($action)) {
            case "addTemplateNode":
                if ($_POST[TemplateID]) {
                    $query = "SELECT count(*) FROM PageTemplate WHERE Parent_id=".$_POST[PageTemplateID]." AND AssemblyTemplate_id=".$this->AssemblyTemplateID." AND OrderInTemplate=".$_POST[OrderInTemplate]." AND ApplyTemplate_id=".$_POST[TemplateID];
                    $result = mysql_query($query, $this->conn_id);
                    if ($result) {
                        $row = mysql_fetch_row($result);
                        if ($row[0] == 0) {
                            $query = "INSERT INTO PageTemplate (Parent_id,AssemblyTemplate_id,OrderInTemplate,ApplyTemplate_id,AllowChangeData)
                                         VALUES (".$_POST[PageTemplateID].",".$this->AssemblyTemplateID.",".$_POST[OrderInTemplate].",".$_POST[TemplateID].",'".(($_POST[AllowChangeData]) ? "Y": "N")."')";
                            //echo $query."<br>";
                            $result = mysql_query($query, $this->conn_id);
                            if (!$result) $this->ShowError($this->getMysqlError($query));
                        }
                    } else $this->ShowError($this->getMysqlError($query));
                }  else $this->ShowError("Не выбран шаблон !");
            break;
            case "deleteTemplateNode":
                $this->deleteTemplateNode($_GET[ID]);
            break;
            case "changeAccess":
                $query = "UPDATE PageTemplate SET AllowChangeData = IF(AllowChangeData='Y','N','Y')  WHERE ID=".$_GET[ID];
                $result = @mysql_query($query, $this->conn_id);
                if (!$result) $this->ShowError($this->getMysqlError($query));
            break;
            case "setTemplateData":
                $PageTemplateDataID = ($_POST[PageTemplateDataID]) ? $_POST[PageTemplateDataID] : $_GET[PageTemplateDataID];
                $PageTemplateID = ($_POST[PageTemplateID]) ? $_POST[PageTemplateID] : $_GET[PageTemplateID];
                if (strval($PageTemplateDataID) == "0") {
                    $query = "DELETE FROM PageTemplate_PageData WHERE Page_id=".$this->Page[id]." AND PageTemplate_id=".$PageTemplateID;
                    $result = mysql_query($query, $this->conn_id);
                    if (!$result) $this->ShowError($this->getMysqlError($query));
                } elseif (isset($PageTemplateDataID)) {
                    $query = "SELECT count(*) FROM PageTemplate_PageData WHERE Page_id=".$this->Page[id]." AND PageTemplate_id=".$PageTemplateID;
                    $result = mysql_query($query, $this->conn_id);
                    if ($result) {
                        $row = mysql_fetch_row($result);
                        if ($row[0] == 0) {
                            $query = "INSERT INTO PageTemplate_PageData (Page_id,PageTemplate_id,Data_id) VALUES (".$this->Page[id].",".$PageTemplateID.",".$PageTemplateDataID.")";
                        } else {
                            $query = "UPDATE PageTemplate_PageData SET Data_id=".$PageTemplateDataID." WHERE Page_id=".$this->Page[id]." AND PageTemplate_id=".$PageTemplateID;
                        }
                        $result = mysql_query($query, $this->conn_id);
                        if (!$result) $this->ShowError($this->getMysqlError($query));
                    } else $this->ShowError($this->getMysqlError($query));
                } else $this->ShowError("Вы не выбрали компонент !");
            break;
        }
    }

    function getTitle() {
        return "tiTle";
    }

    function getTemplateName($PageTemplateID) {
        if (strval($PageTemplateID) != "0") {
            $query = "SELECT T.title FROM Templates T, PageTemplate PT WHERE T.id=PT.ApplyTemplate_id AND PT.id=".$PageTemplateID;
            $result = mysql_query($query, $this->conn_id);
            if ($result) {
                if ($row = mysql_fetch_row($result)) {
                    $ret = $row[0];
                }
            } else $this->ShowError($this->getMysqlError($query));
        } else $ret = "Точка входа в главный шаблон";
        return $ret;
    }
    function getTemplateTypeID($PageTemplateID) {
        $query = "SELECT T.TypeID FROM Templates T, PageTemplate PT WHERE T.id=PT.ApplyTemplate_id AND PT.id=".$PageTemplateID;
        $result = mysql_query($query, $this->conn_id);
        if ($result) {
            if ($row = mysql_fetch_row($result)) {
                $ret = $row[0];
            }
        } else $this->ShowError($this->getMysqlError($query));
        return $ret;
    }


    function checkTemplateType($TemplateType) {
        if (isset($TemplateType)) {
            $ret = false;
            if (strpos($TemplateType,",")) {
                $res = preg_split ("/[\s,]+/", $TemplateType);
                while (list($key,$val) = each($res)) $res[$key] = "'".$res[$key]."'";
                $query = "SELECT count(*) FROM TemplateType WHERE TypeID in (".join(",",$res).")";
            } else {
                $query = "SELECT count(*) FROM TemplateType WHERE TypeID='".$TemplateType."'";
            }
            $result = mysql_query($query, $this->conn_id);
            if ($result) {
                $row = mysql_fetch_row($result);
                if ($row[0]) $ret = true;
            } else $this->ShowError($this->getMysqlError($query));
        } else $ret = true;
        return $ret;
    }
    function getTemplateList($TemplateType) {
        $ret = array();
        if ($TemplateType) {
            if (strpos($TemplateType,",")) {
                $res = preg_split ("/[\s,]+/", $TemplateType);
                while (list($key,$val) = each($res)) $res[$key] = "'".$res[$key]."'";
                $query = "SELECT ID, Title FROM Templates WHERE TypeID in (".join(",",$res).") ORDER BY Title";
            } else {
                $query = "SELECT ID, Title FROM Templates WHERE TypeID='".$TemplateType."' ORDER BY Title";
            }
        } else {
            $TemplateType = $this->getTemplateTypeID($this->PageTemplateParentID);
            $query = "SELECT distinct T.ID, T.Title
                      FROM  Templates T, TemplateTypeTree TTT
                      WHERE (T.typeID='' OR T.TypeID is NULL) OR
                            (T.TypeID=TTT.TypeID AND TTT.ParentTypeID='".$TemplateType."') OR
                            ('".$TemplateType."'='' AND T.TypeID='main')
                      ORDER BY T.Title";
        }
        $result = mysql_query($query, $this->conn_id);
        if ($result) {
            while ($row = mysql_fetch_row($result)) {
                $ret[$row[0]] = $row[1];
            }
        } else $this->ShowError($this->getMysqlError($query));
        return $ret;
    }
    function getTemplateSelectForm($PageTemplateID,$OrderInTemplate,$TemplateType) {
        global $PHP_SELF;
        if ($this->checkTemplateType($TemplateType)) {
            $TemplateList = $this->getTemplateList($TemplateType);
            $ret  = "1<table border=1 width=100% style=\"border:1px\"><tr><td align=left valign=center>";
            $ret .= "<form action=\"".$PHP_SELF."?AssemblyTemplateID=".$this->AssemblyTemplateID."\" method=post>\n";
            $ret .= "   <input type=hidden name=action value=\"addTemplateNode\">\n";
            $ret .= "   <input type=hidden name=PageTemplateID value=\"".$PageTemplateID."\">\n";
            $ret .= "   <input type=hidden name=OrderInTemplate value=\"".$OrderInTemplate."\">\n";
            $ret .= "   <select name=TemplateID><option value=0>Выберите шаблон</option>\n";
            while (list($TemplateID,$Title) = each ($TemplateList)) {
                $ret .= "   <option value=\"".$TemplateID."\">".$Title."</option>\n";
            }
            $ret .= "   </select><br>\n";
            $ret .= "   <input type=submit class=Submit value=\"Применить\">\n";
            $ret .= "</form>\n";
            $ret .= "</td></tr></table>\n";
        } else {
            $ret = "Ошибка: указан несушествующий тип шаблона \"<b>$TemplateType</b>\"";
        }
        return $ret;
    }

    function getTemplateDataSelectForm($PageTemplateID,$ItemList,$CurrentItemID="") {
        global $PHP_SELF;
/*$ret  = '
                    <div id="d1_'.$PageTemplateID.'" style="position: relative; background-color: #DDDDDD; top: 0; left: 0; width: 100%; height: 1%; text-align: center;" onMouseOver="showBorder ('.$PageTemplateID.')" onMouseOut="hideBorder ('.$PageTemplateID.')">
                        <br>
                        Выберите блок
                        <br>&nbsp;

                        <div class="marker_top" id="d2_'.$PageTemplateID.'"></div>
                        <div class="marker_left" id="d3_'.$PageTemplateID.'"></div>
                        <div class="marker_right" id="d4_'.$PageTemplateID.'"></div>
                        <div class="marker_bottom" id="d5_'.$PageTemplateID.'"></div>
                        <a  href="#" onclick="callDialog(); return false;"><img class="botton" id="button_'.$PageTemplateID.'" src="/Admin/img/botton-text.gif" width="29" height="16" border=0 alt="редактировать" onmousedown="changeImages(this, \'/Admin/img/botton-text-down.gif\'); return true;" onmouseup="changeImages(this, \'/Admin/img/botton-text.gif\'); return true;" ></a>
                    </div>
';*/

        $ret  = "2<table  class=select cellpadding=0 cellspacing=0 border=0><tr><td colspan=3 class=select_h><img src=/Admin/img/emp.gif></td></tr><tr><td class=select_v><img src=/Admin/img/emp.gif></td><td>
                    <table class=mod_1 border=0 cellpadding=0 cellspacing=0 >
                        <tr>
                            <th class=mod_1_left><img src=/Admin/img/simedit-header.gif width=9 height=22></th>
                            <th class=mod_1_header>".$this->PageTemplateTitle."</th>
                        </tr>
                        <tr>
                            <th colspan=2 class=mod_1_header>
                                <form action=\"".$PHP_SELF."?AssemblyTemplateID=".$this->AssemblyTemplateID."&p=".$this->Page[id]."\" method=post>
                                    <input type=hidden name=action value=\"setTemplateData\">
                                    <input type=hidden name=PageTemplateID value=\"".$PageTemplateID."\">
                                    <select name=PageTemplateDataID><option value=0>".local('ADMIN_CHOOSE_ELEMENT')."</option>\n";
        while (list($ItemID,$ItemTitle) = @each ($ItemList)) {
            $selected = ($ItemID == $CurrentItemID) ? "selected" : "";
            $ret .= "   <option value=\"".$ItemID."\" ".$selected.">".$ItemTitle."</option>\n";
        }
        $ret .= "                   </select>
                                    <input type=submit class=Submit value=\"Применить\">
                                </form>
                            </th>
                        </tr>
                    </table>
                </td>
                <td class=select_v><img src=/Admin/img/emp.gif></td>
            </tr>
            <tr>
                <td colspan=3 class=select_h><img src=/Admin/img/emp.gif></td>
            </tr>
        </table>
        ";
        return $ret;
    }
    function getTemplateDataChangeForm($PageTemplateID,$ItemList,$CurrentItemID) {
        global $PHP_SELF;
        $ret .= "3<table  border=0><tr><td><form action=\"".$PHP_SELF."?AssemblyTemplateID=".$this->AssemblyTemplateID."&p=".$this->Page[id]."\" method=post>\n";
        $ret .= "   <input type=hidden name=action value=\"setTemplateData\">\n";
        $ret .= "   <input type=hidden name=PageTemplateID value=\"".$PageTemplateID."\">\n";
        $ret .= "   <select name=PageTemplateDataID>\n";
        while (list($ItemID,$ItemTitle) = @each ($ItemList)) {
            $selected = ($ItemID == $CurrentItemID) ? "selected" : "";
            $ret .= "   <option value=\"".$ItemID."\" ".$selected.">".$ItemTitle."</option>\n";
        }
        $ret .= "   </select>\n";
        $ret .= "   <input type=submit class=Submit value=\"Изменить\">\n";
        $ret .= "</td></tr></form></table>\n";
        return $ret;
    }

    function getTemplateDataForm($PageTemplateID,$TemplateID,$DataTitle,$AllowChangeData="") {
        global $PHP_SELF;
        $bgcolor = ($AllowChangeData == "Y") ? "#D4F0CD" : "#F5B7B8";
        $access_confirmation = ($AllowChangeData == "Y") ? "Запретить редактирование блока '".$DataTitle."' ?" : "Разрешить редактирование блока '".$DataTitle."' ?";

        $ChangeUrl = "javascript: if (confirm('Удалить элемент \'".addslashes(str_replace("\"","'",$DataTitle))."\' ?')) document.location.href='".$PHP_SELF."?action=deleteTemplateNode&ID=".$PageTemplateID."&AssemblyTemplateID=".$this->AssemblyTemplateID."'";
        $AccessUrl = "javascript: if (confirm('".addslashes(str_replace("\"","'",$access_confirmation))."')) document.location.href='".$PHP_SELF."?action=changeAccess&ID=".$PageTemplateID."&AssemblyTemplateID=".$this->AssemblyTemplateID."'";
        $EditUrl = "/Admin/controls/CMS/Templates/edittemplate.php?action=edit&ID=".$TemplateID;

        $ret  = "
        4<table class=mod_2 border=0 cellpadding=0>";
        $ret .= "<tr><td bgcolor=$bgcolor><div id=mod_2><span>".$DataTitle."</span><br>
                        <a href=\"".$ChangeUrl."\">Change</a>
                        &nbsp;|&nbsp;
                        <a href=\"javascript: openWin('".$EditUrl."');\">Edit</a>
                        &nbsp;|&nbsp;
                        <a href=\"".$AccessUrl."\">Access</a></div>


                    </td>
                 </tr>
                 </table>


                 \n";
        return $ret;
    }

    // engine

    function ApplyTemplate($OrderInTemplate, $TemplateType=null) {
        global $_GET, $_POST, $_COOKIE;
        global $PHP_SELF, $SERVER_NAME;

        if ($this->Error) return;
        if ($this->getNextTemplate($OrderInTemplate,$TemplateType)) {

            $this->level++;
            if ($this->level > $this->MaxNestingLevel) return $this->ShowError("Превышен максимально допустимый уровень вложенности шаблонов");

            if ($sentenses = $this->Templates[$this->PageTemplate][sentense][$this->Page[lang_id]]) {
                while (list($sentense,$text) = each($sentenses)) {
                    //echo $sentense."<br>";
                    $this->Templates[$this->PageTemplate][body] = preg_replace("/<!--\s*#\s*".$sentense."\s*#\s*\/\/-->/", $text , $this->Templates[$this->PageTemplate][body]);
                }
            }

            $t = new template();
            $meta = array();

            switch (strval($this->Templates[$this->PageTemplate][type])) {
                case "banner":
                    if ( ($this->isAdmin == "SuperAdmin") || ( ($this->isAdmin == "EasyAdmin") && ($this->PageTemplateAllowChangeData == "Y") ) ) {
                        if ($this->Page[id]) {
                            $BannerList = $this->getBannerList();
                            if ($this->getBannerData($meta, $this->PageTemplateDataID)) {
                                $ShowBorder = 1;
                                $DataChangeForm = $this->getTemplateDataChangeForm($this->PageTemplateParentID,$BannerList,$this->PageTemplateDataID);
                            } else {
                                $ApplyTemplate_ret = $this->getTemplateDataSelectForm($this->PageTemplateParentID,$BannerList,$this->PageTemplateDataID);
                            }
                        } else $ApplyTemplate_ret = $this->getTemplateDataForm($this->PageTemplateParentID,$this->PageTemplate,$this->PageTemplateTitle,$this->PageTemplateAllowChangeData);
                    } else {
                        $EvalMode = "skip";
                        if (!($this->getBannerData($meta,$this->PageTemplateDataID))) {
                            $ApplyTemplate_ret = ($this->isAdmin) ? "xxx<table border=1><tr><td valign=center>Баннер не назначен</td></tr></table>" : "";
                        }
                    }
                break;
                case "image":
                    if ( ($this->isAdmin == "SuperAdmin") || ( ($this->isAdmin == "EasyAdmin") && ($this->PageTemplateAllowChangeData == "Y") ) ) {
                        if ($this->Page[id]) {
                            $ImageList = $this->getImageList();
                            if ($this->getImageData($meta,$this->PageTemplateDataID)) {
                                $ShowBorder = 1;
                                $DataChangeForm = $this->getTemplateDataChangeForm($this->PageTemplateParentID,$ImageList,$this->PageTemplateDataID);
                            } else {
                                $ApplyTemplate_ret = $this->getTemplateDataSelectForm($this->PageTemplateParentID,$ImageList,$this->PageTemplateDataID);
                            }
                        } else $ApplyTemplate_ret = $this->getTemplateDataForm($this->PageTemplateParentID,$this->PageTemplate,$this->PageTemplateTitle,$this->PageTemplateAllowChangeData);
                    } else {
                        $EvalMode = "skip";
                        if (!($this->getImageData($meta,$this->PageTemplateDataID))) {
                            $ApplyTemplate_ret = ($this->isAdmin) ? "xxx<table border=1><tr><td valign=center>Картинка не назначена</td></tr></table>" : "";
                        }
                    }
                break;
                case "text":
//              utils::dump($this->getTextBlockData($meta,$this->PageTemplateDataID));
//              utils::dump($meta);
                    if ( ($this->isAdmin == "SuperAdmin") || ( ($this->isAdmin == "EasyAdmin") && ($this->PageTemplateAllowChangeData == "Y") ) ) {
                        if ($this->Page[id]) {
                            $TextBlockList = $this->getTextBlockList();
                            if ($this->getTextBlockData($meta,$this->PageTemplateDataID)) {
                                $ShowBorder = 1;
                                $DataChangeForm = $this->getTemplateDataChangeForm($this->PageTemplateParentID,$TextBlockList,$this->PageTemplateDataID);
                            } else {
                                $ApplyTemplate_ret = $this->getTemplateDataSelectForm($this->PageTemplateParentID,$TextBlockList,$this->PageTemplateDataID);
                            }
                        } else $ApplyTemplate_ret = $this->getTemplateDataForm($this->PageTemplateParentID,$this->PageTemplate,$this->PageTemplateTitle,$this->PageTemplateAllowChangeData);
                    } else {
                        $EvalMode = "skip";
                        if (!($this->getTextBlockData($meta,$this->PageTemplateDataID))) {
                            $ApplyTemplate_ret = ($this->isAdmin) ? "xxx<table border=1><tr><td valign=center>!Текстовый блок не назначен</td></tr></table>" : "";
                        }
                    }
                break;
                case "DynComponent":
                    $DynPHPContentData = "";
                    if ( ($this->isAdmin == "SuperAdmin") || ( ($this->isAdmin == "EasyAdmin") && ($this->PageTemplateAllowChangeData == "Y") ) ) {
                        if ($this->Page[id]) {
                            if ($this->getDynPHPContentData($DynPHPContentData,$this->PageTemplateDataID)) {
                                $ShowBorder = "eval";
                            } else {
                                $List = $this->getDynPHPContentList();
                                $ApplyTemplate_ret = $this->getTemplateDataSelectForm($this->PageTemplateParentID,$List,$this->PageTemplateDataID);
                            }
                        } else $ApplyTemplate_ret = $this->getTemplateDataForm($this->PageTemplateParentID,$this->PageTemplate,$this->PageTemplateTitle,$this->PageTemplateAllowChangeData);
                    } else {
                        $EvalMode = "process";
                        if (!($this->getDynPHPContentData($DynPHPContentData,$this->PageTemplateDataID))) {
                            $ApplyTemplate_ret = ($this->isAdmin) ? "5<table border=1><tr><td valign=center>Динамический компонент не назначен</td></tr></table>" : "";
                        } else {
                        }
                    }
                break;
            }
            if (!isset($ApplyTemplate_ret)) {
                if (sizeof($meta)) {
                    $t->assign($meta);
                    $ApplyTemplate_res = $t->getparsed($this->Templates[$this->PageTemplate][body]);
                } else {
                    $ApplyTemplate_res = ($DynPHPContentData) ? $DynPHPContentData[Body] : $this->Templates[$this->PageTemplate][body];
                }
                if (($this->isAdmin=="SuperAdmin") && !$this->Page[id]) {

                    $DeleteUrl = "javascript: if (confirm('Удалить элемент \'".addslashes(str_replace("\"","'",$this->PageTemplateTitle))."\' ?')) document.location.href='".($PHP_SELF."?action=deleteTemplateNode&ID=".$this->PageTemplateParentID."&AssemblyTemplateID=".$this->AssemblyTemplateID)."'";
                    $EditUrl = "/Admin/controls/CMS/Templates/edittemplate.php?action=edit&ID=".$this->PageTemplate;

                    //<a href=\"$PHP_SELF?action=deleteTemplateNode&ID=$this->PageTemplateParentID&AssemblyTemplateID=$this->AssemblyTemplateID\">Delete</a>&nbsp;|&nbsp;
                    $ApplyTemplate_ret = "
                    6<table  class=select cellpadding=0 cellspacing=0 border=0><tr><td colspan=3 class=select_h><img src=/Admin/img/emp.gif></td></tr><tr><td class=select_v><img src=/Admin/img/emp.gif></td><td>
                    7<table class=mod_1 border=0 cellpadding=0 cellspacing=0 >
                                                    <tr>
                                                        <th class=mod_1_left><img src=/Admin/img/simedit-header.gif width=9 height=22></th>
                                                        <th class=mod_1_header>
                                                            $this->PageTemplateTitle
                                                        </th>
                                                        <th class=mod_1_handle><a  href=\"".$DeleteUrl."\">Delete</a>&nbsp;|&nbsp;<a href=\"javascript: openWin('".$EditUrl."');\">Edit</a></th>
                                                    </tr>
                                                    <tr>
                                                        <td colspan=3 class=mod_1><EVAL>$ApplyTemplate_res</EVAL></td>
                                                    </tr>
                                                </table>
                                                </td><td class=select_v><img src=/Admin/img/emp.gif></td></tr><tr><td colspan=3 class=select_h><img src=/Admin/img/emp.gif></td></tr></table>

                                                ";
                    $ApplyTemplate_ret = $this->TryEval($ApplyTemplate_ret,"admin_process");
                } elseif ($ShowBorder) {
                    $DeleteUrl = "javascript: if (confirm('Удалить элемент ?')) document.location.href='".($PHP_SELF."?action=setTemplateData&PageTemplateID=".$this->PageTemplateParentID."&AssemblyTemplateID=".$this->AssemblyTemplateID."&p=".$this->Page[id]."&PageTemplateDataID=0")."'";
                    $EditUrl = "/Admin/edit.php?mode=".$this->Templates[$this->PageTemplate][type]."&ID=".$this->PageTemplateDataID;
                    $ApplyTemplate_ret = "
                    8<table class=select cellpadding=0 cellspacing=0 border=0><tr><td colspan=3 class=select_h><img src=/Admin/img/emp.gif></td></tr><tr><td class=select_v><img src=/Admin/img/emp.gif></td><td>
                    9<table class=mod_1  cellpadding=0 cellspacing=0>
                                                    <tr>
                                                    <th class=mod_1_left><img src=/Admin/img/simedit-header.gif width=9 height=22></th>

                                                        <th class=mod_1_header>$DataChangeForm".$DynPHPContentData[Title]." </th>
                                                        <th class=mod_1_handle>
                                                            <div id=mod_1><a href=\"".$DeleteUrl."\">Delete</a>&nbsp;|&nbsp;<a href=\"javascript: openWin('".$EditUrl."');\">Edit</a></div>

                                                        </th>
                                                    </tr>

                                                    <tr>
                                                        <td colspan=3><EVAL>$ApplyTemplate_res</EVAL></td>
                                                    </tr>
                                                </table>
                                                </td><td class=select_v><img src=/Admin/img/emp.gif></td></tr><tr><td colspan=3 class=select_h><img src=/Admin/img/emp.gif></td></tr></table>
                                                                                        ";
                    $ApplyTemplate_ret = $this->TryEval($ApplyTemplate_ret,(($ShowBorder == "eval") ? "admin_process":"admin_skip"));
                } else {
                    $ApplyTemplate_ret = $this->TryEval($ApplyTemplate_res,$EvalMode);
//                  $ApplyTemplate_ret = eval(">$ApplyTemplate_res<");
                }
            }
            $this->getParentTemplate();
            $this->level--;
        } else {
            $ApplyTemplate_ret = ($this->isAdmin) ? $this->ErrorTemplate : "";
        }
        return $ApplyTemplate_ret;
    }

    function TryEval(&$SOURCE,$mode="process") {
        global $_GET, $_POST, $_COOKIE;
        global $PHP_SELF, $SERVER_NAME;
        switch (strval($mode)) {
            case "admin_process":
                preg_match("/^(.+?)?<EVAL>(.+?)?<\/EVAL>(.+?)?$/i",str_replace("\n","",$SOURCE), $matches);
                echo $matches[1];
                eval("?>".$matches[2]."<?");
                echo $matches[3];
                break;
            case "admin_skip":
                $ret = preg_replace("/(<(\/)?EVAL>)/","",$SOURCE);
                break;
            case "skip":
                $ret = $SOURCE;
                break;
            default:
                eval("?>".$SOURCE."<?");
        }
        return $ret;
    }

    function getNextTemplate($OrderInTemplate,$TemplateType) {
        $ret = false;
        $query = "SELECT PT.ID, PT.AllowChangeData, PTPD.Data_id, T.ID, T.Title
                     FROM Templates T, PageTemplate PT
                     LEFT JOIN PageTemplate_PageData PTPD on (PTPD.PageTemplate_id=PT.id AND PTPD.page_id='".$this->Page['id']."')
                     WHERE T.id=PT.ApplyTemplate_id AND
                           PT.Parent_id=".$this->PageTemplateParentID." AND
                           PT.OrderInTemplate=".$OrderInTemplate." AND
                           PT.AssemblyTemplate_id=".$this->AssemblyTemplateID;

        $result = mysql_query($query, $this->conn_id);
        if ($result) {
            $PageTemplateParentID = $this->PageTemplateParentID;
            if ($row = mysql_fetch_row($result)) {
                $this->PageTemplateParentID = $row[0];
                $this->PageTemplateAllowChangeData = $row[1];
                $this->PageTemplateDataID = $row[2];
                $this->PageTemplate = $row[3];
                $this->PageTemplateTitle = $row[4];
                $ret = true;
            } else {
                if ($this->isAdmin) {
                    if ($this->Page[id]) {
                        $this->ErrorTemplate = "<table border=1 width=100%><tr><td valign=center><font color=red>В шаблоне \"<b>".$this->getTemplateName($this->PageTemplateParentID)."</b>\" не выбран вложенный шаблон</font></td></tr></table>";
                    } else {
                        $this->ErrorTemplate = $this->getTemplateSelectForm($this->PageTemplateParentID,$OrderInTemplate,$TemplateType);
                    }
                } else {
                    $this->ErrorTemplate = "<table border=1 width=100%><tr><td valign=center><font color=red>В шаблоне \"<b>".$this->getTemplateName($this->PageTemplateParentID)."</b>\" не выбран вложенный шаблон</font></td></tr></table>";
                }
            }
        } else $this->ShowError($this->getMysqlError($query));
        return $ret;
    }
    function getParentTemplate() {
        $query = "SELECT Parent_id FROM PageTemplate WHERE ID=".$this->PageTemplateParentID;
        $result = mysql_query($query, $this->conn_id);
        if ($result) {
            if ($row = mysql_fetch_row($result)) {
                $this->PageTemplateParentID = $row[0];
            } else {
                $this->ShowError("<b>getParentTemplate():</b>  результат выполнения запроса пустой");
            }
        } else $this->ShowError($this->getMysqlError($query));
    }

    // end engine

}
?>