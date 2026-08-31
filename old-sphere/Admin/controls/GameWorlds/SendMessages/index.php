<?PHP

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

error_reporting( E_ALL );

class sendMessage {

	private $dbWorldAccess;

	private $wid;

	private $dbId;

	private $username = "";

	private $message = "";

	private $subject = "";

	private $races = array();

	private $out;

	private $errormsg = "";

	function sendMessage()
	{
		$this->dbWorldAccess = $GLOBALS['dbWorldAccess'];

		if(isset($_POST['submit']))
		{
			$this->getPostData($_POST);

			if (empty($this->errormsg)) {
				$this->proceed();
			}
		}
		$this->showFirstMenu();
	}


	function showFirstMenu()
	{
		$common = DS_Common::CreateForCurentDB(0);
		if (!empty($this->errormsg)) {
			ShowCMSMessage($this->errormsg, 2);
		}

		if (!empty($this->out)) {
			echo $this->out."<br>";
		}

		echo "
		<form action='".$_SERVER["PHP_SELF"]."' method='POST'>
		<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table border=0 cellspacing=1 cellpadding=3>
		<tr>
			<td class=th_2 align=center>"
		.local( 'DS_USER_WORLD' )."</td>
			<td class=th_2 align=center>"
		.local( 'DS_USER_FROM' )."</td>
			<td class=th_2 align=center>"
		.local( 'DS_ACTIVATE_USERS_SUBJECT' )."</td>
			<td class=th_2 align=center>
				Раса</td>
		</tr>
		<tr>
			<td bgcolor='#444E6C' class=maintxt valign=top align=center>";
		$sql = sprintf ( "select * from ds_world" );
		$res = $common->getMultySql ($sql);
		if(!$res)
		{
			$this->errormsg = "SendMessage::showFirstMenu";
			return false;
		}
		echo "<select name='id_world[]' size='9' multiple>";
		for ( $i=0; $i<sizeof ( $res ); $i++ ) {
			$selected = '';
			if (count($this->dbId) > 0) {
				foreach ($this->dbId as $dbKey => $dbData)
				{
					if ($dbData == $res[$i]['dbId'] && $res[$i]['id_world'] == $this->wid[$dbKey]) {
						$selected = 'selected';
					}
				}
			}
			echo "<option value='".$res[$i]['id_world']."_".$res[$i]['dbId']."' $selected>".$res[$i]['worldname']."</option>";
		}
		echo "</select></td>
			<td bgcolor='#444E6C' class=maintxt valign=top align=center>
				<input type='text' name='username' value='".$this->username."'></td>
			<td bgcolor='#444E6C' class=maintxt valign=top align=center>
				<input type='text' name='subject' value='".$this->subject."'></td>
			<td bgcolor='#444E6C' class=maintxt valign=top align=center>";
		$sql = sprintf ( "
				SELECT r.id_race, l.text from ds_race r
				LEFT JOIN ds_localizedtextlanguage l ON (l.id_localizedtext = r.id_localizedname AND l.id_language = 3)
				WHERE r.id_race !=4
				" );
		$res = $common->getResultArray ("", $sql);
		if(!$res)
		{
			$this->errormsg = "SendMessage::showFirstMenu";
			return false;
		}
		echo "<select name='race[]' size='3' multiple>";
		for ( $i=0; $i<sizeof ( $res ); $i++ ) {
			if (count($this->races) > 0) {
				if (in_array($res[$i]['id_race'], $this->races)) {
					$selected = 'selected';
				} else {
					$selected = '';
				}

			} else {
				$selected = 'selected';
			}
			echo "<option value='".$res[$i]['id_race']."' $selected>".$res[$i]['text']."</option>";
		}
		echo "</select></td>
		</tr>
		<tr>
			<td colspan=4 class=th_2>
				&nbsp;&nbsp;&nbsp;".local ('ADMIN_TITLE_MESSAGE')."</td>
		</tr>
		<tr>
			<td colspan=4 bgcolor='#444E6C' class=maintxt>
				<textarea name='message' style='width:600px;height:400px;'>$this->message</textarea></td>
		</tr>
		</table></td></tr></table>
		<input type='submit' value='&nbsp;". local( 'DS_ACTION_SEND' )."&nbsp;' name='submit' class=submit>
		</form>
		";

		return true;
	}


	function getPostData($data)
	{	
		if(isset($data['id_world']))
		{
			if(is_array($data['id_world']))
			{
				foreach($data['id_world'] as $id_world)
				{
					$dataTmp = explode('_', $id_world);
					$this->wid[] = $dataTmp[0];
					$this->dbId[] = $dataTmp[1];
				}
			}
		} else {
			$this->errormsg = 'SELECT WORLD';
		}

		if(isset($data['subject']))
		{
			$this->subject = stripcslashes($data['subject']);
		} else {
			$this->errormsg = 'Enter Subject';
		}

		if(isset($data['message']))
		{
			$this->message = stripcslashes($data['message']);
			if (strlen(trim($data['message'])) < 10) {
				$this->errormsg = 'Enter Message (min 10 words)';
			}
		} else {
			$this->errormsg = 'Enter Message';
		}

		if(isset($data['username']) && !empty($data['username']))
		{
			$this->username = $data['username'];
		} else {
			$this->username = 'DestinySphere';
		}

		if(isset($data['race']))
		{
			$this->races= $data['race'];
		} else {
			$this->errormsg = 'Enter Race';
		}
	}


	function proceed()
	{
		$common = DS_Common::CreateForCurentDB(0);

		$messageScopeID = DS_Message::GetMessageScopeIDByName('WORLD');
		assert($messageScopeID > 0);


		foreach($this->wid as $i => $id_world)
		{
			assert($common->setDBID($this->dbId[$i]));

			$_SESSION['dbId'] = $this->dbId[$i];
			$_SESSION['world'] = (int)$id_world;

			$common->executeQuery("BEGIN");
			assert(empty($common->errormsg));

			$fromUIID = $this->_getFromUIID((int)$id_world);
			assert($fromUIID >= 0);

			$arr = array("`", "\"", "<", ">", "\n", "\t");
			$this->subject = str_replace($arr, "", $this->subject);

			$subject = pg_escape_string(urldecode($this->subject));
			assert(!empty($subject));

			$message = pg_escape_string(GL_EncodeGameMessage::Encode(urldecode($this->message)));
			assert(!empty($message));

			$senderName = $this->username;
			$instances = DS_World::GetAllWorldInstances((int)$id_world, $this->races);
			$world = DE_World::CreateForCurrentWorld();
			$recieverName = $world->getName();
			$messageID = DS_Message::CreateMessage($message, $subject, $senderName, $recieverName, $messageScopeID, $fromUIID);
			foreach ($instances as $k => $instance)
			{
				DS_Message::AddMessageToUser($messageID, (int)$instance['id_userinstance']);
			}

			$shortMassage = substr($this->message, 0, 100);

			//Получаем имя мира по его id
			$sql = "SELECT worldname FROM ds_world WHERE id_world = $id_world";
			$res = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
			$worldName = $res[0]['worldname'];

			actionLog("Send message in World:'".$worldName."'", "Masage: '$shortMassage...'");

			$this->out[] = array('world'  => $res[0]['worldname'], 'sendStatus' => count($instances));

			$common->executeQuery("COMMIT");
			assert(empty($common->errormsg));
		}
		$this->out = $this->formatResult( $this->out );

		return true;
	}

	private function _getFromUIID($worldID)
	{
		assert(is_int($worldID) && $worldID > 0);
		$common = DS_Common::CreateForCurentDB();
		$sql = "SELECT
					ui.*,
					(
						SELECT worldname
						FROM ds_world w
						WHERE w.id_world = '".$worldID."'
					) AS worldname
				FROM 
					ds_userinstance ui
					LEFT JOIN ds_user AS u ON (ui.id_user = u.id_user)
				WHERE
			 		u.username = '".$this->username."' OR ( ui.id_userinstance IN ( '0' ) )
					ORDER BY
					ui.id_userinstance DESC
				LIMIT 1";			
		$res = $common->getQueryArray($sql);
		assert(empty($common->errormsg));
		return (int)$res[0]["id_userinstance"];
	}

	function formatResult( $array = '')
	{
		$out = "
		<table cellSpacing='0' cellPadding='5'>
			<tr>
				<td colspan =2 bgcolor='5eAA5e'>".local( 'DS_USER_WORLD' )."</td>
				<td colspan =2 bgcolor='5eAA5e'>".local( 'DS_ACTION_SEND_STATUS' )."</td>
			</tr>
		";

		foreach ( $array as $row ){

			$out .= "
			<tr>
				<td colspan =2 bgcolor='e5e5e5'>".$row['world']."</td>
				<td colspan =2 bgcolor='e5e5e5'>".($row['sendStatus'])."</td>
			</tr>";
		}

		$out .= "</tr></table>";

		return $out;
	}
}

$sM = new sendMessage();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>