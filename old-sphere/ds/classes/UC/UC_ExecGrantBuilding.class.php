<?php $CVS_Version = '$Revision: 1.9 $ ; $Date: 2009/02/16 10:42:23 $';


class UC_ExecGrantBuilding extends UC_PaymentServiceBase
{
	static private $userInstance;
		
	public function Exec()
	{
		self::_GetParams();
		
		$_SESSION['dbId'] = 0;
		
		//получаем цену
		self::_GetPrice();
		
		switch(self::$action)
		{
			case 'index':
				self::_ShowIndex();
				break;
				
			case 'show_second_menu':
				self::_ShowSecondMenu();
				break;
		
			case 'grant_building':
				
				if (!self::_GrantBuilding()) 
				{
					self::_ShowSecondMenu();
				}
				
				break;	
				
			case 'successful':
					self::_ShowSuccessful();
				break;	
					
			default:
				break;
		}
		
		UC_OutUserCabinet::Out('PaymantServices', self::$data);
	}
	
	static private function _ShowIndex()
	{
		self::$data['MODE'] = 'INDEX';
		self::$data['EXEC_ACTION'] = 'show_second_menu';
		
		$_SESSION['dbId'] = 0;

		//Получаем список миров игрока
		$userworlds = self::_GetUserWorlds();
		
		//Если мир у игрока один
		if(count($userworlds) == 1)
		{
			self::$worldId = (int)$userworlds[0]['id_world'];
			self::$dbId = (int)$userworlds[0]['dbId'];
			self::$worldName = $userworlds[0]['worldname'];

			self::_ShowSecondMenu();
		} 
		else{
			self::$data['USER_WORLDS'] = $userworlds;
		}
	}
	
	static private function _ShowSecondMenu()
	{
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);
		
		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney())
		{
			return false;
		}

		if(GameDown(self::$dbId, self::$worldId))
		{
			header('Location: index_down.html');
			exit;
		}
				
		$_SESSION['dbId'] = self::$dbId;
		
		//Получаем инстанс игрока
		$userInstance = (int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID(self::$user->getId(), self::$worldId);
		
		//Смотрим, нет ли у игрока уже этой возможности в этом мире
		if (self::_IsUserHaveGrantBuilding($userInstance)) {
			self::$data['AlREADY_HAS'] = 'YES';
		}
		
		self::$data['MODE'] = 'GRANT_BUILDING_SECOND_MENU';
		self::$data['EXEC_ACTION'] = 'grant_building';
		self::$data['WORLD_NAME'] = self::$worldName;
		self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
		self::$data['WORLD_NAME'] = self::$worldName;
		self::$data['USER_INSTANCE'] = $userInstance;
	}
	
	static private function _IsUserHaveGrantBuilding($userInstance)
	{
		assert(is_int($userInstance));
		assert($userInstance > 0);
		
		$common = self::getCommon();
		
		$sql = "
			SELECT
				id_building
			FROM	
				ds_buildingsalowedtouserinstance 
			WHERE
				id_userinstance = ".$userInstance."
		";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) > 0)
		{
			return true;
		} else {
			return false;
		}
	}
	
	static private function _GrantBuilding()
	{
		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney())
		{
			return false;
		}
			
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);			
		
		$_SESSION['dbId'] = self::$dbId;
		$_SESSION['world'] = self::$worldId;
		
		// Получаем World Identifier мира (для лога)
		$WI = DE_World::CreateFromID(self::$worldId)->getShortName();
		assert(!empty($WI));
		
		$common = self::getCommon();

		//Проверяем, чтобы у игрока не было этой возможности
		if (self::_IsUserHaveGrantBuilding(self::$userInstance)) {
			return false;
		}
		
		$sql = "
			INSERT INTO 
					ds_buildingsalowedtouserinstance
					(id_userinstance, id_building, adddate)
				VALUES
					(".self::$userInstance.", 65, now())	
			";
		
		$res = $common->getResultArray("", $sql, "", "", "");
		
		if(!empty($common->errormsg))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		$_SESSION['dbId'] = 0;
		
		//Снимаем деньги
		UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_GRANT_BUILDING', 1, $WI);
		
		//Логиреум
		if (!self::_Log('t', 'f', 't', 'f', 'PS_GRANT_BUILDING', self::$data['PRICE'], self::$data['CREDIT'], "World: ".self::$worldName))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}

		ME_EndDBTransactions::Exec();
		header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&worldName=".self::$worldName);
		
		return true;
	}

	private static function _ShowSuccessful()
	{
		self::$data['WORLD_NAME'] = $_REQUEST['worldName'];
		self::$data['WORLD_NAME'] = $_REQUEST['worldName'];
		self::$data['MODE'] = 'SUCCESSFUL';
	}
	
	static private function _GetParams()
	{
		if(key_exists('exec_action', self::$data))
		{
			self::$action = self::$data['exec_action'];
		}
		
		if(key_exists('user_instance', self::$data))
		{
			self::$userInstance = (int)self::$data['user_instance'];
		}
		
		if(key_exists('world', self::$data))
		{
			$arr = explode('_', self::$data['world']);
			self::$dbId = (int)$arr[0];
			self::$worldId = (int)$arr[1];
			self::$worldName = $arr[2];
			
			assert(self::$dbId >= 0);
			assert(self::$worldId > 0);
			assert(!empty(self::$worldName));
		}
	}
	
}

?>