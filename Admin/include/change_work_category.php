<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td>
		<?
		/*$query = "SELECT ID, Title FROM PageCategories WHERE Lang_id='$LanguageID' ORDER BY ShowOrder";
		$result = @mysql_db_query(DBName,$query,$DB_CONNECTION_ID);
		echo "<form name=\"ChangeCategory\" action=\"$PHP_SELF\" method=\"post\">
				 <select name=\"change_cat_id\" onchange=\"javascrip: document.ChangeCategory.submit()\">\n";
		$selected = ($WorkPageCategoryID == "all") ? "selected" : "";
		echo "		 	<option value=all $selected>".local('ADMIN_ALL_EXISTING')."</option>\n";
		$selected = ($WorkPageCategoryID == "null") ? "selected" : "";
		echo "		 	<option value=null $selected>".local('ADMIN_ALL_OTHER')."</option>\n";
		while ($res = mysql_fetch_array($result)) {
			$selected = ($res[ID] == $WorkPageCategoryID) ? "selected" : "";
			echo "<option value=\"".$res[ID]."\" $selected>".$res[Title]."</option>\n";
		}
		echo "	</select>
			  </form>";*/
		?>
		</td>
	</tr>
</table>