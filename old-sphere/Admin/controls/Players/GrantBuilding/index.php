<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=GrantBuilding><font size=3><b>HELP</b></font></a></p>';

class GrantBuilding
{
	
	private $common;
	
	private $username;
	
	private $DBID;
	
	private $worldID;
	
	private $UIID;
	
	private $action;
	
	private $buildingID;
	
	function __construct($data)
	{
		$this->common = DS_Common::CreateForCurentDB(0);
		
		$this->_getParams($data);	
		
		switch($this->action)
		{
			case 'GetUIID': $this->_outUserInstances(); break;
			case 'add': $this->_addGrant(); break;
			case 'delete': $this->_deleteGrant();
			case 'list': $this->_outUsersAllowed(); break;
			default : $this->_outFirstMenu();				
		}
	}
	
	
	private function _getParams($data)
	{
		assert(is_array($data));
		
		if(key_exists('username', $data))
		{
			$this->username = $data['username'];	
		}
		
		if(key_exists('username', $data) && key_exists('world', $data))
		{
			$this->action = 'GetUIID';
		
			list($this->DBID, $this->worldID) = explode(":", $data['world']);
			settype($this->DBID, 'int');
			settype($this->worldID, 'int');
					
			assert($this->DBID >= 0);
			assert($this->worldID > 0);
		}
		
		if(key_exists('UIID', $data))
		{
			list($this->DBID, $this->worldID, $this->UIID) = explode(":", $data['UIID']);
			settype($this->DBID, 'int');
			settype($this->worldID, 'int');
			settype($this->UIID, 'int');
			
			assert($this->DBID >= 0);
			assert($this->worldID > 0);
			assert($this->UIID > 0);
		}
		
		if(key_exists('building', $data))
		{
			$this->buildingID = (int) $data['building'];
			assert($this->buildingID > 0);
		}
		
		if(key_exists('action', $data))
		{			
			$this->action = $data['action'];
			assert(!empty($this->action));
		}
	}
	
	private function _outFirstMenu()
	{
		
		$worlds = $this->_getWorldList();
		
		// search user
		echo "
			<h1>Search user</h1>
			<A href='?action=list'>Show Allowed</a><br><br>
			<form action='' method='post'>
				<SPAN class=mess_info>User name:</SPAN> <input type='text' name='username' size=10>&nbsp;&nbsp;&nbsp;&nbsp; 
				<SPAN class=mess_info>World: </SPAN> <select name='world'>
			";
		foreach($worlds as $worldName => $world)
		{
			echo "<option value='$world'>".$worldName;
		}
		
		echo "
				</select>
				&nbsp;&nbsp;&nbsp;<input type='submit' value='ADD'>
			</form>
			";
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
			if($res)
			{
				foreach($res as $k => $world)
				{
					$worldList[$world['worldname']] = $dbId.":".$world['id_world'];
				}
			}
		}
		
