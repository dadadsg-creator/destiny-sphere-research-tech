<?
// вывод заголовка страницы
function title() {
	global $P;
	echo $P->Page[title];
}
// вывод короткого заголовка страницы
function short_title() {
	global $P;
	echo ($P->Page[title2]) ? $P->Page[title2] : $P->Page[title];
}
// для тегов <meta>
function meta_description() {
	global $P;
	global $default_meta_description;
	echo ($P->Page[description]) ? $P->Page[description] : $default_meta_description;
}
function meta_keywords() {
	global $P;
	global $default_meta_keywords;
	echo ($P->Page[meta_keywords]) ? $P->Page[meta_keywords] : $default_meta_keywords;
}

function make_seed() {
	    list($usec, $sec) = explode(' ', microtime());
	    return (float) $sec + ((float) $usec * 10000);
}	

// применить шаблон
function template($OrderInTemplate, $TemplateType=null) {
	global $P;
	echo $P->ApplyTemplate($OrderInTemplate, $TemplateType);
}

// выдать шаблон
function template_ret($OrderInTemplate, $TemplateType=null) {
	global $P;
	return $P->ApplyTemplate($OrderInTemplate, $TemplateType);
}

// вывод картинки с названием текущего раздела
function category_header_img() {
	global $P;
	$Img = $P->getCategoryHeaderImg();
	if ($Img[Src]) {
		echo "<img src=\"/img/".$Img[Src]."\" width=\"".$Img[Width]."\" height=\"".$Img[Height]."\" align=\"absmiddle\" border=0>";
	}
}

// вывод картинки, закрепленной за текущим разделом
function category_content_img() {
	global $P;
	$Img = $P->getCategoryContentImg();
	if ($Img[Src]) {
		echo "<img src=\"/img/".$Img[Src]."\" width=\"".$Img[Width]."\" height=\"".$Img[Height]."\" border=0>";
	}
}

// ************************************************ //
class PageManager_BASE extends PageManager_MYSQL{
	var $Page = array();
	
