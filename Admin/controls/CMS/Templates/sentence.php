<?
if (!$ID) headerLocation("index.php");

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";

$conn = getMysqlConnection();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";

?>
<h1><? echo local('ADMIN_PAGE_PATTERNS') ?></h1>

<?

if ($action == "update" && is_array($PostSentenses)) {
	$query = "DELETE FROM Sentense WHERE Template_id='$ID' AND Lang_id='$LanguageID'";
	$result = @mysql_db_query(DBName,$query,$conn);
	if ($result) {
		while (list($sentense,$text) = each($PostSentenses)) {
			$query = "INSERT INTO Sentense (Template_id,Sentense,SentenseValue,Lang_id) VALUES ('$ID','$sentense','$text','$LanguageID')";
			$result = @mysql_db_query(DBName,$query,$conn);
			if (!$result) $err .= "<li>".local('ADMIN_DATABASE_ACCESS_ERROR').":<br>".mysql_error($conn)."</li>";
		}
	} else $err .= "<li>".local('ADMIN_DATABASE_ACCESS_ERROR').":<br>".mysql_error($conn)."</li>";
	if (!$err) $Msg = local('ADMIN_INFO_UPDATE_COMPLITE');
}

$query = "SELECT Title, Body, Lang_id FROM Templates WHERE ID='$ID'";
$result = @mysql_db_query(DBName,$query,$conn);
if ($result) {
	if ($Template = mysql_fetch_array($result)) {
		$query = "SELECT Sentense, SentenseValue FROM Sentense WHERE Template_id='$ID' AND Lang_id='$LanguageID'";
		$result = @mysql_db_query(DBName,$query,$conn);
		if ($result) {
			while ($row = mysql_fetch_row($result)) {
				$Sentenses[$row[0]] = $row[1];
			}
		} else $err .= "<li>".local('ADMIN_DATABASE_ACCESS_ERROR').":<br>".mysql_error($conn)."</li>";
	} else $err .= "<li>".local('ADMIN_NOT_FOUND_MOULD_WITH_ID')." \"$ID\"</li>";
} else $err .= "<li>".local('ADMIN_DATABASE_ACCESS_ERROR').":<br>".mysql_error($conn)."</li>";

if (!$err && $Template[Lang_id] && $Template[Lang_id]!=$LanguageID) $err .= "<li>".local('ADMIN_MOULD_NOT_USE_LANGUAGE')."</li>";

?>
<a href="index.php?PartTypeID=<?=$PartTypeID?>"><img src="/Admin/img/button-back-to-list.gif" width="127" height="21" alt="<? echo local('ADMIN_BACK_TO_LIST'); ?>" border="0"></a>
<?
if (!$err) {
	?>
	<p><span class=MAINTXT style='font-size: 16px;'><b><?=$Template[Title]?></b></span></p>
	<?
	if ($Msg) echo "<font color=#FBCF00>$Msg</font>";
	if (preg_match_all ("/<!--\s*#\s*([A-Za-z0-9_]*?)\s*#\s*\/\/-->/", $Template[Body],$matches)) {
		?>
		<form action="<?=$PHP_SELF?>?action=update" method="post">
		<input type=hidden name="ID" value="<?=$ID?>">
		<input type=hidden name="PartTypeID" value="<?=$PartTypeID?>">
		<table border=0 cellpadding="5" cellspacing="0">
			<tr>
				<td class=th width="200">Sentence</td>
				<td class=th><? echo local('ADMIN_VALUE'); ?></td>
			</tr>
			<?
			while (list($k,$sentense) = each($matches[1])) {
				echo "<tr><td class=td>".$sentense."</td>
						  <td class=td><textarea name=\"PostSentenses[".$sentense."]\" rows=5 cols=60>".$Sentenses[$sentense]."</textarea></td>
					  </tr>";
			}
			echo "<br>";
			?>
			<tr>
				<td>&nbsp;</td>
				<td align="right">
					<input type=submit class=Submit value=local('ADMIN_APPLY')>
					&nbsp;&nbsp;&nbsp;&nbsp;
					<input type=reset class="Submit" value="<? echo local('ADMIN_CANCEL'); ?>" >
				</td>
			</tr>
		</table>
		</form>
		<?
	} else {
    	echo "<br><span class=SELECT_TXT>".local('ADMIN_IN_THIS_SENTENCE_NOT_USE')."</span><br>";
	}
} else {
	?>
	<br>
	<font color="red"><?=$err?></font>
	<?
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>