<?php $CVS_Version = "$Revision$ ; $Date$";

class AddAllianceAvatar
{
	private $common;

	private $allianceName = null;

	private $fileName = null;

	private $fileLocation = null;

	private $action;

	private $DBID = null;

	private $worldID = null;

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
			case 'findAlliance':
				$out .= $this->_findAlliance();
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
		$sql = "
			SELECT
      			*
			FROM
    			ds_world";
		$worldsList = $this->common->getMultyArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));

		$options = '';

		foreach($worldsList as $k=>$worldData)
		{
			$prefixName = $worldData["dbname"]."_";
			$prefixID = $worldData["dbId"]."_";

			if($this->DBID == $worldData["dbId"] && $this->worldID == $worldData['id_world'])
			{
				$selected = 'SELECTED';
			}else{
				$selected = '';
			}

			$options .= '<option value="'.$prefixID.$worldData['id_world'].'" '.$selected.'>'.$worldData['worldname'].'</option>';
		}

		$out = "<h2><font color=#F8F8FF>Add Avatar</h2>";
		$out .= "<form method=POST enctype='multipart/form-data'>";

		$out .= "<table border=0>";
		$out .= '<tr><td>';
		$out .= "<b><font color=#F8F8FF>World: </font></b>";
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<select name="world_id">
				'.$options.'
				</select> ';
		$out .= '</td></tr>';

		$out .= '<tr><td>';
		$out .= '<b><font color=#F8F8FF>User: </font></b>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="text" size="24" name="userName" value="'.$this->userName.'">';
		$out .= '<input type="submit" class="submit" name="findAlliance" value="Find Alliance">';
		$out .= '</td></tr>';
		
		$out .= '<tr><td>';
		$out .= '<b><font color=#F8F8FF>Alliance: </font></b>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="text" size="24" name="allianceName" value="'.$this->allianceName.'">';
		$out .= '</td></tr>';

		$out .= '<tr><td>';
		$out .= '<b><font color=#F8F8FF>File location:&nbsp;&nbsp;</font></b>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="text" size="24" name="filelocation" value="/ds/allianceavatars/">';
		$out .= '</td></tr>';

		$out .= '<tr><td>';
		$out .= '<b><font color=#F8F8FF>Enter exist file name:&nbsp;&nbsp;</font></b>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="text" size="24" name="filename" value="'.$this->fileName.'">';
		$out .= '</td></tr>';

		$out .= '<tr><td>';
		$out .= '<b><font color=#F8F8FF>&nbsp;&nbsp;&nbsp;OR</font></b>';
		$out .= '</td></tr>';

		$out .= '<tr><td>';
		$out .= '<b><font color=#F8F8FF>Uploud file:&nbsp;&nbsp;</font></b>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="file" size="24" name="file">';
		$out .= '</td></tr>';

		$out .= '</table>';

		$out .= '<input type="hidden" name="action" value="addAvatar">';
		$out .= '<input type="submit" class="Submit" value="&nbsp;&nbsp;&nbsp;Add&nbsp;&nbsp;&nbsp;">';
		$out .= "</form>";

		$out .= "<hr>";

		$out .= "<h2><font color=#F8F8FF>View Exist Avatars</font></h2>";
		$out .= "<form method=POST>";

		$out .= "<table border=0>";
		$out .= '<tr><td>';
		$out .= "<b><font color=#F8F8FF>World: </font></b>";
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<select name="world_id">
				'.$options.'
				</select> ';
		$out .= '</td></tr>';

		$out .= '<tr><td>';
		$out .= '<b><font color=#F8F8FF>Alliance: </font></b>';
		$out .= '</td>';
		$out .= '<td>';
		$out .= '<input type="text" size="24" name="allianceName" value="All">';
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
		assert($this->worldID > 0);

		$this->common->setDbID($this->DBID);

		if ($this->allianceName != 'All')
		{
			//Проверяем, есть ли альянс с введенным ником
			$sql = "SELECT
				id_alliance
				FROM
				ds_alliance
				WHERE 	alliancename = '$this->allianceName'
						AND
						id_world = $this->worldID";

			$res = $this->common->getResultArray("", $sql, "", "", "");
			assert(empty($this->common->errormsg));

			$out = "<h1>Alliance Avatars</h1>";
			if (count($res)==0)
			{
				$out .= "<h2><font color=red>Alliance <b>$this->allianceName</b> not exist in world $this->worldID </font></h2>";
				$out .= "<form method=POST>";
				$out .= '<input type="hidden" name="action" value="index">';
				$out .= "<input type='submit' class=Submit value='Back'>";
				$out .= "</form>";

				return $out;
			}
		}

		//Выбираем из базы имена альянсов и их аватарки
		if ($this->allianceName == 'All')
		{
			$where = "WHERE a.id_world = $this->worldID";
		}else
		{
			$where = "WHERE a.alliancename='$this->allianceName'
			AND a.id_world = $this->worldID";
		}
		$sql = "SELECT
				a.alliancename, ahi.image_url
				FROM
				ds_alliance_has_image as ahi
				LEFT JOIN ds_alliance as a ON (a.id_alliance = ahi.id_alliance)
				$where";

		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));

		$out = "<h1>Alliance Avatars</h1>";
		if (count($res)==0)
		{
			if ( $this->allianceName = 'All')
			{
				$out .= "<h2><font color=red>In thise world no avatars</font></h2>";
				$out .= "<form method=POST>";
				$out .= '<input type="hidden" name="action" value="index">';
				$out .= "<input type='submit' class=Submit value='Back'>";
				$out .= "</form>";
			}else{
				$out .= "<h2><font color=red>Alliance <b>$this->allianceName</b> has not avatar</font></h2>";
				$out .= "<form method=POST>";
				$out .= '<input type="hidden" name="action" value="index">';
				$out .= "<input type='submit' class=Submit value='Back'>";
				$out .= "</form>";
			}
			return $out;
		}

		$out .= "<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
				<tr>
					<td>
						<table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>";
		$out .= "<tr><th class=th_2 >";
		$out .= " Alliance name ";
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
			$out .= $data['alliancename'];
			$out .= "</td><td bgcolor='444E6C' class=maintxt>
							<a href='".$data['image_url']."' target='_blank' title='view'>";
			$out .= $data['image_url'];
			$out .= "</a>
						</td>";
			$out .= "<td bgcolor='444E6C' class=maintxt>";
			$out .= "<input type='hidden' name='allianceName' value='".$data['alliancename']."'>";
			$out .= "<input type='hidden' name='world_id' value='".$this->DBID."_".$this->worldID."'>";
			$out .= "<input type='hidden' name='oldFileName' value='".$data['image_url']."'>";
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
		assert(!empty($this->allianceName));
		assert($this->worldID > 0);
		assert(!is_null($this->DBID));

		$this->common->setDbID($this->DBID);


		//Проверяем, есть ли альянс с введенным ником
		$sql = "SELECT
				id_alliance
				FROM
				ds_alliance
				WHERE 	alliancename = '$this->allianceName'
						AND
						id_world = $this->worldID";

		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));

		$out = "<h1>Alliance Avatars</h1>";
		if (count($res)==0)
		{
			$out .= "<h2><font color=red>Alliance <b>$this->allianceName</b> not exist in world $this->worldID</font></h2>";
			$out .= "<form method=POST>";
			$out .= '<input type="hidden" name="action" value="index">';
			$out .= "<input type='submit' class=Submit value='Back'>";
			$out .= "</form>";

			return $out;
		}

		//$file = $this->fileLocation.$this->fileName;
		$IDalliance = $res[0]['id_alliance'];

		//Проверяем, нет ли у альянса аватарки
		$sql = "SELECT
				 id_alliance
				FROM ds_alliance_has_image
				WHERE
				 id_alliance = '$IDalliance'";

		$res = $this->common->getResultArray("", $sql, "", "", "");

		if (count($res) != 0)
		{
			$out = "<h2><font color=red>Alliance <b>$this->allianceName</b> already has avatar</font></h2>";
			return $out;
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
				 ds_alliance_has_image
				 	(id_alliance, image_url)
				 VALUES 
				 	($IDalliance, '$file')";

		$sqlResult = $this->common->executeQuery($sql);

		assert($sqlResult == TRUE);

		assert(empty($this->common->errormsg));

		//Получаем имя мира по его id
		$sql = "SELECT
						worldname
						FROM ds_world 
						WHERE id_world = $this->worldID";

		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		$worldName = $res[0]['worldname'];

		//Логируем действие
		actionLog( "Add avatar for alliance '$this->allianceName' in world '$worldName'", "Filename: '$file'");

		echo "<h2><font color=blue>OK</font></h2>";
		return ;
	}

	private function _deleteAvatar()
	{
		assert(!empty($this->allianceName));
		assert(!is_null($this->DBID));
		assert($this->worldID > 0);

		$this->common->setDbID($this->DBID);

		$sql = "SELECT
				 a.id_alliance
				FROM ds_alliance as a
				WHERE
				 a.allianceName =  '$this->allianceName'
				 AND 
				 a.id_world = $this->worldID";

		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));

		$IDalliance = $res[0]['id_alliance'];

		$sql = "DELETE
				FROM 
					ds_alliance_has_image
				WHERE 
					id_alliance='$IDalliance'";

		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));

		//Получаем имя мира по его id
		$sql = "SELECT
						worldname
						FROM ds_world 
						WHERE id_world = $this->worldID";

		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		$worldName = $res[0]['worldname'];

		//Логируем действие
		actionLog( "Delete avatar for alliance '$this->allianceName' in world '$worldName'", "Filename: '$this->oldFileName'");

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

	private function _findAlliance()
	{
		assert(!empty($this->userName));
		assert($this->DBID >= 0);
		assert($this->worldID > 0);

		$this->common->setDbID($this->DBID);

		//Берем id_user по имени
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
		
		
		//Берем userinstance по id_user и id_world
		$sql = "SELECT ui.id_userinstance
				FROM ds_userinstance as ui
				WHERE ui.id_user = $IDuser
				AND ui.id_world = $this->worldID";
		
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		if (count($res) == 0)
		{
			return "<h2><font color=red>User <b>$this->userName</b> in world $this->worldID not exist!</font></h2>";
		}

		$IdUserInstance = $res[0]['id_userinstance'];

		//Берем id альянсa по id_userinstance
		$sql = "SELECT ua.id_alliance
		FROM ds_userhasalliance as ua
		WHERE ua.id_userinstance = $IdUserInstance";

		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));

		if (count($res) == 0)
		{
			return "<h2><font color=red>User <b>$this->userName</b> has not alliance!</font></h2>";
		}

		$IdAlliance = $res[0]['id_alliance'];
		
		//Берем имя альянса по его id
		$sql = "SELECT a.alliancename
				FROM ds_alliance as a
				WHERE a.id_alliance =$IdAlliance";

		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));

		$allianceName = $res[0]['alliancename'];
		
		$this->allianceName = $allianceName;
		return ;
	}

	private function _getParams($data)
	{
		if(key_exists('userName', $data))
		{
			$this->userName = $data['userName'];
			//assert(!empty($this->userName));
		}
		
		if(key_exists('world_id', $data))
		{
			$tmp = explode('_', $data['world_id']);
			assert(count($tmp) == 2);

			$this->DBID = (int)$tmp[0];
			assert($this->DBID >= 0);

			$this->worldID = (int)$tmp[1];
			assert($this->worldID > 0);
		}

		if(key_exists('allianceName', $data))
		{
			$this->allianceName = $data['allianceName'];
			//assert(!empty($this->allianceName));
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
		
		if(key_exists('findAlliance', $data))
		{
			$this->action = 'findAlliance';
		}

		if(key_exists('oldFileName', $data))
		{
			$this->oldFileName = $data['oldFileName'];
		}
	}
}



?>