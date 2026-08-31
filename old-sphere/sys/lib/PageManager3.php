<?

class PageManager extends PageManager_BASE {
	var $MaxNestingLevel = 10;
	var $Templates = array();
	var $AssemblyTemplateID;
	var $Error;

	function PageManager ($Data) {
		global $_GET,$_POST;

		$this->setMysqlConnection();

		if ($Data[Mode] == "admin") {
			$this->isAdmin = 1;
			if ($Data[PageID]) {
				if (!$this->setPageID($Data[PageID])) $this->Error = 1;
			} else {
				if (!$this->setAssemblyTemplate($Data[AssemblyTemplateID])) $this->Error = 1;
			}
		} else {
			$this->isAdmin = 0;
			if (!$this->setPageID($Data[PageID])) $this->Error = 1;
		}

		if (!$this->Error) {
			$this->PageTemplateParentID = 0;
			$this->level = 0;

			$action = ($_POST[action]) ? $_POST[action] : $_GET[action];
			if ($this->isAdmin) $this->ProcessAction($action);
			if (!$this->InitTemplates())  $this->Error = 1;
		} else {
			$this = null;
			return;
		}
	}

	function setAssemblyTemplate($ID) {
		$ret = false;
		if ($ID) {
			$query = "SELECT count(*) FROM AssemblyTemplate WHERE ID=$ID";
			$result = @mysql_db_query(DBName, $query, $this->conn_id);
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
			$query = "SELECT AT.ID, AT.Title, P.Parent_id, P.Title, P.RedirectURL, P.PageCategory_ID, P.lang_id  FROM Pages P LEFT JOIN AssemblyTemplate AT on P.AssemblyTemplate_id=AT.id WHERE P.ID=$ID";
			$result = @mysql_db_query(DBName, $query, $this->conn_id);
			if ($result) {
				if ($row = mysql_fetch_row($result)) {
					if ($row[1]) {
						$this->AssemblyTemplateID = $row[0];
						$this->Page[id] = $ID;
						$this->Page[parent_id] = $row[2];
						$this->Page[title] = $row[3];
						$this->Page[url] = $row[4];
						$this->Page[category_id] = $row[5];
						$this->Page[lang_id] = $row[6];
						$ret = true;
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
		$result = @mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			while ($row = mysql_fetch_row($result)) {
//				$this->Templates[$row[0]] = file($row[0]);
				$this->Templates[$row[0]][title] = $row[1];
				$this->Templates[$row[0]][body] = $row[2];
				$this->Templates[$row[0]][type] = $row[3];
				$ret = true;
			}
		} else $this->ShowError($this->getMysqlError($query));
		return $ret;
	}

	function deleteTemplateNode($PageTemplateID) {
		//echo "delete $PageTemplateID <br>";
		$query = "SELECT id FROM PageTemplate WHERE Parent_id=".$PageTemplateID;
		$result = @mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			while ($row = mysql_fetch_row($result)) {
				$this->deleteTemplateNode($row[0]);
			}
			$query = "DELETE FROM PageTemplate WHERE id=".$PageTemplateID;
			$result = @mysql_db_query(DBName, $query, $this->conn_id);
			if ($result) {
				$query = "DELETE FROM PageTemplate_PageData WHERE PageTemplate_id=".$PageTemplateID;
				$result = @mysql_db_query(DBName, $query, $this->conn_id);
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
					$result = mysql_db_query(DBName, $query, $this->conn_id);
					if ($result) {
						$row = mysql_fetch_row($result);
						if ($row[0] == 0) {
							$query = "INSERT INTO PageTemplate (Parent_id,AssemblyTemplate_id,OrderInTemplate,ApplyTemplate_id,AllowChangeData)
										 VALUES (".$_POST[PageTemplateID].",".$this->AssemblyTemplateID.",".$_POST[OrderInTemplate].",".$_POST[TemplateID].",'".(($_POST[AllowChangeData]) ? "Y": "N")."')";
							//echo $query."<br>";
							$result = mysql_db_query(DBName, $query, $this->conn_id);
							if (!$result) $this->ShowError($this->getMysqlError($query));
						}
					} else $this->ShowError($this->getMysqlError($query));
				}  else $this->ShowError("Не выбран шаблон !");
			break;
			case "deleteTemplateNode":
				$this->deleteTemplateNode($_GET[ID]);
			break;
			case "setTemplateData":
				$PageTemplateDataID = ($_POST[PageTemplateDataID]) ? $_POST[PageTemplateDataID] : $_GET[PageTemplateDataID];
				$PageTemplateID = ($_POST[PageTemplateID]) ? $_POST[PageTemplateID] : $_GET[PageTemplateID];
				if (strval($PageTemplateDataID) == "0") {
					$query = "DELETE FROM PageTemplate_PageData WHERE Page_id=".$this->Page[id]." AND PageTemplate_id=".$PageTemplateID;
					$result = mysql_db_query(DBName, $query, $this->conn_id);
					if (!$result) $this->ShowError($this->getMysqlError($query));
				} elseif (isset($PageTemplateDataID)) {
					$query = "SELECT count(*) FROM PageTemplate_PageData WHERE Page_id=".$this->Page[id]." AND PageTemplate_id=".$PageTemplateID;
					$result = mysql_db_query(DBName, $query, $this->conn_id);
					if ($result) {
						$row = mysql_fetch_row($result);
						if ($row[0] == 0) {
							$query = "INSERT INTO PageTemplate_PageData (Page_id,PageTemplate_id,Data_id) VALUES (".$this->Page[id].",".$PageTemplateID.",".$PageTemplateDataID.")";
						} else {
							$query = "UPDATE PageTemplate_PageData SET Data_id=".$PageTemplateDataID." WHERE Page_id=".$this->Page[id]." AND PageTemplate_id=".$PageTemplateID;
						}
						$result = mysql_db_query(DBName, $query, $this->conn_id);
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
			$result = mysql_db_query(DBName, $query, $this->conn_id);
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
		$result = mysql_db_query(DBName, $query, $this->conn_id);
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
			$query = "SELECT count(*) FROM TemplateType WHERE TypeID='".$TemplateType."'";
			$result = mysql_db_query(DBName, $query, $this->conn_id);
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
			$query = "SELECT ID, Title FROM Templates WHERE TypeID='".$TemplateType."' ORDER BY Title";
		} else {
			$TemplateType = $this->getTemplateTypeID($this->PageTemplateParentID);
			$query = "SELECT distinct T.ID, T.Title
					  FROM  Templates T, TemplateTypeTree TTT
					  WHERE (T.typeID='' OR T.TypeID is NULL) OR
					  		(T.TypeID=TTT.TypeID AND TTT.ParentTypeID='".$TemplateType."') OR
							('".$TemplateType."'='' AND T.TypeID='main')
					  ORDER BY T.Title";
		}
		$result = mysql_db_query(DBName, $query, $this->conn_id);
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
			$ret  = "<table border=1 width=100% style=\"border:1px\"><tr><td align=left valign=center>";
			$ret .= "<form action=\"".$PHP_SELF."\" method=post>\n";
			$ret .= "	<input type=hidden name=action value=\"addTemplateNode\">\n";
			$ret .= "	<input type=hidden name=PageTemplateID value=\"".$PageTemplateID."\">\n";
			$ret .= "	<input type=hidden name=OrderInTemplate value=\"".$OrderInTemplate."\">\n";
			$ret .= "	<input type=hidden name=AssemblyTemplateID value=\"".$this->AssemblyTemplateID."\">\n";
			$ret .= "	<select name=TemplateID><option value=0>Выберите шаблон</option>\n";
			while (list($TemplateID,$Title) = each ($TemplateList)) {
				$ret .= "	<option value=\"".$TemplateID."\">".$Title."</option>\n";
			}
			$ret .= "	</select><br>\n";
			$ret .= "	<input type=checkbox class=check name=AllowChangeData>&nbsp;<font size=1>Доступен для редактирования</font> <br>\n";
			$ret .= "	<input type=submit class=Submit value=\"".local('ADMIN_APPLY')."\">\n";
			$ret .= "</form>\n";
			$ret .= "</td></tr></table>\n";
		} else {
			$ret = "Ошибка: указан несушествующий тип шаблона \"<b>$TemplateType</b>\"";
		}
		return $ret;
	}

	function getTemplateDataSelectForm($PageTemplateID,$ItemList,$CurrentItemID="") {
		global $PHP_SELF;
		$ret  = "<table border=1  cellspacing=0 style=\"border:1px\">";
/*
		$ret .= "<tr><td bgcolor=silver>".$this->PageTemplateTitle."</td>
					 <td bgcolor=silver align=right><a href=\"".$PHP_SELF."?action=deleteTemplateNode&ID=".$PageTemplateID."&AssemblyTemplateID=".$this->AssemblyTemplateID."\">Change</a></td>
				 </tr>";
*/
		$ret .= "<tr><td bgcolor=#636E88>".$this->PageTemplateTitle."</td></tr>";
		$ret .= "<tr><td align=center valign=center>";
		$ret .= "<form action=\"".$PHP_SELF."\" method=post>\n";
		$ret .= "	<input type=hidden name=action value=\"setTemplateData\">\n";
		$ret .= "	<input type=hidden name=PageTemplateID value=\"".$PageTemplateID."\">\n";
		$ret .= "	<input type=hidden name=AssemblyTemplateID value=\"".$this->AssemblyTemplateID."\">\n";
		$ret .= "	<input type=hidden name=p value=\"".$this->Page[id]."\">\n";
		$ret .= "	<select name=PageTemplateDataID><option value=0>".local('ADMIN_CHOOSE_ELEMENT')."</option>\n";
		while (list($ItemID,$ItemTitle) = @each ($ItemList)) {
			$selected = ($ItemID == $CurrentItemID) ? "selected" : "";
			$ret .= "	<option value=\"".$ItemID."\" ".$selected.">".$ItemTitle."</option>\n";
		}
		$ret .= "	</select>\n";
		$ret .= "	<input type=submit class=Submit value=\"".local('ADMIN_APPLY')."\">\n";
		$ret .= "</form>\n";
		$ret .= "</td></tr></table>\n";
		return $ret;
	}

		function getTemplateDataForm($PageTemplateID,$DataTitle,$AllowChangeData="") {
		global $PHP_SELF;
		//#edefe1
		$bgcolor = ($AllowChangeData == "Y") ? "#D4F0CD" : "#F5B7B8";
		$ret  = "<table border=1 width=100% cellspacing=0 style=\"border:1px\">";
		$ret .= "<tr><td bgcolor=$bgcolor>".$DataTitle."</td>
					 <td bgcolor=$bgcolor align=right><a href=\"".$PHP_SELF."?action=deleteTemplateNode&ID=".$PageTemplateID."&AssemblyTemplateID=".$this->AssemblyTemplateID."\">Change</a></td>
				 </tr></table>\n";
		return $ret;
	}

	// engine

	function ApplyTemplate($OrderInTemplate=null,$TemplateType=null) {
		global $_GET, $_POST, $_COOKIE;
		global $PHP_SELF;

		if ($this->Error) return;
		if ($this->getNextTemplate($OrderInTemplate,$TemplateType)) {

			$this->level++;
			if ($this->level > $this->MaxNestingLevel) return $this->ShowError("Превышен максимально допустимый уровень вложенности шаблонов");

			$t = new template();
			$meta = array();

			switch (strval($this->Templates[$this->PageTemplate][type])) {
				case "main":
					$meta["Title"]  = $this->getTitle();
				break;
				case "body":
					$meta["Title"]  = $this->getTitle();
					$meta["LOCALTIME"]  = date("l dS of F Y h:i:s A");
				break;
				case "banners":
					$meta["Title"]  = "Start banners table";
				break;
				case "banner":
					if ($this->isAdmin) {
						if ($this->Page[id]) {
							$BannerList = $this->getBannerList();
							if ($this->getBannerData(&$meta,$this->PageTemplateDataID)) {
								$ShowBorder = 1;
							} else {
								$ApplyTemplate_ret = $this->getTemplateDataSelectForm($this->PageTemplateParentID,$BannerList,$this->PageTemplateDataID);
							}
						} else $ApplyTemplate_ret = $this->getTemplateDataForm($this->PageTemplateParentID,$this->PageTemplateTitle);
					} else {
						if (!($this->getBannerData(&$meta,$this->PageTemplateDataID))) {
							$ApplyTemplate_ret = "<table border=1><tr><td valign=center>Баннер не назначен</td></tr></table>";
						}
					}
				break;
				case "image":
					if ($this->isAdmin) {
						if ($this->Page[id]) {
							$ImageList = $this->getImageList();
							if ($this->getImageData(&$meta,$this->PageTemplateDataID)) {
								$ShowBorder = 1;
							} else {
								$ApplyTemplate_ret = $this->getTemplateDataSelectForm($this->PageTemplateParentID,$ImageList,$this->PageTemplateDataID);
							}
						} else $ApplyTemplate_ret = $this->getTemplateDataForm($this->PageTemplateParentID,$this->PageTemplateTitle);
					} else {
						if (!($this->getImageData(&$meta,$this->PageTemplateDataID))) {
							$ApplyTemplate_ret = "<table border=1><tr><td valign=center>Картинка не назначена</td></tr></table>";
						}
					}
				break;
				case "text":
					if ($this->isAdmin) {
						if ($this->Page[id]) {
							$TextBlockList = $this->getTextBlockList();
							if ($this->getTextBlockData(&$meta,$this->PageTemplateDataID)) {
								$ShowBorder = 1;
							} else {
								$ApplyTemplate_ret = $this->getTemplateDataSelectForm($this->PageTemplateParentID,$TextBlockList,$this->PageTemplateDataID);
							}
//						} else $ApplyTemplate_ret = $this->getTemplateDataForm($this->PageTemplateParentID,"Текстовый блок");
						} else $ApplyTemplate_ret = $this->getTemplateDataForm($this->PageTemplateParentID,$this->PageTemplateTitle,$this->PageTemplateAllowChangeData);
					} else {
						if (!($this->getTextBlockData(&$meta,$this->PageTemplateDataID))) {
							$ApplyTemplate_ret = "<table border=1><tr><td valign=center>Текстовый блок не назначен</td></tr></table>";
						}
					}
				break;
			}
			if (!isset($ApplyTemplate_ret)) {
				$t->assign($meta);
//				$ApplyTemplate_res = $t->getparsed(file($Template));
				$ApplyTemplate_res = $t->getparsed($this->Templates[$this->PageTemplate][body]);
				if ($this->isAdmin && !$this->Page[id]) {
					$ApplyTemplate_ret = eval("?><table border=1 cellpadding=2 cellspacing=0 style=\"border:1px\">
													<tr>
														<td bgcolor=#636E88>
															$this->PageTemplateTitle
														</td>
														<td align=right bgcolor=#636E88>
															<a href=\"$PHP_SELF?action=deleteTemplateNode&ID=$this->PageTemplateParentID&AssemblyTemplateID=$this->AssemblyTemplateID\">Delete</a>&nbsp;|&nbsp;
															<a href=\"/Admin/controls/CMS/Templates/edittemplate.php?action=edit&ID=$this->PageTemplate\" target=new>Edit</a>
														</td>
													</tr>
													<tr>
														<td colspan=2>$ApplyTemplate_res</td>
													</tr>
												</table>
											  <?");
				} elseif ($ShowBorder == 1) {
					$ApplyTemplate_ret = eval("?><table border=1 cellpadding=0 cellspacing=0 style=\"border:1px\">
													<tr>
														<td bgcolor=#636E88 align=left>
															<a href=\"$PHP_SELF?action=setTemplateData&PageTemplateID=$this->PageTemplateParentID&AssemblyTemplateID=$this->AssemblyTemplateID&p=".$this->Page[id]."&PageTemplateDataID=0\">Change</a>
														</td>
													</tr>
													<tr>
														<td>$ApplyTemplate_res</td>
													</tr>
												</table>
											  <?");
				} else {
					$ApplyTemplate_ret = eval("?>$ApplyTemplate_res<?");
				}
			}
			$this->getParentTemplate();
			$this->level--;
		} else {
			$ApplyTemplate_ret = $this->ErrorTemplate;
		}
		return $ApplyTemplate_ret;
	}

	function getNextTemplate($OrderInTemplate,$TemplateType) {
		$ret = false;
		$query = "SELECT PT.ID, PT.AllowChangeData, PTPD.Data_id, T.ID, T.Title
					 FROM PageTemplate PT, Templates T
					 LEFT JOIN PageTemplate_PageData PTPD on (PTPD.PageTemplate_id=PT.id AND PTPD.page_id='".$this->Page[id]."')
					 WHERE T.id=PT.ApplyTemplate_id AND
					 	   PT.Parent_id=".$this->PageTemplateParentID." AND
						   PT.OrderInTemplate=".$OrderInTemplate." AND
						   PT.AssemblyTemplate_id=".$this->AssemblyTemplateID;

		$result = mysql_db_query(DBName, $query, $this->conn_id);
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
		$result = mysql_db_query(DBName, $query, $this->conn_id);
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