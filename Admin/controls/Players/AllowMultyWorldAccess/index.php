<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ds/include/Smarty/Smarty.class.php";

class MultyWorldAccess
{
	/**
	 * Объект для работы с БД
	 *
	 * @var unknown_type
	 */
	private $common;
	
	/**
	 * Объект Smarty для вывода шаблона
	 *
	 * @var Smarty
	 */
	private $tpl;
	
	private $userName;
	
	private $UID;
	
	public function __construct($data)
	{
		$this->common = DS_Common::CreateForCurentDB(0);
		
		$this->_getParams($data);
		
		$this->tpl = new Smarty();
		$this->tpl->template_dir = "./templates/";
		$this->tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
		$this->tpl->compile_check = true;
		$this->tpl->debugging = false;
		
		switch($this->action)
		{
			case 'add': 
				$this->_addToMultyAccess();
				break;
			case 'delete': 
				$this->_deleteFromMultyAccess();
				break;
			case 'usermenu':
				$this->_outUserMenu();
				break;				
			case 'search':
				$this->_outFullList();
				break;				
			default :
				$this->_outFirstScreen();
		}
	}
	
	private function _getParams($data)
	{
		assert(is_array($data));
		
		if(key_exists("username", $data))
		{
			$this->userName = $data['username'];
			assert(!empty($this->userName));
			SI_SearchUserInstance::SetCommon($this->common);
			$userData = SI_SearchUserInstance::GetUserDataByName($this->userName);
			if($userData)
			{
				$this->UID = (int) $userData['id_user'];
				assert($this->UID > 0);
				$this->action = 'usermenu';
			}
		}		
		
		if(key_exists("uid", $data))
		{
			$this->UID = (int) $data['uid'];
			assert($this->UID > 0);
		}
		
		if(key_exists("action", $data))
		{
			$this->action = $data['action'];
		}
	}
	
	private function _addToMultyAccess()
	{
		assert($this->UID > 0);
		
		$sql = "
			INSERT INTO ds_multiworldaccess
				(id_user)
			VALUES 
				(".$this->UID.")
			";
		
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
		echo "<H1>User added</h1>";
		$this->_outFirstScreen();
	}
	
	private function _deleteFromMultyAccess()
	{
		assert($this->UID > 0);
		
		$sql = "
			DELETE FROM 
				ds_multiworldaccess				
			WHERE 
				id_user = ".$this->UID."
			";
		
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
		echo "<H1>User deleted</h1>";
		$this->_outFirstScreen();
	}
	
	private function _outFullList()
	{
		$this->tpl->assign("DATA_LIST", $this->_getAllMultyAccessData());
		$this->tpl->display("datalist.tpl");
	}
	
	private function _outFirstScreen()
	{
		$this->tpl->display("search.tpl");
	}
	
	private function _outUserMenu()
	{
		$this->tpl->display("search.tpl");

		$this->tpl->assign("UID", $this->UID);
		$this->tpl->assign("USER_NAME", $this->userName);
		
		$this->tpl->assign("EXISTS", $this->_getMultyAccessDataByUID($this->UID));
			
		$this->tpl->display("usermenu.tpl");
	}
	
	private function _getMultyAccessDataByUID($UID)
	{
		assert(is_int($UID) && $UID > 0);
		
		$sql = "
			SELECT
				id_user
			FROM
				ds_multiworldaccess				
			WHERE 
				id_user = ".$UID."
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		if($res)
		{
			return true;
		} else {
			return false;
		}
	}
	
	private function _getAllMultyAccessData()
	{
		$sql = "
			SELECT
				mwa.*,
				u.username
			FROM 
				ds_multiworldaccess AS mwa
				INNER JOIN ds_user AS u ON (u.id_user = mwa.id_user) 			
			";
		
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		return $res;
	}
}

$v = new MultyWorldAccess($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>