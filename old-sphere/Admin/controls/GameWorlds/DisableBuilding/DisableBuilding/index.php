<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

class DisableBuilding
{
	
	private $common;
	
	private $dbId;
	
	private $worldId;
	
	private $buildingId;
	
	private $action = 'INDEX';
	
	function __construct($data)
	{
		$this->_getParams($data);
		
		$this->common = DS_Common::CreateForCurentDB();
		
		switch($this->action)
		{
			case 'BUILDING_LIST':
				$this->_buildingList();
				break;
				
			case 'DISABLE_BUILDING':
				$this->_disableBuilding();
				$this->_buildingList();
				break;
				
			case 'ENABLE_BUILDING':
				$this->_enableBuilding();
				$this->_buildingList();;
				break;	
				
			default:
				$this->_out();
				break;
		}
	}
	
	private function _getParams($data)
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
			
			if(key_exists('add', $data) && key_exists('building', $data))
			{
				$this->action = "DISABLE_BUILDING";	
				$this->buildingId = (int)$data['building'];
			}
			
			if(key_exists('del', $data) && key_exists('building', $data))
			{
				$this->action = "ENABLE_BUILDING";	
				$this->buildingId = (int)$data['building'];
			}
		}
	}
	
	
	private function _getBuildingList()
	{		
		$this->common->setDbId($this->dbId);
		$sql = "
			SELECT  
				b.id_building
			FROM
				ds_building AS b
			WHERE
				NOT EXISTS
				(
					SELECT
						*
					FROM
						ds_disabledbuilding AS db
					WHERE
						db.id_world = ".$this->worldId."
						AND
						db.id_building = b.id_building
				)
				AND
				b.id_building > 0
			ORDER BY id_building
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		foreach($res as $k => $building)
		{
			$res[$k]['name'] = $this->_getBuildingName($building['id_building']);
		}
		
		return $res;
	}

	private function _getBuildingName($building)
	{
		$this->common->setDbId($this->dbId);
		$sql = "
			SELECT
				ltl.text
			FROM
				ds_buildingcanbuildbyrace AS bcbr
				INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = bcbr.id_localizedname)
			WHERE
				ltl.id_language = 3
				AND
				bcbr.id_building = ".$building."
			";
		$res = $this->common->getResultArray("admin_building_name_".$building, $sql, "", "", "NO_CACHE");
		assert(empty($this->common->errormsg));
		
		$buildingName = $res[0]['text'];
		foreach($res as $k => $name)
		{
			if($buildingName != $name['text'])
			{
				$buildingName .= "; ".$name['text'];
			}
		}
		
		return $buildingName;
	}
	
	private function _getDisabledList()
	{
		$this->common->setDbId($this->dbId);
		$sql = "
			SELECT
				*
			FROM
				ds_disabledbuilding
			WHERE
				id_world = ".$this->worldId."
			ORDER BY id_building
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		foreach($res as $k => $building)
		{
			$res[$k]['name'] = $this->_getBuildingName($building['id_building']);
		}
		
		return $res;
	}
	
	private function _getWorldList()
	{
		/*$this->common->setDbId(0);
		
		$sql = "
			SELECT
				worldname,
				id_world,
				dbname
			FROM
				ds_worldcollection
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		$worldList = array();*/
		
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
	
	private function _disableBuilding()
	{
		$this->common->setDbId($this->dbId);
		
		$sql = "
			INSERT INTO ds_disabledbuilding
				(id_world, id_building)
			VALUES
				(".$this->worldId.", ".$this->buildingId.")
			";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
	}
	
	private function _enableBuilding()
	{
		$this->common->setDbId($this->dbId);
		
		$sql = "
			DELETE FROM ds_disabledbuilding
			WHERE
				id_world = ".$this->worldId."
				AND
				id_building = ".$this->buildingId."
			";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
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
				<input type="hidden" name="action" value="BUILDING_LIST">
				<input type="submit" value="SELECT">
			</form>';
	}
	
	private function _buildingList()
	{
		$list = $this->_getBuildingList();
		
		$world = $this->_getWorldName();
		
		echo '
			<a href="./"><< назад</A><br><br>
			<h1>'.$world.'</h1>	
			<b>Список разрешенных зданий</b><br>		
			<form action="" method="POST">
				<input type="hidden" name="world" value="'.$this->dbId.'_'.$this->worldId.'">
				<input type="hidden" name="add" value="1">
				<select name="building">
		';
		
		foreach($list as $k => $building)
		{
			echo '<option value="'.$building['id_building'].'">'.$building['name']."\n";
		}
		
		echo '
				</select>
				<input type="submit" value="запретить">
			</form>
			';
		
				$list = $this->_getDisabledList();
		
		$world = $this->_getWorldName();
		
		echo '
			<br><br>		
			<b>Список запрещенных здани</b>й<br>
			<form action="" method="POST">
				<input type="hidden" name="world" value="'.$this->dbId.'_'.$this->worldId.'">
				<input type="hidden" name="del" value="1">
				<select name="building">
		';
		
		foreach($list as $k => $building)
		{
			echo '<option value="'.$building['id_building'].'">'.$building['name']."\n";
		}
		
		echo '
				</select>
				<input type="submit" value="разрешить">
			</form>
			';
	}
	
}

$d = new DisableBuilding($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>