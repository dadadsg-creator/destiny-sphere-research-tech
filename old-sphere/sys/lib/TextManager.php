<?
# Version 1.2  10.07.2002

class TextManager extends PageManager_MYSQL {
	var $ErrMsg;

	function TextManager() {
		$this->setMysqlConnection();
	}

	function GetData($ID) {
		global $SERVER_NAME;
		$ret = false;
		$query = "SELECT E.ElementTitle, T.Body
				  FROM Element E, Element_Data ED, TextWHeader T
				  WHERE E.ID=ED.Element_id AND E.CurrentVersion_id=ED.ElementData_id AND
						E.Status='published' AND E.ElementType='TextWHeader' AND ED.Approbation='Y' AND
				  		E.ID='".$ID."' AND
						ED.ElementData_id=T.ID";
		$result = @mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			if ($row = mysql_fetch_row($result)) {
				$ret["Title"]	= preg_replace("/(\&)([A-Za-z0-9_]*);/","&amp;\\2;",$row[0]);
				$ret["Body"]	= preg_replace("/(\&)([A-Za-z0-9_]*);/","&amp;\\2;",$row[1]);
				//подставляем полный путь для картинок
				//				$ret["Body"] = preg_replace("/src=\"([^\/][a-zA-Z.\/_0-9\-]+.(gif|jpg))\"/i","src=\"http://".$SERVER_NAME."/\\1\"",$ret["Body"]);
				//				$ret["Body"] = preg_replace("/src=\"([^\/].+?.(gif|jpg))\"/i","src=\"http://".$SERVER_NAME."/\\1\"",$ret["Body"]);
				$ret["Body"] = preg_replace("/src=\"(.+?\.(gif|jpg))\"/i","src=\"http://".$SERVER_NAME."/\\1\"",$ret["Body"]);
			}
		} else $this->ErrMsg = "Ошибка при обращении к базе данных";
		return $ret;
	}

	function UpdateData($ID,&$Title,&$Body) {
		global $SERVER_NAME;
		global $_POST;
		$ret = false;
		if (ini_get("magic_quotes_gpc") == 0) {
			$Title 	= addslashes($Title);
			$Body 	= addslashes($Body);
		}
		// заменяем полный путь для картинок на относительный
		//			$Body = preg_replace("/src=(\\\\\")?(http:\/\/".$SERVER_NAME."(:[0-9]+)?)\/([a-zA-Z.\/_0-9\-]+.(gif|jpg))(\\\\\"|\s)/i","src=\"\\4\"",$Body);
		$Body = preg_replace("/src=(\\\\\")?(http:\/\/".$SERVER_NAME."(:[0-9]+)?)\/(.+?.(gif|jpg))(\\\\\"|\s)/i","src=\"\\4\"",$Body);

		$query = "SELECT E.ElementTitle, T.ID
					  FROM Element E, Element_Data ED, TextWHeader T
					  WHERE E.ID=ED.Element_id AND E.CurrentVersion_id=ED.ElementData_id AND
							E.Status='published' AND E.ElementType='TextWHeader' AND ED.Approbation='Y' AND
					  		E.ID='".$ID."' AND
							ED.ElementData_id=T.ID";
		$result = @mysql_db_query(DBName, $query, $this->conn_id);
		if ($result) {
			if ($row = mysql_fetch_row($result)) {
				$query = "UPDATE TextWHeader SET Body='".$Body."' WHERE ID='".$row[1]."'";
				$result = @mysql_db_query(DBName, $query, $this->conn_id);
				if ($result) {
					$ret = true;
				} else $this->ErrMsg = "Ошибка при обращении к базе данных";
			} else $this->ErrMsg = "Нет элемента с иденитфикатором: $ID";
		} else $this->ErrMsg = "Ошибка при обращении к базе данных";
		if (!$ret) {
			$Title	= preg_replace("/(\&)([A-Za-z0-9_]*);/","&amp;\\2;",stripslashes($Title));
			$Body	= preg_replace("/(\&)([A-Za-z0-9_]*);/","&amp;\\2;",stripslashes($Body));
		}
		return $ret;
	}

}
?>
