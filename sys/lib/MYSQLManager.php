<?
class PageManager_MYSQL {
	var $conn_id;
	var $isAdmin;

	function setMysqlConnection() {
		$this->conn_id = getMysqlConnection();
	}

	function ShowError($ErrMsg) {
		echo $ErrMsg;
	}

	function getMysqlError($query) {
		if ($this->isAdmin) {
			$rett = "<!-- <table><tr><td colspan=2><font color=red><b>Error:</b></font></td></tr>
								   <tr><td valign=top><b>Query:</b></td><td>".$query."</td></tr>
								   <tr><td valign=top><b>Mysql:</b></td><td>".mysql_error($this->conn_id)."
					</td></tr></table> -->";
		} else {
			//$rett = "<font color=red><b>Error:</b></font>&nbsp; Ошибка выполнения запроса к базе данных <br>";
			$rett = "<!-- <table><tr><td colspan=2><font color=red><b>Error:</b></font></td></tr>
								   <tr><td valign=top><b>Query:</b></td><td>".$query."</td></tr>
								   <tr><td valign=top><b>Mysql:</b></td><td>".mysql_error($this->conn_id)."
					</td></tr></table> -->";
		}
		return $ret;
	}
}
?>