	function getBannerData(&$Data,$ID) {
		$ret = false;
		$query = "SELECT Concat('/getimage.php?t=banner&id=',B.ID), B.AltText, B.URL, B.BannerText, B.Width, B.Height
				  FROM Element E, Element_Data ED, BannerWText B
				  WHERE E.ID=ED.Element_id AND E.CurrentVersion_id=ED.ElementData_id AND
						E.Status='published' AND E.ElementType='BannerWText' AND ED.Approbation='Y' AND
				  		E.Lang_id='".$this->Page[lang_id]."' AND E.ID='".$ID."' AND
						ED.ElementData_id=B.ID";
		$result = mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			if ($row = mysql_fetch_row($result)) {
				$Data["BannerURL"]		= $row[2];
				$Data["BannerText"]		= $row[3];
				$Data["BannerImgSrc"]	= $row[0];
				$Data["BannerWidth"]	= $row[4];
				$Data["BannerHeight"]	= $row[5];
				$Data["BannerAlt"]		= $row[1];
				$ret = true;
			}
		} else $this->ShowError($this->getMysqlError($query));
		return $ret;
	}
	function getBannerList() {
		$ret = array();
/*
		$query = "SELECT B.ID, B.Title FROM BannerWText B, Pages P
					 WHERE (P.id=".$this->Page[id].") AND (
						 	   (B.PageCategory_id=P.PageCategory_id) OR 
							   ( ( (P.PageCategory_id=0) OR (P.PageCategory_id='') OR (P.PageCategory_id IS NULL) )  AND
							     ( (B.PageCategory_id=0) OR (B.PageCategory_id='') OR (B.PageCategory_id IS NULL) )
							   ) OR
							   (T.AccessibleOnAllPages='Y')
						   ) AND (B.lang_id=P.lang_id)
					 ORDER BY Title";
*/
		$query = "SELECT E.ID, E.ElementTitle
				  FROM Element E, Element_Data ED, BannerWText B
				  LEFT JOIN Element_Page EP on EP.Element_id=E.ID
				  LEFT JOIN Element_PageCategory EPC on EPC.Element_id=E.ID
				  WHERE (EP.Page_id=".$this->Page[id]." OR EPC.PageCategory_id='".$this->Page[category_id]."') AND
				  		E.ID=ED.Element_id AND E.CurrentVersion_id=ED.ElementData_id AND
						E.Status='published' AND E.ElementType='BannerWText' AND ED.Approbation='Y' AND
				  		E.Lang_id='".$this->Page[lang_id]."' AND
						ED.ElementData_id=B.ID
				  ORDER BY E.ElementTitle";
		$result = mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			while ($row = mysql_fetch_row($result)) {
				$ret[$row[0]] = $row[1];
			}
		} else $this->ShowError($this->getMysqlError($query));
		return $ret;
	}

	function getImageData(&$Data,$ID) {
		$ret = false;
		$query = "SELECT Concat('/getimage.php?t=image&id=',I.ID), I.Url, I.AltText, I.Width, I.Height
				  FROM Element E, Element_Data ED, Images I
				  WHERE E.ID=ED.Element_id AND E.CurrentVersion_id=ED.ElementData_id AND
						E.Status='published' AND E.ElementType='Images' AND ED.Approbation='Y' AND
				  		E.Lang_id='".$this->Page[lang_id]."' AND E.ID='".$ID."' AND
						ED.ElementData_id=I.ID";
		$result = mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			if ($row = mysql_fetch_row($result)) {
				$Data["ImgSrc"]	= $row[0];
				$Data["Width"]	= $row[3];
				$Data["Height"]	= $row[4];
				$Data["Alt"]	= $row[2];
				$Data["URL"]	= $row[1];
				$ret = true;
			}
		} else $this->ShowError($this->getMysqlError($query));
		return $ret;
	}
	function getImageList() {
		$ret = array();
/*
		$query = "SELECT distinct I.ID, I.Title FROM Images I
					 LEFT JOIN Image_Page IP on IP.Image_id=I.ID
					 LEFT JOIN Image_PageCategory IPC on IPC.Image_id=I.ID
					 WHERE IP.Page_id=".$this->Page[id]." OR IPC.PageCategory_id='".$this->Page[category_id]."'
					 ORDER BY I.Title";
*/
		$query = "SELECT E.ID, E.ElementTitle
				  FROM Element E, Element_Data ED, Images I
				  LEFT JOIN Element_Page EP on EP.Element_id=E.ID
				  LEFT JOIN Element_PageCategory EPC on EPC.Element_id=E.ID
				  WHERE (EP.Page_id=".$this->Page[id]." OR EPC.PageCategory_id='".$this->Page[category_id]."') AND
				  		E.ID=ED.Element_id AND E.CurrentVersion_id=ED.ElementData_id AND
						E.Status='published' AND E.ElementType='Images' AND ED.Approbation='Y' AND
				  		E.Lang_id='".$this->Page[lang_id]."' AND
						ED.ElementData_id=I.ID
				  ORDER BY E.ElementTitle";
		$result = mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			while ($row = mysql_fetch_row($result)) {
				$ret[$row[0]] = $row[1];
			}
		} else $this->ShowError($this->getMysqlError($query));
		return $ret;
	}

	function getTextBlockData(&$Data,$ID) {
		$ret = false;
//		$query = "SELECT Body FROM TextWHeader WHERE ID='".$ID."'";
		$query = "SELECT 
					T.Body,
					T.inner_title
				  FROM Element E, Element_Data ED, TextWHeader T
				  WHERE E.ID=ED.Element_id AND E.CurrentVersion_id=ED.ElementData_id AND
						E.Status='published' AND E.ElementType='TextWHeader' AND ED.Approbation='Y' AND
				  		E.Lang_id='".$this->Page[lang_id]."' AND E.ID='".$ID."' AND
						ED.ElementData_id=T.ID";
		$result = mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			if ($row = mysql_fetch_row($result)) {
				$Data["Text"]	= $row[0];
				$Data["Title"]	= $row[1];
				$ret = true;
			}
		} else $this->ShowError($this->getMysqlError($query));
		return $ret;
	}
	function getTextBlockList() {
		$ret = array();
/*
		$query = "SELECT T.ID, T.Title FROM TextWHeader T, Pages P
					 WHERE (P.id=".$this->Page[id].") AND (
						 	   (T.PageCategory_id=P.PageCategory_id) OR 
							   ( ( (P.PageCategory_id=0) OR (P.PageCategory_id='') OR (P.PageCategory_id IS NULL) )  AND
							     ( (T.PageCategory_id=0) OR (T.PageCategory_id='') OR (T.PageCategory_id IS NULL) )
							   ) OR
							   (T.AccessibleOnAllPages='Y')
						   ) AND (T.lang_id=P.lang_id)
					 ORDER BY T.Title";
*/
/*
		$query = "SELECT E.ID, E.ElementTitle
				  FROM Element E, Element_Data ED, TextWHeader T
				  WHERE E.ID=ED.Element_id AND E.CurrentVersion_id=ED.ElementData_id AND
						E.Status='published' AND E.ElementType='TextWHeader' AND ED.Approbation='Y' AND
				  		E.Lang_id='".$this->Page[lang_id]."' AND
						ED.ElementData_id=T.ID
				  ORDER BY E.ElementTitle";
*/
		$query = "
			SELECT 
				distinct E.ID, 
				E.ElementTitle
			FROM 
				Element_Data ED, 
				TextWHeader T,
				Element E
			  	LEFT JOIN Element_Page EP on ( EP.Element_id=E.ID )
				LEFT JOIN Element_PageCategory EPC on ( EPC.Element_id=E.ID )
			WHERE 
				(EP.Page_id=".$this->Page[id]." OR EPC.PageCategory_id='".$this->Page[category_id]."') AND
				E.ID=ED.Element_id AND E.CurrentVersion_id=ED.ElementData_id AND
				E.Status='published' AND E.ElementType='TextWHeader' AND ED.Approbation='Y' AND
				E.Lang_id='".$this->Page[lang_id]."' AND
				ED.ElementData_id=T.ID
			ORDER BY E.ElementTitle";

		$result = mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			while ($row = mysql_fetch_row($result)) {
				$ret[$row[0]] = $row[1];
			}
		} else $this->ShowError($this->getMysqlError($query));
		return $ret;
	}

	function getDynPHPContentData(&$Data,$ID) {
		$ret = false;
		$query = "SELECT Title, Body FROM DynPHPContent WHERE ID='".$ID."'";
		$result = mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			if ($row = mysql_fetch_row($result)) {
				$Data[Title] = $row[0];
				$Data[Body] = $row[1];
				$ret = true;
			}
		} else $this->ShowError($this->getMysqlError($query));
		return $ret;
	}
	function getDynPHPContentList() {
		$ret = array();
		$query = "SELECT ID, Title FROM DynPHPContent WHERE (ISNULL(Lang_id) OR Lang_id='' OR Lang_id='".$this->Page[lang_id]."') ORDER BY Title";
		$result = mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			while ($row = mysql_fetch_row($result)) {
				$ret[$row[0]] = $row[1];
			}
		} else $this->ShowError($this->getMysqlError($query));
		return $ret;
	}


	function getMainMenu() {
		$IMG_DIR = "/img/";
		$ret = array();
		$query = "SELECT ID, Title, RedirectURL, ShowOrder,
						 MenuImgAct, MenuImgActWidth, MenuImgActHeight,
						 MenuImgInact, MenuImgInactWidth, MenuImgInactHeight,
						 MenuImgAct2, MenuImgAct2Width, MenuImgAct2Height,
						 MenuImgInact2, MenuImgInact2Width, MenuImgInact2Height
				  FROM PageCategories
				  WHERE lang_id='".$this->Page[lang_id]."' ORDER BY ShowOrder";
		$result = mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			while ($row = mysql_fetch_row($result)) {
				$ret[$row[0]]["Title"] 						= getLocalize($row[1]);
				$ret[$row[0]]["URL"] 						= $row[2];
				$ret[$row[0]]["ShowOrder"] 					= $row[3];
				$ret[$row[0]]["MenuImgAct"]["Src"] 			= $IMG_DIR.$row[4];
				$ret[$row[0]]["MenuImgAct"]["Width"]		= $row[5];
				$ret[$row[0]]["MenuImgAct"]["Height"]		= $row[6];
				$ret[$row[0]]["MenuImgInact"]["Src"]		= $IMG_DIR.$row[7];
				$ret[$row[0]]["MenuImgInact"]["Width"]		= $row[8];
				$ret[$row[0]]["MenuImgInact"]["Height"]		= $row[9];
				$ret[$row[0]]["MenuImgAct2"]["Src"] 		= $IMG_DIR.$row[10];
				$ret[$row[0]]["MenuImgAct2"]["Width"] 		= $row[11];
				$ret[$row[0]]["MenuImgAct2"]["Height"] 		= $row[12];
				$ret[$row[0]]["MenuImgInact2"]["Src"] 		= $IMG_DIR.$row[13];
				$ret[$row[0]]["MenuImgInact2"]["Width"] 	= $row[14];
				$ret[$row[0]]["MenuImgInact2"]["Height"] 	= $row[15];
				$ret[$row[0]]["OperaURL"] 					= str_replace('?p=','index.php?p=',$row[2]);
				
			}
		} else $this->ShowError($this->getMysqlError($query));
		return $ret;
	}
	
	function getMenuLevel2($caregory_id=null) {
		$ret = array();
		if (! $caregory_id) $caregory_id = $this->Page[category_id];
		$query = "select 
						ID, 
						Title, 
						ImgAct, 
						ImgInact, 
						RedirectURL, 
						ShortName,
						inNewWindow,
						notInMenu						
				   from 
						Pages
				   where 
						PageCategory_id='".$caregory_id."' AND (
					 		(Parent_id='') OR (Parent_id=0) OR (Parent_id is NULL)
						   ) AND lang_id='".$this->Page[lang_id]."'
					 order by OrderInParent";
		$result = mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			while ($row = mysql_fetch_row($result)) {
				$ret[$row[0]]["ID"] = $row[0];
				$ret[$row[0]]["Title"] = getLocalize($row[1]);
				$ret[$row[0]]["ImgActSrc"] = $row[2];
				$ret[$row[0]]["ImgInactSrc"] = $row[3];
				$ret[$row[0]]["inNewWindow"] = $row[6];
				$ret[$row[0]]["notInMenu"] = $row[7];
				$ret[$row[0]]["URL"] = ($row[4]) ? $row[4] : ( ($row[5]) ? "?p=".$row[5] : "?p=".$row[0] );
			}
		} else $this->ShowError($this->getMysqlError($query));
		return $ret;
	}

	function getMenuLevel3($parent_id=null, $category_id=null) {
		$ret = array();
		
		$category_id = ($category_id) ? $category_id : $this->Page[category_id];
		
		if ($parent_id) {
			$PageMenuParentID = $parent_id;
		} else {
			if ($this->Page[parent_id]) {
				$query = "SELECT Parent_id FROM Pages WHERE ID=".$this->Page[parent_id];
				$result = mysql_db_query(DBName, $query, $this->conn_id);
				if ($result) {
					$row = mysql_fetch_row($result);
					$PageMenuParentID = ($row[0]) ? $row[0] : $this->Page[parent_id];
				} else $this->ShowError($this->getMysqlError($query));
			} else $PageMenuParentID = $this->Page[id];
		}
		$query = "select 
					P.ID, 
					P.Title, 
					P.RedirectURL, 
					P.ShortName,
					P.inNewWindow						
				from 
					Pages P, 
					Pages PP
				where P.Parent_id=PP.ID AND P.Parent_id='".$PageMenuParentID."'
						   AND P.lang_id='".$this->Page[lang_id]."'
					 order by P.OrderInParent";
//		echo $query;
		
/*
		$query = "select ID, Title, RedirectURL, ShortName from Pages
					 where PageCategory_id='".$this->Page[category_id]."' AND
					 	   Parent_id='".(($this->Page[parent_id]) ? $this->Page[parent_id] : $this->Page[id])."'
						   AND lang_id='".$this->Page[lang_id]."'
					 order by OrderInParent";
*/
		$result = mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			while ($row = mysql_fetch_row($result)) {
				$ret[$row[0]]["ID"] = $row[0];
				$ret[$row[0]]["Title"] = getLocalize($row[1]);
				$ret[$row[0]]["inNewWindow"] = $row[4];
				$ret[$row[0]]["URL"] = ($row[2]) ? $row[2] : ( ($row[3]) ? "?p=".$row[3] : "?p=".$row[0] );
			}
		} else $this->ShowError($this->getMysqlError($query));
		return $ret;
	}

	function getMenuLevel4($parent_id=null) {
		$ret = array();
		$PageMenuParentID = null;
		if ($parent_id) {
			$PageMenuParentID = $parent_id;
		} else {
			if ($this->Page[parent_id]) {
				$query = "SELECT Parent_id FROM Pages WHERE ID=".$this->Page[parent_id];
				$result = mysql_db_query(DBName, $query, $this->conn_id);
				if ($result) {
					$row = mysql_fetch_row($result);
					$PageMenuParentID = ($row[0]) ? $this->Page[parent_id] : $this->Page[id];
				} else $this->ShowError($this->getMysqlError($query));
			}
		}
		if ($PageMenuParentID) {
				$query = "select P.ID, P.Title, P.RedirectURL, P.ShortName from Pages P, Pages PP
							 where P.Parent_id=PP.ID AND P.Parent_id='".$PageMenuParentID."' AND  
							 	   P.PageCategory_id='".$this->Page[category_id]."' AND 
								   NOT (PP.Parent_id=0 OR PP.Parent_id='' OR PP.Parent_id is NULL) 
								   AND P.lang_id='".$this->Page[lang_id]."'
							 order by P.OrderInParent";
				$result = mysql_db_query(DBName, $query, $this->conn_id);
				if ($result) {
					while ($row = mysql_fetch_row($result)) {
						$ret[$row[0]]["Title"] = getLocalize($row[1]);
						$ret[$row[0]]["URL"] = ($row[2]) ? $row[2] : ( ($row[3]) ? "?p=".$row[3] : "?p=".$row[0] );
					}
				} else $this->ShowError($this->getMysqlError($query));
		}
		return $ret;
	}

	function getNews($_LIMIT_NEWS=2, $news_id=null, $year=null) {
		$ret = array();
		if ($news_id) {
			$query = "SELECT E.ID, E.ElementTitle, N.Alert, N.Body, DAYOFMONTH(N.NewsDate), MONTH(N.NewsDate), YEAR(N.NewsDate), not (isNull(N.Image) OR N.Image=''), N.Url, N.AltText, N.Width, N.Height, N.OpenInNewWindow, N.ID
					  FROM Element E, Element_Data ED, News N
					  WHERE E.ID=ED.Element_id AND E.CurrentVersion_id=ED.ElementData_id AND
							E.Status='published' AND E.ElementType='News' AND ED.Approbation='Y' AND
					  		E.Lang_id='".$this->Page[lang_id]."' AND E.ID='".$news_id."' AND
							ED.ElementData_id=N.ID";
		} elseif (intval($year)) {
			$query = "SELECT E.ID, E.ElementTitle, N.Alert, N.Body, DAYOFMONTH(N.NewsDate), MONTH(N.NewsDate), YEAR(N.NewsDate), not (isNull(N.Image) OR N.Image=''), N.Url, N.AltText, N.Width, N.Height, N.OpenInNewWindow, N.ID, DATE_FORMAT(N.NewsDate,'%Y%m%d%H%i%s') date_sort
					  FROM Element E, Element_Data ED, News N
					  WHERE E.ID=ED.Element_id AND E.CurrentVersion_id=ED.ElementData_id AND
							E.Status='published' AND E.ElementType='News' AND ED.Approbation='Y' AND
					  		E.Lang_id='".$this->Page[lang_id]."' AND
							ED.ElementData_id=N.ID AND
							YEAR(N.NewsDate)=".intval($year)."
					  ORDER BY date_sort DESC";
		} else {
			$start_date = mktime(0, 0, 0, date("m"), date("d"),  date("Y"));
			$query = "SELECT E.ID, E.ElementTitle, N.Alert, N.Body, DAYOFMONTH(N.NewsDate), MONTH(N.NewsDate), YEAR(N.NewsDate), not (isNull(N.Image) OR N.Image=''), N.Url, N.AltText, N.Width, N.Height, N.OpenInNewWindow, N.ID, DATE_FORMAT(N.NewsDate,'%Y%m%d%H%i%s') date_sort 
					  FROM Element E, Element_Data ED, News N
					  WHERE E.ID=ED.Element_id AND E.CurrentVersion_id=ED.ElementData_id AND
							E.Status='published' AND E.ElementType='News' AND ED.Approbation='Y' AND
					  		E.Lang_id='".$this->Page[lang_id]."' AND
							ED.ElementData_id=N.ID AND
							UNIX_TIMESTAMP(newsdate) <= $start_date
					  ORDER BY date_sort DESC limit 0, ".($_LIMIT_NEWS);
		}
		//echo $query."<br>";
		$result = mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			while ($row = mysql_fetch_row($result)) {
				$ret[$row[0]]["Title"] 				= $row[1];
				$ret[$row[0]]["Alert"] 				= str_replace("\n","<br>",$row[2]);
//				$ret[$row[0]]["Body"] 				= str_replace("\n","<br>",$row[3]);
				$ret[$row[0]]["Body"] 				= $row[3];
				$ret[$row[0]]["NewsDate"]["day"] 		= $row[4];
				$ret[$row[0]]["NewsDate"]["month"] 		= $row[5];
				$ret[$row[0]]["NewsDate"]["year"] 		= $row[6];
				$ret[$row[0]]["Image"] 				= $row[7];
				$ret[$row[0]]["Url"] 				= $row[8];
				$ret[$row[0]]["AltText"] 			= $row[9];
				$ret[$row[0]]["ImgWidth"] 			= $row[10];
				$ret[$row[0]]["ImgHeight"] 			= $row[11];
				$ret[$row[0]]["OpenInNewWindow"] 	= $row[12];
				$ret[$row[0]]["ImageID"] 			= $row[13];
			}
		} else $this->ShowError($this->getMysqlError($query));
		return $ret;
	}
	function getNews2($_LIMIT_NEWS=2, $news_id=null) {
		$ret = array();
		if ($news_id) {
			$query = "SELECT E.ID, E.ElementTitle, N.Alert, N.Body, DAYOFMONTH(N.NewsDate), MONTH(N.NewsDate), YEAR(N.NewsDate), not (isNull(N.Image) OR N.Image=''), N.Url, N.AltText, N.Width, N.Height, N.OpenInNewWindow
					  FROM Element E, Element_Data ED, News2 N
					  WHERE E.ID=ED.Element_id AND E.CurrentVersion_id=ED.ElementData_id AND
							E.Status='published' AND E.ElementType='News2' AND ED.Approbation='Y' AND
					  		E.Lang_id='".$this->Page[lang_id]."' AND E.ID='".$news_id."' AND
							ED.ElementData_id=N.ID";
		} else {
			$start_date = mktime(0, 0, 0, date("m"), date("d"),  date("Y"));
			$query = "SELECT E.ID, E.ElementTitle, N.Alert, N.Body, DAYOFMONTH(N.NewsDate), MONTH(N.NewsDate), YEAR(N.NewsDate), not (isNull(N.Image) OR N.Image=''), N.Url, N.AltText, N.Width, N.Height, N.OpenInNewWindow, DATE_FORMAT(N.NewsDate,'%Y%m%d%H%i%s') date_sort
					  FROM Element E, Element_Data ED, News2 N
					  WHERE E.ID=ED.Element_id AND E.CurrentVersion_id=ED.ElementData_id AND
							E.Status='published' AND E.ElementType='News2' AND ED.Approbation='Y' AND
					  		E.Lang_id='".$this->Page[lang_id]."' AND
							ED.ElementData_id=N.ID AND
							UNIX_TIMESTAMP(newsdate) <= $start_date
					  ORDER BY date_sort DESC limit 0, ".($_LIMIT_NEWS);
		}
		//echo $query."<br>";
		$result = mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			while ($row = mysql_fetch_row($result)) {
				$ret[$row[0]]["Title"] 				= $row[1];
				$ret[$row[0]]["Alert"] 				= str_replace("\n","<br>",$row[2]);
				$ret[$row[0]]["Body"] 				= str_replace("\n","<br>",$row[3]);
				$ret[$row[0]]["NewsDate"]["day"] 		= $row[4];
				$ret[$row[0]]["NewsDate"]["month"] 		= $row[5];
				$ret[$row[0]]["NewsDate"]["year"] 		= $row[6];
				$ret[$row[0]]["Image"] 				= $row[7];
				$ret[$row[0]]["Url"] 				= $row[8];
				$ret[$row[0]]["AltText"] 			= $row[9];
				$ret[$row[0]]["ImgWidth"] 			= $row[10];
				$ret[$row[0]]["ImgHeight"] 			= $row[11];
				$ret[$row[0]]["OpenInNewWindow"] 	= $row[12];
			}
		} else $this->ShowError($this->getMysqlError($query));
		return $ret;
	}

	function getCategoryHeaderImg() {
		$ret = array();
		if ($this->Page[id]) {
			$query = "SELECT HeaderImg, HeaderImgWidth, HeaderImgHeight FROM PageCategories WHERE ID='".$this->Page[category_id]."'";
			$result = mysql_db_query(DBName, $query, $this->conn_id);
			if ($result) {
				if ($row = mysql_fetch_row($result)) {
					$ret["Src"] 	= $row[0];
					$ret["Width"]	= $row[1];
					$ret["Height"]	= $row[2];
				}
			} else $this->ShowError($this->getMysqlError($query));
		}
		return $ret;
	}
	function getCategoryContentImg() {
		$ret = array();
		if ($this->Page[id]) {
			$query = "SELECT ContentImg, ContentImgWidth, ContentImgHeight FROM PageCategories WHERE ID=".$this->Page[category_id];
			$result = mysql_db_query(DBName, $query, $this->conn_id);
			if ($result) {
				if ($row = mysql_fetch_row($result)) {
					$ret["Src"] 	= $row[0];
					$ret["Width"]	= $row[1];
					$ret["Height"]	= $row[2];
				}
			} else $this->ShowError($this->getMysqlError($query));
		}
		return $ret;
	}
