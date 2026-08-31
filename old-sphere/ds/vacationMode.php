<?php

ini_set('memory_limit', '16M');
set_time_limit(0);

require_once 'Main.php';
require_once $_SERVER['DOCUMENT_ROOT']."/ds/VM_SrchUser.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ds/include/Smarty/Smarty.class.php";

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

//Регистрация своего обработчикка ошибок
//set_error_handler("DS_ErrorHandler");

//Регитрация финализатора
register_shutdown_function ('finilazer');

define('DEBUG_MODE', 1);

$_SESSION["dbId"] = 0;
$_SESSION["lang"] = 3;
$_SESSION["world"] = 1;

DebugTools::TimerStart('TEST');
//echo "<xmp>";
/*********** BEGIN WORK AREA **************/

class vacationModeInterface
{
	private $userName = false;
	
	private $UIID = false;
	
	private $UID = false;
	
	private $isAdmin = false;
	
	private $action = 'show_index';
	
	private $common = false;
	
	private $tpl = false;
	
	private $is_deleted = 0;
	
	private $is_reserved = 0;
	
	private $storeReason = 1;
	
	private $comments = '';
	
	private $UIIDToStore = 0;
	
	private $moveFromWorld = 0;
	
	private $moveToWorld = false;
	
	private $moveToCombCoords = false;
	
	private $dbIdFrom = 0;
	
	private $dbIdTo = false;
	
	private $id_userdata = 0;
	
	private $srch = false;
	
	//private $out;
	
	function __construct()
	{
		$this->common = DS_Common::CreateForCurentDB(0);
		
		$this->common->setDbId(0);
		SI_SearchInterface::SetCommon($this->common);
		
		//$this->out = new SI_OutInterface();
		
		$this->tpl = new Smarty();
		$this->tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/";
		$this->tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';	
		$this->tpl->compile_check = true;
		$this->tpl->debugging = false;
	}
	
	function main($request)
	{
		$this->_getParams($request);
		
		switch($this->action)
		{
			case 'show_index':
				$this->_outUserSearch();
				break;
			case 'show_user_menu':
				$this->_outUserMenu();
				break;
			case 'store_user':		
				$this->storeUser();
				break;
			case 'transfer_user':
				$this->_outUserTransfer();				
				break;
			case 'restore_user':
				$this->restoreUser();
				break;
			case 'search':
				$this->_outUserStoredList();
				break;				
			default:
				$this->_outUserSearch();
		}
	}
	
	private function _getParams($data)
	{
		if(isset($data['user_name']))
		{		
			$this->userName = trim($data['user_name']);
			if($this->userName)
			{
				$this->UIIDs = $this->_getUIIDsByUserName($this->userName);
				
				$this->action = 'show_user_menu';
			}
		}
		
		if(isset($data['delete']))
		{
			$this->is_deleted = 1;
		}		
		
		if(isset($data['reserve']))
		{
			$this->is_reserved = 1;
		}
		
		if(isset($data['reason']))
		{
			$this->storeReason = (int) $data['reason'];
			switch($this->storeReason)
			{
				case 1: 
					$this->action = 'store_user';
					break;
				case 2;
					$this->action = 'transfer_user';
					break;
			}
		}
		
		if(isset($data['UIID']))
		{
			$this->UIIDToStore = (int)$data['UIID'];
			assert(is_int($this->UIIDToStore) && $this->UIIDToStore > 0);
		}
		
		if(isset($data['comments']))
		{
			$this->comments = trim($data['comments']);
		}
		
		if(isset($data['id_storeuserdata']))
		{
			$this->action = 'restore_user';
			$this->id_userdata = $data['id_storeuserdata']; 
			
			$this->srch = new srchStoreUser($this->common);
			
			$storeUser = $this->srch->getStoreUserById($this->id_userdata);
			
			$this->UID = $storeUser['id_user'];
			
			$this->isAdmin = $storeUser['admin'];
			
			if(isset($data['worldFrom']))
			{
				// restore from vacation mode
				$this->moveToWorld = $this->moveFromWorld = $data['worldFrom'];
				
				$worlds = SI_SearchInterface::GetWorldList();

				foreach($worlds as $k => $world)
				{
					if($world['id'] == $this->moveToWorld)
					{
						$this->dbIdFrom = $this->dbIdTo = $world['dbId'];
						break;
					}
				}
			}
		}
		
		if(isset($data['worldTo']))
		{
			list($this->dbIdTo, $this->moveToWorld) = explode(":", $data['worldTo']);
		}
		
		if(isset($data['combCoord']))
		{
			$this->moveToCombCoords = $data['combCoord'];
		}
		
		if(isset($data['mode']))
		{
			$this->action = $data['mode'];
		}
		
		if(isset($data['dosrch']))
		{			
			$this->srch = new srchStoreUser($this->common);
			
			if(isset($data['srch_name']))
			{	
				$this->userName = $data['srch_name'];
				$this->srch->setSrchName($this->userName);
			}
				
			if(isset($data['reason']))
			{	
				$this->srch->setSrchReason($data['reason']);
			}
			
			if(isset($data['restored']))
			{	
				$this->srch->setSrchRestore($data['restored']);
			}
		}
	}
	
