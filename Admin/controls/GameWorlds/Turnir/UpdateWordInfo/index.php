<?php
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
			case 'collect_worlds': 
				$this->_collectWorlds(); break;
			default: 
				$this->_outIndex();
		}
	}	
	
	private function _getParams($data)
	{
		if (isset($data['action'])) {
			$this->action = $data['action'];
		}
	}
	
	private function _outIndex()
	{
		
		echo '<h1>Update Worlds Info?</h1>
			<form action="" method="post">
			<input type="hidden" name="action" value="collect_worlds">
			<input type="submit" value="Update!"></form>';
	}

	
	private function _collectWorlds()
	{
		
		$a = new SU_CollectWorlds();
		$a->allowBases = array(0);
		$a->collect();
	}
}

$v = new ControlClearWorld($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>