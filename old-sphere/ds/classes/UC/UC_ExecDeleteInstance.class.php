<?php $CVS_Version = '$Revision: 1.4 $ ; $Date: 2007/09/11 08:05:53 $';


class UC_ExecDeleteInstance extends UC_PaymentServiceBase
{
	static private $password;
		
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
				
			case 'confirm':
				if (!self::_ShowConfirm()) {
					self::_ShowSecondMenu();
				}
				break;
				
			case 'delete_instance':
				if (!self::_DeleteInstance())
				{
					self::_ShowSecondMenu();
				}
				break;
				
			case 'successful':
					self::_ShowSuccessful();
				break;	
		}
		
		UC_OutUserCabinet::Out('PaymantServices', self::$data);
	}
	
	static private function _ShowIndex()
	{
		self::$data['MODE'] = 'INDEX';
		self::$data['EXEC_ACTION'] = 'show_second_menu';
		
		$_SESSION['dbId'] = 0;
		
		//Если мир один выводим запрос на подтвержление удаления
		//Получаем список миров игрока
		$userworlds = self::_getUserWorlds();
		
		//Если мир у игрока один
		if(count($userworlds) == 1)
		{
			self::$worldId = (int)$userworlds[0]['id_world'];
			self::$dbId = (int)$userworlds[0]['dbId'];
			self::$worldName = $userworlds[0]['worldname'];
			
			self::_ShowSecondMenu();
		} 
		else{
			//Если миров несколько, то выводим выбор миров
			self::$data['USER_WORLDS'] = $userworlds;
		}
	}
	
	static private function _ShowSecondMenu()
	{
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);
		
		//Проверка на гейм даун
		if(GameDown(self::$dbId, self::$worldId))
		{
			header('Location: index_down.html');
			exit;
		}

		self::$data['MODE'] = 'DELETE_INSTANCE_INDEX';
		self::$data['EXEC_ACTION'] = 'confirm';
			
		self::$data['WORLD_NAME'] = self::$worldName;
		self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
	}
	
	static private function _ShowConfirm()
	{
		$_SESSION['dbId'] = 0;
		
		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney())
		{
			return false;
		}
		
		//Если пароль пустой
		if(empty(self::$password))
		{
			self::$data['MSG'] = 'EMPTY_PASSWORD';
			return false;
		}
		//Проверяем правильность пароля
		if(strcmp(md5(self::$password), self::$user->getPassword()) != 0)
		{
			self::$data['MSG'] = 'WRONG_PASSWORD';
			return false;
		}

		self::$data['WORLD_NAME'] = self::$worldName;
		self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
		self::$data['MODE'] = 'DELETE_INSTANCE_COMFIRM';
		self::$data['EXEC_ACTION'] = 'delete_instance';
		
		return true;

	}

	static private function _DeleteInstance()
	{
		$_SESSION['dbId'] = 0;

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

		$userInstanceID = (int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID(self::$user->getId(), (int)self::$worldId);
		assert($userInstanceID > 1);

		$common = self::getCommon();
		
		//Переводим все деньги из мира в кабинет
		$world = DE_World::CreateFromID(self::$worldId);
		
		$worldFromCollection = DE_WorldFromCollection::CreateFromID($world->getShortName());
		$member = new MU_EntityOperationMember(self::$user, $worldFromCollection);
		$account = MU_SearchOperations::GetAccountData($member);
		
		//если аккаунт активный
		if($account['IS_ACTIVE'])
		{
			//Переводим деньги из мира в кабинет
			$memberFrom = new MU_EntityOperationMember(self::$user, $worldFromCollection);
			$memberTo = new MU_EntityOperationMember(self::$user, DE_WorldFromCollection::CreateFromID('UC'));
			//Если есть SC
			if ($account['SC'] > 0)
			{
				$summ = new MU_EntityMoneySumm((int)$account['SC'], 'SC');
				MU_ExecTransferInterworld::Exec($memberFrom, $memberTo, $summ);
			}
			//Если есть GC
			if ($account['GC'] > 0)
			{
				$summ = new MU_EntityMoneySumm((int)$account['GC'], 'GC');
				MU_ExecTransferInterworld::Exec($memberFrom, $memberTo, $summ);
			}
		}
		
		$_SESSION['dbId'] = self::$dbId;
		$common->setDbId(self::$dbId);
		
		//удаляем инстанс(таймаут на 2 минуты)
		$sql = "SET STATEMENT_TIMEOUT to 120000; SELECT drop_uiid($userInstanceID)";
		$common->executeQuery($sql);
		if(!empty($common->errormsg))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		$sql = "DELETE FROM ds_userinstancecollection 
				WHERE 
						id_userinstance =  $userInstanceID
						 AND 
						id_world = ".self::$worldId."
		 ";
		$common->executeQuery($sql);
		
		if(!empty($common->errormsg))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		$_SESSION['dbId'] = 0;
		
		$common = self::getCommon();
		$sql = "DELETE FROM ds_userinstancecollection 
				WHERE 
						id_userinstance =  $userInstanceID
						 AND 
						id_world = ".self::$worldId."
		 ";
		$common->executeQuery($sql);
		
		//Снимаем деньги
		UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_DELETE_INSTANCE', 1, $WI);
		
		//Логиреум
		if (!self::_Log('t', 'f', 't', 'f', 'PS_DELETE_INSTANCE', self::$data['PRICE'], self::$data['CREDIT'], "World: ".self::$worldName))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		
		
		ME_EndDBTransactions::Exec();
		header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&worldName=".self::$worldName);
		return true;
	}
	
	private static function _ShowSuccessful ()
	{				
		self::$data['WORLD_NAME'] = $_REQUEST['worldName'];
		self::$data['MODE'] = 'SUCCESSFUL';
	}
	
	static private function _GetParams()
	{
		self::$action = 'index';
		
		if(key_exists('exec_action', self::$data))
		{
			self::$action = self::$data['exec_action'];
		}
		
		if(key_exists('password', self::$data))
		{
			self::$password = self::$data['password'];
		}
		
		if(key_exists('world', self::$data))
		{
			$arr = explode('_', self::$data['world']);
			self::$dbId = (int)$arr[0];
			self::$worldId = (int)$arr[1];
			self::$worldName = $arr[2];
			assert(self::$dbId >= 0);
			assert(self::$worldId > 0);
		}
	}
	
}
?>