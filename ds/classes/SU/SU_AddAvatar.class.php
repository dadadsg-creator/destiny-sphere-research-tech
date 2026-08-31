<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_AddAvatar
{
	private $common;

	private $DBID = 0;

	private $userName = null;

	private $fileName = null;

	private $fileLocation = null;

	private $action;

	public function __construct()
	{
		$this->common = DS_Common::CreateForCurentDB();
	}

	public function main($data)
	{
		$this->_getParams($data);
		
		$out = '';
		
		$this->common->setDbID($this->DBID);
		
		switch ($this->action)
		{
			case 'index':
				$out .= $this->_viewIndex();
				break;
			case 'addAvatar':
				$out .= $this->_addAvatar();
				$out .= $this->_viewIndex();
				break;
			case 'viewAvatars':
				$out .= $this->_viewAvatars();
				break;
			case 'deleteAvatar':
				$out .= $this->_deleteAvatar();
				$out .= $this->_viewIndex();
				break;
			default:
				$out .= $this->_viewIndex();
		}
		
		return $out;
	}

	private function _viewIndex()
	{
		$out = "<h2>Add Avatar</h2>";
		$out .= "<form method=POST>";

		$out .= "<table border=0>";
		$out .= '<tr><td>';
		$out .= '<b>User: </b>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="text" size="20" name="username" value="'.$this->userName.'">';
		$out .= '</td></tr>';

		$out .= '<tr><td>';
		$out .= '<b>File location:&nbsp;&nbsp;</b>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="text" size="20" name="filelocation" value="/ds/useravatars/">';
		$out .= '</td></tr>';

		$out .= '<tr><td>';
		$out .= '<b>File name:&nbsp;&nbsp;</b>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="text" size="20" name="filename" value="'.$this->fileName.'">';
		$out .= '</td></tr>';

		$out .= '</table>';

		$out .= '<input type="hidden" name="action" value="addAvatar">';
		$out .= '<input type="submit" value="&nbsp;&nbsp;&nbsp;Add&nbsp;&nbsp;&nbsp;">';
		$out .= "</form>";

		$out .= "<hr>";

		$out .= "<h2>View Exist Avatars</h2>";
		$out .= "<form method=POST>";

		$out .= "<table border=0>";
		$out .= '<tr><td>';
		$out .= '<b>User: </b>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="text" size="20" name="username" value="All">';
		$out .= '</td></tr>';

		$out .= '</table>';

		$out .= '<input type="hidden" name="action" value="viewAvatars">';
		$out .= '<input type="submit" value="View Exist Avatars">';
		$out .= "</form>";

		return $out;
	}

	private function _viewAvatars()
	{
		assert(!is_null($this->DBID));

		$this->common->setDbID($this->DBID);

		if ($this->userName != 'All')
		{
			//Проверяем, есть ли пользователь с введенным ником
			$sql = "SELECT
				username
				FROM
				ds_user
				WHERE username='$this->userName'
				";

			$res = $this->common->getResultArray("", $sql, "", "", "");
			assert(empty($this->common->errormsg));

			$out = "<h1>User Avatars</h1>";
			if (count($res)==0)
			{
				$out .= "<h2><font color=red>User <b>$this->userName</b> not exist</h2>";
				$out .= "<form method=POST>";
				$out .= '<input type="hidden" name="action" value="index">';
				$out .= "<input type='submit' value='Back'>";
				$out .= "</form>";

				return $out;
			}
		}

		//Выбираем из базы имена игроков и их аватарки
		if ($this->userName == 'All')
		{
			$where = '';
		}else
		{
			$where = "WHERE u.username='$this->userName'";
		}
		$sql = "SELECT
				u.username, uhi.image_url
				FROM
				ds_user_has_image as uhi
				LEFT JOIN ds_user as u ON (u.id_user = uhi.id_user)
				$where";

		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));

		$out = "<h1>User Avatars</h1>";
		if (count($res)==0)
		{
			$out .= "<h2><font color=red>User <b>$this->userName</b> has not avatar</font></h2>";
			$out .= "<form method=POST>";
			$out .= '<input type="hidden" name="action" value="index">';
			$out .= "<input type='submit' value='Back'>";
			$out .= "</form>";

			return $out;
		}

		$out .= "<table border=1>";
		$out .= "<tr><th>";
		$out .= " User name ";
		$out .= "</th><th>";
		$out .= " Image ";
		$out .= "</th>";
		$out .= "<th>";
		$out .= "&nbsp;&nbsp;&nbsp;";
		$out .= "</th></tr>";

		foreach($res as $key => $data)
		{
			$out .= "<form method=POST>";
			$out .= "<tr><td>";
			$out .= $data['username'];
			$out .= "</td><td>";
			$out .= $data['image_url'];
			$out .= "</td>";
			$out .= "<td>";
			$out .= "<input type='hidden' name='username' value='".$data['username']."'>";
			$out .= "<input type='hidden' name='world_id' value='".$this->DBID."_".$this->worldID."'>";
			$out .= '<input type="hidden" name="action" value="deleteAvatar">';
			$out .= "<input type='submit' value='Delete Avatar'>";
			$out .= "</td></tr>";
			$out .= "</form>";
		}
		$out .= "</table>";

		return $out;
	}

	private function _addAvatar()
	{
		assert(!empty($this->userName));

		assert(!is_null($this->DBID));

		$this->common->setDbID($this->DBID);

		
			//Проверяем, есть ли пользователь с введенным ником
			$sql = "SELECT
				username
				FROM
				ds_user
				WHERE username='$this->userName'
				";

			$res = $this->common->getResultArray("", $sql, "", "", "");
			assert(empty($this->common->errormsg));

			$out = "<h1>User Avatars</h1>";
			if (count($res)==0)
			{
				$out .= "<h2><font color=red>User <b>$this->userName</b> not exist</font></h2>";
				$out .= "<form method=POST>";
				$out .= '<input type="hidden" name="action" value="index">';
				$out .= "<input type='submit' value='Back'>";
				$out .= "</form>";

				return $out;
			}
			
		$sql = "SELECT
				 u.id_user
				FROM ds_user as u
				WHERE
				 u.username = '$this->userName'";

		$res = $this->common->getResultArray("", $sql, "", "", "");
		if (!empty($this->common->errormsg))
		{
			return "<h2><font color=red>User <b>$this->userName</b> not exist!</font></h2>";
		}

		$file = $this->fileLocation.$this->fileName;
		$IDuser = $res[0]['id_user'];

		$sql = "SELECT
				 id_user
				FROM ds_user_has_image
				WHERE
				 id_user = '$IDuser'";
		$res = $this->common->getResultArray("", $sql, "", "", "");

		if (count($res) != 0)
		{
			$out = "<h2><font color=red>User <b>$this->userName</b> already has avatar</font></h2>";
			return $out;
		}


		$sql = "INSERT INTO
				 ds_user_has_image(id_user, image_url) VALUES ($IDuser, '$file')";

		$sqlResult = $this->common->executeQuery($sql);

		assert($sqlResult == TRUE);

		assert(empty($this->common->errormsg));

		echo "<h2><font color=blue>OK</font></h2>";
		return ;
	}

	private function _deleteAvatar()
	{
		assert(!empty($this->userName));
		assert(!is_null($this->DBID));

		$this->common->setDbID($this->DBID);

		$sql = "SELECT
				 u.id_user
				FROM ds_user as u
				WHERE
				 u.username = '$this->userName'";

		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));

		$IDuser = $res[0]['id_user'];

		$sql = "DELETE FROM ds_user_has_image WHERE id_user='$IDuser';";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));

		echo "<h2><font color=blue>OK</font></h2>";
		return ;
	}

	private function _getParams($data)
	{
		if(key_exists('username', $data))
		{
			$this->userName = $data['username'];
			assert(!empty($this->userName));
		}


		if(key_exists('filename', $data))
		{
			$this->fileName = $data['filename'];
			assert(!empty($this->fileName));
		}

		if(key_exists('filelocation', $data))
		{
			$this->fileLocation = $data['filelocation'];
			assert(!empty($this->fileLocation));
		}

		if(key_exists('action', $data))
		{
			$this->action = $data['action'];
		}
	}
}



?>