		return $worldList;
	}
	
	private function _outUserInstances()
	{
		// select user instance
		$sql = "
				SELECT
					id_user
				FROM
					ds_user
				WHERE
					upper(username) = upper('".$this->username."')
				";
		$res = $this->common->getResultArray("", $sql ,"", "", "");
		assert(empty($this->common->errormsg));
		
		if(!$res)
		{
			echo '<h1>USER NOT FOUND</h1>';
			$this->_outFirstMenu();
			return false;
		}
		
		$UID = $res[0]['id_user'];
		
		$this->common->setDbId($this->DBID);
		$sql = "
			SELECT
				ui.id_userinstance,
				w.worldname,
				w.id_world
			FROM
				ds_userinstance AS ui
				INNER JOIN ds_world AS w ON (w.id_world = ui.id_world)
			WHERE
				ui.id_user = ".$UID."
				AND
				ui.id_world = ".$this->worldID."
			";
		$res = $this->common->getResultArray("", $sql ,"", "", "");
		assert(empty($this->common->errormsg));
		
		if(!$res)
		{
			echo '<h1>USER HAS NO INSTANCE IN THIS WORLD</h1>';
			$this->_outFirstMenu();
			return false;
		}
		
		$this->UIID = (int) $res[0]['id_userinstance'];
		
$this->buildingID = 65;
		
		$this->_addGrant();
		
	}
	
	private function _getUsersAllowed()
	{
		foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
		{
			$this->common->setDbId($dbId);
		
			$sql = "
				SELECT
					ui.id_userinstance,
					ui.id_world,
					w.worldname,
					u.username
				FROM
					ds_buildingsalowedtouserinstance AS baui
					INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = baui.id_userinstance)
					INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
					INNER JOIN ds_world AS w ON (w.id_world = ui.id_world)
				ORDER BY
					u.username ASC
				";
			
			$res[$dbId] = $this->common->getResultArray("", $sql, "", "", "");
			assert(empty($this->common->errormsg));
		}
		
		return $res;
	}
	
	private function _outUsersAllowed()
	{
		$usersAllowed = $this->_getUsersAllowed();
		
		echo "
			<a href='index.php'>Back To Main</a><br><br>
			<table cellspacing=0 cellpadding=0 border=1>
				<tr class=th_2><td>&nbsp;&nbsp;user name&nbsp;&nbsp;</td><td>&nbsp;&nbsp;world&nbsp;&nbsp;</td><td>&nbsp;</td></tr>";
		
		foreach($usersAllowed as $dbId => $users)
		{
			foreach($users as $k => $user)
			{
				echo "<tr class=maintxt>
							<td>&nbsp;&nbsp;&nbsp;".$user['username']."&nbsp;&nbsp;&nbsp;</td>
							<td>&nbsp;&nbsp;".$user['worldname']."&nbsp;&nbsp;</td>
							<td>&nbsp;&nbsp;<a href='?action=delete&username=".$user['username']."&UIID=".$dbId.":".$user['id_world'].":".$user['id_userinstance']."'>delete</a>&nbsp;&nbsp;</td></tr>";
			}	
		}
		
		echo "</table>";
	}
	
	private function _addGrant()
	{
		if($this->_haveGrant())
		{
			echo "<h1>User already have grant for this building</h1>";
		} else {		
			$this->common->setDbId($this->DBID);
		
			$sql = "
				INSERT INTO 
					ds_buildingsalowedtouserinstance
					(id_userinstance, id_building, adddate)
				VALUES
					(".$this->UIID.", ".$this->buildingID.", now())
			";
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));
			echo '<h1>GRANT ADDED</h1>';
			
			//Получаем имя здания по его id
			$sql = "SELECT b.buildingname
						FROM
					ds_building as b
						WHERE
						b.id_building = $this->buildingID";
			
			$res = $this->common->getResultArray("", $sql, "", "", "");
			assert(empty($this->common->errormsg));

			$buildingName = $res[0]['buildingname'];
			
			//Логируем действие
			actionLog( "Add grant building '$buildingName' for user $this->username");
		}
		$this->_outFirstMenu();
	}
	
	private function _deleteGrant()
	{
		if(!$this->_haveGrant())
		{
			echo "<h1>User don't have grant for this building!</h1>";
		} else {
			$this->common->setDbId($this->DBID);
			
			//Получаем имя здания по его id instance
			$sql = "SELECT b.buildingname
						FROM
					ds_building as b
						WHERE
						b.id_building = (SELECT ba.id_building
					                        FROM
					                        ds_buildingsalowedtouserinstance as ba
					                        WHERE
					                        ba.id_userinstance = $this->UIID
					                        )";

			$res = $this->common->getResultArray("", $sql, "", "", "");
			assert(empty($this->common->errormsg));

			$buildingName = $res[0]['buildingname'];
			
			$sql = "
				DELETE FROM	
					ds_buildingsalowedtouserinstance 
				WHERE
					id_userinstance = ".$this->UIID."
			";
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));
			
			//Логируем действие
			actionLog( "Delete grant building '$buildingName' for user $this->username");
		}
	}
	
	private function _haveGrant()
	{
		$this->common->setDbId($this->DBID);
		
		$sql = "
			SELECT
				id_building
			FROM	
				ds_buildingsalowedtouserinstance 
			WHERE
				id_userinstance = ".$this->UIID."
		";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		if(count($res) > 0)
		{
			return true;
		} else {
			return false;
		}
	}
}

$v = new GrantBuilding($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>