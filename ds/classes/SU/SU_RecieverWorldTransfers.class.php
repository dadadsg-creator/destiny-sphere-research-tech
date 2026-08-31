<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_RecieverWorldTransfers
{
	private $worldSettings;	

	private $dbId;
	
	private $worldId;
	
	private $mode = 'log';
	
	private $userList = array();
	
	function __construct($dbId, $worldId)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		
		$this->dbId = $dbId;
		
		$this->worldId = $worldId;
		
		$sapi = php_sapi_name();
		if($sapi == "apache")
		{
			$this->mode = 'screen';
			echo '<xmp>';
		}
	}
	
	function __destruct()
	{
		if($this->mode == 'screen')
		{
			echo '</xmp>';
		}
	}
	
	public function Exec()
	{
		if(GameDown($this->dbId, $this->worldId) || GameDown(0))
		{
			$this->_log("GAME DOWN");
			return;
		}
		
		$this->_getWorldSettings();
		
		$this->_getUsersForFreeTransfer();
		
		$this->_getUsersForRequiredTransfer();
		
		$this->_proceedUserList();
		
		$this->_transferUserWithExceededTime();
	}
	
	private function _getWorldSettings()
	{
		$_SESSION['world'] = $this->worldId;
		$_SESSION['dbId'] = $this->dbId;
		
		$this->worldSettings = DE_WorldSettings::CreateForCurentWorld();
	}
	
	private function _getUsersForRequiredTransfer()
	{	
		// 1 RANKING
		$count = (int)$this->worldSettings->get('WORLD_TRANSFER_TOP_USERS_FOR_REQUIRED_TRANSFER');
		assert($count >= 0);
		if($count > 0)
		{
			$instances = DS_RecieverWorld::GetTopRanking($count, $this->dbId, $this->worldId);
			$this->_allowTransfer($instances, true, 'TRANSFER_REQUIRED_FOR_TOP_USER');
		}
		
		$this->_log(count($instances)." TOP USERS FOR REQUIRED TRANSFER FOUND");
		
		// 2 TIME
		$days = (int)$this->worldSettings->get('WORLD_TRANSFER_MAX_STAY_IN_WORLD');
		assert($days >= 0);
		if($days > 0)
		{
			$instances = DS_RecieverWorld::GetInstancesWithDaysAfterCreation($days, $this->dbId, $this->worldId);
			$this->_allowTransfer($instances, true, 'TRANSFER_REQUIRED_FOR_PLAY_TIME');
		}
		
		$this->_log(count($instances)." USERS WITH MAX PLAY TIME FOUND");
	}
	
	private function _getUsersForFreeTransfer()
	{
		// 1 RANKING
		$count = (int)$this->worldSettings->get('WORLD_TRANSFER_TOP_USERS_FOR_FREE_TRANSFER');
		assert($count >= 0);
		if($count > 0)
		{
			$instances = DS_RecieverWorld::GetTopRanking($count, $this->dbId, $this->worldId);
			$this->_allowTransfer($instances, false, 'TRANSFER_ALLOWED_FOR_TOP_USER');
		}
		
		$this->_log(count($instances)." TOP USERS TRANSFER ALLOWED");
		
		// 2 TIME
		$days = (int)$this->worldSettings->get('WORLD_TRANSFER_ENABLE_AFTER_DAYS');
		assert($days >= 0);
		if($days > 0)
		{
			$instances = DS_RecieverWorld::GetInstancesWithDaysAfterCreation($days, $this->dbId, $this->worldId);
			$this->_allowTransfer($instances, false, 'TRANSFER_ALLOWED_FOR_PLAY_TIME');
		}
		
		$this->_log(count($instances)." USERS TRANSFER ALLOWED FOR PLAY TIME");
	}
	
	private function _sendGameMessage($UIID, $messageName, array $messageParams)
	{
		assert(is_int($UIID) && $UIID > 0);

		$messageSubject = DS_Misc::GetLocolizedText(30690, constant('DEFAULT_LANGUAGE'));

		$messageText = DE_SystemMessages::CreateFromUserInstanceID($UIID)->getFormatedMessage($messageName, $messageParams, 'GREEN', null, false);
		
		DS_RecieverWorld::AddGameMessageToUser($UIID, $this->dbId, $messageSubject['text'], $messageText);
	}
	
	private function _allowTransfer(array $instances, $require, $messageName)
	{
		assert(is_bool($require));
		assert(!empty($messageName));

		foreach ($instances as $k => $inst) 
		{
			assert(key_exists('id_userinstance', $inst));
			
			$res = DS_RecieverWorld::CheckTransferForUser((int) $inst['id_userinstance'], $this->dbId);
			
			// Если для инстанса уже обязателен переезд или он уже завершен, то пропускаем
			if(count($res) > 0)
			{
				if(!$require || $res['transfer_required'] == 't' || $res['transfer_done'] == 't')
				{
					continue;
				}
			}
			
			// заносим всех в один массив, чтоб не посылать кучу сообщений игрокам, которые попадают под несколько условий переезда
			if((key_exists($inst['id_userinstance'], $this->userList) && $require) || !key_exists($inst['id_userinstance'], $this->userList))
			{
				$this->userList[$inst['id_userinstance']] = array('REQIURE' => $require, 'REASON' => $messageName);
			}
		}
	}
	
	private function _proceedUserList()
	{
		foreach($this->userList as $UIID => $data)
		{
			assert(is_array($data));
			assert(key_exists('REASON', $data));
			assert(key_exists('REQIURE', $data));
			
			$messParams = array();
			
			switch ($data['REASON']) {
				case 'TRANSFER_ALLOWED_FOR_TOP_USER':
					$count = (int)$this->worldSettings->get('WORLD_TRANSFER_TOP_USERS_FOR_FREE_TRANSFER');
					$messParams[0] = $count;
					break;
				
				case 'TRANSFER_ALLOWED_FOR_PLAY_TIME':
					$days = (int)$this->worldSettings->get('WORLD_TRANSFER_ENABLE_AFTER_DAYS');
					$messParams[0] = $days;
					break;
					
				case 'TRANSFER_REQUIRED_FOR_PLAY_TIME':
					$daysForFreeTransfer = (int)$this->worldSettings->get('WORLD_TRANSFER_FREE_TRANSFER_DAYS');
					$messParams[0] = $daysForFreeTransfer;
					break;
					
				case 'TRANSFER_REQUIRED_FOR_TOP_USER':
					$count = (int)$this->worldSettings->get('WORLD_TRANSFER_TOP_USERS_FOR_REQUIRED_TRANSFER');
					$daysForFreeTransfer = (int)$this->worldSettings->get('WORLD_TRANSFER_FREE_TRANSFER_DAYS');
					$messParams[0] = $count;
					$messParams[1] = $daysForFreeTransfer;
					break;
			}
			
			DS_RecieverWorld::AllowTransferForUser((int)$UIID, $this->worldId, $this->dbId, $data['REQIURE']);
			
			$this->_sendGameMessage((int)$UIID, $data['REASON'], $messParams);
		}
	}
	
	private function _transferUserWithExceededTime()
	{
		$days = (int)$this->worldSettings->get('WORLD_TRANSFER_FREE_TRANSFER_DAYS');
		assert($days > 0);
		
		$users = DS_RecieverWorld::GetUsersWithAllowedTransferForDays($days, $this->worldId, $this->dbId);

		$this->_log(count($users)." ADDED TO TRANSFER LIST");
		
		foreach ($users as $k => $user)
		{
			assert(key_exists('id_user', $user));
			assert(key_exists('id_userinstance', $user));
			assert(key_exists('username', $user));
			$worldTo = GL_RecieverWorldGetWorldForTransfer::Get((int)$user['id_user'], $this->dbId, $this->worldId);
			
			if($worldTo === false)
			{
				// ехать некуда
				
				// Исключения
				$exceptions = array('Enzo', 'Lenusichka', 'dada', 'Lukast', 'Tanker', 'danet', 'dartana', 'Annita');
				
				if (in_array($user['username'], $exceptions)) {
					continue;
				}

				// Удаляем нафиг
				DS_RecieverWorld::DeleteUser((int)$user['id_userinstance'], $this->dbId);
				
				// логируем
				$this->_log((int)$user['id_userinstance'].' : no world for transfer! Deleted!');
				
			} else {
				// если уже заказан переезд из мира, то ничего не делаем
				$worldTransfer = DS_WorldTransfer::getTransferInfo($this->dbId, $this->worldId, (int)$user['id_user']);
				foreach ($worldTransfer as $k => $transfer)
				{
					if($transfer['status'] == 1)
					{
						continue 2;
					}
				}
				
				DS_RecieverWorld::AddToTransferList((int)$user['id_user'], $this->dbId, $this->worldId, (int)$worldTo['dbId'], (int)$worldTo['id_world']);
			}
		}
	}
	
	private function _log($msg)
	{
		$msg = date('Y-d-m H:i:s').' - '.$msg."\n";
		if($this->mode == 'screen')
		{
			echo $msg;
		}
		
		SU_DebugTools::WriteToLog($msg, 'RecieverWorld.log');
	}
}
?>