/*
	function getNews($_LIMIT_NEWS=2, $news_id=null) {
		$ret = array();
		if ($news_id) {
			$query = "SELECT ID, Title, Body, DAYOFMONTH(NewsDate), MONTH(NewsDate), YEAR(NewsDate), not (isNull(Image) OR Image=''), Url, AltText, Width, Height, OpenInNewWindow
					  FROM News WHERE Lang_id='".$this->Page[lang_id]."' AND ID='".$news_id."'";
		} else {
			$start_date = mktime(0, 0, 0, date("m"), date("d"),  date("Y"));
			$query = "SELECT ID, Title, Body, DAYOFMONTH(NewsDate), MONTH(NewsDate), YEAR(NewsDate), not (isNull(Image) OR Image=''), Url, AltText, Width, Height, OpenInNewWindow
					  FROM News WHERE UNIX_TIMESTAMP(newsdate) <= $start_date  AND Lang_id='".$this->Page[lang_id]."'
					  ORDER BY newsdate DESC limit 0, ".($_LIMIT_NEWS + 1);
		}
		//echo $query."<br>";
		$result = mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			while ($row = mysql_fetch_row($result)) {
				$ret[$row[0]]["Title"] 				= $row[1];
				$ret[$row[0]]["Body"] 				= $row[2];
				$ret[$row[0]]["NewsDate"]["day"] 		= $row[3];
				$ret[$row[0]]["NewsDate"]["month"] 		= $row[4];
				$ret[$row[0]]["NewsDate"]["year"] 		= $row[5];
				$ret[$row[0]]["Image"] 				= $row[6];
				$ret[$row[0]]["Url"] 				= $row[7];
				$ret[$row[0]]["AltText"] 			= $row[8];
				$ret[$row[0]]["ImgWidth"] 			= $row[9];
				$ret[$row[0]]["ImgHeight"] 			= $row[10];
				$ret[$row[0]]["OpenInNewWindow"] 	= $row[11];
			}
		} else $this->ShowError($this->getMysqlError($query));
		return $ret;
	}
*/

	function getPage($page_id) {
		$ret = array();
		$query = "SELECT ID, Parent_ID, Title, RedirectURL, PageCategory_ID, Lang_id FROM Pages WHERE ID='".$page_id."'";
		$result = mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			if ($row = mysql_fetch_row($result)) {
				$ret["id"] 	= $row[0];
				$ret["parent_id"]	= $row[1];
				$ret["title"]	= $row[2];
				$ret["url"]	= $row[3];
				$ret["category_id"]	= $row[4];
				$ret["lang_id"]	= $row[5];
			}
		} else $this->ShowError($this->getMysqlError($query));
		return $ret;
	}

}

?>