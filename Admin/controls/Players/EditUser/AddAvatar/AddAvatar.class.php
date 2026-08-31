<?php $CVS_Version = "$Revision$ ; $Date$";

class AddAvatar
{
	private $common;

	private $DBID = 0;

	private $userName = null;

	private $fileName = null;

	private $fileLocation = "/ds/useravatars/";

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
		$out = "<h2><font color=#F8F8FF>Add Avatar</h2>";
		$out .= "<form method=POST enctype='multipart/form-data'>";

		$out .= "<table border=0>";
		$out .= '<tr><td>';
		$out .= '<b><font color=#F8F8FF><font color=#F8F8FF>User: </font></b>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="text" size="20" name="username" value="'.$this->userName.'">';
		$out .= '</td></tr>';

		$out .= '<tr><td>';
		$out .= '<b><font color=#F8F8FF>File location:&nbsp;&nbsp;</font></b>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="text" size="20" name="filelocation" value="/ds/useravatars/">';
		$out .= '</td></tr>';

		$out .= '<tr><td>';
		$out .= '<b><font color=#F8F8FF>Enter exist file name:&nbsp;&nbsp;</font></b>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="text" size="20" name="filename" value="'.$this->fileName.'">';
		$out .= '</td></tr>';

		$out .= '<tr><td>';
		$out .= '<b><font color=#F8F8FF>&nbsp;&nbsp;&nbsp;OR</font></b>';
		$out .= '</td></tr>';

		$out .= '<tr><td>';
		$out .= '<b><font color=#F8F8FF>Uploud file:&nbsp;&nbsp;</font></b>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="file" size="20" name="file">';
		$out .= '</td></tr>';

		$out .= '</table>';

		$out .= '<input type="hidden" name="action" value="addAvatar">';
		$out .= '<input type="submit" class=Submit value="&nbsp;&nbsp;&nbsp;Add&nbsp;&nbsp;&nbsp;">';
		$out .= "</form>";

		$out .= "<hr>";

		$out .= "<h2><font color=#F8F8FF>View Exist Avatars</font></h2>";
		$out .= "<form method=POST>";

		$out .= "<table border=0>";
		$out .= '<tr><td>';
		$out .= '<b><font color=#F8F8FF>User: </font></b>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="text" size="20" name="username" value="All">';
		$out .= '</td></tr>';

		$out .= '</font></table>';

		$out .= '<input type="hidden" name="action" value="viewAvatars">';
		$out .= '<input type="submit" class=Submit value="View Exist Avatars">';
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
				$out .= "<input type='submit' class=Submit value='Back'>";
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
			$out .= "<input type='submit' class=Submit value='Back'>";
			$out .= "</form>";

			return $out;
		}

		$out .= "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<tr>
					<td>
						<table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>";
		$out .= "<tr><th class=th_2 >";
		$out .= " User name ";
		$out .= "</th><th class=th_2 >";
		$out .= " Image ";
		$out .= "</th>";
		$out .= "<th class=th_2 >";
		$out .= "&nbsp;&nbsp;&nbsp;";
		$out .= "</th></tr>";

		foreach($res as $key => $data)
		{
			$out .= "<form method=POST>";
			$out .= "<tr><td bgcolor='444E6C' class=maintxt>";
			$out .= $data['username'];
			$out .= "</td><td bgcolor='444E6C' class=maintxt>
							<a href='".$data['image_url']."' target='_blank' title='view'>";
			$out .= $data['image_url'];
			$out .= "</a>
						</td>";
			$out .= "<td bgcolor='444E6C' class=maintxt>";
			$out .= "<input type='hidden' name='username' value='".$data['username']."'>";
			$out .= "<input type='hidden' name='oldFileName' value='".$data['image_url']."'>";
			$out .= "<input type='hidden' name='world_id' value='".$this->DBID."_".$this->worldID."'>";
			$out .= '<input type="hidden" name="action" value="deleteAvatar">';
			$out .= "<input type='submit' class=Submit value='Delete Avatar'>";
			$out .= "</td></tr>";
			$out .= "</form>";
		}
		$out .= "</table>
					</td>
				</tr>
			</table>";

		return $out;
	}

	private function _addAvatar()
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

		if (count($res) == 0)
		{
			return "<h2><font color=red>User <b>$this->userName</b> not exist!</font></h2>";
		}

		$IDuser = $res[0]['id_user'];

		//Проверяем, нет ли у пользователя аватарки
		$sql = "SELECT
				 id_user
				FROM ds_user_has_image
				WHERE
				 id_user = '$IDuser'";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));

		if (count($res) != 0)
		{
			return "<h2><font color=red>User <b>$this->userName</b> already has avatar</font></h2>";
		}

		if ($_FILES['file']['name'] != "")
		{
			//Если у нас есть временный файл, заливаем его на ftp

			$path = $_SERVER['DOCUMENT_ROOT'].$this->fileLocation;
			$newFile = $_FILES['file']['name'];
			$oldFile = $_FILES['file']['name'];

			//Проверяем, нет ли файла с таким же именем
			$i = 1;
			while (file_exists($path.$newFile) AND $i<100)
			{
				//Если есть, то прибавляем к файлу цифру, и делаем проверку заново
				$tempName = explode(".", $oldFile);
				$tempName[0] = $tempName[0].$i;
				$newFile = "";

				foreach ($tempName as $key => $val)
				{
					if ($key != 0 )
					{
						//Добавляем точуку
						$newFile .= ".";
					}
					$newFile .= $val;
				}
				$i++;
			}

			if (move_uploaded_file($_FILES['file']['tmp_name'], $path.$newFile)) {
				//echo "File was uploaded with name $newFile";
			} else {
				exit("Cant't upload file $newFile<br>");
			}
			$this->fileName = $newFile;
		}

		$file = $this->fileLocation.$this->fileName;

		if (!file_exists($_SERVER['DOCUMENT_ROOT'].$file))
		{
			return "<h2><font color=red>File $this->fileName not exist!s</font></h2>";
		}


		//Вставляем сведения об аватарки в базу данных
		$sql = "INSERT INTO
				 ds_user_has_image(id_user, image_url) VALUES ($IDuser, '$file')";

		$sqlResult = $this->common->executeQuery($sql);

		assert($sqlResult == TRUE);

		assert(empty($this->common->errormsg));

		//Логируем действие
		actionLog( "Add avatar for user $this->userName", "Filename: '$file'");

		echo "<h2><font color=blue>Avatars was added with name $this->fileName</font></h2>";
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
		
		//Логируем действие
		actionLog( "Delete avatar for user $this->userName", "FileName: '$this->oldFileName'");

		$deleteFile = $_SERVER['DOCUMENT_ROOT'].$this->oldFileName;

		if (!unlink($deleteFile))
		{
			echo "<h2><font color=red>Cant delete file $this->oldFileName</font></h2>";
		} else
		{
			echo "<h2><font color=blue>OK</font></h2>";
		}

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
			//assert(!empty($this->fileName));
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

		if(key_exists('oldFileName', $data))
		{
			$this->oldFileName = $data['oldFileName'];
		}
	}
}



?>