	private function _outUserSearch()
	{
		assert($this->tpl);	
		$this->tpl->clear_all_assign();
		$this->tpl->display("VM_UserSearch.tpl");
	} 
	
	private function _outUserMenu()
	{
		assert($this->tpl);
		$this->tpl->clear_all_assign();
		
		$this->tpl->assign("USER_NAME", $this->userName);
		$this->tpl->assign("UIID", $this->UIIDs);
		$this->tpl->assign("COMB_NAMES", $this->_getCombNameList($this->UIIDs));
		
		$this->tpl->display("VM_UserMenu.tpl");
	}
	
	private function _outUserTransfer()
	{
		assert($this->tpl);
		
		$this->tpl->clear_all_assign();
		
		$this->tpl->assign("USER_NAME", $this->userName);
		$this->tpl->assign("USER_WORLD", SI_SearchInterface::GetWorldNameByUIID($this->UIIDToStore));
		$this->tpl->assign("WORLD", SI_SearchInterface::GetWorldList());
		$this->id_userdata = $this->storeUser();
		assert($this->id_userdata);
		$this->tpl->assign("COMB", SI_SearchInterface::GetStoreUserCombs($this->id_userdata));
		$this->tpl->assign("ID_STORE_USER_DATA", $this->id_userdata);
		
		$this->tpl->display("VM_UserTransfer.tpl");
	}
	
	private function _outUserStoredList()
	{
		assert($this->tpl);
		$this->tpl->clear_all_assign();
		
		$this->tpl->assign("REASON", SI_SearchInterface::GetReasonList());
		if($this->srch)
		{
			$this->tpl->assign("SRCH_RESULTS", $this->srch->search());
		} else {
			$this->tpl->assign("SRCH_RESULTS", 0);
		}
		$this->tpl->display("VM_UserList.tpl");
	}
	
	private function _getUIIDsByUserName()
	{
		$user = SI_SearchInterface::GetUserInfoByUserName($this->userName);	
				
		$this->UID = $user[0]['id_user'];
		$this->isAdmin = $user[0]['admin'];
		
		foreach($user as $k => $UIID)
		{
			$res[$k] = $UIID['id_userinstance'];			
		}
		
		return $res;
	}
	
	private function _getCombNameList($UIIDs)
	{
		assert(is_array($UIIDs));
		$nameList = array();
		
		foreach($UIIDs as $k => $UIID)
		{
			$nameList[$k] = implode("\", \"", SI_SearchInterface::GetCombsNameByUIID($UIID, $this->dbIdFrom));
		}
		return $nameList;
	}
	
	function deleteUserInstance($UIID)
	{
		$this->common->setDbId($this->dbIdFrom);
		$deleteControl = new DU_ControllerDeleteUserInstance($this->common);
		$deleteControl->drop((int)$UIID);

		$collectWorlds = new SU_CollectWorlds();
		$collectWorlds->deleteFromUserInstanceCollection($UIID);
	}
	
	
	
	function storeUser()
	{
		$this->common->setDbId($this->dbIdFrom);
		SI_ExecStoreUser::SetCommon($this->common);
		$storeUserInstance = SI_ExecStoreUser::Exec($this->UIIDToStore, $this->moveFromWorld, $this->storeReason, 'admin', $this->comments);	
		
		$id_storeuserdata = SI_ExecStoreUser::GetUserDataId();
		
		if($this->is_deleted)
		{
			$this->deleteUserInstance($this->UIIDToStore);
		}
		
		$combs = $storeUserInstance->getComb();
		
		$reserve = false;
		if($this->is_reserved && $this->action == 'store_user' && $this->is_deleted)
		{	
			$reserve = true;
		}
		
		$this->common->setDbId($this->dbIdFrom);
		SI_ExecStoreUserCombs::SetCommon($this->common);
		SI_ExecStoreUserCombs::Exec($combs, $id_storeuserdata, $this->userName, $this->dbIdFrom, $reserve);
	
		return $id_storeuserdata;
	}

	
	function restoreUser()
	{
		assert($this->dbIdTo !== false);		
		assert($this->id_userdata);
		
		SI_ExecRestoreUser::SetCommon($this->common);
		SI_ExecRestoreUser::Exec($this->id_userdata, $this->dbIdTo, $this->moveToWorld, 'admin');
	}
}

$VM_Interface = new vacationModeInterface();
$VM_Interface->main($_REQUEST);


/*********** END WORK AREA **************/
//echo "</xmp>";
echo "<hr>WORK AREA TIME: ".DebugTools::TimerStop('TEST');

$GLOBALS['CorrectExit'] = 1;
$GLOBALS['HaveErrors'] = 0;

GB_SingletonObjectStorage::CommitAllCommitable();
?>