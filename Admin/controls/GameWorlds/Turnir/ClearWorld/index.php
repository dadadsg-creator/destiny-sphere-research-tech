<?php
ini_set('memory_limit', '50M');
set_time_limit(0);


require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/include/Smarty/Smarty.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

class ControlClearWorld
{
	private $dbId;
	
	private $worldId;
	
	private $action;

	private $common;
	
	function __construct($data)
	{
		$this->common = DS_Common::CreateForCurentDB(0);
		$this->common->setDbId(0);
		
		$this->_getParams($data);
		
		switch($this->action)
		{
			case 'show_worlds': 
				$this->_outWorldList(); break;
			case 'clear': 
				$this->_clearWorld(); break;
			default: 
				$this->_outWorldList();
		}
	}	
	
	private function _getParams($data)
	{
		$this->action = 'show_worlds';
		
		if(!is_array($data) || count($data) == 0)
		{
			return;
		}
		
		if(key_exists('world', $data))
		{
			list($this->dbId, $this->worldId) = explode(":", $data['world']);
			settype($this->dbId, 'int');
			settype($this->worldId, 'int');
			
			assert($this->dbId >= 0);
			assert($this->worldId > 0);
			
			$this->action = 'clear';
		}
	}
	
	private function _outWorldList()
	{
		$worlds = $this->_getWorldList();
		
		if(count($worlds) == 0)
		{
			echo "<h1>No turnir worlds</h1>";
			return;
		}
		
		echo '<h1>Select world to clear:</h1>
			<form action="" method="post">
			<select name="world">';
		foreach($worlds as $worldName => $worldId)
		{
			echo '<option value="'.$worldId.'">'.$worldName;
		}
		echo '</select> <input type="submit" value="clear"></form>';
	}
	
	private function _getWorldList()
	{
		$worlds = array();
		
		foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
		{
			$this->common->setDbId($dbId);
			$sql = "
				SELECT
					w.id_world,
					w.worldname
				FROM
					ds_world AS w
					INNER JOIN ds_gamemodifier AS gm ON (gm.id_world = w.id_world)
				WHERE
					gm.modifiername = 'TURNIR_WORLD'
					AND
					gm.value = '1'
				";
			
			$res = $this->common->getResultArray("", $sql , "", "", "");
			assert(empty($this->common->errormsg));
			
			foreach($res as $k => $world)
			{
				$worlds[$world['worldname']] = $dbId.":".$world['id_world'];
			}
		}
		
		return $worlds;
	}
	
	private function _clearWorld()
	{
		DU_ClearWorld::Clear($this->dbId, $this->worldId);
				
		actionLog( "Clear Turnir World DB '".$this->dbId."' WId '$this->worldId'");
	}
}

$v = new ControlClearWorld($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>