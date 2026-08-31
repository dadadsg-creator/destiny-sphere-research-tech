<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

class DisableScience
{
	private $common;
	
	private $dbId;
	
	private $worldId;
	
	private $scienceId;
	
	private $action = 'INDEX';
	
	
	function __construct($data)
	{
		assert(is_array($data));
		
		$this->_getParams($data);
		
		$this->common = DS_Common::CreateForCurentDB();
		
		switch($this->action)
		{
			case 'SCIENCE_LIST':
				$this->_scienceList();
				break;
				
			case 'DISABLE_SCIENCE':
				$this->_disableScience();
				$this->_scienceList();
				break;
				
			case 'ENABLE_SCIENCE':
				$this->_enableScience();
				$this->_scienceList();;
				break;	
				
			default:
				$this->_out();
				break;
		}
	}
	
	public function _getParams($data)
	{
		assert(is_array($data));
		
		if(key_exists('world', $data))
		{
			$arr = explode("_", $data['world']);
			assert(count($arr) == 2);
			
			$this->dbId = (int)$arr[0];
			assert($this->dbId >= 0);
			
			$this->worldId = (int)$arr[1];
			assert($this->worldId > 0);
			
			if(key_exists('action', $data))
			{
				$this->action = $data['action'];
			}
			
			if(key_exists('add', $data) && key_exists('science', $data))
			{
				$this->action = "DISABLE_SCIENCE";	
				$this->scienceId = (int)$data['science'];
			}
			
			if(key_exists('del', $data) && key_exists('science', $data))
			{
				$this->action = "ENABLE_SCIENCE";
				$this->scienceId = (int)$data['science'];
			}
		}
	}
	
	
	private function _getWorldList()
	{
		foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
		{
			$this->common->setDbId($dbId);
			
			$sql = "
				SELECT
					worldname,
					id_world
				FROM
					ds_world
				";
			$res = $this->common->getResultArray("", $sql, "", "", "");
			assert(empty($this->common->errormsg));
			
			foreach($res as $k => $world)
			{
				$worldList[$dbId.'_'.$world['id_world']] = $world['worldname'];
			}
		}
		
		return $worldList;
	}
	
	private function _out()
	{
		$worldLsit = $this->_getWorldList();
				
		echo '
			<form action="" method="POST">
				<b>World:</b>&nbsp;<select name="world">
		';
		
		foreach($worldLsit as $id => $worldName)
		{
			echo '<option value="'.$id.'">'.$worldName;
		}
		
		echo '
				</select>&nbsp;&nbsp;
				<input type="hidden" name="action" value="SCIENCE_LIST">
				<input type="submit" value="SELECT">
			</form>';
	}
	
	private function _getWorldName()
	{
		$this->common->setDbId($this->dbId);
		$sql = "
			SELECT
				worldname
			FROM
				ds_world
			WHERE
				id_world = ".$this->worldId."
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		return $res[0]['worldname'];
	}
	
	private function _getScienceList()
	{
		$this->common->setDbId($this->dbId);
		$sql = "
			SELECT  
				s.id_science,
				ltl.text
			FROM
				ds_science AS s
				INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = s.id_localizedname AND ltl.id_language = ".constant('DEFAULT_LANGUAGE').")
			WHERE
				NOT EXISTS
				(
					SELECT
						ds.id_science
					FROM
						ds_disabledscience AS ds
					WHERE
						ds.id_world = ".$this->worldId."
						AND
						ds.id_science = s.id_science
				)
			ORDER BY s.id_branch, s.id_science
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
			
		return $res;
	}
	
	private function _getDisabledScienceList()
	{
		$this->common->setDbId($this->dbId);
		$sql = "
			SELECT  
				s.id_science,
				ltl.text
			FROM
				ds_science AS s
				INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = s.id_localizedname AND ltl.id_language = ".constant('DEFAULT_LANGUAGE').")
			WHERE
				EXISTS
				(
					SELECT
						ds.id_science
					FROM
						ds_disabledscience AS ds
					WHERE
						ds.id_world = ".$this->worldId."
						AND
						ds.id_science = s.id_science
				)
			ORDER BY s.id_branch, s.id_science
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
			
		return $res;
	}
	
	private function _scienceList()
	{
		$list = $this->_getScienceList();
		
		$world = $this->_getWorldName();
		
		echo '
			<a href="./"><< назад</A><br><br>
			<h1>'.$world.'</h1>	
			<b>Список разрешенных наук</b><br>		
			<form action="" method="POST">
				<input type="hidden" name="world" value="'.$this->dbId.'_'.$this->worldId.'">
				<input type="hidden" name="add" value="1">
				<select name="science">
		';
		
		foreach($list as $k => $science)
		{
			echo '<option value="'.$science['id_science'].'">'.$science['text']."\n";
		}
		
		echo '
				</select>
				<input type="submit" value="запретить">
			</form>
			';
		
		$list = $this->_getDisabledScienceList();
		
		$world = $this->_getWorldName();
		
		echo '
			<br><br>		
			<b>Список запрещенных наук</b><br>
			<form action="" method="POST">
				<input type="hidden" name="world" value="'.$this->dbId.'_'.$this->worldId.'">
				<input type="hidden" name="del" value="1">
				<select name="science">
		';
		
		foreach($list as $k => $science)
		{
			echo '<option value="'.$science['id_science'].'">'.$science['text']."\n";
		}
		
		echo '
				</select>
				<input type="submit" value="разрешить">
			</form>
			';
	}
	
	private function _disableScience()
	{
		$this->common->setDbId($this->dbId);
		
		$sql = "
			INSERT INTO ds_disabledscience
				(id_world, id_science)
			VALUES
				(".$this->worldId.", ".$this->scienceId.")
			";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
	}
	
	private function _enableScience()
	{
		$this->common->setDbId($this->dbId);
		
		$sql = "
			DELETE FROM ds_disabledscience
			WHERE
				id_world = ".$this->worldId."
				AND
				id_science = ".$this->scienceId."
			";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
	}
}

$d = new DisableScience($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>